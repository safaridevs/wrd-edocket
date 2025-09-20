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
                               ($case->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                        </span>
                    </div>
                    <div>
                        <strong>Key Dates:</strong>
                        <div class="text-sm mt-1">
                            @if($case->submitted_at) <div>Submitted: {{ $case->submitted_at->format('M j, Y') }}</div> @endif
                            @if($case->accepted_at) <div>Accepted: {{ $case->accepted_at->format('M j, Y') }}</div> @endif
                            @if($case->closed_at) <div>Closed: {{ $case->closed_at->format('M j, Y') }}</div> @endif
                        </div>
                    </div>
                    <div>
                        <strong>Assigned ALU Attorney:</strong>
                        <div class="text-sm mt-1">{{ $case->assignee?->name ?? 'Not assigned' }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <strong>Caption:</strong>
                    <p class="mt-1 text-sm">{{ $case->caption }}</p>
                </div>
                
                @if(auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected']))
                <div class="mt-4">
                    <a href="{{ route('cases.edit', $case) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
                        Edit Case
                    </a>
                </div>
                @endif
            </div>

            <!-- Parties & Service List -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Parties & Service List</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium mb-2">Case Parties</h4>
                        @php $partyData = $case->metadata ?? []; @endphp
                        @if(isset($partyData['applicants']))
                            @foreach($partyData['applicants'] as $applicant)
                            <div class="py-2 border-b">
                                <div class="font-medium">{{ $applicant }}</div>
                                <div class="text-sm text-gray-600">Applicant</div>
                            </div>
                            @endforeach
                        @endif
                        @if(isset($partyData['protestants']))
                            @foreach($partyData['protestants'] as $protestant)
                            <div class="py-2 border-b">
                                <div class="font-medium">{{ $protestant }}</div>
                                <div class="text-sm text-gray-600">Protestant</div>
                            </div>
                            @endforeach
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
                        <div class="font-medium">{{ $ose->basin_code }}</div>
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
                    @if($case->status === 'active' && auth()->user()->canFileToCase())
                    <a href="{{ route('documents.file', $case) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm">File Document</a>
                    @endif
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
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Stamped</span>
                                    @endif
                                    @if($doc->approved)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ ucfirst(str_replace('_', ' ', $doc->doc_type)) }} • 
                                {{ number_format($doc->size_bytes / 1024, 1) }} KB • 
                                {{ $doc->uploaded_at->format('M j, Y g:i A') }}
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('documents.download', $doc) }}" class="text-blue-600 hover:text-blue-800 text-sm">Download</a>
                            @if(auth()->user()->canApplyStamp() && !$doc->stamped && $doc->mime === 'application/pdf')
                            <button onclick="stampDocument({{ $doc->id }})" class="text-green-600 hover:text-green-800 text-sm">Stamp</button>
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

    <script>
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
    </script>
</x-app-layout>