<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
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
            // HU users see all cases
            $cases = CaseModel::latest()->get();
        } elseif ($user->role === 'party') {
            // Party users see cases where their email matches a person in case parties
            $cases = CaseModel::whereHas('parties.person', function($query) use ($user) {
                $query->where('email', $user->email);
            })->latest()->get();
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
        
        return view('cases.create');
    }

    public function store(Request $request)
    {
        if (!Auth::user()->canCreateCase()) {
            abort(403);
        }

        $validated = $request->validate([
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'applicants' => 'required|array|min:1',
            'applicants.*' => 'required|string|max:255',
            'protestants' => 'nullable|array',
            'protestants.*' => 'nullable|string|max:255',
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code' => 'nullable|string|in:RG,PE,CA',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'service_list' => 'nullable|array',
            'service_list.*.type' => 'nullable|in:individual,company',
            'service_list.*.first_name' => 'nullable|string|max:255',
            'service_list.*.last_name' => 'nullable|string|max:255',
            'service_list.*.organization' => 'nullable|string|max:255',
            'service_list.*.email' => 'nullable|email|max:255',
            'service_list.*.phone' => 'nullable|string|max:20',
            'service_list.*.address_line1' => 'nullable|string|max:500',
            'service_list.*.city' => 'nullable|string|max:100',
            'service_list.*.state' => 'nullable|string|max:50',
            'service_list.*.zip' => 'nullable|string|max:10',
            'service_list.*.method' => 'nullable|in:email,mail',
            'documents' => 'nullable|array',
            'documents.application' => 'nullable|file|mimes:pdf|max:10240',
            'documents.notice_publication' => 'nullable|file|mimes:docx|max:10240',
            'documents.request_to_docket' => 'nullable|file|mimes:pdf|max:10240',
            'documents.protest_letter' => 'nullable|array',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:10240',
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
        $case->load(['creator', 'assignee', 'documents.uploader', 'parties.person', 'serviceList.person', 'oseFileNumbers', 'auditLogs.user']);
        return view('cases.show', compact('case'));
    }

    public function edit(CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() || !in_array($case->status, ['draft', 'rejected'])) {
            abort(403);
        }
        
        $case->load(['parties.person', 'serviceList.person', 'oseFileNumbers', 'documents']);
        return view('cases.edit', compact('case'));
    }

    public function update(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canCreateCase() || !in_array($case->status, ['draft', 'rejected'])) {
            abort(403);
        }

        $validated = $request->validate([
            'case_type' => 'required|in:aggrieved,protested,compliance',
            'caption' => 'required|string|max:1000',
            'applicants' => 'required|array|min:1',
            'applicants.*' => 'required|string|max:255',
            'protestants' => 'nullable|array',
            'protestants.*' => 'nullable|string|max:255',
            'ose_numbers' => 'nullable|array',
            'ose_numbers.*.basin_code' => 'nullable|string|in:RG,PE,CA',
            'ose_numbers.*.file_no_from' => 'nullable|string|max:50',
            'ose_numbers.*.file_no_to' => 'nullable|string|max:50',
            'service_list' => 'nullable|array',
            'service_list.*.type' => 'nullable|in:individual,company',
            'service_list.*.first_name' => 'nullable|string|max:255',
            'service_list.*.last_name' => 'nullable|string|max:255',
            'service_list.*.organization' => 'nullable|string|max:255',
            'service_list.*.email' => 'nullable|email|max:255',
            'service_list.*.phone' => 'nullable|string|max:20',
            'service_list.*.address_line1' => 'nullable|string|max:500',
            'service_list.*.city' => 'nullable|string|max:100',
            'service_list.*.state' => 'nullable|string|max:50',
            'service_list.*.zip' => 'nullable|string|max:10',
            'service_list.*.method' => 'nullable|in:email,mail',
            'documents' => 'nullable|array',
            'documents.application' => 'nullable|file|mimes:pdf|max:10240',
            'documents.notice_publication' => 'nullable|file|mimes:docx|max:10240',
            'documents.request_to_docket' => 'nullable|file|mimes:pdf|max:10240',
            'documents.protest_letter' => 'nullable|array',
            'documents.protest_letter.*' => 'nullable|file|mimes:pdf|max:10240',
            'affirmation' => 'required|accepted',
            'action' => 'required|in:draft,validate,submit'
        ]);

        try {
            $updatedCase = $this->caseService->updateCase($case, $validated, Auth::user(), $request);
            
            $message = match($validated['action']) {
                'draft' => 'Case updated and saved as draft.',
                'validate' => 'Case updated and validated successfully.',
                'submit' => 'Case updated and submitted to Hearing Unit.',
            };
            
            return redirect()->route('cases.show', $updatedCase)->with('success', $message);
            
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
}