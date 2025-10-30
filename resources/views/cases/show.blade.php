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
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $attorney->name }}</span>
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

                        <strong class="mt-3 block">Assigned Hydrology Experts:</strong>
                        <div class="text-sm mt-1">
                            @if($case->hydrologyExperts->count() > 0)
                                @foreach($case->hydrologyExperts as $expert)
                                    <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $expert->name }}</span>
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

                        <strong class="mt-3 block">Assigned ALU Clerks:</strong>
                        <div class="text-sm mt-1">
                            @if($case->aluClerks->count() > 0)
                                @foreach($case->aluClerks as $clerk)
                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $clerk->name }}</span>
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

                        <strong class="mt-3 block">Assigned WRDs:</strong>
                        <div class="text-sm mt-1">
                            @if($case->wrds->count() > 0)
                                @foreach($case->wrds as $wrd)
                                    <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $wrd->name }}</span>
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
                    <form method="POST" action="{{ route('cases.update', $case) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="case_type" value="{{ $case->case_type }}">
                        <input type="hidden" name="caption" value="{{ $case->caption }}">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="affirmation" value="1">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition-colors" onclick="return confirm('Submit this case to Hearing Unit for review?')">
                            Submit to HU
                        </button>
                    </form>
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
                        @foreach($case->parties as $index => $party)
                        <div class="border rounded-lg mb-3 bg-gray-50">
                            <div class="p-3 cursor-pointer" onclick="togglePartyDetails({{ $index }})">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">{{ $party->person->full_name }}</div>
                                        <div class="text-sm text-gray-600">{{ ucfirst($party->role) }}</div>
                                        <div class="text-xs text-gray-500">{{ $party->person->email }}</div>
                                        @if($party->representation === 'attorney' && $party->attorney)
                                            <div class="text-xs text-blue-600 mt-1">
                                                <span class="bg-blue-100 px-2 py-1 rounded">Represented by: {{ $party->attorney->name }}</span>
                                            </div>
                                        @elseif($party->representation === 'self')
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="bg-gray-100 px-2 py-1 rounded">Self-Represented</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($party->representation === 'attorney')
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

                                    @if($party->representation === 'attorney' && $party->attorney)
                                        <div class="md:col-span-2 mt-3 pt-3 border-t">
                                            <strong class="text-blue-700">Attorney Information:</strong>
                                            <div class="mt-2 bg-blue-50 p-3 rounded">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div><strong>Name:</strong> {{ $party->attorney->name }}</div>
                                                    <div><strong>Email:</strong> {{ $party->attorney->email }}</div>
                                                    @if($party->attorney->phone)
                                                        <div><strong>Phone:</strong> {{ $party->attorney->phone }}</div>
                                                    @endif
                                                    @if($party->attorney->bar_number)
                                                        <div><strong>Bar Number:</strong> {{ $party->attorney->bar_number }}</div>
                                                    @endif
                                                </div>
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

            <!-- Documents -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Documents ({{ $case->documents->count() }})</h3>
                    <div class="flex space-x-2">
                        @if(auth()->user()->canWriteCase() || auth()->user()->isHearingUnit())
                        <a href="{{ route('cases.documents.manage', $case) }}" class="bg-purple-500 text-white px-4 py-2 rounded-md text-sm hover:bg-purple-600">{{ (auth()->user()->canCreateCase() && !in_array($case->status, ['draft', 'rejected'])) ? 'View Documents' : 'Manage Documents' }}</a>
                        @endif
                        @if($case->status === 'active' && auth()->user()->canFileToCase() && auth()->user()->canAccessCase($case))
                        <a href="{{ route('documents.file', $case) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm">File Document</a>
                        @endif
                        @if((in_array($case->status, ['draft', 'rejected']) && auth()->user()->canCreateCase()) || auth()->user()->isHearingUnit() || (in_array($case->status, ['active', 'approved']) && auth()->user()->canUploadDocuments() && auth()->user()->canAccessCase($case)))
                        <button onclick="showUploadModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">Upload Documents</button>
                        @endif
                        @if($case->status === 'active' && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
                        <button onclick="showApproveModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">Approve Case</button>
                        <button onclick="showRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm hover:bg-red-600">Reject Case</button>
                        @elseif($case->status === 'approved')
                        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-md text-sm font-medium">✓ Case Approved</span>
                        @endif
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex space-x-4">
                    <select id="docTypeFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        <option value="application">Application</option>
                        <option value="filing_other">Filing</option>
                        <option value="order">Order</option>
                        <option value="hearing_video">Hearing Media</option>
                    </select>
                    <select id="statusFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="stamped">Stamped</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>

                <div class="space-y-2">
                    @foreach($case->documents->sortByDesc('uploaded_at') as $doc)
                    <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50"
                         data-doc-type="{{ $doc->doc_type }}"
                         data-status="{{ $doc->stamped ? 'stamped' : ($doc->approved ? 'approved' : 'pending') }}">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="font-medium">{{ $doc->original_filename }}</div>
                                <div class="flex space-x-2">
                                    @if($doc->stamped)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded" title="Stamped on {{ $doc->stamped_at?->format('M j, Y g:i A') }}">📋 E-Stamped</span>
                                    @endif
                                    @if($doc->approved)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">✓ Approved</span>
                                    @endif
                                    @if(in_array($doc->pleading_type, ['request_to_docket', 'request_for_pre_hearing']))
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
                            @if(auth()->user()->role === 'hu_admin' && !$doc->stamped && in_array($doc->pleading_type, ['request_to_docket', 'request_for_pre_hearing']) && $doc->mime === 'application/pdf')
                            <button onclick="stampDocument({{ $doc->id }})" class="text-green-600 hover:text-green-800 text-sm" title="Apply E-Stamp">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </button>
                            @endif
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
                                <strong>{{ $log->user->name }}</strong>
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



        // Document filtering
        document.getElementById('docTypeFilter').addEventListener('change', filterDocuments);
        document.getElementById('statusFilter').addEventListener('change', filterDocuments);

        function filterDocuments() {
            const typeFilter = document.getElementById('docTypeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const docs = document.querySelectorAll('[data-doc-type]');

            docs.forEach(doc => {
                const docType = doc.getAttribute('data-doc-type');
                const docStatus = doc.getAttribute('data-status');

                const typeMatch = !typeFilter || docType === typeFilter;
                const statusMatch = !statusFilter || docStatus === statusFilter;

                doc.style.display = (typeMatch && statusMatch) ? 'flex' : 'none';
            });
        }

        function stampDocument(docId) {
            if (confirm('Apply stamp to this document?')) {
                fetch(`/documents/${docId}/stamp`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }})
                    .then(() => location.reload());
            }
        }

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
    </script>

    <!-- Approve Case Modal -->
    @if($case->status === 'active' && in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
    <div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Approve Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">The following persons will be notified of the case approval:</p>

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
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Approve & Notify All</button>
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

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Upload Document</h3>
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
                                    <option value="{{ $docType->code }}">{{ $docType->name }}</option>
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
                                Upload Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
