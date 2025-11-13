<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseAssignment;
use App\Models\OseFileNumber;
use App\Services\CaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaseController extends Controller
{
    public function __construct(private CaseService $caseService) {}

    public function index()
    {
        $user = Auth::user();

        if ($user->isHearingUnit()) {
            // HU users see only submitted cases (not drafts)
            $cases = CaseModel::whereNotIn('status', ['draft'])->latest()->get();
        } elseif ($user->canAssignAttorneys()) {
            // ALU Manager sees all cases
            $cases = CaseModel::latest()->get();
        } elseif ($user->role === 'party') {
            // Party users (including attorneys) see all active and approved cases
            $cases = CaseModel::whereIn('status', ['active', 'approved'])->latest()->get();
        } else {
            // Other users see only their created cases
            $cases = $user->createdCases()->latest()->get();
        }

        return view('cases.index', compact('cases'));
    }

    public function create()
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $basinCodes = \App\Models\OseBasinCode::orderBy('initial')->get();
        $attorneys = \App\Models\Attorney::orderBy('name')->get();
        $documentTypes = \App\Models\DocumentType::forCaseCreation()->orderBy('sort_order')->get();
        return view('cases.create', compact('basinCodes', 'attorneys', 'documentTypes'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $validated = $request->validate([
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'parties' => 'required|array|min:1',
            'parties.*.role' => 'required|in:applicant,protestant,intervenor,respondent,violator,alleged_violator',
            'parties.*.type' => 'required|in:individual,company',
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
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code_from' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.basin_code_to' => 'nullable|string|exists:ose_basin_codes,initial',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'pleading_type' => 'required|in:request_pre_hearing,request_to_docket',
            'documents' => 'nullable|array',
            'documents.application' => 'required|array',
            'documents.application.*' => 'required|file|mimes:pdf|max:10240',
            'documents.notice_publication' => 'nullable|array',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.pleading' => 'nullable|array',
            'documents.pleading.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.protest_letter' => 'nullable|array',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.supporting' => 'nullable|array',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:10240',
            'assigned_attorneys' => 'nullable|array',
            'assigned_attorneys.*' => 'exists:users,id',
            'assigned_clerks' => 'nullable|array',
            'assigned_clerks.*' => 'exists:users,id',
            'optional_docs' => 'nullable|array',
            'optional_docs.*.type' => 'nullable|string',
            'optional_docs.*.files' => 'nullable|array',
            'optional_docs.*.files.*' => 'nullable|file|mimes:pdf|max:10240',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit'
        ]);

        try {
            $case = $this->caseService->createCase($validated, Auth::user(), $request);

            // Handle ALU attorney assignments
            if (isset($validated['assigned_attorneys']) && !empty($validated['assigned_attorneys'])) {
                foreach ($validated['assigned_attorneys'] as $attorneyId) {
                    CaseAssignment::create([
                        'case_id' => $case->id,
                        'user_id' => $attorneyId,
                        'assignment_type' => 'alu_attorney',
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
        if (Auth::user()->role === 'party' && $case->status === 'draft') {
            abort(403, 'Draft cases are not accessible to parties and attorneys.');
        }

        $case->load(['creator', 'assignee', 'assignedAttorney', 'assignedHydrologyExpert', 'assignedAluClerk', 'assignedWrd', 'aluAttorneys', 'hydrologyExperts', 'aluClerks', 'wrds', 'documents.uploader', 'parties.person', 'serviceList.person', 'oseFileNumbers', 'auditLogs.user']);
        return view('cases.show', compact('case'));
    }

    public function edit(CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() || !in_array($case->status, ['draft', 'rejected'])) {
            abort(403);
        }

        $case->load(['parties.person', 'serviceList.person', 'oseFileNumbers', 'documents']);
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
            'custom_message' => 'nullable|string|max:1000'
        ]);

        try {
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
                
                $case->update(['status' => 'submitted_to_hu']);
                
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

    public function huReview(CaseModel $case)
    {
        $case->load(['documents', 'parties.person']);
        return view('cases.hu-review', compact('case'));
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
            'reason' => 'required|string|max:500'
        ]);

        if ($this->caseService->rejectCase($case, Auth::user(), $validated['reason'])) {
            return back()->with('success', 'Case rejected.');
        }

        return back()->with('error', 'Unable to reject case.');
    }

    public function approve(CaseModel $case)
    {
        if (!in_array(Auth::user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403, 'Only HU Admin and HU Clerk can approve cases.');
        }

        if ($this->caseService->approveCase($case, Auth::user())) {
            return back()->with('success', 'Case approved and all parties notified.');
        }

        return back()->with('error', 'Unable to approve case.');
    }

    public function assignAttorneyForm(CaseModel $case)
    {
        if (!Auth::user()->canAssignAttorneys()) {
            abort(403);
        }

        $attorneys = \App\Models\User::where('role', 'alu_atty')->get();
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

        $case->assignments()->where('assignment_type', 'alu_attorney')->delete();
        
        foreach ($validated['attorney_ids'] as $attorneyId) {
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $attorneyId,
                'assignment_type' => 'alu_attorney',
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

        $experts = \App\Models\User::where('role', 'hydrology_expert')->get();
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

        $clerks = \App\Models\User::where('role', 'alu_clerk')->get();
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

        $wrds = \App\Models\User::where('role', 'wrd')->get();
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
        if (!Auth::user()->canUploadDocuments() && !Auth::user()->isHearingUnit() && !Auth::user()->canCreateCase()) {
            abort(403);
        }

        // Check if party user can access this case and if case allows document uploads
        if (Auth::user()->role === 'party') {
            if (!Auth::user()->canAccessCase($case)) {
                abort(403, 'You can only upload documents to cases you are associated with.');
            }
            if (!in_array($case->status, ['active', 'approved'])) {
                abort(403, 'You can only upload documents to active or approved cases.');
            }
        }

        $case->load(['documents.uploader']);
        
        if (Auth::user()->role === 'party' || Auth::user()->isAttorney()) {
            $documentTypes = \App\Models\DocumentType::where('is_active', true)
                ->where('category', 'party_upload')
                ->orderBy('sort_order')->get();
        } else {
            $documentTypes = \App\Models\DocumentType::where('is_active', true)->orderBy('sort_order')->get();
        }
        
        return view('cases.upload-documents', compact('case', 'documentTypes'));
    }

    public function storeDocuments(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canUploadDocuments() && !Auth::user()->isHearingUnit() && !Auth::user()->canCreateCase()) {
            abort(403);
        }

        // Check if party user can access this case and if case allows document uploads
        if (Auth::user()->role === 'party') {
            if (!Auth::user()->canAccessCase($case)) {
                abort(403, 'You can only upload documents to cases you are associated with.');
            }
            if (!in_array($case->status, ['active', 'approved'])) {
                abort(403, 'You can only upload documents to active or approved cases.');
            }
        }

        // Validate the form structure
        $request->validate([
            'documents.application.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.request_to_docket.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.request_for_pre_hearing.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.notice_publication.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.supporting.*' => 'nullable|file|mimes:pdf|max:10240',
            'documents.other.*.type' => 'required|string',
            'documents.other.*.file.*' => 'required|file|mimes:pdf,doc,docx|max:10240'
        ]);

        try {
            // Check if any files were uploaded
            $hasFiles = false;
            $documentTypes = ['application', 'notice_publication', 'request_to_docket', 'request_for_pre_hearing', 'protest_letter', 'supporting'];

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
        $party = $case->parties()->with(['person'])->findOrFail($partyId);
        $attorneys = \App\Models\Attorney::orderBy('name')->get();
        
        return view('cases.attorney-management', compact('case', 'party', 'attorneys'))->render();
    }

    public function assignPartyAttorney(Request $request, CaseModel $case, $partyId)
    {
        $party = $case->parties()->findOrFail($partyId);
        
        $validated = $request->validate([
            'attorney_name' => 'required|string|max:255',
            'attorney_email' => 'required|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
        ]);

        try {
            $attorney = \App\Models\Attorney::create([
                'name' => $validated['attorney_name'],
                'email' => $validated['attorney_email'],
                'phone' => $validated['attorney_phone'],
                'bar_number' => $validated['bar_number'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip' => $validated['zip']
            ]);
            
            // Attorney assignment logic would go here if needed

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function removeAttorney(CaseModel $case, $partyId)
    {
        $party = $case->parties()->findOrFail($partyId);
        
        if ($party->person->type === 'company') {
            return response()->json(['success' => false, 'error' => 'Companies must have attorney representation']);
        }
        
        // Self-representation logic would go here if needed

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
            'role' => 'required|in:applicant,protestant,intervenor,respondent,violator,alleged_violator',
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
        ]);

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
                        'bar_number' => $validated['bar_number']
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
            'role' => 'required|in:applicant,protestant,intervenor,respondent,violator,alleged_violator',
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
                    'bar_number' => $validated['bar_number']
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
        $case->load(['documents.uploader']);
        $documentTypes = \App\Models\DocumentType::where('is_active', true)->orderBy('sort_order')->get();
        return view('cases.documents.manage', compact('case', 'documentTypes'));
    }

    public function storeDocument(Request $request, CaseModel $case)
    {
        $validDocTypes = \App\Models\DocumentType::where('is_active', true)->pluck('code')->toArray();
        
        $validated = $request->validate([
            'doc_type' => 'required|in:' . implode(',', $validDocTypes),
            'pleading_type' => 'nullable|in:none,request_to_docket,request_pre_hearing',
            'document.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'description' => 'nullable|string|max:500'
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
                $oseString = $oseList ? ' - ' . implode(', ', $oseList) : '';
            }
            
            $uploadedCount = 0;
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('case_documents', $filename, 'public');
                    
                    $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';
                    if ($index > 0) {
                        $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . ' (' . ($index + 1) . ').pdf';
                    }

                    $documentData = [
                        'case_id' => $case->id,
                        'doc_type' => $validated['doc_type'],
                        'original_filename' => $originalFilename,
                        'stored_filename' => $filename,
                        'mime' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'checksum' => md5_file($file->getRealPath()),
                        'storage_uri' => $path,
                        'uploaded_by_user_id' => auth()->id(),
                        'uploaded_at' => now(),
                        'pleading_type' => $validated['pleading_type'] ?? 'none'
                    ];
                    
                    // Set pleading type for pleading documents
                    if ($documentType && $documentType->is_pleading && isset($validated['pleading_type']) && $validated['pleading_type'] !== 'none') {
                        $documentData['pleading_type'] = $validated['pleading_type'];
                    } else {
                        $documentData['pleading_type'] = 'none';
                    }
                    
                    \App\Models\Document::create($documentData);
                    $uploadedCount++;
                }
            }

            $message = $uploadedCount === 1 ? 'Document uploaded successfully.' : "{$uploadedCount} documents uploaded successfully.";
            return redirect()->route('cases.documents.manage', $case)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }

    public function approveDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);
        
        // Check case status
        if (!in_array($case->status, ['active', 'approved'])) {
            return response()->json(['success' => false, 'error' => 'Documents can only be approved in active or approved cases']);
        }
        
        // Use DocumentService to approve the document (which includes automatic stamping for pleading docs)
        $documentService = app(\App\Services\DocumentService::class);
        
        if ($documentService->approveDocument($document, auth()->user())) {
            return response()->json(['success' => true, 'message' => 'Document approved successfully']);
        }
        
        return response()->json(['success' => false, 'error' => 'Failed to approve document']);
    }

    public function rejectDocument(Request $request, CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $document = $case->documents()->findOrFail($documentId);
        $document->update([
            'approved' => false,
            'rejected_reason' => $validated['reason'],
            'approved_by_user_id' => null,
            'approved_at' => null
        ]);

        return response()->json(['success' => true]);
    }



    public function unapproveDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);
        $document->update([
            'approved' => false,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'stamped' => false,
            'stamp_text' => null
        ]);

        return response()->json(['success' => true]);
    }

    public function requestDocumentFix(Request $request, CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $document = $case->documents()->findOrFail($documentId);
        
        // Update document with fix request
        $document->update([
            'approved' => false,
            'rejected_reason' => 'Fix Required: ' . $validated['reason'],
            'approved_by_user_id' => null,
            'approved_at' => null
        ]);

        // TODO: Send notification to document uploader about fix request
        
        return response()->json(['success' => true]);
    }

    public function destroyDocument(CaseModel $case, $documentId)
    {
        $document = $case->documents()->findOrFail($documentId);
        
        // Allow ALU clerks to delete documents from draft/rejected cases
        if (!((auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected'])) || 
              auth()->user()->role === 'admin' || 
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
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if ($this->caseService->closeCase($case, auth()->user(), $validated['reason'])) {
            return back()->with('success', 'Case closed successfully.');
        }

        return back()->with('error', 'Unable to close case.');
    }

    public function archive(CaseModel $case)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'admin'])) {
            abort(403);
        }

        if ($this->caseService->archiveCase($case, auth()->user())) {
            return back()->with('success', 'Case archived successfully.');
        }

        return back()->with('error', 'Unable to archive case.');
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
            $hasComplianceParty = $case->parties()->whereIn('role', ['respondent', 'violator', 'alleged_violator'])->exists();
            if (!$hasComplianceParty) {
                $errors[] = 'At least one Respondent, Violator, or Alleged Violator must be added to compliance cases.';
            }
        } else {
            $hasApplicant = $case->parties()->where('role', 'applicant')->exists();
            if (!$hasApplicant) {
                $errors[] = 'At least one Applicant must be added to the case.';
            }
        }
        
        // Check if pleading document exists (Request to Docket OR Request for Pre-Hearing)
        $hasPleadingDoc = $case->documents()->whereIn('pleading_type', ['request_to_docket', 'request_for_pre_hearing'])->exists();
        if (!$hasPleadingDoc) {
            $errors[] = 'Either Request to Docket or Request for Pre-Hearing document must be uploaded.';
        }
        
        return $errors;
    }
}
