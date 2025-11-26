@php
    use Illuminate\Support\Facades\Storage;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Document Management - Case {{ $case->case_no }}
            </h2>
            <a href="{{ route('cases.show', $case) }}" class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-600">
                Back to Case
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Case Info -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-2">{{ $case->case_no }}</h3>
                <p class="text-sm text-gray-600">{{ $case->caption }}</p>
                <div class="mt-2 flex space-x-4 text-sm">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ ucfirst(str_replace('_', ' ', $case->case_type)) }}</span>
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span>
                </div>
            </div>

            <!-- Document Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ $case->documents->count() }}</div>
                    <div class="text-sm text-gray-600">Total Documents</div>
                </div>
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-600">{{ $case->documents->where('approved', true)->count() }}</div>
                    <div class="text-sm text-gray-600">Accepted</div>
                </div>
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-2xl font-bold text-yellow-600">{{ $case->documents->where('approved', false)->where('rejected_reason', null)->count() }}</div>
                    <div class="text-sm text-gray-600">Pending Review</div>
                </div>
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $case->documents->whereNotNull('rejected_reason')->count() }}</div>
                    <div class="text-sm text-gray-600">Rejected</div>
                </div>
            </div>

            <!-- Document Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Documents ({{ $case->documents->count() }})</h3>
                    <div class="flex space-x-2">
                        <button onclick="showUploadModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">
                            + File Document
                        </button>
                        <select id="filterType" onchange="filterDocuments()" class="border-gray-300 rounded-md text-sm">
                            <option value="">All Types</option>
                            @foreach($documentTypes->unique('code') as $docType)
                            <option value="{{ $docType->code }}">{{ $docType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Document List -->
                @if($case->documents->count() > 0)
                    <div class="space-y-4" id="documentList">
                        @foreach($case->documents->sortByDesc('uploaded_at') as $document)
                        <div class="border rounded-lg p-4 bg-gray-50 document-item" data-type="{{ $document->doc_type }}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-3">
                                            <h4 class="font-medium">{{ $document->original_filename }}</h4>
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ ucfirst(str_replace('_', ' ', $document->doc_type)) }}</span>
                                            
                                            @if($document->approved)
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">✓ Accepted</span>
                                            @elseif($document->rejected_reason)
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">✗ Rejected</span>
                                            @else
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">⏳ Pending</span>
                                            @endif

                                            @if($document->stamped)
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">📋 Electronically Filed</span>
                                            @endif



                                            @php
                                                $hasNamingIssue = !preg_match('/^\d{4}-\d{2}-\d{2} - .+\.pdf$/', $document->original_filename);
                                                $isRequiredDoc = in_array($document->doc_type, ['application', 'notice_publication']);
                                                $hasFileIssue = !$document->storage_uri || !Storage::disk('public')->exists($document->storage_uri);
                                            @endphp

                                            @if($hasNamingIssue)
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded" title="Document name doesn't follow naming convention: YYYY-MM-DD - [Type].pdf (OSE Numbers optional)">
                                                    ⚠️ Naming Issue
                                                </span>
                                            @endif

                                            @if($hasFileIssue)
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded" title="File is missing or corrupted">
                                                    ❌ File Issue
                                                </span>
                                            @endif

                                            @if($document->doc_type === 'notice_publication' && !$document->approved)
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded" title="Notice of Publication requires approval">
                                                    ❌ Requires Review
                                                </span>
                                            @endif
                                        </div>
                                        

                                    </div>
                                    
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div class="flex items-center space-x-4">
                                            <span>📁 {{ number_format($document->size_bytes / 1024, 1) }} KB</span>
                                            <span>📅 {{ $document->uploaded_at->format('M j, Y g:i A') }}</span>
                                            <span>👤 {{ $document->uploader->name }}</span>
                                        </div>
                                        
                                        @if($document->pleading_type && $document->pleading_type !== 'none')
                                            <div class="text-blue-600">
                                                <strong>Pleading Type:</strong> {{ ucfirst(str_replace('_', ' ', $document->pleading_type)) }}
                                            </div>
                                        @endif
                                        
                                        @if($document->rejected_reason)
                                            <div class="text-red-600 bg-red-50 p-2 rounded mt-2">
                                                <strong>Rejection Reason:</strong> {{ $document->rejected_reason }}
                                            </div>
                                        @endif
                                        
                                        @if($document->stamp_text)
                                            <div class="text-purple-600 bg-purple-50 p-2 rounded mt-2">
                                                <strong>Stamp:</strong> {{ $document->stamp_text }}
                                            </div>
                                        @endif

                                        @if($hasNamingIssue)
                                            <div class="text-yellow-600 bg-yellow-50 p-2 rounded mt-2">
                                                <strong>⚠️ Naming Convention Issue:</strong> Document should follow format: YYYY-MM-DD - [Document Type].pdf (OSE File Numbers are optional)
                                            </div>
                                        @endif

                                        @if($hasFileIssue)
                                            <div class="text-red-600 bg-red-50 p-2 rounded mt-2">
                                                <strong>❌ File Issue:</strong> Document file is missing or corrupted and needs to be re-uploaded.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2 ml-4">
                                    @if($document->storage_uri)
                                        <a href="{{ Storage::url($document->storage_uri) }}" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 text-sm bg-blue-50 px-3 py-1 rounded">
                                            📄 View
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-sm bg-gray-50 px-3 py-1 rounded">
                                            📄 No File
                                        </span>
                                    @endif
                                    
                                    @if(in_array(auth()->user()->role, ['hu_admin', 'hu_clerk']))
                                        @if(!$document->approved && !$document->rejected_reason)
                                            <button onclick="approveDocument({{ $document->id }})" 
                                                    class="text-green-600 hover:text-green-800 text-sm bg-green-50 px-3 py-1 rounded whitespace-nowrap">
                                                ✓ Accept
                                            </button>
                                            <button onclick="rejectDocument({{ $document->id }})" 
                                                    class="text-red-600 hover:text-red-800 text-sm bg-red-50 px-3 py-1 rounded whitespace-nowrap">
                                                ✗ Reject
                                            </button>
                                        @elseif($document->approved && !$document->stamped && in_array($document->pleading_type, ['request_to_docket', 'request_pre_hearing']))
                                            <button onclick="stampDocument({{ $document->id }})" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm bg-blue-50 px-3 py-1 rounded whitespace-nowrap">
                                                📋 Stamp
                                            </button>
                                        @elseif($document->rejected_reason)
                                            <button onclick="approveDocument({{ $document->id }})" 
                                                    class="text-green-600 hover:text-green-800 text-sm bg-green-50 px-3 py-1 rounded whitespace-nowrap">
                                                ✓ Accept
                                            </button>
                                        @endif
                                        
                                        @if($hasNamingIssue || $hasFileIssue)
                                            <button onclick="requestFix({{ $document->id }})" 
                                                    class="text-orange-600 hover:text-orange-800 text-sm bg-orange-50 px-3 py-1 rounded whitespace-nowrap">
                                                🔧 Request Fix
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->role === 'admin' || $document->uploaded_by_user_id === auth()->id())
                                        <button onclick="deleteDocument({{ $document->id }})" 
                                                class="text-red-600 hover:text-red-800 text-sm bg-red-50 px-3 py-1 rounded whitespace-nowrap">
                                            🗑️ Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">📄</div>
                        <p class="text-gray-500 text-lg">No documents uploaded yet</p>
                        <p class="text-gray-400 text-sm">Upload your first document to get started</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Document Action Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
                <div class="p-6">
                    <h3 id="confirmTitle" class="text-lg font-medium mb-4">Confirm Action</h3>
                    <p id="confirmMessage" class="text-gray-600 mb-4">Are you sure you want to proceed?</p>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="documentViewedCheckbox" class="mr-2">
                            <span class="text-sm">I confirm that I have viewed this document</span>
                        </label>
                    </div>
                    
                    <div id="rejectReasonSection" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for rejection:</label>
                        <textarea id="rejectReasonInput" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideConfirmModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                            Cancel
                        </button>
                        <button type="button" id="confirmActionBtn" onclick="executeAction()" disabled class="px-4 py-2 rounded-md transition-colors duration-200 bg-gray-300 text-gray-500 cursor-not-allowed">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">File Document</h3>
                    <form id="uploadForm" action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                                <select name="doc_type" required class="block w-full border-gray-300 rounded-md" onchange="togglePleadingType()">
                                    <option value="">Select document type...</option>
                                    @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->code }}" data-is-pleading="{{ $docType->is_pleading ? 'true' : 'false' }}">{{ $docType->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="pleadingTypeSection" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pleading Type</label>
                                <select name="pleading_type" class="block w-full border-gray-300 rounded-md">
                                    <option value="none">None</option>
                                    <option value="request_to_docket">Request to Docket</option>
                                    <option value="request_pre_hearing">Request for Pre-Hearing</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Files *</label>
                                <input type="file" name="document[]" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple
                                       class="block w-full border-gray-300 rounded-md" onchange="validateFiles(this)">
                                <p class="text-xs text-gray-500 mt-1">Select multiple files. Supported formats: PDF, DOC, DOCX, JPG, PNG (Max: 10MB each)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                                <textarea name="description" rows="3" class="block w-full border-gray-300 rounded-md" 
                                          placeholder="Brief description of the document..."></textarea>
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

    <script>
        function showUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function hideUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.getElementById('uploadForm').reset();
        }

        function togglePleadingType() {
            const select = document.querySelector('select[name="doc_type"]');
            const selectedOption = select.options[select.selectedIndex];
            const pleadingSection = document.getElementById('pleadingTypeSection');
            
            if (selectedOption && selectedOption.dataset.isPleading === 'true') {
                pleadingSection.classList.remove('hidden');
            } else {
                pleadingSection.classList.add('hidden');
            }
        }

        function validateFiles(input) {
            const files = Array.from(input.files);
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            for (let file of files) {
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Each file must be less than 10MB.`);
                    input.value = '';
                    return;
                }
            }
            
            if (files.length > 0) {
                const fileNames = files.map(f => f.name).join(', ');
                console.log(`Selected ${files.length} file(s): ${fileNames}`);
            }
        }

        function filterDocuments() {
            const filterType = document.getElementById('filterType').value;
            const documents = document.querySelectorAll('.document-item');
            
            documents.forEach(doc => {
                if (!filterType || doc.dataset.type === filterType) {
                    doc.style.display = 'block';
                } else {
                    doc.style.display = 'none';
                }
            });
        }

        let currentAction = null;
        let currentDocumentId = null;

        function showConfirmModal(title, message, action, documentId, isReject = false) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('documentViewedCheckbox').checked = false;
            
            const confirmBtn = document.getElementById('confirmActionBtn');
            confirmBtn.disabled = true;
            confirmBtn.className = 'px-4 py-2 rounded-md transition-colors duration-200 bg-gray-300 text-gray-500 cursor-not-allowed';
            
            document.getElementById('rejectReasonInput').value = '';
            
            const rejectSection = document.getElementById('rejectReasonSection');
            if (isReject) {
                rejectSection.classList.remove('hidden');
            } else {
                rejectSection.classList.add('hidden');
            }
            
            currentAction = action;
            currentDocumentId = documentId;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            currentAction = null;
            currentDocumentId = null;
        }

        function executeAction() {
            if (currentAction === 'approve') {
                fetch(`/cases/{{ $case->id }}/documents/${currentDocumentId}/approve`, {
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
                        alert('Failed to approve document');
                    }
                });
            } else if (currentAction === 'reject') {
                const reason = document.getElementById('rejectReasonInput').value.trim();
                if (!reason) {
                    alert('Please provide a reason for rejection');
                    return;
                }
                
                fetch(`/cases/{{ $case->id }}/documents/${currentDocumentId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to reject document');
                    }
                });
            }
            hideConfirmModal();
        }

        function approveDocument(documentId) {
            showConfirmModal(
                'Accept Document',
                'Are you sure you want to accept this document?',
                'approve',
                documentId
            );
        }

        function rejectDocument(documentId) {
            showConfirmModal(
                'Reject Document',
                'Are you sure you want to reject this document?',
                'reject',
                documentId,
                true
            );
        }

        // Enable/disable confirm button based on checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('documentViewedCheckbox');
            const confirmBtn = document.getElementById('confirmActionBtn');
            
            checkbox.addEventListener('change', function() {
                confirmBtn.disabled = !this.checked;
                if (this.checked) {
                    confirmBtn.className = 'px-4 py-2 rounded-md transition-colors duration-200 bg-blue-500 text-white hover:bg-blue-600 cursor-pointer';
                } else {
                    confirmBtn.className = 'px-4 py-2 rounded-md transition-colors duration-200 bg-gray-300 text-gray-500 cursor-not-allowed';
                }
            });
        });





        function deleteDocument(documentId) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                fetch(`/cases/{{ $case->id }}/documents/${documentId}`, {
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
                });
            }
        }

        function requestFix(documentId) {
            const reason = prompt('Please specify what needs to be fixed:');
            if (reason) {
                fetch(`/cases/{{ $case->id }}/documents/${documentId}/request-fix`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Fix request sent successfully');
                        location.reload();
                    } else {
                        alert('Failed to send fix request');
                    }
                });
            }
        }

        function stampDocument(documentId) {
            if (confirm('Are you sure you want to apply electronic stamp to this document?')) {
                fetch(`/cases/{{ $case->id }}/documents/${documentId}/stamp`, {
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
                        alert('Failed to stamp document');
                    }
                });
            }
        }
    </script>
</x-app-layout>