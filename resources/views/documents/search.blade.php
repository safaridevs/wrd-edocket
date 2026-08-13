@extends(Auth::check() ? 'layouts.app' : 'layouts.public')

@section('content')

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('documents.search') }}" class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                <label for="q" class="block text-sm font-medium text-gray-700">Search document text, file names, case numbers, and captions</label>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $query }}"
                        placeholder="Example: motion to dismiss, C-21, Capitan, party name"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Search
                    </button>
                </div>
                @unless($textIndexAvailable)
                    <p class="mt-3 text-sm text-amber-700">Text indexing has not been migrated yet, so this search is currently limited to document and case details.</p>
                @endunless
            </form>

            @if($query === '')
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-600">
                    Enter a keyword or phrase to search indexed documents.
                </div>
            @elseif($documents && $documents->count())
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <p class="text-sm text-gray-600">
                            Showing {{ $documents->firstItem() }}-{{ $documents->lastItem() }} of {{ $documents->total() }} matching document(s).
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($documents as $document)
                            @php
                                $textIndex = $textIndexAvailable && $document->relationLoaded('textIndex') ? $document->textIndex : null;
                                $snippet = $searchService->snippet($textIndex?->content_text, $query);
                            @endphp
                            <article class="px-5 py-5 hover:bg-gray-50">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-semibold text-gray-900">
                                                {{ $document->custom_title ?: $document->doc_type_label }}
                                            </h3>
                                            @if($textIndexAvailable)
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                    {{ $textIndex?->extraction_status ? \Illuminate\Support\Str::headline($textIndex->extraction_status) : 'Not indexed' }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-1 text-sm text-gray-600">{{ $document->original_filename }}</p>

                                        @if($document->case)
                                            <p class="mt-2 text-sm text-gray-700">
                                                <a href="{{ Auth::check() ? route('cases.show', $document->case) : route('public.cases.show', $document->case) }}" class="font-medium text-blue-700 hover:text-blue-900">
                                                    {{ $document->case->case_no }}
                                                </a>
                                                <span class="text-gray-400">/</span>
                                                <span>{{ \Illuminate\Support\Str::limit($document->case->caption, 180) }}</span>
                                            </p>
                                        @endif

                                        @if($snippet)
                                            <p class="mt-3 rounded-md bg-yellow-50 px-3 py-2 text-sm leading-6 text-gray-800">{{ $snippet }}</p>
                                        @elseif($textIndex?->extraction_error)
                                            <p class="mt-3 text-sm text-amber-700">{{ $textIndex->extraction_error }}</p>
                                        @endif

                                        <p class="mt-3 text-xs text-gray-500">
                                            Uploaded {{ $document->uploaded_at?->format('M j, Y g:i A') }}
                                            @if($textIndex?->indexed_at)
                                                / Indexed {{ $textIndex->indexed_at->format('M j, Y g:i A') }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 gap-3">
                                        @auth
                                            <a href="{{ route('documents.preview', $document) }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                                Preview
                                            </a>
                                        @endauth
                                        <a href="{{ Auth::check() ? route('documents.download', $document) : route('public.documents.download', $document) }}" class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($documents->hasPages())
                        <div class="border-t border-gray-200 px-5 py-4">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
                    <p class="text-gray-700">No matching documents found for "{{ $query }}".</p>
                    <p class="mt-2 text-sm text-gray-500">If older documents have not been indexed yet, run the document text index command.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
