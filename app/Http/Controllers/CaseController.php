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
            'parties.*.role' => 'required|in:applicant,protestant,intervenor',
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
            'parties.*.representation' => 'required|in:self,attorney',
            'parties.*.attorney_id' => 'nullable|exists:attorneys,id',
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
            'assigned_attorney_id' => 'nullable|exists:users,id',
            'optional_docs' => 'nullable|array',
            'optional_docs.*.type' => 'nullable|string',
            'optional_docs.*.files' => 'nullable|array',
            'optional_docs.*.files.*' => 'nullable|file|mimes:pdf|max:10240',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit'
        ]);

        try {
            $case = $this->caseService->createCase($validated, Auth::user(), $request);

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
            'action' => 'required|in:draft,validate,submit'
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
                $case->update(['status' => 'submitted_to_hu']);
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
            'documents.other.*.type' => 'required|string',
            'documents.other.*.file' => 'required|file|mimes:pdf,doc,docx|max:10240'
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
        $party = $case->parties()->with(['attorney', 'person'])->findOrFail($partyId);
        $attorneys = \App\Models\Attorney::orderBy('name')->get();
        
        return view('cases.attorney-management', compact('case', 'party', 'attorneys'))->render();
    }

    public function assignPartyAttorney(Request $request, CaseModel $case, $partyId)
    {
        $party = $case->parties()->findOrFail($partyId);
        
        $validated = $request->validate([
            'attorney_id' => 'nullable|exists:attorneys,id',
            'attorney_name' => 'required_without:attorney_id|string|max:255',
            'attorney_email' => 'required_without:attorney_id|email|max:255',
            'attorney_phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
        ]);

        try {
            if ($validated['attorney_id']) {
                $party->update([
                    'attorney_id' => $validated['attorney_id'],
                    'representation' => 'attorney'
                ]);
            } else {
                $attorney = \App\Models\Attorney::create([
                    'name' => $validated['attorney_name'],
                    'email' => $validated['attorney_email'],
                    'phone' => $validated['attorney_phone'],
                    'bar_number' => $validated['bar_number']
                ]);
                
                $party->update([
                    'attorney_id' => $attorney->id,
                    'representation' => 'attorney'
                ]);
            }

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
        
        $party->update([
            'attorney_id' => null,
            'representation' => 'self'
        ]);

        return response()->json(['success' => true]);
    }

    public function manageParties(CaseModel $case)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $case->load(['parties.person', 'parties.attorney', 'serviceList.person']);
        $attorneys = \App\Models\Attorney::orderBy('name')->get();
        
        return view('cases.parties.manage', compact('case', 'attorneys'));
    }

    public function storeParty(Request $request, CaseModel $case)
    {
        if (!auth()->user()->canCreateCase() && !auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,intervenor',
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
            'representation' => 'required|in:self,attorney',
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

            // Handle attorney if needed
            $attorneyId = null;
            if ($validated['representation'] === 'attorney') {
                if (!empty($validated['attorney_id'])) {
                    $attorneyId = $validated['attorney_id'];
                } elseif (!empty($validated['attorney_name']) && !empty($validated['attorney_email'])) {
                    $attorney = \App\Models\Attorney::create([
                        'name' => $validated['attorney_name'],
                        'email' => $validated['attorney_email'],
                        'phone' => $validated['attorney_phone'],
                        'bar_number' => $validated['bar_number']
                    ]);
                    $attorneyId = $attorney->id;
                }
            }

            // Create case party with consolidated attorney relationship
            \App\Models\CaseParty::create([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'attorney_id' => $attorneyId,
                'role' => $validated['role'],
                'service_enabled' => true,
                'representation' => $validated['representation']
            ]);

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

        $party = $case->parties()->with(['person', 'attorney'])->findOrFail($partyId);
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
            'role' => 'required|in:applicant,protestant,intervenor',
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
            'representation' => 'required|in:self,attorney',
            'attorney_id' => 'nullable|exists:attorneys,id',
            'attorney_name' => 'required_if:representation,attorney|required_without:attorney_id|string|max:255',
            'attorney_email' => 'required_if:representation,attorney|required_without:attorney_id|email|max:255',
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
            $attorneyId = null;
            if ($validated['representation'] === 'attorney') {
                if ($validated['attorney_id']) {
                    $attorneyId = $validated['attorney_id'];
                } else {
                    $attorney = \App\Models\Attorney::create([
                        'name' => $validated['attorney_name'],
                        'email' => $validated['attorney_email'],
                        'phone' => $validated['attorney_phone'],
                        'bar_number' => $validated['bar_number']
                    ]);
                    $attorneyId = $attorney->id;
                }
            }

            // Update party
            $party->update([
                'role' => $validated['role'],
                'attorney_id' => $attorneyId,
                'representation' => $validated['representation']
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
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            \Log::info('Starting document upload', ['doc_type' => $validated['doc_type'], 'case_id' => $case->id]);
            
            // Load OSE file numbers
            $case->load('oseFileNumbers');
            
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('case_documents', $filename, 'public');
            
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
            
            $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';

            // Check for duplicate document types and add numbering
            $existingCount = \App\Models\Document::where('case_id', $case->id)
                ->where('doc_type', $validated['doc_type'])
                ->whereDate('uploaded_at', now()->toDateString())
                ->count();
            
            if ($existingCount > 0) {
                $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . ' (' . ($existingCount + 1) . ').pdf';
            }

            \Log::info('Creating document with data:', [
                'case_id' => $case->id,
                'doc_type' => $validated['doc_type'],
                'original_filename' => $originalFilename,
                'stored_filename' => $filename,
                'storage_uri' => $path,
                'ose_numbers_count' => $case->oseFileNumbers ? $case->oseFileNumbers->count() : 0
            ]);

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
            $documentType = \App\Models\DocumentType::where('code', $validated['doc_type'])->first();
            if ($documentType && $documentType->is_pleading && isset($validated['pleading_type']) && $validated['pleading_type'] !== 'none') {
                $documentData['pleading_type'] = $validated['pleading_type'];
            } else {
                $documentData['pleading_type'] = 'none';
            }
            
            \Log::info('About to create document', $documentData);
            $document = \App\Models\Document::create($documentData);
            \Log::info('Document created successfully', ['document_id' => $document->id, 'doc_type' => $validated['doc_type'], 'pleading_type' => $documentData['pleading_type']]);

            return redirect()->route('cases.documents.manage', $case)->with('success', 'Document uploaded successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to upload document: ' . $e->getMessage()]);
        }
    }

    public function approveDocument(CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);
        $document->update([
            'approved' => true,
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
            'rejected_reason' => null
        ]);

        return response()->json(['success' => true]);
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

    public function stampDocument(Request $request, CaseModel $case, $documentId)
    {
        if (!in_array(auth()->user()->role, ['hu_admin', 'hu_clerk'])) {
            abort(403);
        }

        $document = $case->documents()->findOrFail($documentId);
        
        // Use DocumentService to stamp the document (which includes PDF stamping)
        $documentService = app(\App\Services\DocumentService::class);
        
        if ($documentService->stampDocument($document, auth()->user())) {
            return response()->json(['success' => true, 'message' => 'Document stamped successfully']);
        }
        
        return response()->json(['success' => false, 'error' => 'Document marked as stamped but visual PDF stamping may have failed. Check logs for details.']);
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
}
