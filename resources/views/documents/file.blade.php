<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">File Document - Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Upload Failed</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('documents.store', $case) }}" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Case Info -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-medium">{{ $case->case_no }}</h3>
                        <p class="text-sm text-gray-600">{{ $case->caption }}</p>
                    </div>

                    <!-- Document Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document (PDF Required) *</label>
                        <input type="file" name="document" accept=".pdf" required class="block w-full border-gray-300 rounded-md">
                        <p class="text-xs text-gray-500 mt-1">
                            Filename must follow convention: YYYY-MM-DD — Doc Type — Description — {{ $case->case_no }}
                        </p>
                    </div>

                    <!-- Document Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                        <select name="doc_type" required class="block w-full border-gray-300 rounded-md">
                            <option value="">Select Type</option>
                            <option value="affidavit_publication">Affidavit Of Publication</option>
                            <option value="aggrieval_letter">Aggrieval Letter</option>
                            <option value="filing_other">Filing</option>
                            <option value="protest_letter">Protest Letter</option>
                        </select>
                    </div>

                    <!-- Service Recipients -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Recipients</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto border rounded-md p-3">
                            @foreach($case->serviceList as $service)
                            <label class="flex items-center">
                                <input type="checkbox" name="service_recipients[]" value="{{ $service->id }}" 
                                       {{ $service->is_primary ? 'checked' : '' }} class="mr-2">
                                <span class="text-sm">{{ $service->person->full_name }} ({{ $service->email }})</span>
                            </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Primary service recipients are pre-selected</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            Submit for HU Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
