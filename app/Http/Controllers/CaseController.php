<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseRequest;
use App\Models\AuditLog;
use App\Models\CaseModel;
use App\Models\CaseAssignment;
use App\Models\CaseParty;
use App\Models\Document;
use App\Models\DocumentCorrection;
use App\Models\OseFileNumber;
use App\Models\Person;
use App\Models\User;
use App\Services\CaseService;
use App\Services\CaseStorageService;
use App\Services\ServiceListResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function __construct(
        private CaseService $caseService,
        private CaseStorageService $caseStorageService,
        private ServiceListResolver $serviceListResolver
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $assignedTypesByRole = [
            'alu_atty' => ['alu_atty', 'alu_attorney'],
            'contract_attorney' => ['alu_atty', 'alu_attorney'],
            'wrd' => ['wrd'],
            'hydrology_expert' => ['hydrology_expert'],
            'alu_clerk' => ['alu_clerk'],
            'alu_paralegal' => ['alu_clerk', 'alu_paralegal'],
        ];
        $currentRole = $user->getCurrentRole();
        $scope = $request->string('scope')->toString();
        $query = $this->buildCaseIndexBaseQuery($user, $assignedTypesByRole, $currentRole);
        $allowedStatuses = $this->getAllowedCaseStatuses($user, $currentRole);

        if ($scope === 'my_cases') {
            $query = $this->buildMyCasesQuery($user, $assignedTypesByRole, $currentRole);
        } elseif ($scope === 'pending_review') {
            $query->where('status', 'submitted_to_hu');
        } elseif ($scope === 'active') {
            $query->where('status', 'active');
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

        return view('cases.index', compact('cases', 'allowedStatuses', 'allowedTypes', 'scope'));
    }

    private function buildCaseIndexBaseQuery(User $user, array $assignedTypesByRole, string $currentRole)
    {
        if ($user->isHearingUnit()) {
            return CaseModel::whereNotIn('status', ['draft']);
        }

        if ($user->canAssignAttorneys() && !$user->isContractAttorney()) {
            return CaseModel::query();
        }

        if ($user->isContractAttorney()) {
            return CaseModel::accessibleToContractAttorney($user);
        }

        if (in_array($currentRole, ['party', 'external_attorney'], true)) {
            return CaseModel::whereNotIn('status', ['draft'])
                ->where(function ($caseQuery) use ($user) {
                    $caseQuery->whereHas('parties', function ($partyQuery) use ($user) {
                        $partyQuery->whereHas('person', function ($personQuery) use ($user) {
                            $personQuery->where('email', $user->email);
                        });
                    })->orWhereHas('assignments', function ($assignmentQuery) use ($user) {
                        $assignmentQuery->whereIn('assignment_type', ['alu_paralegal', 'alu_atty', 'alu_attorney'])
                            ->where('user_id', $user->id);
                    });
                });
        }

        if (isset($assignedTypesByRole[$currentRole])) {
            if (!in_array($currentRole, ['alu_atty', 'contract_attorney'], true)) {
                return CaseModel::whereHas('assignments', function ($assignmentQuery) use ($user, $assignedTypesByRole, $currentRole) {
                    $assignmentQuery->where('user_id', $user->id)
                        ->whereIn('assignment_type', $assignedTypesByRole[$currentRole]);
                })->whereNotIn('status', ['draft']);
            }

            return CaseModel::where(function ($caseQuery) use ($user, $assignedTypesByRole, $currentRole) {
                $caseQuery->where('created_by_user_id', $user->id)
                    ->orWhereHas('assignments', function ($assignmentQuery) use ($user, $assignedTypesByRole, $currentRole) {
                        $assignmentQuery->where('user_id', $user->id)
                            ->whereIn('assignment_type', $assignedTypesByRole[$currentRole]);
                    });
            });
        }

        return $user->createdCases();
    }

    private function buildMyCasesQuery(User $user, array $assignedTypesByRole, string $currentRole)
    {
        if ($user->isContractAttorney()) {
            return CaseModel::accessibleToContractAttorney($user);
        }

        if (in_array($currentRole, ['party', 'external_attorney'], true)) {
            if ($user->isExternalAttorney()) {
                return CaseModel::whereNotIn('status', ['draft'])
                    ->where(function ($caseQuery) use ($user) {
                        $caseQuery->whereHas('parties', function ($query) use ($user) {
                            $query->whereHas('person', function ($subQuery) use ($user) {
                                $subQuery->where('email', $user->email);
                            });
                        })->orWhereHas('assignments', function ($query) use ($user) {
                            $query->whereIn('assignment_type', ['alu_atty', 'alu_attorney'])
                                ->where('user_id', $user->id);
                        });
                    });
            }

            if ($user->isParalegal()) {
                return CaseModel::where(function ($caseQuery) use ($user) {
                    $caseQuery->whereHas('parties', function ($query) use ($user) {
                        $query->where('role', 'paralegal')
                            ->whereHas('person', function ($subQuery) use ($user) {
                                $subQuery->where('email', $user->email);
                            });
                    })->orWhereHas('assignments', function ($query) use ($user) {
                        $query->where('assignment_type', 'alu_paralegal')
                            ->where('user_id', $user->id);
                    });
                })->whereIn('status', ['active', 'submitted_to_hu']);
            }

            if ($user->isAttorney()) {
                return CaseModel::where(function ($caseQuery) use ($user) {
                    $caseQuery->whereHas('parties', function ($query) use ($user) {
                        $query->where('role', 'counsel')
                            ->whereHas('person', function ($subQuery) use ($user) {
                                $subQuery->where('email', $user->email);
                            });
                    })->orWhereHas('assignments', function ($query) use ($user) {
                        $query->whereIn('assignment_type', ['alu_atty', 'alu_attorney'])
                            ->where('user_id', $user->id);
                    });
                })->whereIn('status', ['active', 'submitted_to_hu']);
            }

            return CaseModel::whereHas('parties', function ($query) use ($user) {
                $query->whereIn('role', ['applicant', 'protestant', 'aggrieved_party', 'respondent'])
                    ->whereHas('person', function ($subQuery) use ($user) {
                        $subQuery->where('email', $user->email);
                    });
            })->whereIn('status', ['active', 'submitted_to_hu']);
        }

        if (isset($assignedTypesByRole[$currentRole])) {
            if (!in_array($currentRole, ['alu_atty', 'contract_attorney'], true)) {
                return CaseModel::whereHas('assignments', function ($query) use ($assignedTypesByRole, $currentRole, $user) {
                    $query->where('user_id', $user->id)
                        ->whereIn('assignment_type', $assignedTypesByRole[$currentRole]);
                })->whereNotIn('status', ['draft']);
            }

            return CaseModel::where(function ($caseQuery) use ($assignedTypesByRole, $currentRole, $user) {
                $caseQuery->where('created_by_user_id', $user->id)
                    ->orWhereHas('assignments', function ($query) use ($assignedTypesByRole, $currentRole, $user) {
                        $query->where('user_id', $user->id)
                            ->whereIn('assignment_type', $assignedTypesByRole[$currentRole]);
                    });
            });
        }

        if ($user->isHearingUnit()) {
            return CaseModel::whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->whereNotIn('status', ['draft']);
        }

        return $user->createdCases();
    }

    private function getAllowedCaseStatuses(User $user, string $currentRole): array
    {
        if ($user->isHearingUnit()) {
            return ['submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        }

        if ($user->canAssignAttorneys() && !$user->isContractAttorney()) {
            return ['draft', 'submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        }

        if (in_array($currentRole, ['party', 'external_attorney', 'wrd', 'hydrology_expert', 'alu_clerk', 'alu_paralegal'], true)) {
            return ['submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        }

        if (in_array($currentRole, ['alu_atty', 'contract_attorney'], true)) {
            return ['draft', 'submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
        }

        return ['draft', 'submitted_to_hu', 'active', 'closed', 'archived', 'rejected'];
    }

    public function create()
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $basinCodes = \App\Models\OseBasinCode::orderBy('initial')->get();
        $attorneys = Person::counselDirectory()->get();

        $documentTypes = \App\Models\DocumentType::forRoles(Auth::user()->documentFilingRoles())
            ->dropdownOrder()
            ->get();
        $pleadingDocs = $documentTypes
            ->where('is_pleading', true);
        $optionalDocs = $documentTypes
            ->where('is_pleading', false)
            ->where('category', 'case_creation');

        return view('cases.create', compact('basinCodes', 'attorneys', 'documentTypes', 'pleadingDocs', 'optionalDocs'));
    }

    public function store(StoreCaseRequest $request)
    {
        $validated = $request->validated();

        if (Auth::user()->isALUAttorney() || Auth::user()->isContractAttorney()) {
            $validated['assigned_attorneys'] = collect($validated['assigned_attorneys'] ?? [])
                ->push(Auth::id())
                ->unique()
                ->values()
                ->all();
        }

        // Log raw request data for debugging
        \Log::info('Raw request parties:', ['parties' => $request->input('parties')]);
        \Log::info('Party validation data:', ['parties' => $validated['parties']]);

        try {
            if ($message = $this->requestHasWrdRepresentativeCounselConflict($validated)) {
                return back()->withInput()->withErrors(['assigned_attorneys' => $message]);
            }

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

            // Handle ALU clerk/paralegal assignments
            if (isset($validated['assigned_clerks']) && !empty($validated['assigned_clerks'])) {
                foreach ($validated['assigned_clerks'] as $clerkId) {
                    $clerk = User::findOrFail($clerkId);
                    $assignmentType = $clerk->aluSupportAssignmentType();

                    if (!$assignmentType) {
                        throw new \InvalidArgumentException('Only ALU clerks and paralegals may be assigned as ALU support staff.');
                    }

                    CaseAssignment::create([
                        'case_id' => $case->id,
                        'user_id' => $clerkId,
                        'assignment_type' => $assignmentType,
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

    private function requestHasWrdRepresentativeCounselConflict(array $validated): ?string
    {
        $assignedAttorneyIds = $validated['assigned_attorneys'] ?? [];
        if (empty($assignedAttorneyIds) || empty($validated['parties'])) {
            return null;
        }

        $assignedEmails = User::whereIn('id', $assignedAttorneyIds)
            ->get(['name', 'email'])
            ->mapWithKeys(fn (User $user) => [strtolower(trim((string) $user->email)) => $user->name])
            ->filter(fn ($name, $email) => $email !== '');

        if ($assignedEmails->isEmpty()) {
            return null;
        }

        foreach ($validated['parties'] as $partyData) {
            $counselEmail = null;

            if (!empty($partyData['attorney_id'])) {
                $counselEmail = Person::whereKey($partyData['attorney_id'])->value('email');
            } elseif (!empty($partyData['attorney_email'])) {
                $counselEmail = $partyData['attorney_email'];
            }

            $counselEmail = strtolower(trim((string) $counselEmail));
            if ($counselEmail !== '' && $assignedEmails->has($counselEmail)) {
                return "{$assignedEmails[$counselEmail]} is selected as a WRD representative and also entered as private counsel in this case.";
            }
        }

        return null;
    }

    public function show(CaseModel $case)
    {
        // HU users cannot see draft cases
        if (Auth::user()->isHearingUnit() && $case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to Hearing Unit staff.');
        }

        $restrictedCaseRoles = ['party', 'interested_party', 'external_attorney', 'contract_attorney'];

        // Parties and external participants cannot see draft cases
        if (in_array(Auth::user()->getCurrentRole(), ['party', 'interested_party', 'external_attorney'], true) && $case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to parties and attorneys.');
        }

        if (in_array(Auth::user()->getCurrentRole(), $restrictedCaseRoles, true) && !Auth::user()->canAccessCase($case)) {
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
            'parties.attorneys.person',
            'parties.agents.person',
            'serviceList.person',
            'oseFileNumbers',
            'auditLogs.user',
            'rejections.rejectedBy',
            'rejections.resubmittedBy',
            'rejections.items.resolvedBy',
        ]);
        $submissionNotificationRecipients = $this->caseService->getSubmissionNotificationOptions();
        $acceptanceNotificationRecipients = $this->caseService->getAcceptanceNotificationOptions($case);
        $pendingAluAcceptanceDocuments = $case->pendingAluDocumentsForAcceptance()
            ->with('uploader.roleRelation')
            ->orderByDesc('uploaded_at')
            ->get();
        $documentTypes = \App\Models\DocumentType::forRoles(Auth::user()->documentFilingRoles($case))
            ->dropdownOrder()
            ->get();
        $resolvedServiceList = $this->serviceListResolver->resolve($case);

        return view('cases.show', compact('case', 'submissionNotificationRecipients', 'acceptanceNotificationRecipients', 'pendingAluAcceptanceDocuments', 'documentTypes', 'resolvedServiceList'));
    }

    public function downloadServiceList(CaseModel $case)
    {
        $serviceEntries = $this->serviceListResolver->resolve($case);

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
                fputcsv($handle, [
                    $case->case_no,
                    $service['name'],
                    $service['organization'],
                    $service['address_line1'],
                    $service['address_line2'],
                    $service['city'],
                    $service['state'],
                    $service['zip'],
                    $service['email'],
                    $service['role'],
                    $service['service_method'],
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function edit(CaseModel $case)
    {
        if (!Auth::user()->canManageDraftCase($case)) {
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
        if (!Auth::user()->canManageDraftCase($case)) {
            abort(403);
        }

        $validated = $request->validate([
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string',
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
                $validationErrors = $this->validateSubmissionRequirements($case, Auth::user());
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
                $customMessage = $validated['custom_message'] ?? null;
                $this->caseService->notifyCaseSubmission($case, [], $customMessage);
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
        $validated = request()->validate([
            'notify_recipients' => 'nullable|array',
            'notify_recipients.*' => 'string',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $pendingAluDocumentCount = $case->pendingAluDocumentsForAcceptance()->count();
        if ($pendingAluDocumentCount > 0) {
            return back()->with('error', "Accept all ALU-submitted documents before accepting the case. {$pendingAluDocumentCount} document(s) still need acceptance.");
        }

        if ($this->caseService->acceptCase(
            $case,
            Auth::user(),
            $validated['notify_recipients'] ?? [],
            $validated['custom_message'] ?? null
        )) {
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
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $attorneys = \App\Models\User::whereAnyCurrentRole(['alu_atty', 'contract_attorney'])->orderBy('name')->get();
        return view('cases.assign-attorney', compact('case', 'attorneys'));
    }

    public function assignAttorney(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        if (Auth::user()->isContractAttorney()) {
            $request->merge([
                'attorney_ids' => collect($request->input('attorney_ids', []))
                    ->push(Auth::id())
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }

        $validated = $request->validate([
            'attorney_ids' => 'required|array|min:1',
            'attorney_ids.*' => 'exists:users,id'
        ]);

        $attorneys = User::whereIn('id', $validated['attorney_ids'])->get();
        foreach ($attorneys as $attorney) {
            if ($conflict = CaseParty::privateCounselForEmail($case, $attorney->email)) {
                $clientName = $conflict->clientParty?->person?->full_name ?? 'a party';

                return back()->withErrors([
                    'attorney_ids' => "{$attorney->name} is already private counsel for {$clientName} in this case and cannot also represent WRD."
                ])->withInput();
            }
        }

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
        if (!Auth::user()->canAssignHydrologyExperts() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $experts = \App\Models\User::whereCurrentRole('hydrology_expert')->orderBy('name')->get();
        return view('cases.assign-hydrology-expert', compact('case', 'experts'));
    }

    public function assignHydrologyExpert(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignHydrologyExperts() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $validated = $request->validate([
            'expert_ids' => 'nullable|array',
            'expert_ids.*' => 'exists:users,id'
        ]);

        // Remove existing assignments
        $case->assignments()->where('assignment_type', 'hydrology_expert')->delete();

        // Add new assignments
        foreach ($validated['expert_ids'] ?? [] as $expertId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $expertId,
                'assignment_type' => 'hydrology_expert',
                'assigned_by' => Auth::id()
            ]);
        }

        $message = empty($validated['expert_ids'])
            ? 'Hydrology expert assignments removed successfully.'
            : 'Hydrology experts assigned successfully.';

        return redirect()->route('cases.show', $case)->with('success', $message);
    }

    public function assignAluClerkForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $clerks = \App\Models\User::whereAnyCurrentRole(['alu_clerk', 'alu_paralegal'])->orderBy('name')->get();
        return view('cases.assign-alu-clerk', compact('case', 'clerks'));
    }

    public function assignAluClerk(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $validated = $request->validate([
            'clerk_ids' => 'required|array|min:1',
            'clerk_ids.*' => 'exists:users,id'
        ]);

        $supportUsers = User::whereIn('id', $validated['clerk_ids'])->get()->keyBy('id');

        if ($supportUsers->count() !== count(array_unique($validated['clerk_ids']))) {
            return back()->withErrors(['clerk_ids' => 'One or more selected ALU support users could not be found.']);
        }

        foreach ($supportUsers as $supportUser) {
            if (!$supportUser->aluSupportAssignmentType()) {
                return back()->withErrors(['clerk_ids' => 'Only ALU clerks and paralegals may be assigned as ALU support staff.']);
            }
        }

        $case->assignments()->whereIn('assignment_type', ['alu_clerk', 'alu_paralegal'])->delete();

        foreach ($validated['clerk_ids'] as $clerkId) {
            $supportUser = $supportUsers->get((int) $clerkId) ?? $supportUsers->get((string) $clerkId);

            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $clerkId,
                'assignment_type' => $supportUser->aluSupportAssignmentType(),
                'assigned_by' => Auth::id()
            ]);
        }

        return redirect()->route('cases.show', $case)->with('success', 'ALU clerks/paralegals assigned successfully.');
    }

    public function assignWrdForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $wrds = \App\Models\User::whereCurrentRole('wrd')->orderBy('name')->get();
        return view('cases.assign-wrd', compact('case', 'wrds'));
    }

    public function assignWrd(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys() || !Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $validated = $request->validate([
            'wrd_ids' => 'nullable|array',
            'wrd_ids.*' => 'exists:users,id'
        ]);

        $case->assignments()->where('assignment_type', 'wrd')->delete();

        foreach ($validated['wrd_ids'] ?? [] as $wrdId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $wrdId,
                'assignment_type' => 'wrd',
                'assigned_by' => Auth::id()
            ]);
        }

        $message = empty($validated['wrd_ids'])
            ? 'WRD expert assignments removed successfully.'
            : 'WRD experts assigned successfully.';

        return redirect()->route('cases.show', $case)->with('success', $message);
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

    public function storeDocuments(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canUploadDocumentsToCase($case)) {
            if (in_array(Auth::user()->getCurrentRole(), ['alu_clerk', 'alu_paralegal'], true) && $case->status === 'active') {
                abort(403, 'You must be assigned as an ALU clerk or paralegal to file documents in this active case.');
            }

            if (in_array(Auth::user()->getCurrentRole(), ['alu_atty', 'contract_attorney'], true) && $case->status === 'active') {
                abort(403, 'You must be assigned as an attorney to file documents in this active case.');
            }

            if (in_array(Auth::user()->getCurrentRole(), ['party', 'external_attorney'], true) || Auth::user()->isAttorney() || Auth::user()->isALUAttorney() || Auth::user()->isParalegal()) {
                abort(403, 'You can only upload documents to active cases you are associated with.');
            }

            abort(403);
        }

        $otherDocumentFileRule = Auth::user()->isHearingUnit()
            ? 'required|file|mimes:pdf|max:204800'
            : 'required|file|mimes:pdf,doc,docx|max:204800';

        // Validate the form structure
        $request->validate([
            'documents.application.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.request_to_docket.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.request_pre_hearing.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:204800',
            'documents.other.*.type' => 'required|string',
            'documents.other.*.file.*' => $otherDocumentFileRule,
            'notification_message' => 'nullable|string|max:5000',
        ], [
            'documents.other.*.file.*.mimes' => Auth::user()->isHearingUnit()
                ? 'HU orders and notices must be uploaded as PDF files so the electronic stamp can be applied.'
                : 'Documents must be PDF, DOC, or DOCX files.',
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

                $existingDocumentIds = $case->documents()->pluck('id');
                $this->caseService->handleDocumentUploads($case, $request, Auth::user());
                $case->refresh(); // Refresh to get updated documents
                $newDocuments = $case->documents()
                    ->whereNotIn('id', $existingDocumentIds)
                    ->get();

                if (Auth::user()->isHearingUnit() && $newDocuments->isNotEmpty()) {
                    $stampingFailures = [];

                    foreach ($newDocuments as $document) {
                        try {
                            app(\App\Services\PdfStampingService::class)->stampDocument($document, $case);
                            $document->update([
                                'approved' => false,
                                'approved_by_user_id' => null,
                                'approved_at' => null,
                                'rejected_reason' => null,
                            ]);
                        } catch (\Throwable $e) {
                            \Log::warning('HU document uploaded but automatic PDF stamping failed', [
                                'case_id' => $case->id,
                                'document_id' => $document->id,
                                'error' => $e->getMessage(),
                            ]);

                            $document->update([
                                'approved' => false,
                                'approved_by_user_id' => null,
                                'approved_at' => null,
                                'rejected_reason' => null,
                                'stamped' => false,
                                'stamp_text' => null,
                                'stamped_at' => null,
                            ]);

                            $stampingFailures[] = ($document->custom_title ?: $document->original_filename) . ': ' . $e->getMessage();
                        }

                        if (trim((string) $request->input('notification_message')) !== '') {
                            session()->put($this->huIssueMessageSessionKey($document), trim((string) $request->input('notification_message')));
                        }
                    }

                    $newDocuments = $case->documents()
                        ->whereIn('id', $newDocuments->pluck('id'))
                        ->get();

                    if (!empty($stampingFailures)) {
                        return redirect()->route('cases.documents.manage', $case)
                            ->withErrors(['error' => $this->huStampingFailureMessage($stampingFailures)]);
                    }
                }

                if (!Auth::user()->isHearingUnit()) {
                    $this->notifyDocumentUploadRecipients(
                        $case,
                        $newDocuments,
                        Auth::user(),
                        $request->input('notification_message')
                    );
                }

                \Log::info('Document upload completed. Case now has ' . $case->documents->count() . ' documents');
            } else {
                \Log::warning('No files found in upload request');
                return back()->withErrors(['error' => 'No files were selected for upload.']);
            }

            $successMessage = Auth::user()->isHearingUnit()
                ? 'Stamped preview generated. Review the PDF, then click Issue & Notify to send it to the service list.'
                : 'Documents uploaded successfully.';

            return redirect()->route(Auth::user()->isHearingUnit() ? 'cases.documents.manage' : 'cases.show', $case)
                ->with('success', $successMessage);

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
        $attorneys = Person::counselDirectory()
            ->get()
            ->reject(fn($attorney) => $selectedEmails->contains(strtolower(trim((string) $attorney->email))))
            ->values();

        return view('cases.attorney-management', compact('case', 'party', 'attorneys'))->render();
    }

    public function assignPartyAttorney(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canManageCaseParties($case)) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);

        $validated = $request->validate([
            'attorney_option' => 'nullable|in:existing,new',
            'attorney_id' => 'nullable|exists:persons,id',
            'attorney_name' => 'nullable|string|max:255',
            'attorney_prefix' => 'nullable|string|max:10',
            'attorney_first_name' => 'nullable|string|max:255',
            'attorney_middle_name' => 'nullable|string|max:255',
            'attorney_last_name' => 'nullable|string|max:255',
            'attorney_suffix' => 'nullable|string|max:10',
            'attorney_title' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
        ]);

        try {
            $attorneyPerson = $this->resolveCounselPerson($validated);

            if (!$attorneyPerson) {
                return response()->json(['success' => false, 'error' => 'Select an existing attorney or enter a new one.']);
            }

            if (CaseParty::wrdRepresentativeAssignmentForEmail($case, $attorneyPerson->email)) {
                return response()->json([
                    'success' => false,
                    'error' => "{$attorneyPerson->full_name} is already assigned to represent WRD in this case and cannot also represent a private party."
                ]);
            }

            \App\Models\CaseParty::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $attorneyPerson->id,
                'role' => 'counsel',
                'client_party_id' => $party->id,
            ], [
                'service_enabled' => true,
                'representation_capacity' => CaseParty::CAPACITY_PRIVATE_COUNSEL,
            ]);

            \App\Models\ServiceList::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $attorneyPerson->id,
            ], [
                'email' => $attorneyPerson->email,
                'service_method' => 'email',
                'is_primary' => false
            ]);

            $party->removeFromServiceListWhileRepresented();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function removeAttorney(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canManageCaseParties($case)) {
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

        $party->restoreServiceListIfUnrepresented();

        return response()->json(['success' => true]);
    }

    private function resolveCounselPerson(array $data, string $addressPrefix = ''): ?Person
    {
        if (!empty($data['attorney_id'])) {
            return Person::find($data['attorney_id']);
        }

        if (!$this->hasNewCounselData($data)) {
            return null;
        }

        $name = $this->counselNameAttributes($data);
        $address = fn (string $field) => $data[$addressPrefix . $field] ?? null;

        $person = Person::firstOrCreate(
            ['email' => $data['attorney_email']],
            [
                'type' => 'individual',
                'prefix' => $data['attorney_prefix'] ?? null,
                'first_name' => $name['first_name'],
                'middle_name' => $data['attorney_middle_name'] ?? null,
                'last_name' => $name['last_name'],
                'suffix' => $data['attorney_suffix'] ?? null,
                'title' => $data['attorney_title'] ?? null,
                'phone_office' => $data['attorney_phone'] ?? null,
                'address_line1' => $address('address_line1'),
                'address_line2' => $address('address_line2'),
                'city' => $address('city'),
                'state' => $address('state'),
                'zip' => $address('zip'),
            ]
        );

        $updates = array_filter([
            'prefix' => $person->prefix ?: ($data['attorney_prefix'] ?? null),
            'first_name' => $person->first_name ?: $name['first_name'],
            'middle_name' => $person->middle_name ?: ($data['attorney_middle_name'] ?? null),
            'last_name' => $person->last_name ?: $name['last_name'],
            'suffix' => $person->suffix ?: ($data['attorney_suffix'] ?? null),
            'title' => $person->title ?: ($data['attorney_title'] ?? null),
            'phone_office' => $person->phone_office ?: ($data['attorney_phone'] ?? null),
            'address_line1' => $person->address_line1 ?: $address('address_line1'),
            'address_line2' => $person->address_line2 ?: $address('address_line2'),
            'city' => $person->city ?: $address('city'),
            'state' => $person->state ?: $address('state'),
            'zip' => $person->zip ?: $address('zip'),
        ], fn ($value) => filled($value));

        if (!empty($updates)) {
            $person->update($updates);
        }

        return $person;
    }

    private function hasNewCounselData(array $data): bool
    {
        $hasStructuredName = !empty($data['attorney_first_name']) && !empty($data['attorney_last_name']);
        $hasLegacyName = !empty($data['attorney_name']);

        return ($hasStructuredName || $hasLegacyName) && !empty($data['attorney_email']);
    }

    private function counselNameAttributes(array $data): array
    {
        if (!empty($data['attorney_first_name']) || !empty($data['attorney_last_name'])) {
            return [
                'first_name' => $data['attorney_first_name'] ?? null,
                'last_name' => $data['attorney_last_name'] ?? null,
            ];
        }

        return Person::splitDisplayName($data['attorney_name'] ?? null);
    }

    public function manageParties(CaseModel $case)
    {
        if ((!auth()->user()->canWriteCase() && !auth()->user()->isHearingUnit()) || !auth()->user()->canAccessCase($case)) {
            abort(403);
        }

        $case->load(['parties.person', 'serviceList.person']);
        $attorneys = Person::counselDirectory()->get();
        $resolvedServiceList = $this->serviceListResolver->resolve($case);

        return view('cases.parties.manage', compact('case', 'attorneys', 'resolvedServiceList'));
    }

    public function storeParty(Request $request, CaseModel $case)
    {
        if (!auth()->user()->canManageCaseParties($case)) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,intervenor,aggrieved_party,respondent',
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
            'attorney_id' => 'nullable|exists:persons,id',
            'attorney_name' => 'nullable|string|max:255',
            'attorney_prefix' => 'nullable|string|max:10',
            'attorney_first_name' => 'nullable|string|max:255',
            'attorney_middle_name' => 'nullable|string|max:255',
            'attorney_last_name' => 'nullable|string|max:255',
            'attorney_suffix' => 'nullable|string|max:10',
            'attorney_title' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
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

            if ($person && $case->parties()->where('person_id', $person->id)->exists()) {
                return back()->withInput()->withErrors([
                    'email' => "{$person->full_name} is already associated with this case.",
                ]);
            }

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
            if ($this->hasNewCounselData($validated) ||
                ($request->has('attorney_id') && !empty($request->attorney_id))) {
                $attorneyPerson = $this->resolveCounselPerson($validated, 'attorney_');

                if ($attorneyPerson) {
                    if (CaseParty::wrdRepresentativeAssignmentForEmail($case, $attorneyPerson->email)) {
                        return back()->withInput()->withErrors([
                            'attorney_id' => "{$attorneyPerson->full_name} is already assigned to represent WRD in this case and cannot also represent a private party."
                        ]);
                    }

                    \App\Models\CaseParty::firstOrCreate([
                        'case_id' => $case->id,
                        'person_id' => $attorneyPerson->id,
                        'role' => 'counsel',
                        'client_party_id' => $clientParty->id,
                    ], [
                        'service_enabled' => true,
                        'representation_capacity' => CaseParty::CAPACITY_PRIVATE_COUNSEL,
                    ]);

                    \App\Models\ServiceList::firstOrCreate([
                        'case_id' => $case->id,
                        'person_id' => $attorneyPerson->id,
                    ], [
                        'email' => $attorneyPerson->email,
                        'service_method' => 'email',
                        'is_primary' => false,
                    ]);

                    $clientParty->removeFromServiceListWhileRepresented();
                }
            }

            $clientParty->restoreServiceListIfUnrepresented();

            return redirect()->route('cases.parties.manage', $case)->with('success', 'Party added successfully.');

        } catch (\Exception $e) {
            \Log::error('Failed to add party from manage parties page', [
                'case_id' => $case->id,
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to add party: ' . $e->getMessage()]);
        }
    }

    public function editParty(CaseModel $case, $partyId)
    {
        if (!auth()->user()->canManageCaseParties($case)) {
            abort(403);
        }

        $party = $case->parties()->with(['person', 'attorneys.person'])->findOrFail($partyId);
        $attorneys = Person::counselDirectory()->get();

        return view('cases.parties.edit', compact('case', 'party', 'attorneys'))->render();
    }

    public function updateParty(Request $request, CaseModel $case, $partyId)
    {
        if (!auth()->user()->canManageCaseParties($case)) {
            abort(403);
        }

        $party = $case->parties()->findOrFail($partyId);

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,intervenor,aggrieved_party,respondent',
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
            'attorney_prefix' => 'nullable|string|max:10',
            'attorney_first_name' => 'nullable|string|max:255',
            'attorney_middle_name' => 'nullable|string|max:255',
            'attorney_last_name' => 'nullable|string|max:255',
            'attorney_suffix' => 'nullable|string|max:10',
            'attorney_title' => 'nullable|string|max:255',
            'attorney_email' => 'nullable|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
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
        if (!auth()->user()->canManageCaseParties($case)) {
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
        if (!Auth::user()->canAccessCase($case)) {
            abort(403);
        }

        $case->load([
            'documents.uploader',
            'documents.correctionCycles.requestedBy',
            'documents.correctionCycles.resubmittedBy',
            'documents.correctionCycles.acceptedBy',
            'documents.correctionCycles.replacementDocument',
            'documents.correctionCycles.items.resolvedBy',
        ]);
        $documentTypes = \App\Models\DocumentType::forRoles(Auth::user()->documentFilingRoles($case))->dropdownOrder()->get();
        $resolvedServiceList = $this->serviceListResolver->resolve($case);

        return view('cases.documents.manage', compact('case', 'documentTypes', 'resolvedServiceList'));
    }

    public function storeDocument(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canUploadDocumentsToCase($case)) {
            if (in_array(Auth::user()->getCurrentRole(), ['alu_clerk', 'alu_paralegal'], true) && $case->status === 'active') {
                abort(403, 'You must be assigned as an ALU clerk or paralegal to file documents in this active case.');
            }

            if (in_array(Auth::user()->getCurrentRole(), ['alu_atty', 'contract_attorney'], true) && $case->status === 'active') {
                abort(403, 'You must be assigned as an attorney to file documents in this active case.');
            }

            if (in_array(Auth::user()->getCurrentRole(), ['party', 'external_attorney'], true) || Auth::user()->isAttorney() || Auth::user()->isALUAttorney() || Auth::user()->isParalegal()) {
                abort(403, 'You can only upload documents to active cases you are associated with.');
            }

            abort(403);
        }

        $validDocTypes = \App\Models\DocumentType::forRoles(Auth::user()->documentFilingRoles($case))
            ->pluck('code')
            ->push('other')
            ->unique()
            ->toArray();

        if (blank($request->input('doc_type'))) {
            $request->merge(['doc_type' => 'other']);
        }

        $documentFileRule = Auth::user()->isHearingUnit()
            ? 'required|file|mimes:pdf|max:204800'
            : 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:204800';

        $validated = $request->validate([
            'doc_type' => 'nullable|in:' . implode(',', $validDocTypes),
            'custom_title' => 'required|string|max:255',
            'pleading_type' => 'nullable|in:none,request_to_docket,request_pre_hearing',
            'document' => 'required|array|min:1',
            'document.*' => $documentFileRule,
            'notification_message' => 'nullable|string|max:5000',
            'time_sensitive_notice' => 'nullable|boolean',
        ], [
            'document.required' => 'Select at least one document to upload.',
            'document.*.mimes' => Auth::user()->isHearingUnit()
                ? 'HU orders and notices must be uploaded as PDF files so the electronic stamp can be applied.'
                : 'Documents must be PDF, DOC, DOCX, JPG, JPEG, or PNG files.',
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
            $uploadedDocuments = collect();
            $stampingFailures = [];
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

                    if (Auth::user()->isHearingUnit()) {
                        $documentData['approved'] = false;
                        $documentData['approved_by_user_id'] = null;
                        $documentData['approved_at'] = null;
                        $documentData['rejected_reason'] = null;
                    }

                    if ($documentType && $documentType->is_pleading && isset($validated['pleading_type']) && $validated['pleading_type'] !== 'none') {
                        $documentData['pleading_type'] = $validated['pleading_type'];
                    } else {
                        $documentData['pleading_type'] = 'none';
                    }

                    $document = \App\Models\Document::create($documentData);

                    if (Auth::user()->isHearingUnit()) {
                        try {
                            app(\App\Services\PdfStampingService::class)->stampDocument($document, $case);
                            $document->refresh();
                        } catch (\Throwable $e) {
                            \Log::warning('HU document uploaded but automatic PDF stamping failed', [
                                'case_id' => $case->id,
                                'document_id' => $document->id,
                                'error' => $e->getMessage(),
                            ]);

                            $document->update([
                                'approved' => false,
                                'approved_by_user_id' => null,
                                'approved_at' => null,
                                'rejected_reason' => null,
                                'stamped' => false,
                                'stamp_text' => null,
                                'stamped_at' => null,
                            ]);

                            $stampingFailures[] = ($document->custom_title ?: $document->original_filename) . ': ' . $e->getMessage();
                        }

                        if (trim((string) ($validated['notification_message'] ?? '')) !== '') {
                            session()->put($this->huIssueMessageSessionKey($document), trim((string) $validated['notification_message']));
                        }
                    }

                    $uploadedDocuments->push($document);
                    $uploadedCount++;
                }
            }

            $timeSensitiveNoticeCount = 0;

            if (!Auth::user()->isHearingUnit()) {
                $this->notifyDocumentUploadRecipients(
                    $case,
                    $uploadedDocuments,
                    Auth::user(),
                    $validated['notification_message'] ?? null
                );

                if ($request->boolean('time_sensitive_notice')) {
                    $timeSensitiveNoticeCount = $this->notifyTimeSensitiveFilingRecipients(
                        $case,
                        $uploadedDocuments,
                        Auth::user()
                    );
                }
            }

            $message = Auth::user()->isHearingUnit()
                ? ($uploadedCount === 1
                    ? 'Stamped preview generated. Review the PDF, then click Issue & Notify to send it to the service list.'
                    : "{$uploadedCount} stamped previews generated. Review each PDF, then click Issue & Notify to send service-list notifications.")
                : ($uploadedCount === 1
                    ? 'Document uploaded successfully and is pending HU acceptance.'
                    : "{$uploadedCount} documents uploaded successfully and are pending HU acceptance.");

            if ($timeSensitiveNoticeCount > 0) {
                $message .= " {$timeSensitiveNoticeCount} recipient(s) notified of the time-sensitive filing.";
            } elseif (!Auth::user()->isHearingUnit() && $request->boolean('time_sensitive_notice')) {
                $message .= ' No service-list recipients were available for the time-sensitive notice.';
            }

            if (Auth::user()->isHearingUnit() && !empty($stampingFailures)) {
                return redirect()->route('cases.documents.manage', $case)
                    ->withErrors(['error' => $this->huStampingFailureMessage($stampingFailures)]);
            }

            return redirect()->route('cases.documents.manage', $case)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }

    private function notifyDocumentUploadRecipients(CaseModel $case, \Illuminate\Support\Collection $documents, User $uploader, ?string $customMessage = null): void
    {
        if ($documents->isEmpty()) {
            return;
        }

        $notificationService = app(\App\Services\NotificationService::class);
        $documentList = $documents
            ->map(fn (Document $document) => '- ' . ($document->custom_title ?: $document->doc_type_label))
            ->implode("\n");

        if ($uploader->isHearingUnit()) {
            $message = "The Hearing Unit has issued or filed document(s) in case {$case->case_no}.\n\nDocuments:\n{$documentList}";

            if (trim((string) $customMessage) !== '') {
                $message .= "\n\nAdditional message from the Hearing Unit:\n" . trim((string) $customMessage);
            }

            $message .= "\n\nView case: " . route('cases.show', $case);

            $notificationService->notifyEmailAddresses(
                $this->serviceNotificationEmails($case),
                'issuance',
                "Case {$case->case_no}: Order or Notice Issued",
                $message,
                $case
            );

            return;
        }

        $huUsers = User::whereAnyCurrentRole(['hu_admin', 'hu_clerk'])
            ->where('is_active', true)
            ->where('id', '!=', $uploader->id)
            ->get()
            ->all();

        $message = "{$uploader->name} has filed document(s) in case {$case->case_no}.\n\nDocuments:\n{$documentList}\n\nReview case documents: " . route('cases.documents.manage', $case);
        $title = "Case {$case->case_no}: New Filing Uploaded";

        if (!empty($huUsers)) {
            $notificationService->notifyMultiple(
                $huUsers,
                'new_filing',
                $title,
                $message,
                $case
            );
        }

        $huContactEmail = strtolower(trim((string) config('edocket.contact.hu_email')));
        $huUserEmails = collect($huUsers)
            ->map(fn (User $user) => strtolower(trim((string) $user->email)))
            ->filter()
            ->all();

        if ($huContactEmail !== '' && !in_array($huContactEmail, $huUserEmails, true)) {
            $notificationService->notifyEmailAddress(
                $huContactEmail,
                'new_filing',
                $title,
                $message,
                $case,
                false
            );
        }
    }

    private function notifyTimeSensitiveFilingRecipients(CaseModel $case, \Illuminate\Support\Collection $documents, User $uploader): int
    {
        if ($documents->isEmpty()) {
            return 0;
        }

        $notificationService = app(\App\Services\NotificationService::class);

        $message = "A time-sensitive pleading has been submitted for filing that has not yet been accepted for filing. Click on the link below to view the submission.\n\n"
            . "View case: " . route('cases.show', $case);

        return $notificationService->notifyEmailAddresses(
            $this->partyAndServiceNotificationEmails($case),
            'time_sensitive_filing',
            "Hearing Unit Number {$case->case_no}: Time-Sensitive Filing Submitted",
            $message,
            $case
        );
    }

    private function serviceNotificationEmails(CaseModel $case): array
    {
        return $this->serviceListResolver->emails($case);
    }

    private function partyAndServiceNotificationEmails(CaseModel $case): array
    {
        $case->loadMissing(['parties.person']);
        $emails = [];

        foreach ($this->serviceNotificationEmails($case) as $email) {
            $this->addNotificationEmail($emails, $email);
        }

        foreach ($case->parties->reject(fn ($party) => $party->isWrdAgencyParty()) as $party) {
            $this->addNotificationEmail($emails, $party->person?->email);
        }

        return array_values($emails);
    }

    private function addNotificationEmail(array &$emails, ?string $email): void
    {
        $normalizedEmail = strtolower(trim((string) $email));

        if ($normalizedEmail !== '') {
            $emails[$normalizedEmail] = $normalizedEmail;
        }
    }

    private function isHuIssuedDocument(Document $document): bool
    {
        $document->loadMissing('uploader.roleRelation');

        return (bool) $document->approved && (bool) $document->uploader?->isHearingUnit();
    }

    private function isPendingHuIssue(Document $document): bool
    {
        $document->loadMissing('uploader.roleRelation');

        return !$document->approved
            && !$document->rejected_reason
            && (bool) $document->stamped
            && (bool) $document->uploader?->isHearingUnit();
    }

    private function isPendingHuUpload(Document $document): bool
    {
        $document->loadMissing('uploader.roleRelation');

        return !$document->approved
            && !$document->rejected_reason
            && !$document->stamped
            && (bool) $document->uploader?->isHearingUnit();
    }

    private function huStampingFailureMessage(array $failures): string
    {
        $prefix = count($failures) === 1
            ? 'The file was saved, but automatic electronic stamping failed: '
            : 'The files were saved, but automatic electronic stamping failed for: ';

        return $prefix
            . implode('; ', $failures)
            . ' No notification was sent. Re-save or print the PDF to a new PDF, then upload the corrected file or try Retry Stamp from the document management page.';
    }

    private function huIssueMessageSessionKey(Document $document): string
    {
        return "hu_issue_message_{$document->id}";
    }

    public function issueStampedDocument(Request $request, CaseModel $case, $documentId)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'notification_message' => 'nullable|string|max:5000',
        ]);

        $document = $case->documents()->with('uploader.roleRelation')->findOrFail($documentId);

        if (!$this->isPendingHuIssue($document)) {
            return back()->withErrors(['error' => 'Only pending HU stamped previews can be issued.']);
        }

        $document->update([
            'approved' => true,
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
            'rejected_reason' => null,
        ]);

        $customMessage = trim((string) ($validated['notification_message'] ?? ''));
        if ($customMessage === '') {
            $customMessage = session()->pull($this->huIssueMessageSessionKey($document), '');
        } else {
            session()->forget($this->huIssueMessageSessionKey($document));
        }

        $this->notifyDocumentUploadRecipients(
            $case,
            collect([$document->refresh()]),
            auth()->user(),
            $customMessage
        );

        return redirect()->route('cases.documents.manage', $case)
            ->with('success', 'Stamped document issued and service-list notifications sent.');
    }

    public function approveDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);

        if ($this->isHuIssuedDocument($document)) {
            return response()->json(['success' => false, 'error' => 'HU-issued documents are already issued and do not require acceptance.']);
        }

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

        $notifiedCount = $this->notifyDocumentAcceptanceRecipients($case, $document->refresh());

        return response()->json([
            'success' => true,
            'message' => $notifiedCount > 0
                ? "Document accepted successfully. {$notifiedCount} recipient(s) notified."
                : 'Document accepted successfully. No service-list recipients were available to notify.',
        ]);
    }

    private function notifyDocumentAcceptanceRecipients(CaseModel $case, Document $document): int
    {
        $notificationService = app(\App\Services\NotificationService::class);
        $document->loadMissing('documentType');
        $isPleadingDocument = (bool) $document->documentType?->is_pleading || $document->pleading_type !== 'none';

        $message = $isPleadingDocument
            ? "Please click on the link below to view a recently docketed matter that you are a party to or have an interest in.\n\n"
                . "View case: " . route('cases.show', $case)
            : "Please click on the link below to view the most recent document that has been accepted for filing in Hearing Unit Number {$case->case_no}.\n\n"
                . "View case: " . route('cases.show', $case);

        return $notificationService->notifyEmailAddresses(
            $this->documentAcceptanceNotificationEmails($case),
            'document_accepted',
            "Case {$case->case_no}: Document Accepted",
            $message,
            $case
        );
    }

    private function documentAcceptanceNotificationEmails(CaseModel $case): array
    {
        return $this->partyAndServiceNotificationEmails($case);
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
        if ($this->isHuIssuedDocument($document)) {
            return response()->json(['success' => false, 'error' => 'HU-issued documents cannot be rejected.'], 422);
        }

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
                "Your pleading has been not been accepted for filing. Please click on the link below to better understand why the pleading was rejected.\n\nView case: " . route('cases.show', $case),
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
        if ($this->isHuIssuedDocument($document)) {
            return response()->json(['success' => false, 'error' => 'HU-issued documents do not use the correction workflow.'], 422);
        }

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
                "The Hearing Unit has requested that you \"fix\" the pleading or document that you submitted for filing. Please click on the link below to better understand the corrections being requested.\n\nView case: " . route('cases.show', $case),
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

        if ($this->isHuIssuedDocument($document)) {
            return response()->json(['success' => false, 'error' => 'HU-issued documents do not need to be stamped.']);
        }

        $isPendingHuUpload = $this->isPendingHuUpload($document);
        $isAcceptedPleading = in_array($document->pleading_type, ['request_to_docket', 'request_pre_hearing']);
        $canStamp = $isPendingHuUpload || ($document->approved && ($case->status === 'active' || $isAcceptedPleading));

        // In active cases, any accepted document can be stamped.
        // Before a case is active, stamping remains limited to accepted pleading documents.
        if (!$canStamp) {
            return response()->json(['success' => false, 'error' => 'Only pending HU uploads, accepted documents in active cases, or accepted pleading documents, can be stamped']);
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

            if ($isPendingHuUpload) {
                $document->update([
                    'approved' => false,
                    'approved_by_user_id' => null,
                    'approved_at' => null,
                    'rejected_reason' => null,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Document stamping failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function replacePendingHuDocument(Request $request, CaseModel $case, $documentId)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $document = $case->documents()->with('uploader.roleRelation')->findOrFail($documentId);

        if (!$this->isPendingHuUpload($document)) {
            return back()->withErrors(['error' => 'Only HU uploads that still need stamping can be replaced here.']);
        }

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf|max:204800',
        ], [
            'document.required' => 'Choose a corrected PDF to upload.',
            'document.mimes' => 'HU orders and notices must be uploaded as PDF files so the electronic stamp can be applied.',
        ]);

        $file = $validated['document'];
        $oldStorageUri = $document->storage_uri;
        $storageFolder = $this->caseStorageService->getCaseStorageFolder($case);
        $documentType = \App\Models\DocumentType::where('code', $document->doc_type)->first();
        $displayType = $documentType ? $documentType->name : ucfirst(str_replace('_', ' ', $document->doc_type));
        $titleOrType = $document->custom_title ?: $displayType;
        $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . '.pdf';
        $storedFilename = $this->generateReadableStoredFilename($originalFilename, $storageFolder);
        $path = $file->storeAs($storageFolder, $storedFilename, 'public');

        $document->update([
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => md5_file($file->getRealPath()),
            'storage_uri' => $path,
            'uploaded_by_user_id' => auth()->id(),
            'uploaded_at' => now(),
            'approved' => false,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_reason' => null,
            'stamped' => false,
            'stamp_text' => null,
            'stamped_at' => null,
        ]);

        if ($oldStorageUri && $oldStorageUri !== $path && Storage::disk('public')->exists($oldStorageUri)) {
            Storage::disk('public')->delete($oldStorageUri);
        }

        try {
            app(\App\Services\PdfStampingService::class)->stampDocument($document->refresh(), $case);
        } catch (\Throwable $e) {
            \Log::warning('Replacement HU PDF uploaded but automatic PDF stamping failed', [
                'case_id' => $case->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('cases.documents.manage', $case)
                ->withErrors(['error' => $this->huStampingFailureMessage([
                    ($document->custom_title ?: $document->original_filename) . ': ' . $e->getMessage(),
                ])]);
        }

        return redirect()->route('cases.documents.manage', $case)
            ->with('success', 'Corrected PDF uploaded and stamped. Review the preview, then click Issue & Notify to send it to the service list.');
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
        if (!((auth()->user()->canManageDraftCase($case)) ||
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

    public function updateHuDisplayStatus(Request $request, CaseModel $case)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'hu_display_status' => ['nullable', Rule::in(array_keys(CaseModel::huDisplayStatuses()))],
            'hu_display_status_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->caseService->updateHuDisplayStatus(
            $case,
            auth()->user(),
            $validated['hu_display_status'] ?? null,
            $validated['hu_display_status_note'] ?? null
        )) {
            return back()->with('success', 'HU status updated successfully.');
        }

        return back()->with('error', 'Unable to update HU status.');
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

    private function validateSubmissionRequirements(CaseModel $case, User $user): array
    {

        $errors = [];

        // Check if ALU Attorney is assigned
        if ((!$case->aluAttorneys || $case->aluAttorneys->count() === 0) && !$user->isALUManagingAtty()) {
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

        $outsideCounselParty = $case->parties()->where('role', 'counsel')->whereHas('person', function($q) use ($user) {
            $q->where('email', $user->email);
        })->first();
        $isOutsideCounsel = (bool) $outsideCounselParty;
        $isAssignedAluAttorney = ($user->isALUAttorney() || $user->isContractAttorney()) && $case->assignments()
            ->where('assignment_type', 'alu_atty')
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOutsideCounsel && !$isAssignedAluAttorney) {
            abort(403, 'Only attorneys can add paralegals.');
        }

        if (!$isOutsideCounsel && !$isAssignedAluAttorney) {
            abort(403, 'You can only add paralegals to cases you are representing.');
        }

        $validated = $request->validate([
            'existing_person_id' => 'nullable|exists:persons,id',
            'existing_user_id' => 'nullable|exists:users,id',
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

        $selectedAluParalegal = null;

        if ($isAssignedAluAttorney) {
            if (empty($validated['existing_user_id'])) {
                return back()->withErrors(['existing_user_id' => 'Select an ALU paralegal.'])->withInput();
            }

            $selectedAluParalegal = User::whereKey($validated['existing_user_id'])
                ->whereCurrentRole('alu_paralegal')
                ->where('is_active', true)
                ->first();

            if (!$selectedAluParalegal) {
                return back()->withErrors(['existing_user_id' => 'The selected user is not an active ALU paralegal.'])->withInput();
            }
        }

        $usingExistingParalegal = !empty($validated['existing_person_id']);

        if ($selectedAluParalegal) {
            $person = null;
            $paralegalUser = $selectedAluParalegal;
        } elseif ($usingExistingParalegal) {
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

        if (!$selectedAluParalegal) {
            // Ensure an external paralegal has a login-capable user account.
            $paralegalUser = User::firstOrCreate(
                ['email' => $person->email],
                [
                    'name' => $person->full_name,
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
                    'name' => $person->full_name,
                ]);
            }
        }

        if ($isOutsideCounsel) {
            $exists = $case->parties()->where('person_id', $person->id)->exists();
            if ($exists) {
                return back()->withErrors(['error' => 'This person is already associated with this case.']);
            }

            \App\Models\CaseParty::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'role' => 'paralegal',
                'client_party_id' => $outsideCounselParty->client_party_id,
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

        if (!$selectedAluParalegal) {
            Password::sendResetLink(['email' => $paralegalUser->email]);
        }

        app(\App\Services\NotificationService::class)->notify(
            $selectedAluParalegal ?: $person,
            'paralegal_added',
            'Paralegal Access Added',
            $selectedAluParalegal
                ? "You have been assigned as an ALU paralegal on case {$case->case_no}. You can now access the case, receive case notifications, and file documents."
                : "You have been added as a paralegal on case {$case->case_no}. You can now access the case, receive case notifications, and file documents for the represented party. If you have not signed in before, use Forgot Password with this email address to set your password.",
            $case
        );

        return back()->with('success', 'Paralegal added successfully.');
    }

    public function removeParalegal(CaseModel $case, $partyId)
    {
        $user = Auth::user();

        $isAssignedAluAttorney = ($user->isALUAttorney() || $user->isContractAttorney()) && $case->assignments()
            ->where('assignment_type', 'alu_atty')
            ->where('user_id', $user->id)
            ->exists();

        $isOutsideCounsel = $case->parties()->where('role', 'counsel')->whereHas('person', function($q) use ($user) {
            $q->where('email', $user->email);
        })->exists();

        if (!$isOutsideCounsel && !$isAssignedAluAttorney) {
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
        if (!Auth::user()->canManageDraftCase($case) || $case->status !== 'draft') {
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
