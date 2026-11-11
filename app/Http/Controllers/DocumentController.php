<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\DocumentTextIndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentTextIndexService $documentTextIndexService
    ) {}

    public function myDocuments(Request $request)
    {
        $documents = Auth::user()->documents()
            ->with(['case', 'documentType'])
            ->latest('uploaded_at')
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $documents = null;

        if ($query !== '') {
            $documents = Auth::check()
                ? $this->documentTextIndexService->search(Auth::user(), $query)
                : $this->documentTextIndexService->searchPublic($query);
        }

        return view('documents.search', [
            'documents' => $documents,
            'query' => $query,
            'searchService' => $this->documentTextIndexService,
            'textIndexAvailable' => $this->documentTextIndexService->isTextIndexAvailable(),
        ]);
    }

    public function download(Document $document)
    {
        $this->authorizeDocumentAccess($document);
        $filePath = $this->documentService->downloadDocument($document);

        return Response::download($filePath, $document->original_filename);
    }

    public function preview(Document $document)
    {
        $this->authorizeDocumentAccess($document);
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

    private function authorizeDocumentAccess(Document $document): void
    {
        $user = Auth::user();
        $document->loadMissing(['case', 'uploader.roleRelation']);

        abort_unless($user && $user->canAccessCase($document->case), 403);

        if (!$user->isHearingUnit() && $document->isPendingHearingUnitDocument()) {
            abort(404);
        }
    }
}
