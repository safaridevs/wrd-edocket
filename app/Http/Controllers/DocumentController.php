<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function myDocuments(Request $request)
    {
        $documents = Auth::user()->documents()
            ->with(['case', 'documentType'])
            ->latest('uploaded_at')
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function store(Request $request, CaseModel $case)
    {
        // Check if party user can access this case
        if (Auth::user()->getCurrentRole() === 'party' && !Auth::user()->canAccessCase($case)) {
            abort(403, 'You can only file documents to cases you are associated with.');
        }

        $documentTypes = $this->partyUploadDocumentTypes();
        if ($documentTypes->isEmpty()) {
            return back()->withErrors([
                'doc_type' => 'No document types are configured for your role.'
            ])->withInput();
        }

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf|max:204800',
            'doc_type' => ['required', 'string', Rule::in($documentTypes->pluck('code')->all())]
        ], [
            'document.required' => 'Please select a document to upload.',
            'document.mimes' => 'Document must be a PDF file.',
            'document.max' => 'Document size cannot exceed 200MB.',
            'doc_type.required' => 'Please select a document type.',
            'doc_type.in' => 'Invalid document type selected for your role.'
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
                Auth::user(),
                'none' // Party documents are not pleading documents
            );

            return redirect()->route('cases.show', $case)->with('success', 'Document uploaded successfully and is pending HU acceptance.');
        } catch (\Exception $e) {
            return back()->withErrors(['document' => 'Upload failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function download(Document $document)
    {
        $filePath = $this->documentService->downloadDocument($document);

        return Response::download($filePath, $document->original_filename);
    }

    public function preview(Document $document)
    {
        $filePath = $this->documentService->downloadDocument($document);

        return Response::file($filePath, [
            'Content-Type' => $document->mime,
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"'
        ]);
    }

    public function fileForm(CaseModel $case)
    {
        // Check if party user can access this case
        if (Auth::user()->getCurrentRole() === 'party' && !Auth::user()->canAccessCase($case)) {
            abort(403, 'You can only file documents to cases you are associated with.');
        }

        $case->load(['serviceList.person']);
        $documentTypes = $this->partyUploadDocumentTypes();

        return view('documents.file', compact('case', 'documentTypes'));
    }

    public function approve(Document $document)
    {
        if ($this->documentService->approveDocument($document, Auth::user())) {
            return back()->with('success', 'Document approved successfully.');
        }

        return back()->with('error', 'Unable to approve document.');
    }

    private function partyUploadDocumentTypes()
    {
        return DocumentType::forRole(Auth::user()->getCurrentRole())
            ->where('category', 'party_upload')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}



