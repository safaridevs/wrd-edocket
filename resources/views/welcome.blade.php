<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>OSE E-Docket - New Mexico Office of the State Engineer</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <img src="{{ asset('images/ose-logo.png') }}" alt="OSE Logo" class="h-12 w-auto">
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-blue-900">OSE E-Docket</h1>
                            <p class="text-sm text-gray-600 hidden md:block">New Mexico Office of the State Engineer</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                    Log in
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                        Register
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-blue-900 to-blue-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        OSE E-Docket
                    </h1>
                    <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto">
                        Access Office of The State Engineer Hearing Unit Cases
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('public.cases.index') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                            Browse Cases
                        </a>
                        <a href="{{ route('documents.search') }}" class="bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors border border-blue-600">
                            Search Documents
                        </a>
                        @guest
                        <a href="{{ route('register') }}" class="bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-colors border border-blue-600">
                            Create Account
                        </a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>

        <!-- Public Cases Section -->
        <div id="public-cases" class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Hearing Unit Cases</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Browse Hearing Unit cases. All documents and proceedings are available for public review.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ $selectedYear }} Cases ({{ $publicCases->count() }} total)
                                </h3>
                            </div>

                            <form method="GET" action="{{ route('welcome') }}#public-cases" class="flex items-center gap-3">
                                <label for="case-year" class="text-sm font-medium text-gray-700">Year</label>
                                <select id="case-year" name="year" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @forelse($caseYears as $year)
                                        <option value="{{ $year }}" @selected((string) $selectedYear === (string) $year)>{{ $year }}</option>
                                    @empty
                                        <option value="{{ $selectedYear }}">{{ $selectedYear }}</option>
                                    @endforelse
                                </select>
                                <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Filter
                                </button>
                            </form>
                        </div>
                    </div>

                    @if($publicCases->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($publicCases as $case)
                            <div class="p-6 border-l-4 border-l-transparent hover:border-l-blue-500 hover:bg-gradient-to-r hover:from-blue-50 hover:to-transparent">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-3 mb-3">
                                            <h4 class="text-xl font-semibold text-blue-600">
                                                <a href="{{ route('public.cases.show', $case) }}" class="hover:text-blue-800 transition-colors">
                                                    {{ $case->case_no }}
                                                </a>
                                            </h4>
                                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full {{ $case->visible_status_badge_class }}">
                                                {{ $case->visible_status_label }}
                                            </span>
                                            @if($case->hu_display_status)
                                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $case->hu_display_status_badge_class }}"
                                                      @if($case->hu_display_status_note) title="{{ $case->hu_display_status_note }}" @endif>
                                                    {{ $case->hu_display_status_label }}
                                                </span>
                                            @endif
                                        </div>

                                        @include('public.cases.partials.caption-preview', ['caption' => $case->caption])

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

                                    <div class="lg:ml-4">
                                        <a href="{{ route('public.cases.show', $case) }}"
                                           class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto">
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
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Cases Found</h3>
                            <p class="text-gray-600">No active Hearing Unit cases are available for {{ $selectedYear }}.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Information Section -->
        <div class="bg-white py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- About Section -->
                    <div class="bg-gray-50 rounded-xl p-8">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">About OSE E-Docket</h3>
                        <p class="text-gray-600 mb-4">
                            The OSE Hearing Unit E-Docket provides public access to water rights hearing cases, pleadings, orders, and related documents from the New Mexico Office of the State Engineer.
                        </p>
                        <p class="text-gray-600">
                            This system is under active development to provide comprehensive access to all case materials including applications, protests, compliance orders, and hearing proceedings.
                        </p>
                    </div>

                    <!-- How to Use -->
                    <div class="bg-gray-50 rounded-xl p-8">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">How to Use</h3>
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-start space-x-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span>Browse public cases without logging in</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span>View case documents and proceedings</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span>Register to participate in your cases</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mt-2 flex-shrink-0"></span>
                                <span>Upload documents and track case status</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Legal Resources -->
                    <div class="bg-gray-50 rounded-xl p-8">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Legal Resources, Instructions and Guideline</h3>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Information for Parties</h3>
                        <div class="space-y-3">
                            <a href="https://nmonesource.com/nmos/nmsa-unanno/en/item/18575/index.do#!fragment/zoupio-_Toc233806439/BQCwhgziBcwMYgK4DsDWszIQewE4BUBTADwBdoAvbRABwEtsBaAfX2zgCYBmLgDgAYAbABYuATgCUAGmTZShCAEVEhXAE9oAck1SIhMLgTLVG7bv2GQAZTykAQhoBKAUQAyzgGoBBAHIBhZylSMAAjaFJ2CQkgA" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Water Law - Chapter 72
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Administration and Use of Water - General Provisions - 19.25.1 NMAC
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25p1" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Hearing Unit Procedures - 19.25.2 NMAC
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c26" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Surface Water Rules - 19.26.1 NMAC
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c27" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Groundwater Rules - 19.27.1 NMAC
                            </a>
                            <a href="{{ asset('documents/Instructions for Parties in State Engineer Administrative Hearings_Rev_11_2_22.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Instructions for Parties in State Engineer Administrative Hearings Rev 11-2-22
                            </a>
                        </div>
                        <br>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Alternative Dispute Resolution (ADR)</h3>
                        <div class="space-y-3">
                            <a href="{{ asset('documents/New Mexico Mediation Procedures Act.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                New Mexico Mediation Procedures Act - NMSA 1978, Chapter 44, Art. 7(B)
                            </a>
                            <a href="{{ asset('documents/OSE Mediation - NM Law and Mediation.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                OSE Mediation - NM Law and Mediation
                            </a>
                            <a href="{{ asset('documents/OSE Mediation FAQs.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                OSE Mediation FAQs
                            </a>

                            <a href="{{ asset('documents/Mediation Guidelines.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Mediation Guidelines
                            </a>
                            <a href="https://nmonesource.com/nmos/nmsa/en/item/4374/index.do#a8A" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Governmental Dispute Resolution Act - NMSA 1978, Chapter 12, Art. 8(A)
                            </a>

                        </div>
                         {{-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Instructions for Parties</h3>
                        <div class="space-y-3">
                            <a href="{{ asset('documents/Mediation Guidelines.pdf') }}" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Mediation Guidelines
                            </a>

                        </div> --}}
                    </div>

                    {{--  --}}
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-gray-400">
                        © {{ date('Y') }} New Mexico Office of the State Engineer. All rights reserved.
                    </p>
                    <p class="text-gray-500 text-sm mt-2">
                        For technical support, contact: <a href="mailto:{{ config('edocket.contact.support_email') }}" class="text-blue-400 hover:text-blue-300">{{ config('edocket.contact.support_email') }}</a>
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
