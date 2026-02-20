<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upload Documents - Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium">Upload Case Documents</h3>
                    <p class="text-sm text-gray-600 mt-1">Upload required documents for case {{ $case->case_no }}. Only PDF files are accepted.</p>
                </div>

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Upload Failed</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    


                    @if(auth()->user()->role !== 'party')
                    <!-- Core Documents -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Core Documents</h4>
                        <div class="space-y-4">
                            <!-- Application -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Application (PDF)</label>
                                <input type="file" name="documents[application][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-gray-500 mt-1">Upload multiple files - will be renamed to: YYYY-MM-DD Application.pdf</p>
                                @error('documents.application')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.application.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Request to Docket -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Request to Docket (PDF) - Pleading Document</label>
                                <input type="file" name="documents[request_to_docket][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-blue-600 mt-1">⚡ These documents will be automatically stamped when the case is approved</p>
                                @error('documents.request_to_docket')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.request_to_docket.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Request for Pre-Hearing -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Request for Pre-Hearing (PDF) - Pleading Document</label>
                                <input type="file" name="documents[request_for_pre_hearing][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-blue-600 mt-1">⚡ These documents will be automatically stamped when the case is approved</p>
                                @error('documents.request_for_pre_hearing')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.request_for_pre_hearing.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Optional Documents -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Optional Documents</h4>
                        <div class="space-y-4">
                            <!-- Notice of Publication -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notice of Publication (PDF)</label>
                                <input type="file" name="documents[notice_publication][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <p class="text-xs text-gray-500 mt-1">Upload with any filename - will be renamed to: YYYY-MM-DD Notice of Publication.pdf</p>
                                @error('documents.notice_publication')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.notice_publication.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Protest Letters -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Protest Letters (PDF)</label>
                                <input type="file" name="documents[protest_letter][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <p class="text-xs text-gray-500 mt-1">Upload with any filename - will be renamed to: YYYY-MM-DD Protest Letter.pdf</p>
                                @error('documents.protest_letter')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.protest_letter.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Supporting Documents -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Supporting Documents (PDF)</label>
                                <input type="file" name="documents[supporting][]" accept=".pdf" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <p class="text-xs text-gray-500 mt-1">Upload with any filename - will be renamed to: YYYY-MM-DD Supporting Document.pdf</p>
                                @error('documents.supporting')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.supporting.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Other Documents -->
                            <div class="border rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Other Documents</label>
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <select name="documents[other][0][type]" class="border-gray-300 rounded-md text-sm">
                                            <option value="">Select Document Type</option>
                                            @foreach($documentTypes as $docType)
                                            <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="file" name="documents[other][0][file]" accept=".pdf,.docx,.doc" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                    </div>
                                    @error('documents.other.0.type')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('documents.other.0.file')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                    </div>
                                    <button type="button" onclick="addOtherDocument()" class="text-blue-600 hover:text-blue-800 text-sm">+ Add Another Document</button>
                                </div>
                                <div id="otherDocuments"></div>
                                <p class="text-xs text-gray-500 mt-1">PDF, DOC, or DOCX files accepted</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Party Document Upload -->
                    @if(auth()->user()->role === 'party')
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Upload Documents</h4>
                        <div class="border rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Upload</label>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <select name="documents[other][0][type]" class="border-gray-300 rounded-md text-sm">
                                        <option value="">Select Document Type</option>
                                        @foreach($documentTypes as $docType)
                                        <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                                        @endforeach
                                    </select>
                                    <input type="file" name="documents[other][0][file]" accept=".pdf,.docx,.doc" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                </div>
                                @error('documents.other.0.type')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('documents.other.0.file')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" onclick="addOtherDocument()" class="text-blue-600 hover:text-blue-800 text-sm mt-3">+ Add Another Document</button>
                            <div id="otherDocuments"></div>
                            <p class="text-xs text-gray-500 mt-1">PDF, DOC, or DOCX files accepted</p>
                        </div>
                    </div>
                    @endif

                    <!-- Existing Documents -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Existing Documents ({{ $case->documents->count() }})</h4>
                        @if($case->documents->count() > 0)
                            <div class="bg-gray-50 rounded-lg p-4">
                                @foreach($case->documents as $doc)
                                <div class="flex items-center justify-between py-2 border-b last:border-b-0">
                                    <div>
                                        <div class="font-medium text-sm">{{ $doc->original_filename ?? 'No filename' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ ucfirst(str_replace('_', ' ', $doc->doc_type ?? 'unknown')) }} • 
                                            {{ $doc->uploaded_at ? $doc->uploaded_at->format('M j, Y') : 'No date' }}
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        @if($doc->stamped)
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Stamped</span>
                                        @endif
                                        <a href="{{ route('documents.preview', $doc) }}" target="_blank" class="text-gray-600 hover:text-gray-800 text-xs" title="Preview">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <a href="{{ route('documents.download', $doc) }}" class="text-blue-600 hover:text-blue-800 text-xs" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
                                No documents uploaded yet
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600">
                            Upload Documents
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let otherDocumentCount = 1;
        
        // Handle pleading type selection
        document.querySelectorAll('input[name="pleading_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label[for^="pleading"]').forEach(label => {
                    label.classList.remove('border-blue-500', 'bg-blue-50');
                });
                this.closest('label').classList.add('border-blue-500', 'bg-blue-50');
            });
        });
        
        // Add more document upload fields

        
        function addOtherDocument() {
            const container = document.getElementById('otherDocuments');
            const newField = document.createElement('div');
            newField.className = 'grid grid-cols-2 gap-3 mt-3';
            
            const documentOptions = `@foreach($documentTypes as $docType)<option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>@endforeach`;
            
            newField.innerHTML = `
                <select name="documents[other][${otherDocumentCount}][type]" class="border-gray-300 rounded-md text-sm">
                    <option value="">Select Document Type</option>
                    ${documentOptions}
                </select>
                <input type="file" name="documents[other][${otherDocumentCount}][file]" accept=".pdf,.docx,.doc" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
            `;
            container.appendChild(newField);
            otherDocumentCount++;
        }
    </script>
</x-app-layout>
