<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>OSE E-Docket System - New Mexico Office of the State Engineer</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .hero-pattern {
                background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f1f5f9' fill-opacity='0.4'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            }
        </style>
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
                            <h1 class="text-xl font-bold text-blue-900">OSE E-Docket System</h1>
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
        <div class="bg-gradient-to-br from-blue-900 to-blue-800 hero-pattern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        Water Rights E-Docket System
                    </h1>
                    <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto">
                        Access approved water rights hearing cases, documents, and proceedings from the New Mexico Office of the State Engineer Hearing Unit.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="#approved-cases" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                            Browse Approved Cases
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

        <!-- Stats Section -->
        <div class="bg-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ \App\Models\CaseModel::where('status', 'approved')->count() }}</div>
                        <div class="text-gray-600 mt-2">Approved Cases</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">{{ \App\Models\Document::whereHas('case', function($q) { $q->where('status', 'approved'); })->count() }}</div>
                        <div class="text-gray-600 mt-2">Public Documents</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ \App\Models\CaseModel::where('status', 'active')->count() }}</div>
                        <div class="text-gray-600 mt-2">Active Hearings</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approved Cases Section -->
        <div id="approved-cases" class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Approved Water Rights Cases</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Browse approved water rights hearing cases. All documents and proceedings are available for public review.
                    </p>
                </div>

                @php
                    $approvedCases = \App\Models\CaseModel::where('status', 'approved')
                        ->with(['parties.person', 'documents', 'oseFileNumbers'])
                        ->latest()
                        ->take(12)
                        ->get();
                @endphp

                @if($approvedCases->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        @foreach($approvedCases as $case)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-lg">{{ $case->case_no }}</h3>
                                        <p class="text-sm text-gray-600">{{ ucfirst($case->case_type) }} Case</p>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                        Approved
                                    </span>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-700 line-clamp-2">{{ Str::limit($case->caption, 100) }}</p>
                                </div>

                                @if($case->oseFileNumbers->count() > 0)
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 mb-1">OSE File Numbers:</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($case->oseFileNumbers->take(2) as $ose)
                                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                                            {{ $ose->file_no_from }}{{ $ose->file_no_to ? '-' . $ose->file_no_to : '' }}
                                        </span>
                                        @endforeach
                                        @if($case->oseFileNumbers->count() > 2)
                                        <span class="text-xs text-gray-500">+{{ $case->oseFileNumbers->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span>{{ $case->documents->count() }} documents</span>
                                    <span>{{ $case->created_at->format('M j, Y') }}</span>
                                </div>

                                <a href="{{ route('cases.show', $case) }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-center block">
                                    View Case Details
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if(\App\Models\CaseModel::where('status', 'approved')->count() > 12)
                    <div class="text-center">
                        <a href="{{ route('cases.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors">
                            View All Approved Cases ({{ \App\Models\CaseModel::where('status', 'approved')->count() }} total)
                        </a>
                    </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Approved Cases Yet</h3>
                        <p class="text-gray-600">Approved water rights cases will appear here for public access.</p>
                    </div>
                @endif
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
                                <span>Browse approved cases without logging in</span>
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
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Legal Resources</h3>
                        <div class="space-y-3">
                            <a href="https://nmonesource.com/nmos/en/a/s/index.do?cont=chapter+72+water+code" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                NMSA 1978, Chapter 72 Water Code
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                19.25.1 NMAC - General Provisions
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25p1" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                19.25.2 NMAC - Hearing Unit Procedures
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c26" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Title 19 Chapter 26 - Surface Water Rules
                            </a>
                            <a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c27" target="_blank" class="block text-purple-600 hover:text-purple-800 text-sm">
                                Title 19 Chapter 27 - Underground Water Rules
                            </a>
                        </div>
                    </div>
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
                        For technical support, contact: <a href="mailto:support@ose.nm.gov" class="text-blue-400 hover:text-blue-300">support@ose.nm.gov</a>
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>