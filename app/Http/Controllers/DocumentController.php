<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\DocumentStorage;
use App\Services\DocumentTextIndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentStorage $documentStorage,
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
        Gate::authorize('view', $document);

        return $this->documentStorage->download($document);
    }

    public function preview(Document $document)
    {
        Gate::authorize('view', $document);

        return $this->documentStorage->inline($document);
    }

    public function approve(Document $document)
    {
        if ($this->documentService->approveDocument($document, Auth::user())) {
            return back()->with('success', 'Document approved successfully.');
        }

        return back()->with('error', 'Unable to approve document.');
    }
}
