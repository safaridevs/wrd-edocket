<div class="mb-6" id="application-section">
    <h3 class="text-lg font-medium mb-4">Application Document</h3>
    <div class="border rounded-lg p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Application (PDF) *</label>
        <input type="file" name="documents[application][]" accept=".pdf" multiple required class="mt-1 block w-full border-gray-300 rounded-md p-2">
        <p class="text-xs text-gray-500 mt-1">Name format: YYYY-MM-DD Application (e.g., 2025-07-18 Application.pdf)</p>
    </div>
</div>

<div class="mb-6">
    <h3 class="text-lg font-medium mb-4">Required Documents</h3>

    <div id="compliance-documents" class="space-y-4" style="display: none;">
        <div class="border rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Compliance Document Type *</label>
            <div class="grid grid-cols-1 gap-3 mb-4">
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="compliance_doc_type" value="compliance_order" class="mr-3" onchange="updateComplianceDocLabel()">
                    <div>
                        <div class="font-medium">Compliance Order</div>
                        <div class="text-xs text-gray-500">Official order for compliance action</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="compliance_doc_type" value="pre_compliance_letter" class="mr-3" onchange="updateComplianceDocLabel()">
                    <div>
                        <div class="font-medium">Pre-Compliance Letter</div>
                        <div class="text-xs text-gray-500">Initial notice before formal action</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="compliance_doc_type" value="compliance_letter" class="mr-3" onchange="updateComplianceDocLabel()">
                    <div>
                        <div class="font-medium">Compliance Letter</div>
                        <div class="text-xs text-gray-500">Formal compliance notification</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="compliance_doc_type" value="notice_of_violation" class="mr-3" onchange="updateComplianceDocLabel()">
                    <div>
                        <div class="font-medium">Notice of Violation</div>
                        <div class="text-xs text-gray-500">Official violation notice</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="compliance_doc_type" value="notice_of_reprimand" class="mr-3" onchange="updateComplianceDocLabel()">
                    <div>
                        <div class="font-medium">Notice of Reprimand (Well Driller)</div>
                        <div class="text-xs text-gray-500">Reprimand notice for well drilling violations</div>
                    </div>
                </label>
            </div>

            <label id="compliance-file-label" class="block text-sm font-medium text-gray-700 mb-2">Select Document Type First</label>
            <input type="file" name="documents[compliance][]" accept=".pdf" multiple class="mt-1 block w-full border-gray-300 rounded-md p-2" disabled id="compliance-file-input">
            <p class="text-xs text-gray-500 mt-1">Choose document type above to enable file upload</p>
        </div>
    </div>
</div>

@if($pleadingDocs->count() > 0)
<div class="mb-6">
    <h3 class="text-lg font-medium mb-4">Pleading Documents</h3>
    <div class="border rounded-lg p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pleading Type *</label>
        <div class="grid grid-cols-2 gap-4 mb-4">
            @foreach($pleadingDocs as $docType)
            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="pleading_type" value="{{ $docType->code }}" required class="mr-3" onchange="updatePleadingLabel()">
                <div>
                    <div class="font-medium">{{ $docType->name }}</div>
                    <div class="text-xs text-gray-500">{{ $docType->code === 'request_pre_hearing' ? 'For pre-hearing conference requests' : 'For docketing requests' }}</div>
                </div>
            </label>
            @endforeach
        </div>

        <label id="pleading-file-label" class="block text-sm font-medium text-gray-700 mb-2">Select Pleading Type First</label>
        <input type="file" name="documents[pleading][]" accept=".pdf" multiple required class="mt-1 block w-full border-gray-300 rounded-md p-2" disabled id="pleading-file-input">
        <p class="text-xs text-gray-500 mt-1">Choose pleading type above to enable file upload</p>
    </div>
</div>
@endif

@if($optionalDocs->count() > 0)
<div class="mb-6">
    <h4 class="font-medium text-gray-900 mb-3">Supporting Documents</h4>
    <div id="optional-documents" class="space-y-4">
        <div class="border rounded-lg p-4 optional-doc-item">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                    <select name="optional_docs[0][type]" class="mt-1 block w-full border-gray-300 rounded-md" onchange="updateOptionalDocLabel(0)">
                        <option value="">Select document type...</option>
                        @foreach($optionalDocs as $docType)
                        <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">File Upload</label>
                    <input type="file" name="optional_docs[0][files][]" accept=".pdf" multiple class="mt-1 block w-full border-gray-300 rounded-md p-2" disabled>
                </div>
            </div>
            <p class="text-xs text-gray-500">Select document type first, then upload files. Files will be renamed to: YYYY-MM-DD [Document Type].pdf</p>
        </div>
    </div>
    <button type="button" onclick="addOptionalDocument()" class="mt-3 text-blue-600 text-sm hover:text-blue-800">+ add additional supporting document</button>
</div>
@endif

<div id="upload-progress" class="hidden">
    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span class="text-sm text-blue-800">Processing documents...</span>
        </div>
    </div>
</div>
