<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseModel;
use App\Models\CaseAssignment;
use App\Models\Document;
use App\Models\DocumentCorrection;
use App\Models\OseFileNumber;
use App\Models\User;
use App\Services\CaseService;
use App\Services\CaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function __construct(
        private CaseService $caseService,
        private CaseStorageService $caseStorageService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $assignedTypesByRole = [
            'alu_atty' => ['alu_atty', 'alu_attorney'],
            'wrd' => ['wrd'],
            'hydrology_expert' => ['hydrology_expert'],
            'alu_clerk' => ['alu_clerk'],
        ];
        $currentRole = $user->getCurrentRole();

        if ($user->isHearingUnit()) {
            // HU users see only submitted cases (not drafts)
            $query = CaseModel::whereNotIn('status', ['draft']);
            $allowedStatuses = ['submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        } elseif ($user->canAssignAttorneys()) {
            // ALU Manager sees all cases
            $query = CaseModel::query();
            $allowedStatuses = ['draft', 'submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        } elseif ($currentRole === 'party') {
            // Parties, counsel, and paralegals only see cases they are actually on.
            $query = CaseModel::whereNotIn('status', ['draft'])
                ->where(function ($caseQuery) use ($user) {
                    $caseQuery->whereHas('parties', function ($partyQuery) use ($user) {
                        $partyQuery->whereHas('person', function ($personQuery) use ($user) {
                            $personQuery->where('email', $user->email);
                        });
                    })->orWhereHas('assignments', function ($assignmentQuery) use ($user) {
                        $assignmentQuery->where('assignment_type', 'alu_paralegal')
                            ->where('user_id', $user->id);
                    });
                });
            $allowedStatuses = ['submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        } elseif (isset($assignedTypesByRole[$currentRole])) {
            $query = CaseModel::whereHas('assignments', function ($assignmentQuery) use ($user, $assignedTypesByRole, $currentRole) {
                $assignmentQuery->where('user_id', $user->id)
                    ->whereIn('assignment_type', $assignedTypesByRole[$currentRole]);
            })->whereNotIn('status', ['draft']);
            $allowedStatuses = ['submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        } else {
            // Other users see only their created cases
            $query = $user->createdCases();
            $allowedStatuses = ['draft', 'submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('case_type', $request->type);
        }

        if ($request->filled('case_no')) {
            $caseNo = trim((string) $request->case_no);
            $normalizedCaseNo = str_replace(['/', ' ', '–', '—'], '-', $caseNo);

            $patterns = [$caseNo, $normalizedCaseNo];

            if (preg_match('/^(\d{4})[-\/\s]?0*(\d+)$/', $caseNo, $matches)) {
                $year = $matches[1];
                $seq = (int) $matches[2];
                $patterns[] = $year . '-' . $seq;
                $patterns[] = sprintf('%s-%03d', $year, $seq);
            }

            $digitsOnly = preg_replace('/\D+/', '', $caseNo);
            if (strlen($digitsOnly) >= 5) {
                $year = substr($digitsOnly, 0, 4);
                $seqRaw = ltrim(substr($digitsOnly, 4), '0');
                $seq = $seqRaw === '' ? '0' : $seqRaw;
                $patterns[] = $year . '-' . $seq;
                $patterns[] = sprintf('%s-%03d', $year, (int) $seq);
                $patterns[] = $year . '%' . $seq;
            }

            $patterns = array_values(array_unique(array_filter($patterns)));

            $query->where(function ($q) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $q->orWhere('case_no', 'like', '%' . $pattern . '%');
                }
            });
        }

        if ($request->filled('ose_file_no')) {
            $searchOse = $request->ose_file_no;
            $query->whereHas('oseFileNumbers', function ($q) use ($searchOse) {
                $q->where('basin_code', 'like', '%' . $searchOse . '%')
                    ->orWhere('file_no_from', 'like', '%' . $searchOse . '%')
                    ->orWhere('file_no_to', 'like', '%' . $searchOse . '%');
            });
        }

        $cases = $query->latest()->paginate(15)->withQueryString();

        $allowedTypes = ['aggrieved', 'protested', 'compliance'];

        return view('cases.index', compact('cases', 'allowedStatuses', 'allowedTypes'));
    }

    public function create()
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $basinCodes = \App\Models\OseBasinCode::orderBy('initial')->get();
        $attorneys = \App\Models\Attorney::orderBy('name')->get();

        $userRole = Auth::user()->getCurrentRole();
        $documentTypes = \App\Models\DocumentType::forRole($userRole)
            ->orderBy('name')->get();
        $pleadingDocs = $documentTypes
            ->where('is_pleading', true);
        $optionalDocs = $documentTypes
            ->where('is_pleading', false)
            ->where('category', 'case_creation');

        return view('cases.create', compact('basinCodes', 'attorneys', 'documentTypes', 'pleadingDocs', 'optionalDocs'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $caseType = $request->input('case_type');

        $rules = [
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'wrd_office' => 'required|in:albuquerque,santa_fe',
            'parties' => 'required|array|min:1',
            'parties.*.role' => 'required|in:applicant,protestant,aggrieved_party,intervenor,respondent',
            'parties.*.type' => 'required|in:individual,company',
            'parties.*.representation' => 'nullable|in:self,attorney',
            'parties.*.attorney_option' => 'nullable|in:existing,new,no_attorney_yet',
            'parties.*.attorney_id' => 'nullable|exists:attorneys,id',
            'parties.*.prefix' => 'nullable|string|max:10',
            'parties.*.first_name' => 'nullable|string|max:255',
            'parties.*.middle_name' => 'nullable|string|max:255',
            'parties.*.last_name' => 'nullable|string|max:255',
            'parties.*.suffix' => 'nullable|string|max:10',
            'parties.*.organization' => 'nullable|string|max:255',
            'parties.*.title' => 'nullable|string|max:255',
            'parties.*.email' => 'required|email|max:255',
            'parties.*.phone_mobile' => 'nullable|string|max:20',
            'parties.*.phone_office' => 'nullable|string|max:20',
            'parties.*.address_line1' => 'nullable|string|max:500',
            'parties.*.address_line2' => 'nullable|string|max:500',
            'parties.*.city' => 'nullable|string|max:100',
            'parties.*.state' => 'nullable|string|max:50',
            'parties.*.zip' => 'nullable|string|max:10',
            'parties.*.attorney_name' => 'nullable|string|max:255',
            'parties.*.attorney_email' => 'nullable|email|max:255',
            'parties.*.attorney_phone' => 'nullable|string|max:20',
            'parties.*.bar_number' => 'nullable|string|max:50',
            'parties.*.attorney_address_line1' => 'nullable|string|max:500',
            'parties.*.attorney_address_line2' => 'nullable|string|max:500',
            'parties.*.attorney_city' => 'nullable|string|max:100',
            'parties.*.attorney_state' => 'nullable|string|max:50',
            'parties.*.attorney_zip' => 'nullable|string|max:10',
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code_from' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.basin_code_to' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'documents' => 'nullable|array',
            'documents.notice_publication' => 'nullable|array',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.pleading' => 'nullable|array',
            'documents.pleading.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.protest_letter' => 'nullable|array',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.supporting' => 'nullable|array',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:204800',
            'assigned_attorneys' => 'nullable|array',
            'assigned_attorneys.*' => 'exists:users,id',
            'assigned_clerks' => 'nullable|array',
            'assigned_clerks.*' => 'exists:users,id',
            'optional_docs' => 'nullable|array',
            'optional_docs.*.type' => 'nullable|string',
            'optional_docs.*.custom_title' => 'nullable|string|max:255',
            'optional_docs.*.files' => 'nullable|array',
            'optional_docs.*.files.*' => 'nullable|file|mimes:pdf|max:204800',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit'
        ];

        if ($caseType === 'compliance') {
            $rules['pleading_type'] = 'nullable|in:request_pre_hearing,request_to_docket';
            $rules['documents.application'] = 'nullable|array';
            $rules['documents.application.*'] = 'nullable|file|mimes:pdf|max:204800';
            $rules['compliance_doc_type'] = 'required|in:compliance_order,pre_compliance_letter,compliance_letter,notice_of_violation,notice_of_reprimand';
            $rules['documents.compliance'] = 'required|array';
            $rules['documents.compliance.*'] = 'required|file|mimes:pdf|max:204800';
        } else {
            $rules['pleading_type'] = 'required|in:request_pre_hearing,request_to_docket';
            $rules['documents.application'] = 'required|array';
            $rules['documents.application.*'] = 'required|file|mimes:pdf|max:204800';
        }

        $validated = $request->validate($rules);

        // Log raw request data for debugging
        \Log::info('Raw request parties:', ['parties' => $request->input('parties')]);
        \Log::info('Party validation data:', ['parties' => $validated['parties']]);

        // Additional validation for individuals and attorney representation
        foreach ($validated['parties'] as $index => $party) {
            if ($caseType !== 'compliance' && ($party['role'] ?? null) === 'respondent') {
                return back()->withInput()->withErrors(["parties.{$index}.role" => 'Respondent role is only allowed for compliance action cases.']);
            }

            // Require first_name and last_name for individuals only
            if ($party['type'] === 'individual') {
                if (empty($party['first_name']) || empty($party['last_name'])) {
                    \Log::warning('Individual missing name', ['index' => $index, 'party' => $party]);
                    return back()->withInput()->withErrors(["parties.{$index}.first_name" => 'First name and last name are required for individuals.']);
                }
            }

            // Require attorney information when representation is attorney
            if (isset($party['representation']) && $party['representation'] === 'attorney') {
                $hasExistingAttorney = !empty($party['attorney_id']);
                $hasNewAttorney = !empty($party['attorney_name']) && !empty($party['attorney_email']);
                $hasNoAttorneyYet = ($party['type'] ?? null) === 'company' && ($party['attorney_option'] ?? null) === 'no_attorney_yet';

                if (($party['type'] ?? null) !== 'company' && ($party['attorney_option'] ?? null) === 'no_attorney_yet') {
                    return back()->withInput()->withErrors(["parties.{$index}.attorney_option" => 'No Attorney Yet is only allowed for entities (non-person).']);
                }

                if (!$hasExistingAttorney && !$hasNewAttorney && !$hasNoAttorneyYet) {
                    return back()->withInput()->withErrors(["parties.{$index}.attorney_name" => 'Please select an existing attorney or provide new attorney name and email.']);
                }
            }
        }

        try {
            $case = $this->caseService->createCase($validated, Auth::user(), $request);

            // Handle ALU attorney assignments
            if (isset($validated['assigned_attorneys']) && !empty($validated['assigned_attorneys'])) {
                foreach ($validated['assigned_attorneys'] as $attorneyId) {
                    CaseAssignment::create([
                        'case_id' => $case->id,
                        'user_id' => $attorneyId,
                        'assignment_type' => 'alu_atty',
                        'assigned_by' => Auth::id()
                    ]);
                }
            }

            // Handle ALU clerk assignments
            if (isset($validated['assigned_clerks']) && !empty($validated['assigned_clerks'])) {
                foreach ($validated['assigned_clerks'] as $clerkId) {
                    CaseAssignment::create([
                        'case_id' => $case->id,
                        'user_id' => $clerkId,
                        'assignment_type' => 'alu_clerk',
                        'assigned_by' => Auth::id()
                    ]);
                }
            }

            $message = match($validated['action']) {
                'draft' => 'Case saved as draft successfully.',
                'validate' => 'Case validated successfully. Ready for submission.',
                'submit' => 'Case submitted to Hearing Unit successfully.',
            };

            return redirect()->route('cases.show', $case)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to create case: ' . $e->getMessage()]);
        }
    }

    public function show(CaseModel $case)
    {
        // HU users cannot see draft cases
        if (Auth::user()->isHearingUnit() && $case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to Hearing Unit staff.');
        }

        // Parties and attorneys cannot see draft cases
        if (Auth::user()->getCurrentRole() === 'party' && $case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to parties and attorneys.');
        }

        if (Auth::user()->getCurrentRole() === 'party' && !Auth::user()->canAccessCase($case)) {
            abort(403, 'You can only access cases you are associated with.');
        }

        $case->load([
            'creator',
            'assignee',
            'assignedAttorney',
            'assignedHydrologyExpert',
            'assignedAluClerk',
            'assignedWrd',
            'aluAttorneys',
            'aluParalegals',
            'hydrologyExperts',
            'aluClerks',
            'wrds',
            'assignments.user',
            'documents.uploader',
            'parties.person',
            'serviceList.person',
            'oseFileNumbers',
            'auditLogs.user',
            'rejections.rejectedBy',
            'rejections.resubmittedBy',
            'rejections.items.resolvedBy',
        ]);
        return view('cases.show', compact('case'));
    }

    public function downloadServiceList(CaseModel $case)
    {
        if (!Auth::user()->isHearingUnit()) {
            abort(403);
        }

        if ($case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to Hearing Unit staff.');
        }

        $case->load(['parties.person', 'serviceList.person']);

        $serviceEntries = $case->serviceList
            ->reject(fn($service) => strtoupper(trim((string) ($service->person->organization ?? ''))) === 'WATER RIGHTS DIVISION')
            ->sortBy(fn($service) => strtolower((string) ($service->person->full_name ?? $service->email)))
            ->values();

        $filename = sprintf('case-%s-service-list.csv', str_replace(['\\', '/'], '-', $case->case_no));

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($serviceEntries, $case) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Case Number',
                'Name',
                'Organization',
                'Address Line 1',
                'Address Line 2',
                'City',
                'State',
                'ZIP',
                'Email',
                'Role',
                'Service Method',
            ]);

            foreach ($serviceEntries as $service) {
                $person = $service->person;
                $roles = $case->parties
                    ->where('person_id', $service->person_id)
                    ->pluck('role')
                    ->filter()
                    ->unique()
                    ->map(fn($role) => ucfirst(str_replace('_', ' ', $role)))
                    ->implode(', ');

                fputcsv($handle, [
                    $case->case_no,
                    $person?->full_name ?? '',
                    $person?->organization ?? '',
                    $person?->address_line1 ?? '',
                    $person?->address_line2 ?? '',
                    $person?->city ?? '',
                    $person?->state ?? '',
                    $person?->zip ?? '',
                    $service->email ?? '',
                    $roles,
                    $service->service_method ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function edit(CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() || !in_array($case->status, ['draft', 'rejected'])) {
            abort(403);
        }

        $case->load([
            'parties.person',
            'serviceList.person',
            'oseFileNumbers',
            'documents',
            'rejections.rejectedBy',
            'rejections.items.resolvedBy',
        ]);
        $basinCodes = \App\Models\OseBasinCode::orderBy('initial')->get();
        return view('cases.edit', compact('case', 'basinCodes'));
    }

    public function update(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() && !Auth::user()->canSubmitToHU()) {
            abort(403);
        }

        if (!in_array($case->status, ['draft', 'rejected']) && !Auth::user()->canSubmitToHU()) {
            abort(403);
        }

        $validated = $request->validate([
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code_from' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.basin_code_to' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit',
            'notify_recipients' => 'nullable|array',
            'notify_recipients.*' => 'string',
            'custom_message' => 'nullable|string|max:1000',
            'rejection_items' => 'nullable|array',
            'rejection_items.*.resolution_note' => 'nullable|string|max:2000',
            'rejection_items.*.mark_resolved' => 'nullable',
        ]);

        try {
            $resolutionErrors = $this->syncOpenRejectionResolutions(
                $case,
                $request->input('rejection_items', []),
                Auth::user(),
                $validated['action'] === 'submit'
            );

            if (!empty($resolutionErrors)) {
                return back()->withInput()->withErrors($resolutionErrors);
            }

            // Update core case information
            $case->update([
                'case_type' => $validated['case_type'],
                'caption' => $validated['caption']
            ]);

            // Update OSE file numbers
            if (isset($validated['ose_numbers'])) {
                $case->oseFileNumbers()->delete();
                foreach ($validated['ose_numbers'] as $oseData) {
                    if ((!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) ||
                        (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to']))) {

                        $fileNoFrom = null;
                        $fileNoTo = null;
                        $basinCode = null;

                        if (!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) {
                            $fileNoFrom = $oseData['basin_code_from'] . '-' . $oseData['file_no_from'];
                            $basinCode = $oseData['basin_code_from'];
                        }

                        if (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to'])) {
                            $fileNoTo = $oseData['basin_code_to'] . '-' . $oseData['file_no_to'];
                            $basinCode = $basinCode ?: $oseData['basin_code_to'];
                        }

                        OseFileNumber::create([
                            'case_id' => $case->id,
                            'basin_code' => $basinCode,
                            'file_no_from' => $fileNoFrom,
                            'file_no_to' => $fileNoTo
                        ]);
                    }
                }
            }

            // Update status based on action
            if ($validated['action'] === 'submit' && in_array($case->status, ['draft', 'rejected'])) {
                // Validate submission requirements
                $validationErrors = $this->validateSubmissionRequirements($case);
                if (!empty($validationErrors)) {
                    return back()->withInput()->withErrors(['submission' => implode(' ', $validationErrors)]);
                }

                $updates = ['status' => 'submitted_to_hu'];
                if (empty($case->submitted_at)) {
                    $updates['submitted_at'] = now();
                }
                $case->update($updates);

                if ($case->status === 'submitted_to_hu') {
                    $this->markOpenRejectionResubmitted($case, Auth::user());
                }

                // Send notifications to selected recipients
                $recipients = $validated['notify_recipients'] ?? [];
                $customMessage = $validated['custom_message'] ?? null;
                $this->caseService->notifyCaseSubmission($case, $recipients, $customMessage);
            }

            $message = match($validated['action']) {
                'draft' => 'Case updated and saved as draft.',
                'validate' => 'Case updated and validated successfully.',
                'submit' => 'Case updated and submitted to Hearing Unit.',
            };

            return redirect()->route('cases.show', $case)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to update case: ' . $e->getMessage()]);
        }
    }



    public function accept(CaseModel $case)
    {
        if ($this->caseService->acceptCase($case, Auth::user())) {
            return back()->with('success', 'Case accepted successfully.');
        }

        return back()->with('error', 'Unable to accept case.');
    }

    public function reject(Request $request, CaseModel $case)
    {
        $validated = $request->validate([
            'reason_summary' => 'required|string|max:2000',
            'rejection_items' => 'nullable|array',
            'rejection_items.*.category' => 'nullable|in:missing_document,caption_issue,party_issue,service_issue,ose_issue,document_issue,filing_issue,other',
            'rejection_items.*.item_note' => 'nullable|string|max:2000',
            'rejection_items.*.required_action' => 'nullable|string|max:2000',
        ]);

        if ($this->caseService->rejectCase(
            $case,
            Auth::user(),
            $validated['reason_summary'],
            $validated['rejection_items'] ?? []
        )) {
            return back()->with('success', 'Case rejected.');
        }

        return back()->with('error', 'Unable to reject case.');
    }

    public function assignAttorneyForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $attorneys = \App\Models\User::whereCurrentRole('alu_atty')->get();
        return view('cases.assign-attorney', compact('case', 'attorneys'));
    }

    public function assignAttorney(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $validated = $request->validate([
            'attorney_ids' => 'required|array|min:1',
            'attorney_ids.*' => 'exists:users,id'
        ]);

        $case->assignments()->whereIn('assignment_type', ['alu_attorney', 'alu_atty'])->delete();

        foreach ($validated['attorney_ids'] as $attorneyId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $attorneyId,
                'assignment_type' => 'alu_atty',
                'assigned_by' => Auth::id()
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'Attorneys assigned successfully.');
    }

    public function assignHydrologyExpertForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignHydrologyExperts()) {
            abort(403);
        }

        $experts = \App\Models\User::whereCurrentRole('hydrology_expert')->get();
        return view('cases.assign-hydrology-expert', compact('case', 'experts'));
    }

    public function assignHydrologyExpert(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignHydrologyExperts()) {
            abort(403);
        }

        $validated = $request->validate([
            'expert_ids' => 'required|array|min:1',
            'expert_ids.*' => 'exists:users,id'
        ]);

        // Remove existing assignments
        $case->assignments()->where('assignment_type', 'hydrology_expert')->delete();

        // Add new assignments
        foreach ($validated['expert_ids'] as $expertId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $expertId,
                'assignment_type' => 'hydrology_expert',
                'assigned_by' => Auth::id()
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'Hydrology experts assigned successfully.');
    }

    public function assignAluClerkForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $clerks = \App\Models\User::whereCurrentRole('alu_clerk')->get();
        return view('cases.assign-alu-clerk', compact('case', 'clerks'));
    }

    public function assignAluClerk(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $validated = $request->validate([
            'clerk_ids' => 'required|array|min:1',
            'clerk_ids.*' => 'exists:users,id'
        ]);

        $case->assignments()->where('assignment_type', 'alu_clerk')->delete();

        foreach ($validated['clerk_ids'] as $clerkId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $clerkId,
                'assignment_type' => 'alu_clerk',
                'assigned_by' => Auth::id()
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'ALU Clerks assigned successfully.');
    }

    public function assignWrdForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $wrds = \App\Models\User::whereCurrentRole('wrd')->get();
        return view('cases.assign-wrd', compact('case', 'wrds'));
    }

    public function assignWrd(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $validated = $request->validate([
            'wrd_ids' => 'required|array|min:1',
            'wrd_ids.*' => 'exists:users,id'
        ]);

        $case->assignments()->where('assignment_type', 'wrd')->delete();

        foreach ($validated['wrd_ids'] as $wrdId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $wrdId,
                'assignment_type' => 'wrd',
                'assigned_by' => Auth::id()
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'WRDs assigned successfully.');
    }

    public function notifyParties(Request $request, CaseModel $case)
    {
        if (!Auth::user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'notify_recipients' => 'required|array|min:1',
            'notify_recipients.*' => 'required|string',
            'custom_message' => 'nullable|string|max:1000'
        ]);

        $notificationCount = $this->caseService->notifySelectedParties($case, $validated['notify_recipients'], $validated['custom_message'] ?? null, Auth::user());

        return redirect()->route('cases.parties.manage', $case)->with('success', "Notifications sent to {$notificationCount} recipients.");
    }

    public function uploadDocuments(CaseModel $case)
    {
        if (!Auth::user()->canUploadDocumentsToCase($case)) {
            if (Auth::user()->getCurrentRole() === 'alu_clerk' && $case->status === 'active') {
                abort(403, 'ALU clerks cannot upload documents after a case becomes active.');
            }

            if (Auth::user()->getCurrentRole() === 'party' || Auth::user()->isAttorney() || Auth::user()->isALUAttorney() || Auth::user()->isParalegal()) {
                abort(403, 'You can only upload documents to active cases you are associated with.');
            }

            abort(403);
        }

        $case->load(['documents.uploader']);

        $userRole = Auth::user()->getCurrentRole();

        if (Auth::user()->getCurrentRole() === 'party' || Auth::user()->isAttorney()) {
            $documentTypes = \App\Models\DocumentType::forRole($userRole)
                ->where('category', 'party_upload')
                ->orderBy('name')->get();
        } else {
            $documentTypes = \App\Models\DocumentType::forRole($userRole)
                ->orderBy('name')->get();
        }

        return view('cases.upload-documents', compact('case', 'documentTypes'));
    }

    public function storeDocuments(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canUploadDocumentsToCase($case)) {
            if (Auth::user()->getCurrentRole() === 'alu_clerk' && $case->status === 'active') {
                abort(403, 'ALU clerks cannot upload documents after a case becomes active.');
            }

            if (Auth::user()->getCurrentRole() === 'party' || Auth::user()->isAttorney() || Auth::user()->isALUAttorney() || Auth::user()->isParalegal()) {
                abort(403, 'You can only upload documents to active cases you are associated with.');
            }

            abort(403);
        }

        // Validate the form structure
        $request->validate([
            'documents.application.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.request_to_docket.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.request_pre_hearing.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.other.*.type' => 'required|string',
            'documents.other.*.file.*' => 'required|file|mimes:pdf,doc,docx|max:204800'
        ]);

        try {
            // Check if any files were uploaded
            $hasFiles = false;
            $documentTypes = ['application', 'notice_publication', 'request_to_docket', 'request_pre_hearing', 'protest_letter', 'supporting'];

            foreach ($documentTypes as $type) {
                if ($request->hasFile("documents.{$type}")) {
                    $hasFiles = true;
                    break;
                }
            }

            // Check for other document types
            if (!$hasFiles && $request->has('documents.other')) {
                foreach ($request->input('documents.other') as $index => $otherDoc) {
                    if ($request->hasFile("documents.other.{$index}.file")) {
                        $hasFiles = true;
                        break;
                    }
                }
            }

            if ($hasFiles) {
                \Log::info('Starting document upload for case: ' . $case->id);

                $this->caseService->handleDocumentUploads($case, $request, Auth::user());
                $case->refresh(); // Refresh to get updated documents

                \Log::info('Document upload completed. Case now has ' . $case->documents->count() . ' documents');
            } else {
                \Log::warning('No files found in upload request');
                return back()->withErrors(['error' => 'No files were selected for upload.']);
            }

            return redirect()->route('cases.show', $case)->with('success', 'Documents uploaded successfully.');

        } catch (\Exception $e) {
            \Log::error('Document upload failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }

    public function showAttorneyManagement(CaseModel $case, $partyId)
    {
        $party = $case->parties()->with(['person', 'attorneys.person'])->findOrFail($partyId);
        $selectedEmails = $party->attorneys
            ->map(fn($attorneyParty) => strtolower(trim((string) $attorneyParty->person?->email)))
            ->filter()
            ->values();
        $attorneys = \App\Models\Attorney::orderBy('name')
            ->get()
            ->reject(fn($attorney) => $selectedEmails->contains(strtolower(trim((string) $attorney->email))))
            ->values();

        return view('cases.attorney-management', compact('case', 'party', 'attorneys'))->render();
    }

    public function assignPartyAttorney(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);

        $validated = $request->validate([
            'attorney_option' => 'nullable|in:existing,new',
            'attorney_id' => 'nullable|exists:attorneys,id',
            'attorney_name' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
        ]);

        try {
            $attorney = null;
            if (!empty($validated['attorney_id'])) {
                $attorney = \App\Models\Attorney::find($validated['attorney_id']);
            } elseif (!empty($validated['attorney_name']) && !empty($validated['attorney_email'])) {
                $attorney = \App\Models\Attorney::firstOrCreate(
                    ['email' => $validated['attorney_email']],
                    [
                        'name' => $validated['attorney_name'],
                        'phone' => $validated['attorney_phone'],
                        'bar_number' => $validated['bar_number'],
                        'address_line1' => $validated['address_line1'],
                        'address_line2' => $validated['address_line2'],
                        'city' => $validated['city'],
                        'state' => $validated['state'],
                        'zip' => $validated['zip']
                    ]
                );
            }

            if (!$attorney) {
                return response()->json(['success' => false, 'error' => 'Select an existing attorney or enter a new one.']);
            }

            $attorneyPerson = \App\Models\Person::firstOrCreate(
                ['email' => $attorney->email],
                [
                    'type' => 'individual',
                    'first_name' => explode(' ', $attorney->name)[0] ?? '',
                    'last_name' => explode(' ', $attorney->name, 2)[1] ?? '',
                    'phone_office' => $attorney->phone
                ]
            );

            \App\Models\CaseParty::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $attorneyPerson->id,
                'role' => 'counsel',
                'client_party_id' => $party->id,
            ], [
                'service_enabled' => true
            ]);

            \App\Models\ServiceList::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $attorneyPerson->id,
                'email' => $attorneyPerson->email,
                'service_method' => 'email',
                'is_primary' => false
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function removeAttorney(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);
        $validated = $request->validate([
            'counsel_party_id' => 'nullable|integer',
        ]);

        $counselParties = $case->parties()
            ->where('role', 'counsel')
            ->where('client_party_id', $party->id)
            ->get();

        if (!empty($validated['counsel_party_id'])) {
            $counselParties = $counselParties->where('id', (int) $validated['counsel_party_id']);
        }

        if ($counselParties->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'Attorney not found for this party.']);
        }

        $remainingCounselCount = $case->parties()
            ->where('role', 'counsel')
            ->where('client_party_id', $party->id)
            ->count() - $counselParties->count();

        if ($party->person->type === 'company' && $remainingCounselCount < 1) {
            return response()->json(['success' => false, 'error' => 'Companies must have at least one attorney representation']);
        }

        foreach ($counselParties as $counselParty) {
            $counselParty->delete();

            $personStillUsed = $case->parties()
                ->where('person_id', $counselParty->person_id)
                ->exists();

            if (!$personStillUsed) {
                \App\Models\ServiceList::where('case_id', $case->id)
                    ->where('person_id', $counselParty->person_id)
                    ->delete();
            }
        }

        return response()->json(['success' => true]);
    }

    public function manageParties(CaseModel $case)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $case->load(['parties.person', 'serviceList.person']);
        $attorneys = \App\Models\Attorney::orderBy('name')->get();

        return view('cases.parties.manage', compact('case', 'attorneys'));
    }

    public function storeParty(Request $request, CaseModel $case)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,aggrieved_party,respondent',
            'type' => 'required|in:individual,company',
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'organization' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
            'attorney_id' => 'nullable|exists:attorneys,id',
            'attorney_name' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
            'attorney_address_line1' => 'nullable|string|max:500',
            'attorney_address_line2' => 'nullable|string|max:500',
            'attorney_city' => 'nullable|string|max:100',
            'attorney_state' => 'nullable|string|max:50',
            'attorney_zip' => 'nullable|string|max:10',
        ]);

        if ($case->case_type !== 'compliance' && $validated['role'] === 'respondent') {
            return back()->withInput()->withErrors(['role' => 'Respondent role is only allowed for compliance action cases.']);
        }

        try {
            // Create or find person
            $person = \App\Models\Person::where('email', $validated['email'])->first();

            if (!$person) {
                $person = \App\Models\Person::create([
                    'type' => $validated['type'],
                    'prefix' => $validated['prefix'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'last_name' => $validated['last_name'],
                    'suffix' => $validated['suffix'],
                    'organization' => $validated['organization'],
                    'title' => $validated['title'],
                    'email' => $validated['email'],
                    'phone_mobile' => $validated['phone_mobile'],
                    'phone_office' => $validated['phone_office'],
                    'address_line1' => $validated['address_line1'],
                    'address_line2' => $validated['address_line2'],
                    'city' => $validated['city'],
                    'state' => $validated['state'],
                    'zip' => $validated['zip']
                ]);
            }

            // Create case party
            $clientParty = \App\Models\CaseParty::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'role' => $validated['role'],
                'service_enabled' => true
            ]);

            // Handle attorney representation
            if ((!empty($validated['attorney_name']) && !empty($validated['attorney_email'])) ||
                ($request->has('attorney_id') && !empty($request->attorney_id))) {
                // Check if selecting existing attorney
                if ($request->has('attorney_id') && !empty($request->attorney_id)) {
                    $attorney = \App\Models\Attorney::find($request->attorney_id);
                    if ($attorney) {
                        // Create attorney person if doesn't exist
                        $attorneyPerson = \App\Models\Person::where('email', $attorney->email)->first();
                        if (!$attorneyPerson) {
                            $attorneyPerson = \App\Models\Person::create([
                                'type' => 'individual',
                                'first_name' => explode(' ', $attorney->name)[0] ?? '',
                                'last_name' => explode(' ', $attorney->name, 2)[1] ?? '',
                                'email' => $attorney->email,
                                'phone_office' => $attorney->phone
                            ]);
                        }

                        // Create counsel party entry linked to client
                        \App\Models\CaseParty::create([
                            'case_id' => $case->id,
                            'person_id' => $attorneyPerson->id,
                            'role' => 'counsel',
                            'client_party_id' => $clientParty->id,
                            'service_enabled' => true
                        ]);
                    }
                }

                // Create new attorney if name and email provided
                if (!empty($validated['attorney_name']) && !empty($validated['attorney_email'])) {
                    // Create new attorney
                    $attorney = \App\Models\Attorney::create([
                        'name' => $validated['attorney_name'],
                        'email' => $validated['attorney_email'],
                        'phone' => $validated['attorney_phone'],
                        'bar_number' => $validated['bar_number'],
                        'address_line1' => $validated['attorney_address_line1'] ?? null,
                        'address_line2' => $validated['attorney_address_line2'] ?? null,
                        'city' => $validated['attorney_city'] ?? null,
                        'state' => $validated['attorney_state'] ?? null,
                        'zip' => $validated['attorney_zip'] ?? null
                    ]);

                    // Create attorney person
                    $attorneyPerson = \App\Models\Person::create([
                        'type' => 'individual',
                        'first_name' => explode(' ', $attorney->name)[0] ?? '',
                        'last_name' => explode(' ', $attorney->name, 2)[1] ?? '',
                        'email' => $attorney->email,
                        'phone_office' => $attorney->phone
                    ]);

                    // Create counsel party entry linked to client
                    \App\Models\CaseParty::create([
                        'case_id' => $case->id,
                        'person_id' => $attorneyPerson->id,
                        'role' => 'counsel',
                        'client_party_id' => $clientParty->id,
                        'service_enabled' => true
                    ]);
                }
            }

            // Create service list entry
            \App\Models\ServiceList::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'email' => $person->email,
                'service_method' => 'email',
                'is_primary' => true
            ]);

            return redirect()->route('cases.parties.manage', $case)->with('success', 'Party added successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to add party: ' . $e->getMessage()]);
        }
    }

    public function editParty(CaseModel $case, $partyId)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $party = $case->parties()->with(['person'])->findOrFail($partyId);
        $attorneys = \App\Models\Attorney::orderBy('name')->get();

        return view('cases.parties.edit', compact('case', 'party', 'attorneys'))->render();
    }

    public function updateParty(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,aggrieved_party,respondent',
            'type' => 'required|in:individual,company',
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'organization' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
            'attorney_name' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
        ]);

        if ($case->case_type !== 'compliance' && $validated['role'] === 'respondent') {
            return response()->json([
                'success' => false,
                'error' => 'Respondent role is only allowed for compliance action cases.'
            ], 422);
        }

        try {
            // Update person
            $party->person->update([
                'type' => $validated['type'],
                'prefix' => $validated['prefix'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'],
                'organization' => $validated['organization'],
                'title' => $validated['title'],
                'email' => $validated['email'],
                'phone_mobile' => $validated['phone_mobile'],
                'phone_office' => $validated['phone_office'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip' => $validated['zip']
            ]);

            // Handle attorney
            if (!empty($validated['attorney_name']) && !empty($validated['attorney_email'])) {
                $attorney = \App\Models\Attorney::create([
                    'name' => $validated['attorney_name'],
                    'email' => $validated['attorney_email'],
                    'phone' => $validated['attorney_phone'],
                    'bar_number' => $validated['bar_number'],
                    'address_line1' => $validated['attorney_address_line1'] ?? null,
                    'address_line2' => $validated['attorney_address_line2'] ?? null,
                    'city' => $validated['attorney_city'] ?? null,
                    'state' => $validated['attorney_state'] ?? null,
                    'zip' => $validated['attorney_zip'] ?? null
                ]);
            }

            // Update party
            $party->update([
                'role' => $validated['role']
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function destroyParty(CaseModel $case, $partyId)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);

        // Remove from service list
        $case->serviceList()->where('person_id', $party->person_id)->delete();

        // Remove party
        $party->delete();

        return response()->json(['success' => true]);
    }

    public function manageDocuments(CaseModel $case)
    {
        $case->load([
            'documents.uploader',
            'documents.correctionCycles.requestedBy',
            'documents.correctionCycles.resubmittedBy',
            'documents.correctionCycles.acceptedBy',
            'documents.correctionCycles.replacementDocument',
            'documents.correctionCycles.items.resolvedBy',
        ]);
        $userRole = Auth::user()->getCurrentRole();
        $documentTypes = \App\Models\DocumentType::forRole($userRole)->orderBy('name')->get();
        return view('cases.documents.manage', compact('case', 'documentTypes'));
    }

    public function storeDocument(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canUploadDocumentsToCase($case)) {
            if (Auth::user()->getCurrentRole() === 'alu_clerk' && $case->status === 'active') {
                abort(403, 'ALU clerks cannot upload documents after a case becomes active.');
            }

            if (Auth::user()->getCurrentRole() === 'party' || Auth::user()->isAttorney() || Auth::user()->isALUAttorney() || Auth::user()->isParalegal()) {
                abort(403, 'You can only upload documents to active cases you are associated with.');
            }

            abort(403);
        }

        $validDocTypes = \App\Models\DocumentType::where('is_active', true)->pluck('code')->toArray();

        $validated = $request->validate([
            'doc_type' => 'required|in:' . implode(',', $validDocTypes),
            'custom_title' => 'required|string|max:255',
            'pleading_type' => 'nullable|in:none,request_to_docket,request_pre_hearing',
            'document.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:204800'
        ]);

        try {
            \Log::info('Starting document upload', ['doc_type' => $validated['doc_type'], 'case_id' => $case->id]);

            // Load OSE file numbers
            $case->load('oseFileNumbers');

            $files = $request->file('document');
            if (!is_array($files)) {
                $files = [$files];
            }

            // Get display name from database
            $documentType = \App\Models\DocumentType::where('code', $validated['doc_type'])->first();
            $displayType = $documentType ? $documentType->name : ucfirst(str_replace('_', ' ', $validated['doc_type']));

            // Get OSE file numbers for the case
            $oseString = '';
            if ($case->oseFileNumbers && $case->oseFileNumbers->count() > 0) {
                $oseList = [];
                foreach ($case->oseFileNumbers as $ose) {
                    if ($ose->file_no_from && $ose->file_no_to) {
                        $oseList[] = $ose->file_no_from . '-' . $ose->file_no_to;
                    } elseif ($ose->file_no_from) {
                        $oseList[] = $ose->file_no_from;
                    } elseif ($ose->file_no_to) {
                        $oseList[] = $ose->file_no_to;
                    }
                }

                if (count($oseList) > 1) {
                    // Multiple OSE numbers: use first one + "et al."
                    $oseString = ' - ' . $oseList[0] . ' et al.';
                } elseif (count($oseList) === 1) {
                    // Single OSE number: use as is
                    $oseString = ' - ' . $oseList[0];
                }
            }

            $storageFolder = $this->caseStorageService->getCaseStorageFolder($case);

            $uploadedCount = 0;
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $titleOrType = !empty($validated['custom_title']) ? $validated['custom_title'] : $displayType;

                    $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . '.pdf';
                    if ($index > 0) {
                        $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . ' (' . ($index + 1) . ').pdf';
                    }

                    $storedFilename = $this->generateReadableStoredFilename($originalFilename, $storageFolder);
                    $path = $file->storeAs($storageFolder, $storedFilename, 'public');

                    $documentData = [
                        'case_id' => $case->id,
                        'doc_type' => $validated['doc_type'],
                        'custom_title' => $validated['custom_title'] ?? null,
                        'original_filename' => $originalFilename,
                        'stored_filename' => $storedFilename,
                        'mime' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'checksum' => md5_file($file->getRealPath()),
                        'storage_uri' => $path,
                        'uploaded_by_user_id' => auth()->id(),
                        'uploaded_at' => now(),
                        'pleading_type' => $validated['pleading_type'] ?? 'none'
                    ];

                    if ($documentType && $documentType->is_pleading && isset($validated['pleading_type']) && $validated['pleading_type'] !== 'none') {
                        $documentData['pleading_type'] = $validated['pleading_type'];
                    } else {
                        $documentData['pleading_type'] = 'none';
                    }

                    \App\Models\Document::create($documentData);
                    $uploadedCount++;
                }
            }

            $message = $uploadedCount === 1
                ? 'Document uploaded successfully and is pending HU acceptance.'
                : "{$uploadedCount} documents uploaded successfully and are pending HU acceptance.";
            return redirect()->route('cases.documents.manage', $case)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }

    public function approveDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);

        // Check case status - allow submitted_to_hu and active
        if (!in_array($case->status, ['submitted_to_hu', 'active'])) {
            return response()->json(['success' => false, 'error' => 'Documents can only be accepted in submitted or active cases']);
        }

        $document->update([
            'approved' => true,
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
            'rejected_reason' => null
        ]);

        $document->replacementForCorrection()
            ->where('status', 'resubmitted')
            ->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by_user_id' => auth()->id(),
            ]);

        return response()->json(['success' => true, 'message' => 'Document accepted successfully']);
    }

    public function rejectDocument(Request $request, CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'reason_summary' => 'nullable|string|max:2000',
            'correction_items' => 'nullable|array',
            'correction_items.*.category' => 'nullable|in:missing_document,caption_issue,party_issue,service_issue,ose_issue,document_issue,filing_issue,other',
            'correction_items.*.item_note' => 'nullable|string|max:2000',
            'correction_items.*.required_action' => 'nullable|string|max:2000',
        ]);

        $document = $case->documents()->findOrFail($documentId);
        $summary = $validated['reason_summary'] ?? $validated['reason'] ?? null;
        if (!$summary) {
            return response()->json(['success' => false, 'error' => 'A correction summary is required.'], 422);
        }

        $document->update([
            'approved' => false,
            'rejected_reason' => $summary,
            'approved_by_user_id' => null,
            'approved_at' => null
        ]);

        $correction = $this->createDocumentCorrection(
            $case,
            $document,
            auth()->user(),
            'rejected',
            $summary,
            $validated['correction_items'] ?? []
        );

        // Notify document uploader
        if ($document->uploader) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notify(
                $document->uploader,
                'document_rejected',
                'Document Rejected - Action Required',
                "Your document '{$document->original_filename}' in case {$case->case_no} has been rejected.\n\nSummary: {$summary}\n\nPlease review the correction items, submit a corrected replacement, and wait for HU review.",
                $case
            );
        }

        return response()->json(['success' => true, 'correction_id' => $correction->id]);
    }





    public function requestDocumentFix(Request $request, CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'reason_summary' => 'nullable|string|max:2000',
            'correction_items' => 'nullable|array',
            'correction_items.*.category' => 'nullable|in:missing_document,caption_issue,party_issue,service_issue,ose_issue,document_issue,filing_issue,other',
            'correction_items.*.item_note' => 'nullable|string|max:2000',
            'correction_items.*.required_action' => 'nullable|string|max:2000',
        ]);

        $document = $case->documents()->findOrFail($documentId);
        $summary = $validated['reason_summary'] ?? $validated['reason'] ?? null;
        if (!$summary) {
            return response()->json(['success' => false, 'error' => 'A correction summary is required.'], 422);
        }

        // Update document with fix request
        $document->update([
            'approved' => false,
            'rejected_reason' => 'Fix Required: ' . $summary,
            'approved_by_user_id' => null,
            'approved_at' => null
        ]);

        $correction = $this->createDocumentCorrection(
            $case,
            $document,
            auth()->user(),
            'fix_required',
            $summary,
            $validated['correction_items'] ?? []
        );

        // Notify document uploader about fix request
        if ($document->uploader) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notify(
                $document->uploader,
                'document_fix_required',
                'Document Fix Required - Action Needed',
                "Your document '{$document->original_filename}' in case {$case->case_no} requires corrections.\n\nSummary: {$summary}\n\nPlease make the necessary changes, submit a corrected replacement, and wait for HU review.",
                $case
            );
        }

        return response()->json(['success' => true, 'correction_id' => $correction->id]);
    }

    public function submitCorrectedDocument(Request $request, CaseModel $case, $documentId)
    {
        $user = Auth::user();

        if ($user->isHearingUnit() || !$user->canUploadDocumentsToCase($case)) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);

        if ((int) $document->uploaded_by_user_id !== (int) $user->id) {
            abort(403, 'Only the original document submitter can submit a corrected replacement.');
        }

        $correction = $document->correctionCycles()
            ->with('items')
            ->whereIn('status', ['open', 'resubmitted'])
            ->first();

        if (!$correction) {
            return back()->withErrors(['error' => 'There is no open document correction cycle for this filing.']);
        }

        $validated = $request->validate([
            'custom_title' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:204800',
            'resolution_items' => 'required|array',
            'resolution_items.*.resolution_note' => 'nullable|string|max:2000',
        ]);

        $resolutionErrors = [];
        foreach ($correction->items as $item) {
            $resolutionNote = trim((string) ($request->input("resolution_items.{$item->id}.resolution_note") ?? ''));
            if ($resolutionNote === '') {
                $resolutionErrors["resolution_items.{$item->id}.resolution_note"] = "Add a resolution note for correction item {$item->id}.";
            }
        }

        if (!empty($resolutionErrors)) {
            return back()->withInput()->withErrors($resolutionErrors);
        }

        $file = $request->file('document');
        $documentType = \App\Models\DocumentType::where('code', $document->doc_type)->first();
        $displayType = $documentType ? $documentType->name : ucfirst(str_replace('_', ' ', $document->doc_type));
        $storageFolder = $this->caseStorageService->getCaseStorageFolder($case);
        $oldStorageUri = $document->storage_uri;

        $titleOrType = !empty($validated['custom_title']) ? $validated['custom_title'] : $displayType;
        $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . '.pdf';
        $storedFilename = $this->generateReadableStoredFilename($originalFilename, $storageFolder);
        $path = $file->storeAs($storageFolder, $storedFilename, 'public');

        $document->update([
            'custom_title' => $validated['custom_title'],
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => md5_file($file->getRealPath()),
            'storage_uri' => $path,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
            'pleading_type' => $document->pleading_type ?? 'none',
            'approved' => false,
            'stamped' => false,
            'stamp_text' => null,
            'stamped_at' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_reason' => null,
        ]);

        if ($oldStorageUri && $oldStorageUri !== $path && Storage::disk('public')->exists($oldStorageUri)) {
            Storage::disk('public')->delete($oldStorageUri);
        }

        foreach ($correction->items as $item) {
            $item->update([
                'resolution_note' => trim((string) $request->input("resolution_items.{$item->id}.resolution_note")),
                'resolved_at' => now(),
                'resolved_by_user_id' => $user->id,
            ]);
        }

        $correction->update([
            'status' => 'resubmitted',
            'resubmitted_at' => now(),
            'resubmitted_by_user_id' => $user->id,
            'replacement_document_id' => $document->id,
        ]);

        AuditLog::log('submit_document_correction', $user, $case, [
            'original_document_id' => $document->id,
            'replacement_document_id' => $document->id,
            'document_correction_id' => $correction->id,
            'superseded_storage_uri' => $oldStorageUri,
        ]);

        return redirect()->route('cases.documents.manage', $case)->with('success', 'Corrected document submitted and is pending HU review.');
    }

    public function stampDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);

        $isAcceptedPleading = in_array($document->pleading_type, ['request_to_docket', 'request_pre_hearing']);
        $canStamp = $document->approved && ($case->status === 'active' || $isAcceptedPleading);

        // In active cases, any accepted document can be stamped.
        // Before a case is active, stamping remains limited to accepted pleading documents.
        if (!$canStamp) {
            return response()->json(['success' => false, 'error' => 'Only accepted documents in active cases, or accepted pleading documents, can be stamped']);
        }

        if ($document->stamped) {
            return response()->json(['success' => false, 'error' => 'Document is already stamped']);
        }

        try {
            $stampingService = app(\App\Services\PdfStampingService::class);
            $result = $stampingService->stampDocument($document, $case);

            if (!$result) {
                \Log::error('Stamping service returned false for document: ' . $documentId);
                return response()->json(['success' => false, 'error' => 'Unable to e-stamp this PDF. Check the document format and try again.']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Document stamping failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to stamp document: ' . $e->getMessage()]);
        }
    }

    private function createDocumentCorrection(CaseModel $case, Document $document, User $user, string $type, string $summary, array $items): DocumentCorrection
    {
        $normalizedItems = collect($items)
            ->map(function (array $item, int $index) {
                return [
                    'category' => $item['category'] ?? 'other',
                    'item_note' => trim((string) ($item['item_note'] ?? '')),
                    'required_action' => trim((string) ($item['required_action'] ?? '')),
                    'sort_order' => $index,
                ];
            })
            ->filter(fn (array $item) => $item['item_note'] !== '' || $item['required_action'] !== '')
            ->values();

        if ($normalizedItems->isEmpty()) {
            $normalizedItems = collect([[
                'category' => 'other',
                'item_note' => $summary,
                'required_action' => 'Review the correction summary, fix the document, and submit a corrected replacement for HU review.',
                'sort_order' => 0,
            ]]);
        }

        $document->correctionCycles()
            ->whereIn('status', ['open', 'resubmitted'])
            ->update(['status' => 'superseded']);

        $correction = DocumentCorrection::create([
            'case_id' => $case->id,
            'original_document_id' => $document->id,
            'requested_by_user_id' => $user->id,
            'correction_type' => $type,
            'summary' => $summary,
            'status' => 'open',
            'requested_at' => now(),
        ]);

        foreach ($normalizedItems as $item) {
            $correction->items()->create($item);
        }

        AuditLog::log('document_correction_requested', $user, $case, [
            'document_id' => $document->id,
            'document_correction_id' => $correction->id,
            'correction_type' => $type,
            'items_count' => $correction->items()->count(),
        ]);

        return $correction->load('items');
    }

    private function syncOpenRejectionResolutions(CaseModel $case, array $resolutionInput, User $user, bool $requireResolved): array
    {
        $openRejection = $case->rejections()
            ->where('status', 'open')
            ->with('items')
            ->first();

        if (!$openRejection) {
            return [];
        }

        $errors = [];

        foreach ($openRejection->items as $item) {
            $itemInput = $resolutionInput[$item->id] ?? [];
            $resolutionNote = trim((string) ($itemInput['resolution_note'] ?? $item->resolution_note ?? ''));
            $markResolved = $item->resolved_at !== null || isset($itemInput['mark_resolved']);

            if ($markResolved && $resolutionNote === '') {
                $errors["rejection_items.{$item->id}.resolution_note"] = "Add a resolution note for rejection item {$item->id}.";
                continue;
            }

            if ($requireResolved && (!$markResolved || $resolutionNote === '')) {
                $errors["rejection_items.{$item->id}.mark_resolved"] = "Resolve rejection item {$item->id} before resubmitting.";
                continue;
            }

            $updates = [];

            if ($resolutionNote !== '' && $resolutionNote !== (string) $item->resolution_note) {
                $updates['resolution_note'] = $resolutionNote;
            }

            if ($markResolved && $resolutionNote !== '' && $item->resolved_at === null) {
                $updates['resolved_at'] = now();
                $updates['resolved_by_user_id'] = $user->id;
            }

            if (!empty($updates)) {
                $item->update($updates);
            }
        }

        return $errors;
    }

    private function markOpenRejectionResubmitted(CaseModel $case, User $user): void
    {
        $openRejection = $case->rejections()
            ->where('status', 'open')
            ->with('items')
            ->first();

        if (!$openRejection) {
            return;
        }

        $metadata = $case->metadata ?? [];
        unset($metadata['rejection_reason']);
        $case->update(['metadata' => $metadata]);

        $openRejection->update([
            'status' => 'resubmitted',
            'resubmitted_at' => now(),
            'resubmitted_by_user_id' => $user->id,
        ]);

        AuditLog::log('resubmit_rejected_case', $user, $case, [
            'rejection_id' => $openRejection->id,
            'resolved_items' => $openRejection->items->whereNotNull('resolved_at')->count(),
            'total_items' => $openRejection->items->count(),
        ]);
    }

    public function updateDocumentTitle(Request $request, CaseModel $case, $documentId)
    {
        $document = $case->documents()->findOrFail($documentId);

        if (!auth()->user()->isHearingUnit() && $document->uploaded_by_user_id !== auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        }

        $validated = $request->validate([
            'custom_title' => 'nullable|string|max:255'
        ]);

        $customTitle = $validated['custom_title'] ?? null;
        $displayType = $this->getDisplayType($document->doc_type);
        $titleOrType = !empty($customTitle) ? $customTitle : $displayType;

        $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . '.pdf';

        $oldFilename = $document->original_filename;
        $oldTitle = $document->custom_title ?? $displayType;

        $document->update([
            'custom_title' => $customTitle,
            'original_filename' => $originalFilename
        ]);

        \App\Services\AuditService::logDocumentTitleChange(
            $case,
            auth()->user(),
            $document->id,
            $oldTitle,
            $customTitle ?? $displayType
        );

        return response()->json(['success' => true]);
    }

    private function getDisplayType($docType)
    {
        $documentType = \App\Models\DocumentType::where('code', $docType)->first();
        return $documentType ? $documentType->name : ucfirst(str_replace('_', ' ', $docType));
    }

    public function destroyDocument(CaseModel $case, $documentId)
    {
        $document = $case->documents()->findOrFail($documentId);

        // Allow ALU clerks to delete documents from draft/rejected cases
        if (!((auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected'])) ||
              auth()->user()->getCurrentRole() === 'admin' ||
              $document->uploaded_by_user_id === auth()->id())) {
            abort(403);
        }

        // Delete file from storage
        if (\Storage::disk('public')->exists($document->storage_uri)) {
            \Storage::disk('public')->delete($document->storage_uri);
        }

        $document->delete();

        return response()->json(['success' => true]);
    }

    public function close(Request $request, CaseModel $case)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:Applicant\'s failure to submit hearing fee,Applicant\'s failure to participate,Mediated Settlement,Withdrawal of Protest(s),Withdrawal of Application,Final Decision,Other',
            'other_reason' => 'nullable|string|max:500',
        ]);

        if ($validated['reason'] === 'Other' && empty($validated['other_reason'])) {
            return back()->withErrors(['other_reason' => 'Please provide the reason for closing this case.'])->withInput();
        }

        $reason = $validated['reason'] === 'Other'
            ? $validated['other_reason']
            : $validated['reason'];

        if ($this->caseService->closeCase($case, auth()->user(), $reason)) {
            return back()->with('success', 'Case closed successfully.');
        }

        return back()->with('error', 'Unable to close case.');
    }

    public function archive(CaseModel $case)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'admin'])) {
            abort(403);
        }

        if ($this->caseService->archiveCase($case, auth()->user())) {
            return back()->with('success', 'Case archived successfully.');
        }

        return back()->with('error', 'Unable to archive case.');
    }

    public function reopen(Request $request, CaseModel $case)
    {
        if (auth()->user()->getCurrentRole() !== 'hu_admin') {
            abort(403, 'Only HU Admin can reopen cases.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if ($this->caseService->reopenCase($case, auth()->user(), $validated['reason'])) {
            return back()->with('success', 'Case reopened and parties notified.');
        }

        return back()->with('error', 'Unable to reopen case.');
    }

    private function validateSubmissionRequirements(CaseModel $case): array
    {

        $errors = [];

        // Check if ALU Attorney is assigned
        if (!$case->aluAttorneys || $case->aluAttorneys->count() === 0) {
            $errors[] = 'ALU Attorney must be assigned before submission.';
        }

        // Check if at least one primary party exists (Applicant for regular cases, or compliance roles for compliance cases)
        if ($case->case_type === 'compliance') {
            $hasComplianceParty = $case->parties()->whereIn('role', ['respondent'])->exists();
            if (!$hasComplianceParty) {
                $errors[] = 'At least one Respondent must be added to compliance cases.';
            }
        } else {
            $hasApplicant = $case->parties()->where('role', 'applicant')->exists();
            if (!$hasApplicant) {
                $errors[] = 'At least one Applicant must be added to the case.';
            }
        }

        // Check if pleading document exists (Request to Docket OR Request for Pre-Hearing)
        $hasPleadingDoc = $case->documents()->whereIn('pleading_type', ['request_to_docket', 'request_pre_hearing'])->exists();
        if (!$hasPleadingDoc) {
            $errors[] = 'Either Request to Docket or Request for Pre-Hearing document must be uploaded.';
        }
        return $errors;
    }

    public function addParalegal(Request $request, CaseModel $case)
    {
        $user = Auth::user();

        $isOutsideCounsel = $user->isAttorney();
        $isAssignedAluAttorney = $user->isALUAttorney() && $case->assignments()
            ->where('assignment_type', 'alu_atty')
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOutsideCounsel && !$isAssignedAluAttorney) {
            abort(403, 'Only attorneys can add paralegals.');
        }

        if ($isOutsideCounsel) {
            $isCounsel = $case->parties()->where('role', 'counsel')->whereHas('person', function($q) use ($user) {
                $q->where('email', $user->email);
            })->exists();
        } else {
            $isCounsel = $isAssignedAluAttorney;
        }

        if (!$isCounsel) {
            abort(403, 'You can only add paralegals to cases you are representing.');
        }

        $validated = $request->validate([
            'existing_person_id' => 'nullable|exists:persons,id',
            'type' => 'nullable|in:individual',
            'prefix' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_office' => 'nullable|string|max:20',
            'phone_mobile' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
            'notes' => 'nullable|string'
        ]);

        $usingExistingParalegal = !empty($validated['existing_person_id']);

        if ($usingExistingParalegal) {
            $person = \App\Models\Person::findOrFail($validated['existing_person_id']);

            if (blank($person->email)) {
                return back()->withErrors(['existing_person_id' => 'The selected paralegal does not have an email address on file.']);
            }
        } else {
            if (empty($validated['first_name']) || empty($validated['last_name']) || empty($validated['email'])) {
                return back()->withErrors([
                    'first_name' => 'First name, last name, and email are required when creating a new paralegal.'
                ])->withInput();
            }

            // Find or create person
            $person = \App\Models\Person::where('email', $validated['email'])->first();

            if (!$person) {
                $person = \App\Models\Person::create([
                    'type' => $validated['type'] ?? 'individual',
                    'prefix' => $validated['prefix'] ?? null,
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'],
                    'suffix' => $validated['suffix'] ?? null,
                    'organization' => $validated['organization'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'email' => $validated['email'],
                    'phone_office' => $validated['phone_office'] ?? null,
                    'phone_mobile' => $validated['phone_mobile'] ?? null,
                    'address_line1' => $validated['address_line1'] ?? null,
                    'address_line2' => $validated['address_line2'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'zip' => $validated['zip'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            } else {
                $person->update([
                    'type' => $validated['type'] ?? 'individual',
                    'prefix' => $validated['prefix'] ?? null,
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'],
                    'suffix' => $validated['suffix'] ?? null,
                    'organization' => $validated['organization'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'phone_office' => $validated['phone_office'] ?? null,
                    'phone_mobile' => $validated['phone_mobile'] ?? null,
                    'address_line1' => $validated['address_line1'] ?? null,
                    'address_line2' => $validated['address_line2'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'zip' => $validated['zip'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        }

        // Ensure the paralegal has a login-capable user account.
        $paralegalUser = User::firstOrCreate(
            ['email' => $person->email],
            [
                'name' => $person->full_name ?: trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')),
                'password' => Hash::make(Str::random(32)),
                'role' => 'party',
                'is_active' => true,
            ]
        );

        if (!$paralegalUser->is_active) {
            $paralegalUser->update(['is_active' => true]);
        }

        if (blank($paralegalUser->name)) {
            $paralegalUser->update([
                'name' => $person->full_name ?: trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')),
            ]);
        }

        if ($isOutsideCounsel) {
            $exists = $case->parties()->where('person_id', $person->id)->exists();
            if ($exists) {
                return back()->withErrors(['error' => 'This person is already associated with this case.']);
            }

            $counselParty = $case->parties()->where('role', 'counsel')->whereHas('person', function($q) use ($user) {
                $q->where('email', $user->email);
            })->first();

            \App\Models\CaseParty::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'role' => 'paralegal',
                'client_party_id' => $counselParty->client_party_id,
                'service_enabled' => true
            ]);

            \App\Models\ServiceList::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $person->id,
            ], [
                'email' => $person->email,
                'service_method' => 'email',
                'is_primary' => false
            ]);
        } else {
            $assignmentExists = $case->assignments()
                ->where('assignment_type', 'alu_paralegal')
                ->where('user_id', $paralegalUser->id)
                ->exists();

            if ($assignmentExists) {
                return back()->withErrors(['error' => 'This paralegal is already assigned to this case.']);
            }

            $case->assignments()->create([
                'user_id' => $paralegalUser->id,
                'assignment_type' => 'alu_paralegal',
                'assigned_by' => $user->id,
            ]);
        }

        Password::sendResetLink(['email' => $paralegalUser->email]);

        app(\App\Services\NotificationService::class)->notify(
            $person,
            'paralegal_added',
            'Paralegal Access Added',
            "You have been added as a paralegal on case {$case->case_no}. You can now access the case, receive case notifications, and file documents for the represented party. If you have not signed in before, use Forgot Password with this email address to set your password.",
            $case
        );

        return back()->with('success', 'Paralegal added successfully.');
    }

    public function removeParalegal(CaseModel $case, $partyId)
    {
        $user = Auth::user();

        $isAssignedAluAttorney = $user->isALUAttorney() && $case->assignments()
            ->where('assignment_type', 'alu_atty')
            ->where('user_id', $user->id)
            ->exists();

        if (!$user->isAttorney() && !$isAssignedAluAttorney) {
            abort(403);
        }

        $paralegalParty = $case->parties()->where('id', $partyId)->where('role', 'paralegal')->first();

        if ($paralegalParty) {
            $isCounsel = $case->parties()
                ->where('role', 'counsel')
                ->where('client_party_id', $paralegalParty->client_party_id)
                ->whereHas('person', function($q) use ($user) {
                    $q->where('email', $user->email);
                })->exists();

            if (!$isCounsel) {
                abort(403, 'You can only remove your own paralegals.');
            }

            $case->serviceList()->where('person_id', $paralegalParty->person_id)->delete();
            $paralegalParty->delete();
        } else {
            $assignment = $case->assignments()
                ->where('id', $partyId)
                ->where('assignment_type', 'alu_paralegal')
                ->firstOrFail();

            if (!$isAssignedAluAttorney || (int) $assignment->assigned_by !== (int) $user->id) {
                abort(403, 'You can only remove your own paralegals.');
            }

            $assignment->delete();
        }

        return response()->json(['success' => true, 'message' => 'Paralegal removed successfully.']);
    }

    public function destroy(CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() || $case->status !== 'draft') {
            abort(403, 'Only draft cases can be deleted.');
        }

        // Delete all documents and files
        foreach ($case->documents as $document) {
            if (\Storage::disk('public')->exists($document->storage_uri)) {
                \Storage::disk('public')->delete($document->storage_uri);
            }
            $document->delete();
        }

        // Delete case (cascading will handle related records)
        $case->delete();

        return redirect()->route('cases.index')->with('success', 'Draft case deleted successfully.');
    }

    private function generateReadableStoredFilename(string $displayFilename, string $storageFolder): string
    {
        $baseName = pathinfo($displayFilename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($displayFilename, PATHINFO_EXTENSION) ?: 'pdf');
        $baseName = preg_replace('/ - [A-Za-z0-9]+-\d+(?: et al\.)?(?=( \(\d+\))?$)/', '', $baseName);
        $baseName = preg_replace('/[\\\\\\/:*?"<>|]/', '-', (string) $baseName);
        $baseName = trim(preg_replace('/\s+/', ' ', $baseName) ?: 'document');
        if ($baseName === '') {
            $baseName = 'document';
        }

        $storageFolder = trim($storageFolder, '/');

        do {
            $timestamp = now()->format('Ymd_His_u');
            $candidate = "{$baseName} - {$timestamp}.{$extension}";
            $path = $storageFolder === '' ? $candidate : "{$storageFolder}/{$candidate}";
        } while (\Storage::disk('public')->exists($path));

        return $candidate;
    }
}
