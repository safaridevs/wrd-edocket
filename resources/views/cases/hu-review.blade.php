<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">HU Review - {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Case Info -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Case Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><strong>Case No:</strong> {{ $case->case_no }}</div>
                    <div><strong>Type:</strong> {{ ucfirst($case->case_type) }}</div>
                    <div><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $case->status)) }}</div>
                    <div><strong>Submitted:</strong> {{ $case->submitted_at?->format('M j, Y g:i A') }}</div>
                </div>
                <div class="mt-4">
                    <strong>Caption:</strong>
                    <p class="mt-1">{{ $case->caption }}</p>
                </div>
            </div>

            <!-- Validation Checklist -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Validation Checklist</h3>
                <div class="space-y-2">
                    @php
                        $requiredDocs = ['application', 'notice_publication', 'request_to_docket'];
                        $hasApplication = $case->documents->where('doc_type', 'application')->count() > 0;
                        $hasNotice = $case->documents->where('doc_type', 'notice_publication')->count() > 0;
                        $hasRequest = $case->documents->where('doc_type', 'request_to_docket')->count() > 0;
                        $namingOk = $case->documents->where('naming_compliant', false)->count() === 0;
                        $allPdfs = $case->documents->where('mime', '!=', 'application/pdf')->where('doc_type', '!=', 'notice_publication')->count() === 0;
                    @endphp
                    
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasApplication ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasApplication ? '✓' : '✗' }}
                        </span>
                        Application PDF Present
                    </div>
                    
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasNotice ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasNotice ? '✓' : '✗' }}
                        </span>
                        Notice of Publication (Word) Present
                    </div>
                    
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasRequest ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasRequest ? '✓' : '✗' }}
                        </span>
                        Request to Docket PDF Present
                    </div>
                    
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $namingOk ? 'bg-green-500' : 'bg-yellow-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $namingOk ? '✓' : '!' }}
                        </span>
                        Filename Convention {{ $namingOk ? 'Compliant' : 'Issues' }}
                    </div>
                    
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $allPdfs ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $allPdfs ? '✓' : '✗' }}
                        </span>
                        PDF Types Valid
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Documents ({{ $case->documents->count() }})</h3>
                <div class="space-y-2">
                    @foreach($case->documents as $doc)
                    <div class="flex items-center justify-between p-3 border rounded">
                        <div>
                            <div class="font-medium">{{ $doc->original_filename }}</div>
                            <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $doc->doc_type)) }} • {{ number_format($doc->size_bytes / 1024, 1) }} KB</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($doc->naming_compliant)
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Naming OK</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Naming Issue</span>
                            @endif
                            @if($doc->stamped)
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Stamped</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            @if($case->status === 'submitted_to_hu' && auth()->user()->canAcceptFilings())
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Actions</h3>
                <div class="flex space-x-4">
                    <form method="POST" action="{{ route('cases.accept', $case) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                            Accept & Apply Stamps
                        </button>
                    </form>
                    
                    <button onclick="showRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        Reject
                    </button>
                    
                    <button onclick="showFixModal()" class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600">
                        Request Fix
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg max-w-md w-full">
            <h3 class="text-lg font-medium mb-4">Reject Case</h3>
            <form method="POST" action="{{ route('cases.reject', $case) }}">
                @csrf
                <textarea name="reason" placeholder="Reason for rejection..." required class="w-full border-gray-300 rounded-md mb-4" rows="3"></textarea>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideRejectModal()" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal() { document.getElementById('rejectModal').classList.remove('hidden'); }
        function hideRejectModal() { document.getElementById('rejectModal').classList.add('hidden'); }
        function showFixModal() { alert('Request Fix functionality - return to ALU with comments'); }
    </script>
</x-app-layout>