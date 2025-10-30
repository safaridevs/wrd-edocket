<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Case</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Messages -->
            @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Please fix the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-6">
                @csrf

                <!-- Case Type -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Case Type *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="aggrieved" {{ old('case_type') == 'aggrieved' ? 'checked' : '' }} required class="mr-2">
                            Aggrieved
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="protested" {{ old('case_type') == 'protested' ? 'checked' : '' }} required class="mr-2">
                            Protested
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="compliance" {{ old('case_type') == 'compliance' ? 'checked' : '' }} required class="mr-2">
                            Compliance Action
                        </label>
                    </div>
                    @error('case_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Caption -->
                <div class="mb-6">
                    <label for="caption" class="block text-sm font-medium text-gray-700">Caption *</label>
                    <textarea name="caption" id="caption" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm {{ $errors->has('caption') ? 'border-red-500' : '' }}">{{ old('caption') }}</textarea>
                    @error('caption')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Attorney Assignment -->
                @if(auth()->user()->canAssignAttorneys())
                <div class="mb-6">
                    <label for="assigned_attorney_id" class="block text-sm font-medium text-gray-700">Assign ALU Attorney</label>
                    <select name="assigned_attorney_id" id="assigned_attorney_id" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">Select Attorney (Optional)</option>
                        @foreach(\App\Models\User::where('role', 'alu_atty')->get() as $attorney)
                            <option value="{{ $attorney->id }}" {{ old('assigned_attorney_id') == $attorney->id ? 'selected' : '' }}>{{ $attorney->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_attorney_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- OSE File Numbers -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">OSE File Numbers</label>
                    <div id="ose-numbers" class="space-y-2">
                        <div class="flex gap-2 items-center flex-wrap">
                            <div class="flex items-center gap-1">
                                <select name="ose_numbers[0][basin_code_from]" class="border-gray-300 rounded-md text-sm">
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}" {{ old('ose_numbers.0.basin_code_from') == $code->initial ? 'selected' : '' }}>{{ $code->initial }} - {{ $code->description }}</option>
                                    @endforeach
                                </select>
                                <span class="text-sm">-</span>
                                <input type="text" name="ose_numbers[0][file_no_from]" placeholder="12345" value="{{ old('ose_numbers.0.file_no_from') }}" class="border-gray-300 rounded-md w-20 text-sm">
                            </div>
                            <div id="to-section-0" class="flex items-center gap-1 {{ old('ose_numbers.0.file_no_to') ? '' : 'hidden' }}">
                                <span class="text-sm text-gray-600">to</span>
                                <select name="ose_numbers[0][basin_code_to]" class="border-gray-300 rounded-md text-sm">
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}" {{ old('ose_numbers.0.basin_code_to') == $code->initial ? 'selected' : '' }}>{{ $code->initial }} - {{ $code->description }}</option>
                                    @endforeach
                                </select>
                                <span class="text-sm">-</span>
                                <input type="text" name="ose_numbers[0][file_no_to]" placeholder="12350" value="{{ old('ose_numbers.0.file_no_to') }}" class="border-gray-300 rounded-md w-20 text-sm">
                                <button type="button" onclick="hideToSection(0)" class="text-red-600 text-xs ml-1">✕</button>
                            </div>
                            <button id="add-to-0" type="button" onclick="showToSection(0)" class="text-blue-600 text-xs {{ old('ose_numbers.0.file_no_to') ? 'hidden' : '' }}">+ Add Range</button>
                        </div>
                    </div>
                    <button type="button" onclick="addOseNumber()" class="mt-2 text-blue-600 text-sm">+ Add Another</button>
                </div>

                <!-- Parties with Contact Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Parties & Contact Information</h3>

                    <div id="parties-list" class="space-y-6">
                        <!-- First Applicant (Required) -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-900">Applicant 1 *</h4>
                            </div>

                            <input type="hidden" name="parties[0][role]" value="applicant">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type *</label>
                                    <select name="parties[0][type]" required class="mt-1 block w-full border-gray-300 rounded-md" onchange="togglePersonFields(0)">
                                        <option value="">Select Type</option>
                                        <option value="individual" {{ old('parties.0.type') == 'individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="company" {{ old('parties.0.type') == 'company' ? 'selected' : '' }}>Company</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Service Method</label>
                                    <select name="parties[0][service_method]" class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option value="email" {{ old('parties.0.service_method') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="mail" {{ old('parties.0.service_method') == 'mail' ? 'selected' : '' }}>Mail</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Individual Fields -->
                            <div id="individual-fields-0" class="{{ old('parties.0.type') == 'individual' ? '' : 'hidden' }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                        <input type="text" name="parties[0][first_name]" value="{{ old('parties.0.first_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                        <input type="text" name="parties[0][last_name]" value="{{ old('parties.0.last_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>

                            <!-- Company Fields -->
                            <div id="company-fields-0" class="{{ old('parties.0.type') == 'company' ? '' : 'hidden' }}">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Organization Name *</label>
                                    <input type="text" name="parties[0][organization]" value="{{ old('parties.0.organization') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Representation -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Representation</label>
                                <div id="representation-0" class="mt-2">
                                    <div class="individual-representation {{ old('parties.0.type') == 'individual' ? '' : 'hidden' }}">
                                        <label class="flex items-center mb-2">
                                            <input type="radio" name="parties[0][representation]" value="self" {{ old('parties.0.representation') == 'self' ? 'checked' : '' }} class="mr-2" onchange="toggleAttorneyFields(0)">
                                            Self-Represented
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="parties[0][representation]" value="attorney" {{ old('parties.0.representation') == 'attorney' ? 'checked' : '' }} class="mr-2" onchange="toggleAttorneyFields(0)">
                                            Represented by Attorney
                                        </label>
                                    </div>
                                    <div class="company-representation {{ old('parties.0.type') == 'company' ? '' : 'hidden' }}">
                                        <input type="hidden" name="parties[0][representation]" value="attorney">
                                        <p class="text-sm text-gray-600 bg-blue-50 p-2 rounded">Companies must be represented by an attorney</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Attorney Fields -->
                            <div id="attorney-fields-0" class="{{ (old('parties.0.representation') == 'attorney' || old('parties.0.type') == 'company') ? '' : 'hidden' }} border-t pt-4 mt-4">
                                <h5 class="font-medium text-gray-700 mb-3">Attorney Information</h5>

                                <div class="mb-4">
                                    <label class="flex items-center mb-2">
                                        <input type="radio" name="parties[0][attorney_option]" value="existing" class="mr-2" onchange="toggleAttorneyOption(0)" {{ old('parties.0.attorney_id') ? 'checked' : '' }}>
                                        Select Existing Attorney
                                    </label>
                                    <select name="parties[0][attorney_id]" class="mt-1 block w-full border-gray-300 rounded-md" {{ old('parties.0.attorney_id') ? '' : 'disabled' }}>
                                        <option value="">Choose an attorney...</option>
                                        @foreach($attorneys as $attorney)
                                            <option value="{{ $attorney->id }}" {{ old('parties.0.attorney_id') == $attorney->id ? 'selected' : '' }}>
                                                {{ $attorney->name }} - {{ $attorney->email }}
                                                @if($attorney->bar_number) (Bar: {{ $attorney->bar_number }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="flex items-center mb-2">
                                        <input type="radio" name="parties[0][attorney_option]" value="new" class="mr-2" onchange="toggleAttorneyOption(0)" {{ !old('parties.0.attorney_id') ? 'checked' : '' }}>
                                        Add New Attorney
                                    </label>
                                    <div id="new-attorney-0" class="{{ old('parties.0.attorney_id') ? 'opacity-50' : '' }}">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Attorney Name *</label>
                                                <input type="text" name="parties[0][attorney_name]" value="{{ old('parties.0.attorney_name') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ old('parties.0.attorney_id') ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Attorney Email *</label>
                                                <input type="email" name="parties[0][attorney_email]" value="{{ old('parties.0.attorney_email') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ old('parties.0.attorney_id') ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Attorney Phone</label>
                                                <input type="text" name="parties[0][attorney_phone]" value="{{ old('parties.0.attorney_phone') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ old('parties.0.attorney_id') ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Bar Number</label>
                                                <input type="text" name="parties[0][bar_number]" value="{{ old('parties.0.bar_number') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ old('parties.0.attorney_id') ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Common Contact Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email *</label>
                                    <input type="email" name="parties[0][email]" value="{{ old('parties.0.email') }}" required class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" name="parties[0][phone]" value="{{ old('parties.0.phone') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <input type="text" name="parties[0][address_line1]" value="{{ old('parties.0.address_line1') }}" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <input type="text" name="parties[0][city]" value="{{ old('parties.0.city') }}" placeholder="City" class="border-gray-300 rounded-md">
                                <input type="text" name="parties[0][state]" value="{{ old('parties.0.state') }}" placeholder="State" maxlength="2" class="border-gray-300 rounded-md">
                                <input type="text" name="parties[0][zip]" value="{{ old('parties.0.zip') }}" placeholder="ZIP" class="border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-x-2">
                        <button type="button" onclick="addParty('applicant')" class="text-blue-600 text-sm hover:text-blue-800">+ Add Applicant</button>
                        <button type="button" onclick="addParty('protestant')" class="text-blue-600 text-sm hover:text-blue-800">+ Add Protestant</button>
                        <button type="button" onclick="addParty('counsel')" class="text-blue-600 text-sm hover:text-blue-800">+ Add Counsel</button>
                    </div>
                </div>

                <!-- Document Uploads -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Document Uploads</h3>

                    @php
                        $requiredDocs = $documentTypes->where('is_required', true)->where('is_pleading', false);
                        $pleadingDocs = $documentTypes->where('is_pleading', true);
                        $optionalDocs = $documentTypes->where('is_required', false)->where('is_pleading', false);
                    @endphp

                    <!-- Required Documents -->
                    @if($requiredDocs->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Required Documents</h4>
                        @foreach($requiredDocs as $docType)
                        <div class="border rounded-lg p-4 mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $docType->name }} (PDF) *</label>
                            <input type="file" name="documents[{{ $docType->code }}][]" accept=".pdf" multiple required class="mt-1 block w-full border-gray-300 rounded-md p-2">
                            <p class="text-xs text-gray-500 mt-1">Name format: YYYY-MM-DD {{ $docType->name }} (e.g., 2025-07-18 {{ $docType->name }}.pdf)</p>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Pleading Document -->
                    @if($pleadingDocs->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Pleading Document</h4>
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

                    <!-- Optional Documents -->
                    @if($optionalDocs->count() > 0)
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Optional Documents</h4>
                        <div id="optional-documents" class="space-y-4">
                            <!-- Initial optional document upload -->
                            <div class="border rounded-lg p-4 optional-doc-item">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                                        <select name="optional_docs[0][type]" class="mt-1 block w-full border-gray-300 rounded-md" onchange="updateOptionalDocLabel(0)">
                                            <option value="">Select document type...</option>
                                            @foreach($optionalDocs as $docType)
                                            <option value="{{ $docType->code }}">{{ $docType->name }}</option>
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
                        <button type="button" onclick="addOptionalDocument()" class="mt-3 text-blue-600 text-sm hover:text-blue-800">+ Add Another Optional Document</button>
                    </div>
                    @endif

                    <!-- Upload Progress -->
                    <div id="upload-progress" class="hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div class="flex items-center">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                                <span class="text-sm text-blue-800">Processing documents...</span>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Affirmation -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="affirmation" {{ old('affirmation') ? 'checked' : '' }} required class="mr-2">
                        <span class="text-sm">Information provided is complete and correct *</span>
                    </label>
                    @error('affirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit" name="action" value="draft" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors">
                        Submit to HU
                    </button>
                    <a href="{{ route('cases.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let oseCount = 1, partyCount = 1, optionalDocCount = 1;

        function showToSection(index) {
            document.getElementById(`to-section-${index}`).classList.remove('hidden');
            document.getElementById(`add-to-${index}`).classList.add('hidden');
        }

        function hideToSection(index) {
            const toSection = document.getElementById(`to-section-${index}`);
            const addButton = document.getElementById(`add-to-${index}`);

            // Clear the to fields
            toSection.querySelector('select').selectedIndex = 0;
            toSection.querySelector('input').value = '';

            toSection.classList.add('hidden');
            addButton.classList.remove('hidden');
        }

        function addOseNumber() {
            const basinOptions = `@foreach($basinCodes as $code)<option value="{{ $code->initial }}">{{ $code->initial }} - {{ $code->description }}</option>@endforeach`;
            document.getElementById('ose-numbers').insertAdjacentHTML('beforeend', `
                <div class="flex gap-2 items-center flex-wrap">
                    <div class="flex items-center gap-1">
                        <select name="ose_numbers[${oseCount}][basin_code_from]" class="border-gray-300 rounded-md text-sm">
                            ${basinOptions}
                        </select>
                        <span class="text-sm">-</span>
                        <input type="text" name="ose_numbers[${oseCount}][file_no_from]" placeholder="12345" class="border-gray-300 rounded-md w-20 text-sm">
                    </div>
                    <div id="to-section-${oseCount}" class="flex items-center gap-1 hidden">
                        <span class="text-sm text-gray-600">to</span>
                        <select name="ose_numbers[${oseCount}][basin_code_to]" class="border-gray-300 rounded-md text-sm">
                            ${basinOptions}
                        </select>
                        <span class="text-sm">-</span>
                        <input type="text" name="ose_numbers[${oseCount}][file_no_to]" placeholder="12350" class="border-gray-300 rounded-md w-20 text-sm">
                        <button type="button" onclick="hideToSection(${oseCount})" class="text-red-600 text-xs ml-1">✕</button>
                    </div>
                    <button id="add-to-${oseCount}" type="button" onclick="showToSection(${oseCount})" class="text-blue-600 text-xs">+ Add Range</button>
                </div>
            `);
            oseCount++;
        }

        function addParty(role) {
            const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);
            document.getElementById('parties-list').insertAdjacentHTML('beforeend', `
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-medium text-gray-900">${roleTitle} ${partyCount + 1}</h4>
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 text-sm hover:text-red-800">Remove</button>
                    </div>

                    <input type="hidden" name="parties[${partyCount}][role]" value="${role}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select name="parties[${partyCount}][type]" required class="mt-1 block w-full border-gray-300 rounded-md" onchange="togglePersonFields(${partyCount})">
                                <option value="">Select Type</option>
                                <option value="individual">Individual</option>
                                <option value="company">Company</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Service Method</label>
                            <select name="parties[${partyCount}][service_method]" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="email">Email</option>
                                <option value="mail">Mail</option>
                            </select>
                        </div>
                    </div>

                    <div id="individual-fields-${partyCount}" class="hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="parties[${partyCount}][first_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="parties[${partyCount}][last_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div id="company-fields-${partyCount}" class="hidden">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Organization Name *</label>
                            <input type="text" name="parties[${partyCount}][organization]" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Representation -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Representation</label>
                        <div id="representation-${partyCount}" class="mt-2">
                            <div class="individual-representation hidden">
                                <label class="flex items-center mb-2">
                                    <input type="radio" name="parties[${partyCount}][representation]" value="self" class="mr-2" onchange="toggleAttorneyFields(${partyCount})">
                                    Self-Represented
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="parties[${partyCount}][representation]" value="attorney" class="mr-2" onchange="toggleAttorneyFields(${partyCount})">
                                    Represented by Attorney
                                </label>
                            </div>
                            <div class="company-representation hidden">
                                <input type="hidden" name="parties[${partyCount}][representation]" value="attorney">
                                <p class="text-sm text-gray-600 bg-blue-50 p-2 rounded">Companies must be represented by an attorney</p>
                            </div>
                        </div>
                    </div>

                    <!-- Attorney Fields -->
                    <div id="attorney-fields-${partyCount}" class="hidden border-t pt-4 mt-4">
                        <h5 class="font-medium text-gray-700 mb-3">Attorney Information</h5>

                        <div class="mb-4">
                            <label class="flex items-center mb-2">
                                <input type="radio" name="parties[${partyCount}][attorney_option]" value="existing" class="mr-2" onchange="toggleAttorneyOption(${partyCount})">
                                Select Existing Attorney
                            </label>
                            <select name="parties[${partyCount}][attorney_id]" class="mt-1 block w-full border-gray-300 rounded-md" disabled>
                                <option value="">Choose an attorney...</option>
                                @foreach($attorneys as $attorney)
                                    <option value="{{ $attorney->id }}">
                                        {{ $attorney->name }} - {{ $attorney->email }}
                                        @if($attorney->bar_number) (Bar: {{ $attorney->bar_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center mb-2">
                                <input type="radio" name="parties[${partyCount}][attorney_option]" value="new" class="mr-2" onchange="toggleAttorneyOption(${partyCount})" checked>
                                Add New Attorney
                            </label>
                            <div id="new-attorney-${partyCount}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Name *</label>
                                        <input type="text" name="parties[${partyCount}][attorney_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Email *</label>
                                        <input type="email" name="parties[${partyCount}][attorney_email]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Phone</label>
                                        <input type="text" name="parties[${partyCount}][attorney_phone]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bar Number</label>
                                        <input type="text" name="parties[${partyCount}][bar_number]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="parties[${partyCount}][email]" required class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" name="parties[${partyCount}][phone]" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" name="parties[${partyCount}][address_line1]" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" name="parties[${partyCount}][city]" placeholder="City" class="border-gray-300 rounded-md">
                        <input type="text" name="parties[${partyCount}][state]" placeholder="State" maxlength="2" class="border-gray-300 rounded-md">
                        <input type="text" name="parties[${partyCount}][zip]" placeholder="ZIP" class="border-gray-300 rounded-md">
                    </div>
                </div>
            `);
            partyCount++;
        }

        function togglePersonFields(index) {
            const typeSelect = document.querySelector(`select[name="parties[${index}][type]"]`);
            const individualFields = document.getElementById(`individual-fields-${index}`);
            const companyFields = document.getElementById(`company-fields-${index}`);
            const individualRep = document.querySelector(`#representation-${index} .individual-representation`);
            const companyRep = document.querySelector(`#representation-${index} .company-representation`);
            const attorneyFields = document.getElementById(`attorney-fields-${index}`);

            if (typeSelect.value === 'individual') {
                individualFields.classList.remove('hidden');
                companyFields.classList.add('hidden');
                if (individualRep) {
                    individualRep.classList.remove('hidden');
                    companyRep.classList.add('hidden');
                }
                // Hide attorney fields unless attorney is selected
                const attorneySelected = document.querySelector(`input[name="parties[${index}][representation]"][value="attorney"]:checked`);
                if (attorneyFields) {
                    attorneyFields.classList.toggle('hidden', !attorneySelected);
                }
            } else if (typeSelect.value === 'company') {
                individualFields.classList.add('hidden');
                companyFields.classList.remove('hidden');
                if (individualRep) {
                    individualRep.classList.add('hidden');
                    companyRep.classList.remove('hidden');
                }
                // Always show attorney fields for companies
                if (attorneyFields) {
                    attorneyFields.classList.remove('hidden');
                }
            } else {
                individualFields.classList.add('hidden');
                companyFields.classList.add('hidden');
                if (individualRep) {
                    individualRep.classList.add('hidden');
                    companyRep.classList.add('hidden');
                }
                if (attorneyFields) {
                    attorneyFields.classList.add('hidden');
                }
            }
        }

        function toggleAttorneyFields(index) {
            const attorneyFields = document.getElementById(`attorney-fields-${index}`);
            const attorneySelected = document.querySelector(`input[name="parties[${index}][representation]"][value="attorney"]:checked`);

            if (attorneyFields) {
                attorneyFields.classList.toggle('hidden', !attorneySelected);
            }
        }

        function toggleAttorneyOption(index) {
            const option = document.querySelector(`input[name="parties[${index}][attorney_option]"]:checked`)?.value;
            const existingSelect = document.querySelector(`select[name="parties[${index}][attorney_id]"]`);
            const newFields = document.getElementById(`new-attorney-${index}`);
            const newInputs = newFields?.querySelectorAll('input');

            if (option === 'existing') {
                if (existingSelect) existingSelect.disabled = false;
                if (newFields) newFields.classList.add('opacity-50');
                if (newInputs) newInputs.forEach(input => input.disabled = true);
            } else if (option === 'new') {
                if (existingSelect) {
                    existingSelect.disabled = true;
                    existingSelect.value = '';
                }
                if (newFields) newFields.classList.remove('opacity-50');
                if (newInputs) newInputs.forEach(input => input.disabled = false);
            }
        }

        function updatePleadingLabel() {
            const selectedType = document.querySelector('input[name="pleading_type"]:checked');
            const fileLabel = document.getElementById('pleading-file-label');
            const fileInput = document.getElementById('pleading-file-input');

            if (selectedType) {
                const typeText = selectedType.nextElementSibling.querySelector('.font-medium').textContent;
                fileLabel.textContent = `${typeText} Document (PDF) *`;
                fileInput.name = 'documents[pleading][]';
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
                fileInput.required = true;
            } else {
                fileLabel.textContent = 'Select Pleading Type First';
                fileInput.disabled = true;
                fileInput.style.opacity = '0.5';
                fileInput.required = false;
            }
        }

        function updateOptionalDocLabel(index) {
            const select = document.querySelector(`select[name="optional_docs[${index}][type]"]`);
            const fileInput = document.querySelector(`input[name="optional_docs[${index}][files][]"]`);

            if (select.value) {
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
            } else {
                fileInput.disabled = true;
                fileInput.style.opacity = '0.5';
                fileInput.value = '';
            }
        }

        function addOptionalDocument() {
            const optionalDocsContainer = document.getElementById('optional-documents');
            const optionalDocOptions = `@foreach($optionalDocs as $docType)<option value="{{ $docType->code }}">{{ $docType->name }}</option>@endforeach`;

            optionalDocsContainer.insertAdjacentHTML('beforeend', `
                <div class="border rounded-lg p-4 optional-doc-item">
                    <div class="flex justify-between items-start mb-3">
                        <h5 class="font-medium text-gray-700">Optional Document ${optionalDocCount + 1}</h5>
                        <button type="button" onclick="this.closest('.optional-doc-item').remove()" class="text-red-600 text-sm hover:text-red-800">Remove</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                            <select name="optional_docs[${optionalDocCount}][type]" class="mt-1 block w-full border-gray-300 rounded-md" onchange="updateOptionalDocLabel(${optionalDocCount})">
                                <option value="">Select document type...</option>
                                ${optionalDocOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Upload</label>
                            <input type="file" name="optional_docs[${optionalDocCount}][files][]" accept=".pdf" multiple class="mt-1 block w-full border-gray-300 rounded-md p-2" disabled>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Select document type first, then upload files. Files will be renamed to: YYYY-MM-DD [Document Type].pdf</p>
                </div>
            `);
            optionalDocCount++;
        }

        // File preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add file change listeners for previews
            const fileInputs = {
                'documents[application]': 'application-preview',
                'documents[notice_publication][]': 'notice-preview',
                'documents[request_to_docket]': 'pleading-preview',
                'documents[protest_letter][]': 'protest-preview',
                'documents[supporting][]': 'supporting-preview'
            };

            Object.entries(fileInputs).forEach(([inputName, previewId]) => {
                const input = document.querySelector(`input[name="${inputName}"]`);
                const preview = document.getElementById(previewId);

                if (input && preview) {
                    input.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            if (this.multiple) {
                                const fileNames = Array.from(this.files).map(f => f.name).join(', ');
                                preview.textContent = `✓ ${this.files.length} file(s) selected: ${fileNames}`;
                            } else {
                                preview.textContent = `✓ Selected: ${this.files[0].name}`;
                            }
                            preview.classList.remove('hidden');
                        } else {
                            preview.classList.add('hidden');
                        }
                    });
                }
            });

            // Form submission progress
            const form = document.querySelector('form');
            const progressDiv = document.getElementById('upload-progress');

            form.addEventListener('submit', function() {
                progressDiv.classList.remove('hidden');
            });
        });

        // File size validation
        function validateFileSize(input, maxSizeMB = 10) {
            const files = input.files;
            for (let file of files) {
                if (file.size > maxSizeMB * 1024 * 1024) {
                    alert(`File "${file.name}" is too large. Maximum size is ${maxSizeMB}MB.`);
                    input.value = '';
                    return false;
                }
            }
            return true;
        }

        // Add file size validation to all file inputs
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function() {
                    validateFileSize(this);
                });
            });
        });
    </script>
</x-app-layout>
