<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicCaseController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseModel::where('status', 'approved')
            ->with(['parties.person', 'oseFileNumbers'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhereHas('parties.person', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('organization', 'like', "%{$search}%");
                  });
            });
        }

        $cases = $query->paginate(20);
        
        return view('public.cases.index', compact('cases'));
    }

    public function show(CaseModel $case)
    {
        // Only allow approved cases
        if ($case->status !== 'approved') {
            abort(404, 'Case not found or not available for public viewing.');
        }

        $case->load([
            'parties.person', 
            'oseFileNumbers', 
            'documents' => function($query) {
                // Only show approved and stamped documents
                $query->where('approved', true)->where('stamped', true);
            }
        ]);

        return view('public.cases.show', compact('case'));
    }

    public function downloadDocument(Document $document)
    {
        // Only allow download of approved and stamped documents from approved cases
        if (!$document->approved || !$document->stamped || $document->case->status !== 'approved') {
            abort(404, 'Document not available for public download.');
        }

        $filePath = storage_path('app/public/' . $document->storage_uri);
        
        if (!file_exists($filePath)) {
            abort(404, 'Document file not found.');
        }

        return response()->download($filePath, $document->original_filename);
    }
}