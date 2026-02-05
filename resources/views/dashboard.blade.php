<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ auth()->user()->name }}</h1>
                        <p class="text-gray-600 mt-1">
                            @if(auth()->user()->isAttorney())
                                Attorney
                            @elseif(auth()->user()->isParalegal())
                                Paralegal
                            @else
                                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                            @endif
                            • {{ now()->format('l, F j, Y') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->canCreateCase())
                        <a href="{{ route('cases.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>New Case</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- My Cases -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Cases</p>
                            <p class="text-2xl font-bold text-gray-900">
                                @if(auth()->user()->role === 'party')
                                    @php
                                        if (auth()->user()->isParalegal()) {
                                            // Get cases where user is paralegal
                                            $myCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                                $query->where('role', 'paralegal')
                                                      ->whereHas('person', function($subQuery) {
                                                          $subQuery->where('email', auth()->user()->email);
                                                      });
                                            })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])->count();
                                        } elseif (auth()->user()->isAttorney()) {
                                            // Get cases where user is counsel (attorney)
                                            $myCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                                $query->where('role', 'counsel')
                                                      ->whereHas('person', function($subQuery) {
                                                          $subQuery->where('email', auth()->user()->email);
                                                      });
                                            })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])->count();
                                        } else {
                                            // Regular party - get cases where they are applicant, protestant, etc.
                                            $myCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                                $query->whereIn('role', ['applicant', 'protestant', 'aggrieved_party', 'respondent', 'violator', 'alleged_violator'])
                                                      ->whereHas('person', function($subQuery) {
                                                          $subQuery->where('email', auth()->user()->email);
                                                      });
                                            })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])->count();
                                        }
                                    @endphp
                                    {{ $myCases }}
                                @else
                                    {{ auth()->user()->createdCases()->count() }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('cases.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View all cases →</a>
                    </div>
                </div>

                @if(auth()->user()->isHearingUnit())
                <!-- Pending Review -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending Review</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\CaseModel::where('status', 'submitted_to_hu')->count() }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('cases.index') }}" class="text-orange-600 hover:text-orange-800 text-sm font-medium">Review cases →</a>
                    </div>
                </div>

                <!-- Active Cases -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Cases</p>
                            <p class="text-2xl font-bold text-gray-900">{{ \App\Models\CaseModel::where('status', 'active')->count() }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-green-600 text-sm font-medium">Ready for hearing</span>
                    </div>
                </div>
                @endif

                <!-- Documents -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Documents</p>
                            <p class="text-2xl font-bold text-gray-900">{{ auth()->user()->documents()->count() }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('cases.index') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">View documents →</a>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Cases -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Cases</h3>
                        </div>
                        <div class="p-6">
                            @php
                                if (auth()->user()->role === 'party') {
                                    if (auth()->user()->isParalegal()) {
                                        // Get cases where user is paralegal
                                        $recentCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                            $query->where('role', 'paralegal')
                                                  ->whereHas('person', function($subQuery) {
                                                      $subQuery->where('email', auth()->user()->email);
                                                  });
                                        })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])
                                          ->with(['parties' => function($query) {
                                              $query->whereIn('role', ['counsel', 'paralegal'])
                                                    ->with('person', 'clientParty.person');
                                          }])
                                          ->latest()->take(5)->get();
                                    } elseif (auth()->user()->isAttorney()) {
                                        // Get cases where user is counsel (attorney)
                                        $recentCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                            $query->where('role', 'counsel')
                                                  ->whereHas('person', function($subQuery) {
                                                      $subQuery->where('email', auth()->user()->email);
                                                  });
                                        })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])
                                          ->with(['parties' => function($query) {
                                              $query->where('role', 'counsel')
                                                    ->whereHas('person', function($subQuery) {
                                                        $subQuery->where('email', auth()->user()->email);
                                                    })->with('clientParty.person');
                                          }])
                                          ->latest()->take(5)->get();
                                    } else {
                                        // Regular party - get their cases
                                        $recentCases = \App\Models\CaseModel::whereHas('parties', function($query) {
                                            $query->whereIn('role', ['applicant', 'protestant', 'aggrieved_party', 'respondent', 'violator', 'alleged_violator'])
                                                  ->whereHas('person', function($subQuery) {
                                                      $subQuery->where('email', auth()->user()->email);
                                                  });
                                        })->whereIn('status', ['active', 'approved', 'submitted_to_hu'])
                                          ->with('parties.person')
                                          ->latest()->take(5)->get();
                                    }
                                } else {
                                    $recentCases = auth()->user()->isHearingUnit() 
                                        ? \App\Models\CaseModel::whereNotIn('status', ['draft'])->latest()->take(5)->get()
                                        : auth()->user()->createdCases()->latest()->take(5)->get();
                                }
                            @endphp
                            
                            @if($recentCases->count() > 0)
                                <div class="space-y-4">
                                    @foreach($recentCases as $case)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <span class="text-blue-600 font-semibold text-sm">{{ substr($case->case_no, -2) }}</span>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-900">{{ $case->case_no }}</h4>
                                                <p class="text-sm text-gray-600">{{ ucfirst($case->case_type) }} • {{ $case->created_at->format('M j, Y') }}</p>
                                                @php
                                                    if (auth()->user()->isParalegal()) {
                                                        // For paralegals, show the attorney they work with
                                                        $paralegalParty = $case->parties->where('role', 'paralegal')
                                                            ->filter(function($party) {
                                                                return $party->person->email === auth()->user()->email;
                                                            })->first();
                                                        
                                                        $attorneyName = 'Unknown Attorney';
                                                        $clientName = 'Unknown Client';
                                                        
                                                        if ($paralegalParty) {
                                                            $counselParty = \App\Models\CaseParty::where('role', 'counsel')
                                                                ->where('client_party_id', $paralegalParty->client_party_id)
                                                                ->with('person', 'clientParty.person')
                                                                ->first();
                                                            
                                                            if ($counselParty) {
                                                                $attorneyName = $counselParty->person->full_name;
                                                                if ($counselParty->clientParty) {
                                                                    $clientName = $counselParty->clientParty->person->full_name;
                                                                }
                                                            }
                                                        }
                                                    } elseif (auth()->user()->isAttorney()) {
                                                        // For attorneys, show which client they represent
                                                        $counselParty = $case->parties->where('role', 'counsel')
                                                            ->filter(function($party) {
                                                                return $party->person->email === auth()->user()->email;
                                                            })->first();
                                                        
                                                        $clientName = 'Unknown Client';
                                                        if ($counselParty && $counselParty->clientParty) {
                                                            $clientName = $counselParty->clientParty->person->full_name;
                                                        }
                                                    } else {
                                                        // For regular parties, show their role
                                                        $userParty = $case->parties->filter(function($party) {
                                                            return $party->person->email === auth()->user()->email;
                                                        })->first();
                                                        
                                                        $partyRole = $userParty ? ucfirst(str_replace('_', ' ', $userParty->role)) : 'Party';
                                                    }
                                                @endphp
                                                @if(auth()->user()->isParalegal())
                                                    <p class="text-xs text-purple-600 font-medium">Attorney: {{ $attorneyName }}</p>
                                                    <p class="text-xs text-blue-600">Client: {{ $clientName }}</p>
                                                @elseif(auth()->user()->isAttorney())
                                                    <p class="text-xs text-blue-600 font-medium">Representing: {{ $clientName }}</p>
                                                @else
                                                    <p class="text-xs text-green-600 font-medium">Role: {{ $partyRole }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                {{ $case->status === 'active' ? 'bg-green-100 text-green-800' :
                                                   ($case->status === 'approved' ? 'bg-blue-100 text-blue-800' :
                                                   ($case->status === 'submitted_to_hu' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ $case->status === 'approved' ? 'Accepted' : ucfirst(str_replace('_', ' ', $case->status)) }}
                                            </span>
                                            <a href="{{ route('cases.show', $case) }}" class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="mt-6">
                                    <a href="{{ route('cases.index') }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors text-center block">
                                        View All Cases
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500">No cases found</p>
                                    @if(auth()->user()->canCreateCase())
                                    <a href="{{ route('cases.create') }}" class="text-blue-600 hover:text-blue-800 font-medium mt-2 inline-block">Create your first case</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Info -->
                <div class="space-y-6">
                    <!-- My Role -->
                    @if(auth()->user()->role === 'party')
                        @if(auth()->user()->isParalegal())
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Paralegal Profile</h3>
                            </div>
                            <div class="p-6">
                                @php
                                    $paralegalParties = \App\Models\CaseParty::where('role', 'paralegal')
                                        ->whereHas('person', function($query) {
                                            $query->where('email', auth()->user()->email);
                                        })
                                        ->whereHas('case', function($query) {
                                            $query->whereIn('status', ['active', 'approved', 'submitted_to_hu']);
                                        })
                                        ->with('case', 'clientParty.person')
                                        ->get();
                                    
                                    $assignedCases = $paralegalParties->count();
                                    $attorneys = $paralegalParties->map(function($p) {
                                        return \App\Models\CaseParty::where('role', 'counsel')
                                            ->where('client_party_id', $p->client_party_id)
                                            ->with('person')
                                            ->first();
                                    })->filter()->unique('person.email');
                                @endphp
                                
                                <div class="mb-4">
                                    <p class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                </div>
                                
                                <div class="border-t pt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Assigned Cases: {{ $assignedCases }}</p>
                                    @if($attorneys->count() > 0)
                                        <p class="text-xs text-gray-600 mb-2">Working with:</p>
                                        <div class="space-y-2">
                                            @foreach($attorneys as $counsel)
                                                @if($counsel && $counsel->person)
                                                <div class="text-xs bg-purple-50 p-2 rounded">
                                                    <p class="font-medium text-purple-900">{{ $counsel->person->full_name }}</p>
                                                    <p class="text-purple-600">Attorney</p>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @elseif(auth()->user()->isAttorney())
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Attorney Profile</h3>
                        </div>
                        <div class="p-6">
                            @php
                                $attorney = \App\Models\Attorney::where('email', auth()->user()->email)->first();
                                $clientCount = 0;
                                $activeClients = collect();
                                
                                if ($attorney) {
                                    $counselParties = \App\Models\CaseParty::where('role', 'counsel')
                                        ->whereHas('person', function($query) {
                                            $query->where('email', auth()->user()->email);
                                        })
                                        ->whereHas('case', function($query) {
                                            $query->whereIn('status', ['active', 'approved', 'submitted_to_hu']);
                                        })
                                        ->with('clientParty.person', 'case')
                                        ->get();
                                    
                                    $clientCount = $counselParties->count();
                                    $activeClients = $counselParties->take(3);
                                }
                            @endphp
                            
                            @if($attorney)
                                <div class="mb-4">
                                    <p class="text-sm font-medium text-gray-700">{{ $attorney->name }}</p>
                                    @if($attorney->bar_number)
                                        <p class="text-xs text-gray-500">Bar #: {{ $attorney->bar_number }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500">{{ $attorney->email }}</p>
                                </div>
                                
                                <div class="border-t pt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Active Clients: {{ $clientCount }}</p>
                                    @if($activeClients->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($activeClients as $counsel)
                                                @if($counsel->clientParty)
                                                <div class="text-xs bg-blue-50 p-2 rounded">
                                                    <p class="font-medium text-blue-900">{{ $counsel->clientParty->person->full_name }}</p>
                                                    <p class="text-blue-600">Case: {{ $counsel->case->case_no }}</p>
                                                </div>
                                                @endif
                                            @endforeach
                                            @if($clientCount > 3)
                                                <p class="text-xs text-gray-500 text-center">+ {{ $clientCount - 3 }} more</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No attorney profile found</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            @if(auth()->user()->canCreateCase())
                            <a href="{{ route('cases.create') }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Create New Case</span>
                            </a>
                            @endif
                            
                            <a href="{{ route('cases.index') }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Browse Cases</span>
                            </a>

                            @if(auth()->user()->role === 'party')
                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-600 mb-3">Need help with your case?</p>
                                <a href="mailto:support@ose.nm.gov" class="w-full bg-green-100 hover:bg-green-200 text-green-700 font-medium py-2 px-4 rounded-lg transition-colors flex items-center justify-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Contact Support</span>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">System Status</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">All systems operational</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <p>Last updated: {{ now()->format('g:i A') }}</p>
                                <p class="mt-1">Server: Online</p>
                                <p>Database: Connected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
