@extends('layouts.public')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="{{ route('public.cases.index') }}" class="hover:text-gray-700">Public Cases</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900">{{ $case->case_no }}</li>
        </ol>
    </nav>

    <!-- Case Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $case->case_no }}</h1>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        Approved Case
                    </span>
                    <span class="text-sm text-gray-600">{{ ucfirst($case->case_type) }} Case</span>
                </div>
            </div>
            <div class="text-right text-sm text-gray-600">
                <div><strong>Filed:</strong> {{ $case->created_at->format('M j, Y') }}</div>
                @if($case->approved_at)
                <div><strong>Approved:</strong> {{ $case->approved_at->format('M j, Y') }}</div>
                @endif
            </div>
        </div>
        
        <div class="border-t pt-4">
            <h3 class="font-medium text-gray-900 mb-2">Caption</h3>
            <p class="text-gray-700">{{ $case->caption }}</p>
        </div>
    </div>

    <!-- OSE File Numbers -->
    @if($case->oseFileNumbers->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">OSE File Numbers</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($case->oseFileNumbers as $ose)
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="font-medium text-gray-900">
                    {{ $ose->file_no_from }}{{ $ose->file_no_to ? ' - ' . $ose->file_no_to : '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Parties -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Case Parties</h3>
        <div class="space-y-4">
            @foreach($case->parties as $party)
            <div class="border rounded-lg p-4 bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h4 class="font-medium text-gray-900">{{ $party->person->full_name }}</h4>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ ucfirst($party->role) }}
                            </span>
                        </div>
                        
                        @if($party->person->type === 'company' && $party->person->organization)
                        <p class="text-sm text-gray-600 mb-2">{{ $party->person->organization }}</p>
                        @endif
                        
                        <div class="text-sm text-gray-600">
                            @if($party->person->email)
                            <div><strong>Email:</strong> {{ $party->person->email }}</div>
                            @endif
                            
                            @if($party->person->address_line1 || $party->person->city)
                            <div class="mt-2">
                                <strong>Address:</strong>
                                <div class="ml-2">
                                    @if($party->person->address_line1)
                                        <div>{{ $party->person->address_line1 }}</div>
                                    @endif
                                    @if($party->person->address_line2)
                                        <div>{{ $party->person->address_line2 }}</div>
                                    @endif
                                    @if($party->person->city || $party->person->state)
                                        <div>
                                            {{ $party->person->city }}{{ $party->person->city && $party->person->state ? ', ' : '' }}{{ $party->person->state }} {{ $party->person->zip }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Public Documents -->
    @if($case->documents->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Public Documents ({{ $case->documents->count() }})</h3>
        <div class="space-y-3">
            @foreach($case->documents->sortByDesc('uploaded_at') as $document)
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h4 class="font-medium text-gray-900">{{ $document->original_filename }}</h4>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            📋 E-Stamped
                        </span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            ✓ Approved
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        {{ ucfirst(str_replace('_', ' ', $document->doc_type)) }} • 
                        {{ number_format($document->size_bytes / 1024, 1) }} KB • 
                        Uploaded {{ $document->uploaded_at->format('M j, Y') }}
                        @if($document->stamped_at)
                            • Stamped {{ $document->stamped_at->format('M j, Y') }}
                        @endif
                    </div>
                </div>
                <div class="ml-4">
                    <a href="{{ route('public.documents.download', $document) }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                       target="_blank">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Public Documents</h3>
        <div class="text-center py-8">
            <div class="text-gray-400 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-600">No public documents are available for this case.</p>
        </div>
    </div>
    @endif

    <!-- Back to Search -->
    <div class="mt-8 text-center">
        <a href="{{ route('public.cases.index') }}" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Case Search
        </a>
    </div>
</div>
@endsection