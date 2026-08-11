@php
    $isHearingUnit = auth()->user()->isHearingUnit();
    $notificationRecipients = ($resolvedServiceList ?? collect())->filter(fn (array $recipient) => $recipient['email'] !== '')->values();
@endphp

<div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-1">{{ $isHearingUnit ? 'Prepare Order or Notice' : 'File Document' }}</h3>
                @if($isHearingUnit)
                    <p class="mb-4 text-sm text-gray-600">Upload the final PDF, review its electronic stamp, then issue it to the service list.</p>
                @endif

                <form id="uploadForm" action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data" onsubmit="return confirmUpload(event)" data-loading-form>
                    @csrf
                    <input type="hidden" name="time_sensitive_notice" id="timeSensitiveNoticeInput" value="0">
                    @if($isHearingUnit)
                        <input type="hidden" name="pleading_type" value="none">
                    @endif

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                            <select name="doc_type" class="block w-full border-gray-300 rounded-md" onchange="togglePleadingType()">
                                <option value="">Select document type...</option>
                                @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->code }}" data-is-pleading="{{ $docType->is_pleading ? 'true' : 'false' }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $isHearingUnit ? 'Official Document Title' : 'Document Title' }} *</label>
                            <input type="text" name="custom_title" id="customTitleInput" maxlength="255"
                                   required
                                   class="block w-full border-gray-300 rounded-md"
                                   placeholder="{{ $isHearingUnit ? 'e.g., Scheduling Order' : 'e.g., Motion to Dismiss for Lack of Jurisdiction' }}"
                                   oninput="updateFilenamePreview()">
                            <p class="mt-1 text-sm text-amber-700">Enter the title exactly as it appears on the document.</p>
                        </div>

                        <div id="filenamePreview" class="hidden bg-blue-50 border border-blue-200 rounded-md p-3">
                            <p class="text-xs font-medium text-blue-800 mb-1">Filename Preview:</p>
                            <p id="previewText" class="text-sm text-blue-900 font-mono"></p>
                        </div>

                        @unless($isHearingUnit)
                            <div id="pleadingTypeSection" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pleading Type</label>
                                <select name="pleading_type" class="block w-full border-gray-300 rounded-md">
                                    <option value="none">None</option>
                                    <option value="request_to_docket">Request to Docket</option>
                                    <option value="request_pre_hearing">Request for Pre-Hearing</option>
                                </select>
                            </div>
                        @endunless

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Files *</label>
                            <input type="file" name="document[]" required accept="{{ $isHearingUnit ? '.pdf' : '.pdf,.doc,.docx,.jpg,.jpeg,.png' }}" multiple
                                   class="block w-full border-gray-300 rounded-md" onchange="validateFiles(this)">
                            <p class="text-xs text-gray-500 mt-1">
                                @if($isHearingUnit)
                                    Upload final PDF orders or notices. The system creates a stamped preview without notifying recipients.
                                @else
                                    Select multiple files. Supported formats: PDF, DOC, DOCX, JPG, PNG (Max: 200MB each).
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500">All selected files use the document type, title, and notification message entered here. Upload files separately when they need different details.</p>
                        </div>

                        @if($isHearingUnit)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Service-List Notification Message</label>
                                <textarea name="notification_message" rows="4" maxlength="5000"
                                          class="block w-full border-gray-300 rounded-md"
                                          placeholder="Optional conference links, instructions, deadlines, or other information for recipients."></textarea>
                                <p class="text-xs text-gray-500 mt-1">The message is saved with the preview and can be reviewed or changed before final issuance.</p>
                            </div>

                            <div class="rounded-md border {{ $notificationRecipients->isEmpty() ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50' }} p-3">
                                @if($notificationRecipients->isEmpty())
                                    <p class="text-sm font-medium text-red-800">No service-list recipients currently have a valid email address.</p>
                                    <p class="mt-1 text-xs text-red-700">You can still generate and review the stamped preview, but final issuance will have no email recipients.</p>
                                @else
                                    <details>
                                        <summary class="cursor-pointer text-sm font-medium text-gray-800">
                                            {{ $notificationRecipients->count() }} service-list {{ $notificationRecipients->count() === 1 ? 'recipient' : 'recipients' }}
                                        </summary>
                                        <div class="mt-2 max-h-36 space-y-1 overflow-y-auto border-t border-gray-200 pt-2">
                                            @foreach($notificationRecipients as $recipient)
                                                <div class="text-xs text-gray-700">
                                                    <span class="font-medium">{{ $recipient['name'] ?: $recipient['email'] }}</span>
                                                    <span class="text-gray-500">— {{ $recipient['email'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideUploadModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                            Cancel
                        </button>
                        <button type="submit" data-loading-text="{{ $isHearingUnit ? 'Generating preview...' : 'Filing document...' }}" class="inline-flex items-center justify-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            <span data-loading-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <span data-button-label>{{ $isHearingUnit ? 'Generate Stamped Preview' : 'File Document' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
