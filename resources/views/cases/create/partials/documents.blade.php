<div class="space-y-6" id="documentUploadStage">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Clerk Filing Workflow</p>
                <h3 class="mt-2 text-lg font-semibold text-slate-950">Stage documents through the filing modal.</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Use the same filing flow used on the case page. Each document package is staged here first, then submitted with the case intake.</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-100">
                <span class="font-semibold text-slate-900">Title rule</span>
                <span class="mt-1 block">The document title must exactly match the selected document type.</span>
            </div>
        </div>
    </div>

    <div id="application-section" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-slate-950">Application Document</h4>
                <p class="mt-2 text-sm leading-6 text-slate-600">Add the application package for aggrieved or protested cases.</p>
            </div>
            <button type="button" onclick="showCreateDocumentModal('application')" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                File Document
            </button>
        </div>
        <div id="application-documents-list" class="mt-5"></div>
    </div>

    <div id="compliance-documents" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" style="display: none;">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-slate-950">Compliance Document</h4>
                <p class="mt-2 text-sm leading-6 text-slate-600">Choose the compliance filing type, then stage the file package for this case.</p>
            </div>
            <button type="button" onclick="showCreateDocumentModal('compliance')" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                File Document
            </button>
        </div>
        <div id="compliance-documents-list" class="mt-5"></div>
    </div>

    @if($pleadingDocs->count() > 0)
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-slate-950">Pleading Document</h4>
                <p class="mt-2 text-sm leading-6 text-slate-600">Select the pleading type and stage the pleading files through the filing modal.</p>
            </div>
            <button type="button" onclick="showCreateDocumentModal('pleading')" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                File Document
            </button>
        </div>
        <div id="pleading-documents-list" class="mt-5"></div>
    </div>
    @endif

    @if($optionalDocs->count() > 0)
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-slate-950">Supporting Documents</h4>
                <p class="mt-2 text-sm leading-6 text-slate-600">Add as many supporting filings as this intake requires. Each supporting package keeps its own document type and files.</p>
            </div>
            <button type="button" onclick="showCreateDocumentModal('optional')" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Add Supporting Document
            </button>
        </div>
        <div id="optional-documents-list" class="mt-5 space-y-3"></div>
    </div>
    @endif

    <div id="document-hidden-inputs" class="hidden"></div>

    <div id="createDocumentModal" class="fixed inset-0 z-50 hidden bg-gray-600 bg-opacity-50">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white shadow-lg">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">File Document</h3>
                            <p id="createDocumentModalSummary" class="mt-2 text-sm text-gray-600">Choose the filing type and upload the files for this case intake step.</p>
                        </div>
                        <button type="button" onclick="hideCreateDocumentModal()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Close document modal">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-6 space-y-5">
                        <input type="hidden" id="createDocumentModalGroup">

                        <div>
                            <label for="createDocumentType" class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                            <select id="createDocumentType" class="block w-full rounded-md border-gray-300" onchange="syncCreateDocumentTitle()"></select>
                        </div>

                        <div>
                            <label for="createDocumentTitle" class="block text-sm font-medium text-gray-700 mb-2">Document Title *</label>
                            <input type="text" id="createDocumentTitle" maxlength="255" class="block w-full rounded-md border-gray-300" placeholder="Enter the exact document title">
                            <p class="mt-2 text-sm text-orange-600">The title must be the exact same as what is listed as the document title.</p>
                        </div>

                        <div>
                            <label for="createDocumentFiles" class="block text-sm font-medium text-gray-700 mb-2">Files *</label>
                            <input type="file" id="createDocumentFiles" multiple accept=".pdf" class="block w-full rounded-md border-gray-300" onchange="validateFiles(this)">
                            <p class="mt-2 text-xs text-gray-500">Select multiple files. Supported format: PDF (Max: 200MB each)</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideCreateDocumentModal()" class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 transition hover:bg-gray-400">Cancel</button>
                        <button type="button" onclick="stageCreateDocument()" class="rounded-md bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">File Document</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

