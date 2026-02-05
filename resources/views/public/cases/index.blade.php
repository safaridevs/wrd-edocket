@extends('layouts.public')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Search Header -->
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border border-gray-100">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Case Search</h2>
            <p class="text-gray-600 text-lg">Search Active Cases</p>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('public.cases.index') }}" class="max-w-4xl mx-auto">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by case number, caption, or party name..."
                               class="w-full pl-12 pr-4 py-4 border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 search-focus text-lg">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-4 rounded-xl hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl font-semibold">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('public.cases.index') }}" class="bg-gray-100 text-gray-700 px-6 py-4 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                            Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                @if(request('search'))
                    Search Results for "{{ request('search') }}" ({{ $cases->total() }} found)
                @else
                    Active Cases ({{ $cases->total() }} total)
                @endif
            </h3>
        </div>

        @if($cases->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($cases as $case)
                <div class="p-6 card-hover border-l-4 border-l-transparent hover:border-l-blue-500 hover:bg-gradient-to-r hover:from-blue-50 hover:to-transparent">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-3">
                                <h4 class="text-xl font-semibold text-blue-600">
                                    <a href="{{ route('public.cases.show', $case) }}" class="hover:text-blue-800 transition-colors">
                                        {{ $case->case_no }}
                                    </a>
                                </h4>
                                <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-gradient-to-r from-green-400 to-green-500 text-white shadow-sm">
                                    ✓ Accepted
                                </span>
                            </div>

                            <p class="text-gray-900 mb-3">{{ $case->caption }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <strong>Case Type:</strong> {{ ucfirst($case->case_type) }}
                                </div>
                                <div>
                                    <strong>Filed:</strong> {{ $case->created_at->format('M j, Y') }}
                                </div>
                                @if($case->oseFileNumbers->count() > 0)
                                <div class="md:col-span-2">
                                    <strong>OSE File Numbers:</strong>
                                    @foreach($case->oseFileNumbers as $ose)
                                        <span class="inline-block bg-gray-100 px-2 py-1 rounded text-xs mr-1">
                                            {{ $ose->file_no_from }}{{ $ose->file_no_to ? ' - ' . $ose->file_no_to : '' }}
                                        </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            @if($case->parties->count() > 0)
                            <div class="mt-3">
                                <strong class="text-sm text-gray-600">Parties:</strong>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach($case->parties->take(3) as $party)
                                        <span class="inline-block bg-blue-50 text-blue-800 px-2 py-1 rounded text-xs">
                                            {{ $party->person->full_name }} ({{ ucfirst($party->role) }})
                                        </span>
                                    @endforeach
                                    @if($case->parties->count() > 3)
                                        <span class="text-xs text-gray-500">
                                            +{{ $case->parties->count() - 3 }} more
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="ml-4">
                            <a href="{{ route('public.cases.show', $case) }}"
                               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $cases->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No cases found</h3>
                <p class="text-gray-600">
                    @if(request('search'))
                        No approved cases match your search criteria. Try different keywords or browse all cases.
                    @else
                        No approved cases are currently available for public viewing.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
