<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Case Header -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        @php
                            $applicantNames = $case->parties
                                ->where('role', 'applicant')
                                ->map(fn($party) => $party->person->full_name)
                                ->filter()
                                ->values();

                            $displayApplicants = '';
                            if ($applicantNames->count() === 1) {
                                $displayApplicants = $applicantNames[0];
                            } elseif ($applicantNames->count() === 2) {
                                $displayApplicants = $applicantNames[0] . ' and ' . $applicantNames[1];
                            } elseif ($applicantNames->count() > 2) {
                                $displayApplicants = $applicantNames[0] . ' and ' . $applicantNames[1] . ' et al.';
                            }
                        @endphp
                        @if($displayApplicants)
                            <div class="text-xl font-semibold text-gray-900 mb-1">
                                {{ $displayApplicants }}
                            </div>
                        @endif
                        <h3 class="text-lg font-medium">{{ $case->case_no }}</h3>
                        <p class="text-sm text-gray-600">{{ ucfirst($case->case_type) }} Case</p>
                        <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full
                            {{ $case->status === 'active' ? 'bg-green-100 text-green-800' :
                               ($case->status === 'draft' ? 'bg-gray-100 text-gray-800' :
                               ($case->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                            {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                        </span>
                    </div>
                    <div>
                        <strong>Key Dates:</strong>
                        <div class="text-sm mt-1 space-y-1">
                            <div>Created: {{ $case->created_at->format('M j, Y g:i A') }}</div>
                            @if($case->submitted_at)
                                <div class="text-blue-600">Submitted: {{ $case->submitted_at->format('M j, Y g:i A') }}</div>
                            @endif
                            @if($case->accepted_at)
                                <div class="text-green-600">Accepted: {{ $case->accepted_at->format('M j, Y g:i A') }}</div>
                            @endif
                            @if($case->closed_at)
                                <div class="text-gray-600">Closed: {{ $case->closed_at->format('M j, Y g:i A') }}</div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <strong>Assigned ALU Attorneys:</strong>
                        <div class="text-sm mt-1">
                            @if($case->aluAttorneys->count() > 0)
                                @foreach($case->aluAttorneys as $attorney)
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $attorney->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-attorney', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->aluAttorneys->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>



                        <strong class="mt-3 block">Assigned ALU Clerks:</strong>
                        <div class="text-sm mt-1">
                            @if($case->aluClerks->count() > 0)
                                @foreach($case->aluClerks as $clerk)
                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $clerk->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-alu-clerk', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->aluClerks->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>

                        @if(auth()->user()->canAssignAttorneys() || auth()->user()->isHearingUnit())
                        <strong class="mt-3 block">Assigned WRD Experts:</strong>
                        <div class="text-sm mt-1">
                            @if($case->wrds->count() > 0)
                                @foreach($case->wrds as $wrd)
                                    <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $wrd->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-wrd', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->wrds->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>
                          <strong class="mt-3 block">Assigned Hydrology Experts:</strong>
                        <div class="text-sm mt-1">
                            @if($case->hydrologyExperts->count() > 0)
                                @foreach($case->hydrologyExperts as $expert)
                                    <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $expert->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignHydrologyExperts())
                                <a href="{{ route('cases.assign-hydrology-expert', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->hydrologyExperts->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-4">
                    <strong>Caption:</strong>
                    <p class="mt-1 text-sm">{{ $case->caption }}</p>
                </div>

                @if($case->status === 'rejected' && isset($case->metadata['rejection_reason']))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <h4 class="font-medium text-red-800 mb-2">❌ Case Rejected by HU</h4>
                    <p class="text-sm text-red-700">{{ $case->metadata['rejection_reason'] }}</p>
                    <p class="text-xs text-red-600 mt-2">Please make the necessary corrections and resubmit.</p>
                </div>
                @endif

                @if(auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected']))
                <div class="mt-4 flex space-x-3">
                    <a href="{{ route('cases.edit', $case) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
                        {{ $case->status === 'rejected' ? 'Fix & Resubmit Case' : 'Edit Case' }}
                    </a>
                    @if($case->status === 'draft' && auth()->user()->canSubmitToHU())
                    <button onclick="showSubmitModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
                        Submit to HU
                    </button>
                    @endif
                </div>
                @endif
            </div>

            <!-- Parties & Service List -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Parties & Service List</h3>
                    @if(auth()->user()->canCreateCase() || auth()->user()->isHearingUnit())
                        <a href="{{ route('cases.parties.manage', $case) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
                            {{ (auth()->user()->canCreateCase() && !in_array($case->status, ['draft', 'rejected'])) ? 'View Parties' : 'Manage Parties' }}
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium mb-2">Case Parties</h4>
                        @php
                            $sortedParties = $case->parties->whereNotIn('role', ['counsel', 'paralegal'])->sortBy(function($party) {
                                $order = ['applicant' => 1, 'protestant' => 2, 'respondent' => 3, 'intervenor' => 4];
                                return $order[$party->role] ?? 99;
                            });
                        @endphp
                        @foreach($sortedParties as $index => $party)
                        <div class="border rounded-lg mb-3 bg-gray-50">
                            <div class="p-3 cursor-pointer" onclick="togglePartyDetails({{ $index }})">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">{{ $party->person->full_name }}</div>
                                        <div class="text-sm text-gray-600">{{ ucfirst($party->role) }}</div>
                                        <div class="text-xs text-gray-500">{{ $party->person->email }}</div>
                                        @php
                                            $hasAttorney = $party->attorneys->count() > 0;
                                        @endphp
                                        @if($hasAttorney)
                                            <div class="text-xs text-blue-600 mt-1">
                                                <span class="bg-blue-100 px-2 py-1 rounded">Represented by Attorney</span>
                                            </div>
                                        @elseif($party->role !== 'paralegal')
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="bg-gray-100 px-2 py-1 rounded">Self-Represented</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($hasAttorney)
                                            <button onclick="event.stopPropagation(); manageAttorney({{ $party->id }})" class="text-xs text-green-600 hover:text-green-800">Attorney</button>
                                        @endif
                                        <svg class="w-4 h-4 text-gray-400 transform transition-transform party-chevron-{{ $index }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div id="party-details-{{ $index }}" class="hidden px-3 pb-3 border-t bg-white">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-sm">
                                    @if($party->person->type === 'individual')
                                        @if($party->person->title)
                                            <div><strong>Title:</strong> {{ $party->person->title }}</div>
                                        @endif
                                        @if($party->person->prefix || $party->person->middle_name || $party->person->suffix)
                                            <div><strong>Full Name:</strong> {{ trim($party->person->prefix . ' ' . $party->person->first_name . ' ' . $party->person->middle_name . ' ' . $party->person->last_name . ' ' . $party->person->suffix) }}</div>
                                        @endif
                                    @else
                                        @if($party->person->organization)
                                            <div><strong>Organization:</strong> {{ $party->person->organization }}</div>
                                        @endif
                                        @if($party->person->title)
                                            <div><strong>Title:</strong> {{ $party->person->title }}</div>
                                        @endif
                                    @endif

                                    @if($party->person->phone_mobile)
                                        <div><strong>Mobile:</strong> {{ $party->person->phone_mobile }}</div>
                                    @endif
                                    @if($party->person->phone_office)
                                        <div><strong>Office:</strong> {{ $party->person->phone_office }}</div>
                                    @endif

                                    @if($party->person->address_line1 || $party->person->city || $party->person->state)
                                        <div class="md:col-span-2">
                                            <strong>Address:</strong>
                                            <div class="mt-1">
                                                @if($party->person->address_line1)
                                                    <div>{{ $party->person->address_line1 }}</div>
                                                @endif
                                                @if($party->person->address_line2)
                                                    <div>{{ $party->person->address_line2 }}</div>
                                                @endif
                                                @if($party->person->city || $party->person->state || $party->person->zip)
                                                    <div>{{ $party->person->city }}{{ $party->person->city && ($party->person->state || $party->person->zip) ? ', ' : '' }}{{ $party->person->state }} {{ $party->person->zip }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if($hasAttorney)
                                        <div class="md:col-span-2 mt-3 pt-3 border-t">
                                            <strong class="text-blue-700">Attorney Information:</strong>
                                            <div class="mt-2 bg-blue-50 p-3 rounded">
                                                @foreach($party->attorneys as $attorneyParty)
                                                    @php
                                                        $attorney = \App\Models\Attorney::where('email', $attorneyParty->person->email)->first();
                                                        $attorneyParalegals = $case->parties->where('role', 'paralegal')->where('client_party_id', $party->id);
                                                    @endphp
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-3 last:mb-0">
                                                        <div><strong>Name:</strong> {{ $attorneyParty->person->full_name }}</div>
                                                        <div><strong>Email:</strong> {{ $attorneyParty->person->email }}</div>
                                                        @if($attorneyParty->person->phone_office)
                                                            <div><strong>Phone:</strong> {{ $attorneyParty->person->phone_office }}</div>
                                                        @endif
                                                        @if($attorney && $attorney->bar_number)
                                                            <div><strong>Bar Number:</strong> {{ $attorney->bar_number }}</div>
                                                        @endif
                                                    </div>
                                                    @if($attorneyParalegals->isNotEmpty())
                                                        <div class="mt-3 pt-3 border-t border-blue-200">
                                                            <strong class="text-purple-700 text-sm">Paralegals:</strong>
                                                            <div class="mt-2 space-y-2">
                                                                @foreach($attorneyParalegals as $paralegal)
                                                                <div class="bg-purple-50 p-2 rounded text-sm">
                                                                    <div class="font-medium">{{ $paralegal->person->full_name }}</div>
                                                                    <div class="text-xs text-gray-600">{{ $paralegal->person->email }}</div>
                                                                    @if($paralegal->person->phone_office)
                                                                        <div class="text-xs text-gray-600">Phone: {{ $paralegal->person->phone_office }}</div>
                                                                    @endif
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if($case->parties->isEmpty())
                            <p class="text-gray-500 text-sm">No parties assigned</p>
                        @endif

                        @php
                            $userIsCounsel = auth()->user()->role === 'party' && $case->parties->where('role', 'counsel')->filter(function($party) {
                                return $party->person->email === auth()->user()->email;
                            })->isNotEmpty();

                            $counselParty = $userIsCounsel ? $case->parties->where('role', 'counsel')->filter(function($party) {
                                return $party->person->email === auth()->user()->email;
                            })->first() : null;

                            $userParalegals = $counselParty ? $case->parties->where('role', 'paralegal')->filter(function($p) use ($counselParty) {
                                return $p->client_party_id === $counselParty->client_party_id;
                            }) : collect();
                        @endphp

                        @if($userIsCounsel)
                        <div class="mt-4 border-t pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <h5 class="font-medium text-sm">My Paralegals</h5>
                                <button onclick="showAddParalegalModal()" class="text-xs bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">+ Add Paralegal</button>
                            </div>
                            @if($userParalegals->isEmpty())
                                <p class="text-gray-500 text-xs">No paralegals added</p>
                            @else
                                @foreach($userParalegals as $paralegal)
                                <div class="flex justify-between items-center py-2 border-b text-sm">
                                    <div>
                                        <div class="font-medium">{{ $paralegal->person->full_name }}</div>
                                        <div class="text-xs text-gray-600">{{ $paralegal->person->email }}</div>
                                    </div>
                                    <button onclick="removeParalegal({{ $paralegal->id }})" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                @endforeach
                            @endif
                        </div>
                        @endif
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">Service List</h4>
                        @foreach($case->serviceList as $service)
                        <div class="py-2 border-b">
                            <div class="font-medium">{{ $service->person->full_name }}</div>
                            <div class="text-sm text-gray-600">{{ $service->email }} • {{ ucfirst($service->service_method) }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- OSE File Numbers -->
            @if($case->oseFileNumbers->count() > 0)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">OSE File Numbers</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($case->oseFileNumbers as $ose)
                    <div class="border rounded p-3">
                        <div class="text-sm">{{ $ose->file_no_from }}{{ $ose->file_no_to ? ' - ' . $ose->file_no_to : '' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- HU Validation Checklist -->
            @if($case->status === 'submitted_to_hu' && auth()->user()->isHearingUnit())
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">HU Validation Checklist</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $hasApplication = $case->documents->where('doc_type', 'application')->count() > 0;
                        $hasRequest = $case->documents->whereIn('pleading_type', ['request_to_docket', 'request_pre_hearing'])->count() > 0;
                        $namingOk = $case->documents->filter(function($doc) {
                            return !preg_match('/^\d{4}-\d{2}-\d{2} - .+/', $doc->original_filename);
                        })->count() === 0;
                        $allPdfs = $case->documents->where('mime', '!=', 'application/pdf')->where('doc_type', '!=', 'notice_publication')->count() === 0;
                    @endphp

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasApplication ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasApplication ? '✓' : '✗' }}
                        </span>
                        Application PDF Present
                    </div>

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasRequest ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasRequest ? '✓' : '✗' }}
                        </span>
                        Pleading Document Present
                    </div>

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $namingOk ? 'bg-green-500' : 'bg-yellow-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $namingOk ? '✓' : '!' }}
                        </span>
                        Filename Convention {{ $namingOk ? 'Compliant' : 'Issues' }}
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800">
                        <strong>Next Steps:</strong> After reviewing the checklist, go to the Documents section below to Accept or Reject the case and manage individual documents.
                    </p>
                </div>
            </div>
            @endif

            <!-- Documents -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Documents ({{ $case->documents->count() }})</h3>
                    <div class="flex space-x-2">
                        @if(auth()->user()->canWriteCase() || auth()->user()->isHearingUnit())
                        <a href="{{ route('cases.documents.manage', $case) }}" class="bg-purple-500 text-white px-4 py-2 rounded-md text-sm hover:bg-purple-600">{{ (auth()->user()->canCreateCase() && !in_array($case->status, ['draft', 'rejected'])) ? 'View Documents' : 'Manage Documents' }}</a>
                        @endif
                        @if(!in_array($case->status, ['closed', 'archived']))
                            @if($case->status === 'active' && auth()->user()->canFileToCase() && auth()->user()->canAccessCase($case))
                            <a href="{{ route('documents.file', $case) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm">File Document</a>
                            @endif
                            @if((in_array($case->status, ['draft', 'rejected']) && auth()->user()->canCreateCase()) || auth()->user()->isHearingUnit() || (in_array($case->status, ['active', 'approved']) && auth()->user()->canUploadDocuments() && auth()->user()->canAccessCase($case)))
                            <button onclick="showUploadModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">File Documents</button>
                            @endif
                        @endif
                        @if($case->status === 'submitted_to_hu' && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
                        <button onclick="showApproveModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">Accept Case</button>
                        <button onclick="showRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm hover:bg-red-600">Reject Case</button>
                        @elseif($case->status === 'approved')
                        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-md text-sm font-medium">✓ Case Accepted</span>
                        @endif

                        @if(in_array($case->status, ['active', 'approved']) && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
                        <button onclick="showCloseModal()" class="bg-orange-500 text-white px-4 py-2 rounded-md text-sm hover:bg-orange-600">Close Case</button>
                        @elseif($case->status === 'closed')
                        <span class="bg-orange-100 text-orange-800 px-4 py-2 rounded-md text-sm font-medium">📁 Case Closed</span>
                        @if(auth()->user()->role === 'hu_admin')
                        <button onclick="showReopenModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">Reopen Case</button>
                        @endif
                        @if(in_array(auth()->user()->role, ['hu_admin', 'admin']))
                        <button onclick="archiveCase()" class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-600">Archive Case</button>
                        @endif
                        @elseif($case->status === 'archived')
                        <span class="bg-gray-100 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">📦 Case Archived</span>
                        @endif
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="mb-4 flex flex-wrap gap-4">
                    <input type="text" id="searchInput" placeholder="Search documents..." class="border-gray-300 rounded-md text-sm flex-1 min-w-64">
                    <select id="docTypeFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        @php
                            $documentTypes = \App\Models\DocumentType::where('is_active', true)->orderBy('name')->get();
                        @endphp
                        @foreach($documentTypes as $docType)
                            <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                        @endforeach
                    </select>
                    <select id="statusFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="stamped">E-Stamped</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="perPageSelect" class="border-gray-300 rounded-md text-sm">
                        <option value="10">10 per page</option>
                        <option value="25" selected>25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>

                <div id="documentsContainer" class="space-y-2">
                    @foreach($case->documents->sortByDesc('uploaded_at') as $doc)
                    <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50 document-item"
                         data-doc-type="{{ $doc->doc_type }}"
                         data-status="{{ $doc->stamped ? 'stamped' : ($doc->approved ? 'accepted' : ($doc->rejected_reason ? 'rejected' : 'pending')) }}"
                         data-filename="{{ strtolower($doc->original_filename) }}">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="font-medium">{{ $doc->original_filename }}</div>
                                <div class="flex space-x-2">
                                    @if($doc->stamped)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded" title="Stamped on {{ $doc->stamped_at?->format('M j, Y g:i A') }}">📋 E-Stamped</span>
                                    @endif
                                    @if($doc->approved)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">✓ Accepted</span>
                                    @endif
                                    @if(in_array($doc->pleading_type, ['request_to_docket', 'request_pre_hearing']))
                                        <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">Pleading Document</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ ucfirst(str_replace('_', ' ', $doc->doc_type)) }}
                                @if($doc->pleading_type)
                                    • {{ ucfirst(str_replace('_', ' ', $doc->pleading_type)) }}
                                @endif
                                • {{ number_format($doc->size_bytes / 1024, 1) }} KB •
                                {{ $doc->uploaded_at->format('M j, Y g:i A') }}
                                @if($doc->stamped && $doc->stamped_at)
                                    <br><span class="text-blue-600">E-Stamped: {{ $doc->stamped_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('documents.preview', $doc) }}" target="_blank" class="text-gray-600 hover:text-gray-800 text-sm" title="Preview">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="{{ route('documents.download', $doc) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>

                            @if(auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected']))
                            <button onclick="deleteDocument({{ $doc->id }})" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div id="paginationContainer" class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Showing <span id="showingFrom">1</span> to <span id="showingTo">25</span> of <span id="totalDocs">{{ $case->documents->count() }}</span> documents
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevPage" class="px-3 py-1 border rounded text-sm" disabled>Previous</button>
                        <span id="pageNumbers" class="flex space-x-1"></span>
                        <button id="nextPage" class="px-3 py-1 border rounded text-sm">Next</button>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Audit Trail</h3>
                <div class="space-y-3">
                    @foreach($case->auditLogs->sortByDesc('created_at') as $log)
                    <div class="flex items-start space-x-3 py-2 border-b">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm">
                                <strong>{{ $log->user->getDisplayName() }}</strong>
                                {{ str_replace('_', ' ', $log->action) }}
                            </div>
                            <div class="text-xs text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</div>
                            @if($log->meta_json)
                            <div class="text-xs text-gray-600 mt-1">{{ json_encode($log->meta_json) }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>



    <!-- Attorney Management Modal -->
    <div id="attorneyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Manage Attorney Representation</h3>
                    <div id="attorneyContent">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Party details toggle
        function togglePartyDetails(index) {
            const details = document.getElementById(`party-details-${index}`);
            const chevron = document.querySelector(`.party-chevron-${index}`);

            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                details.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }



        // Document filtering and pagination
        let currentPage = 1;
        let itemsPerPage = 25;
        let filteredDocs = [];

        document.getElementById('docTypeFilter').addEventListener('change', filterAndPaginate);
        document.getElementById('statusFilter').addEventListener('change', filterAndPaginate);
        document.getElementById('searchInput').addEventListener('input', filterAndPaginate);
        document.getElementById('perPageSelect').addEventListener('change', function() {
            itemsPerPage = parseInt(this.value);
            currentPage = 1;
            filterAndPaginate();
        });

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                paginateDocuments();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            const totalPages = Math.ceil(filteredDocs.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                paginateDocuments();
            }
        });

        function filterAndPaginate() {
            const typeFilter = document.getElementById('docTypeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const docs = document.querySelectorAll('.document-item');

            filteredDocs = [];
            docs.forEach(doc => {
                const docType = doc.getAttribute('data-doc-type');
                const docStatus = doc.getAttribute('data-status');
                const filename = doc.getAttribute('data-filename');

                const typeMatch = !typeFilter || docType === typeFilter;
                const statusMatch = !statusFilter || docStatus === statusFilter;
                const searchMatch = !searchTerm || filename.includes(searchTerm);

                if (typeMatch && statusMatch && searchMatch) {
                    filteredDocs.push(doc);
                }
            });

            currentPage = 1;
            paginateDocuments();
        }

        function paginateDocuments() {
            const docs = document.querySelectorAll('.document-item');
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const totalPages = Math.ceil(filteredDocs.length / itemsPerPage);

            // Hide all documents
            docs.forEach(doc => doc.style.display = 'none');

            // Show current page documents
            filteredDocs.slice(startIndex, endIndex).forEach(doc => {
                doc.style.display = 'flex';
            });

            // Update pagination info
            document.getElementById('showingFrom').textContent = filteredDocs.length > 0 ? startIndex + 1 : 0;
            document.getElementById('showingTo').textContent = Math.min(endIndex, filteredDocs.length);
            document.getElementById('totalDocs').textContent = filteredDocs.length;

            // Update pagination buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;

            // Update page numbers
            const pageNumbers = document.getElementById('pageNumbers');
            pageNumbers.innerHTML = '';
            for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `px-2 py-1 border rounded text-sm ${i === currentPage ? 'bg-blue-500 text-white' : ''}`;
                pageBtn.onclick = () => {
                    currentPage = i;
                    paginateDocuments();
                };
                pageNumbers.appendChild(pageBtn);
            }
        }

        // Initialize pagination
        filterAndPaginate();



        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                fetch(`/cases/{{ $case->id }}/documents/${docId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete document');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete document');
                });
            }
        }

        function showApproveModal() {
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function hideApproveModal() {
            document.getElementById('approveModal').classList.add('hidden');
        }

        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        function showCloseModal() {
            document.getElementById('closeModal').classList.remove('hidden');
        }

        function hideCloseModal() {
            document.getElementById('closeModal').classList.add('hidden');
        }

        function archiveCase() {
            if (confirm('Are you sure you want to archive this case? This action cannot be undone.')) {
                fetch('{{ route('cases.archive', $case) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to archive case');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to archive case');
                });
            }
        }

        function showSubmitModal() {
            document.getElementById('submitModal').classList.remove('hidden');
        }

        function hideSubmitModal() {
            document.getElementById('submitModal').classList.add('hidden');
        }

        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.notify-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Attorney Management
        function manageAttorney(partyId) {
            fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('attorneyContent').innerHTML = html;
                    document.getElementById('attorneyModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load attorney management');
                });
        }

        function hideAttorneyModal() {
            document.getElementById('attorneyModal').classList.add('hidden');
        }

        function removeAttorney(partyId) {
            if (confirm('Remove attorney representation for this party?')) {
                fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove attorney');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove attorney');
                });
            }
        }

        function assignAttorney(partyId, formData) {
            fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to assign attorney');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to assign attorney');
            });
        }

        function showUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function hideUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.getElementById('uploadForm').reset();
        }

        function showReopenModal() {
            document.getElementById('reopenModal').classList.remove('hidden');
        }

        function hideReopenModal() {
            document.getElementById('reopenModal').classList.add('hidden');
        }

        // Attorney modal functions
        window.toggleAttorneyFields = function() {
            const option = document.querySelector('#attorneyModal input[name="attorney_option"]:checked')?.value;
            const existingSelect = document.querySelector('#attorneyModal select[name="attorney_id"]');
            const newFields = document.getElementById('newAttorneyFields');
            const newInputs = newFields?.querySelectorAll('input') || [];

            if (option === 'existing') {
                existingSelect.disabled = false;
                newFields?.classList.add('opacity-50');
                newInputs.forEach(input => input.disabled = true);
            } else if (option === 'new') {
                existingSelect.disabled = true;
                existingSelect.value = '';
                newFields?.classList.remove('opacity-50');
                newInputs.forEach(input => input.disabled = false);
            }
        };

        window.handleAttorneyForm = function(event, partyId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            assignAttorney(partyId, formData);
        };
    </script>

    <!-- Approve Case Modal -->
    @if(in_array($case->status, ['submitted_to_hu', 'active']) && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
    <div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Accept Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">The following persons will be notified of the case acceptance:</p>

                    <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                        <h4 class="font-medium text-sm mb-2">Case Parties:</h4>
                        @foreach($case->parties as $party)
                        <div class="text-sm py-1">
                            • {{ $party->person->full_name }} ({{ ucfirst($party->role) }}) - {{ $party->person->email }}
                        </div>
                        @endforeach

                        @if($case->assignedAttorney)
                        <h4 class="font-medium text-sm mt-3 mb-2">Assigned Attorney:</h4>
                        <div class="text-sm py-1">
                            • {{ $case->assignedAttorney->name }} - {{ $case->assignedAttorney->email }}
                        </div>
                        @endif

                        @if($case->assignedHydrologyExpert)
                        <h4 class="font-medium text-sm mt-3 mb-2">Hydrology Expert:</h4>
                        <div class="text-sm py-1">
                            • {{ $case->assignedHydrologyExpert->name }} - {{ $case->assignedHydrologyExpert->email }}
                        </div>
                        @endif
                    </div>

                    <form action="{{ route('cases.approve', $case) }}" method="POST">
                        @csrf
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideApproveModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Accept & Notify All</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Case Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Reject Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">The following persons will be notified to make corrections:</p>

                    <div class="bg-red-50 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-sm mb-2">ALU Staff (for corrections):</h4>
                        <div class="text-sm py-1">
                            • {{ $case->creator->name }} (Case Creator) - {{ $case->creator->email }}
                        </div>
                        @if($case->assignedAttorney)
                        <div class="text-sm py-1">
                            • {{ $case->assignedAttorney->name }} (Assigned Attorney) - {{ $case->assignedAttorney->email }}
                        </div>
                        @endif
                    </div>

                    <form action="{{ route('cases.reject', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Rejection *</label>
                            <textarea name="reason" required rows="4" class="block w-full border-gray-300 rounded-md" placeholder="Please provide specific details about what needs to be corrected for resubmission..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">This reason will be sent to ALU staff for corrections.</p>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideRejectModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Reject & Notify ALU</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Close Case Modal -->
    @if(in_array($case->status, ['active', 'approved']) && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
    <div id="closeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Close Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">This will close the case and notify all parties. Closed cases can be archived later.</p>
                    <div class="mb-4 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
                        Please upload the closing letter before closing this case.
                    </div>

                    <form action="{{ route('cases.close', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Closure *</label>
                            <textarea name="reason" required rows="4" class="block w-full border-gray-300 rounded-md" placeholder="Please provide the reason for closing this case..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="flex items-start">
                                <input type="checkbox" name="closing_letter_confirmed" value="1" required class="mt-1 mr-2">
                                <span class="text-sm text-gray-700">I confirm that the closing letter has been uploaded for this case.</span>
                            </label>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideCloseModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600">Close Case</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Reopen Case Modal -->
    @if($case->status === 'closed' && auth()->user()->role === 'hu_admin')
    <div id="reopenModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Reopen Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">This will reopen the case and notify all parties.</p>

                    <form action="{{ route('cases.reopen', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Reopening *</label>
                            <textarea name="reason" required rows="4" class="block w-full border-gray-300 rounded-md" placeholder="Please provide the reason for reopening this case..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideReopenModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Reopen Case</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Submit to HU Modal -->
    @if($case->status === 'draft' && auth()->user()->canSubmitToHU())
    <div id="submitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Submit Case {{ $case->case_no }} to Hearing Unit</h3>
                    <p class="text-sm text-gray-600 mb-4">The following people will be notified about the case submission:</p>

                    <form action="{{ route('cases.update', $case) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="case_type" value="{{ $case->case_type }}">
                        <input type="hidden" name="caption" value="{{ $case->caption }}">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="affirmation" value="1">

                        <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="selectAll" class="mr-2" onchange="toggleAll()">
                                    <span class="font-medium">Select All</span>
                                </label>
                            </div>

                            <h4 class="font-medium text-sm mb-2">Case Parties:</h4>
                            @foreach($case->parties->where('role', '!=', 'counsel') as $party)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="party_{{ $party->id }}" class="mr-2 notify-checkbox" checked>
                                    <span>{{ $party->person->full_name }} ({{ ucfirst($party->role) }}) - {{ $party->person->email }}</span>
                                </label>
                            </div>
                            @endforeach

                            @php
                                $attorneys = $case->parties->where('role', 'counsel');
                            @endphp
                            @if($attorneys->count() > 0)
                            <h4 class="font-medium text-sm mt-3 mb-2">Attorneys:</h4>
                            @foreach($attorneys as $attorney)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="attorney_{{ $attorney->id }}" class="mr-2 notify-checkbox" checked>
                                    <span>{{ $attorney->person->full_name }} - {{ $attorney->person->email }}</span>
                                </label>
                            </div>
                            @endforeach
                            @endif

                            @php
                                $assignedUsers = $case->assignments->whereNotIn('assignment_type', ['hydrology_expert', 'wrd']);
                            @endphp
                            @if($assignedUsers->count() > 0)
                            <h4 class="font-medium text-sm mt-3 mb-2">Assigned Staff:</h4>
                            @foreach($assignedUsers as $assignment)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="staff_{{ $assignment->user_id }}" class="mr-2 notify-checkbox" checked>
                                    <span>{{ $assignment->user->name }} ({{ ucfirst(str_replace('_', ' ', $assignment->assignment_type)) }}) - {{ $assignment->user->email }}</span>
                                </label>
                            </div>
                            @endforeach
                            @endif
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Message (Optional)</label>
                            <textarea name="custom_message" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Add any additional information for the recipients..."></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideSubmitModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Submit to HU & Notify Selected</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">File Document</h3>
                    <form id="uploadForm" action="{{ route('cases.documents.upload', $case) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                                <select name="documents[other][0][type]" required class="block w-full border-gray-300 rounded-md">
                                    <option value="">Select document type...</option>
                                    @php
                                        $documentTypes = \App\Models\DocumentType::where('is_active', true)
                                            ->when(auth()->user()->role === 'party', function($query) {
                                                return $query->where('category', 'party_upload');
                                            })
                                            ->orderBy('sort_order')->get();
                                    @endphp
                                    @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">File *</label>
                                <input type="file" name="documents[other][0][file]" required accept=".pdf,.doc,.docx"
                                       class="block w-full border-gray-300 rounded-md">
                                <p class="text-xs text-gray-500 mt-1">Supported formats: PDF, DOC, DOCX (Max: 10MB)</p>
                                <p class="text-xs text-blue-600 mt-1">File naming convention: YYYY-MM-DD - [Document Type] - [OSE File Numbers].pdf</p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideUploadModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                                Cancel
                            </button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                File Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Paralegal Modal -->
    <div id="addParalegalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Add Paralegal</h3>
                    <form action="{{ route('cases.paralegals.add', $case) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">First Name *</label>
                                <input type="text" name="first_name" required class="w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Last Name *</label>
                                <input type="text" name="last_name" required class="w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Email *</label>
                                <input type="email" name="email" required class="w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Office Phone</label>
                                <input type="text" name="phone_office" class="w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Mobile Phone</label>
                                <input type="text" name="phone_mobile" class="w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideAddParalegalModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Add Paralegal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddParalegalModal() {
            document.getElementById('addParalegalModal').classList.remove('hidden');
        }
        function hideAddParalegalModal() {
            document.getElementById('addParalegalModal').classList.add('hidden');
        }
        function removeParalegal(partyId) {
            if (confirm('Are you sure you want to remove this paralegal?')) {
                fetch(`/cases/{{ $case->id }}/paralegals/${partyId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                }).then(response => response.json()).then(data => { location.reload(); }).catch(error => { location.reload(); });
            }
        }
    </script>

</x-app-layout>
