<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

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
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"',
        ]);
    }

    public function approve(Document $document)
    {
        if ($this->documentService->approveDocument($document, Auth::user())) {
            return back()->with('success', 'Document approved successfully.');
        }

        return back()->with('error', 'Unable to approve document.');
    }
}
