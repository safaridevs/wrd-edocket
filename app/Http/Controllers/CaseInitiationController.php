<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Services\CaseService;
use App\Services\DocumentService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CaseInitiationController extends Controller
{
    public function __construct(
        private CaseService $caseService,
        private DocumentService $documentService,
        private WorkflowService $workflowService
    ) {}

    public function create()
    {
        return view('cases.initiate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'caption' => 'required|string',
            'ose_file_numbers' => 'required|array',
            'parties' => 'required|array',
            'parties.*.name' => 'required|string',
            'parties.*.type' => 'required|in:applicant,protestant,counsel,expert',
            'parties.*.email' => 'nullable|email',
            'source_documents' => 'required|array',
            'source_documents.*' => 'file|mimes:pdf,doc,docx'
        ]);

        return DB::transaction(function () use ($validated) {
            // Create case
            $case = $this->caseService->createCase([
                'title' => $validated['title'],
                'caption' => $validated['caption'],
                'ose_file_numbers' => $validated['ose_file_numbers'],
                'source' => 'alu'
            ], Auth::user());

            // Add parties
            foreach ($validated['parties'] as $partyData) {
                CaseParty::create([
                    'case_id' => $case->id,
                    'name' => $partyData['name'],
                    'type' => $partyData['type'],
                    'email' => $partyData['email'] ?? null,
                    'is_served' => true
                ]);
            }

            // Upload source documents
            foreach ($request->file('source_documents') as $file) {
                $this->documentService->uploadDocument($case, $file, 'evidence', Auth::user());
            }

            return redirect()->route('cases.show', $case)->with('success', 'Case initiated and transmitted to HU.');
        });
    }
}