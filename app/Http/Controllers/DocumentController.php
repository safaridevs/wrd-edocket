<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function store(Request $request, CaseModel $case)
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf|max:102400',
            'doc_type' => 'required|string|in:filing_other,protest_letter,aggrieval_letter,affidavit_publication'
        ], [
            'document.required' => 'Please select a document to upload.',
            'document.mimes' => 'Document must be a PDF file.',
            'document.max' => 'Document size cannot exceed 100MB.',
            'doc_type.required' => 'Please select a document type.',
            'doc_type.in' => 'Invalid document type selected.'
        ]);

        $file = $request->file('document');
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Validate naming convention
        $pattern = '/^\d{4}-\d{2}-\d{2}\s—\s.+\s—\s.+\s—\s' . preg_quote($case->case_no) . '$/';
        if (!preg_match($pattern, $filename)) {
            return back()->withErrors([
                'document' => "Filename must follow convention: YYYY-MM-DD — Doc Type — Description — {$case->case_no}"
            ])->withInput();
        }

        try {
            $document = $this->documentService->uploadDocument(
                $case,
                $file,
                $validated['doc_type'],
                Auth::user()
            );

            return redirect()->route('cases.show', $case)->with('success', 'Document uploaded successfully and submitted for review.');
        } catch (\Exception $e) {
            return back()->withErrors(['document' => 'Upload failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function download(Document $document)
    {
        $filePath = $this->documentService->downloadDocument($document);
        
        return Response::download($filePath, $document->original_filename);
    }

    public function fileForm(CaseModel $case)
    {
        $case->load(['serviceList.person']);
        return view('documents.file', compact('case'));
    }

    public function stamp(Document $document)
    {
        if ($this->documentService->stampDocument($document, Auth::user())) {
            return back()->with('success', 'Document stamped successfully.');
        }
        
        return back()->with('error', 'Unable to stamp document.');
    }
}