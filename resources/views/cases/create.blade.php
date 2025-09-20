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

                <!-- OSE File Numbers -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">OSE File Numbers</label>
                    <div id="ose-numbers" class="space-y-2">
                        <div class="flex gap-2">
                            <select name="ose_numbers[0][basin_code]" class="border-gray-300 rounded-md">
                                <option value="RG" {{ old('ose_numbers.0.basin_code') == 'RG' ? 'selected' : '' }}>Rio Grande</option>
                                <option value="PE" {{ old('ose_numbers.0.basin_code') == 'PE' ? 'selected' : '' }}>Pecos</option>
                                <option value="CA" {{ old('ose_numbers.0.basin_code') == 'CA' ? 'selected' : '' }}>Canadian</option>
                            </select>
                            <input type="text" name="ose_numbers[0][file_no_from]" placeholder="From" value="{{ old('ose_numbers.0.file_no_from') }}" class="border-gray-300 rounded-md">
                            <input type="text" name="ose_numbers[0][file_no_to]" placeholder="To" value="{{ old('ose_numbers.0.file_no_to') }}" class="border-gray-300 rounded-md">
                        </div>
                    </div>
                    <button type="button" onclick="addOseNumber()" class="mt-2 text-blue-600 text-sm">+ Add Another</button>
                </div>

                <!-- Parties -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Parties</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Applicants *</label>
                        <div id="applicants" class="space-y-2">
                            <input type="text" name="applicants[0]" placeholder="Applicant name" value="{{ old('applicants.0') }}" required class="block w-full border-gray-300 rounded-md {{ $errors->has('applicants.0') ? 'border-red-500' : '' }}">
                        </div>
                        <button type="button" onclick="addParty('applicants')" class="mt-2 text-blue-600 text-sm">+ Add Applicant</button>
                        @error('applicants.0')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Protestants</label>
                        <div id="protestants" class="space-y-2">
                            <input type="text" name="protestants[0]" placeholder="Protestant name" value="{{ old('protestants.0') }}" class="block w-full border-gray-300 rounded-md">
                        </div>
                        <button type="button" onclick="addParty('protestants')" class="mt-2 text-blue-600 text-sm">+ Add Protestant</button>
                    </div>
                </div>

                <!-- Document Uploads -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Required Documents</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Application (PDF) *</label>
                            <input type="file" name="documents[application]" accept=".pdf" required class="mt-1 block w-full">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notice of Publication (DOCX) *</label>
                            <input type="file" name="documents[notice_publication]" accept=".docx" required class="mt-1 block w-full">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Request to Docket (PDF) *</label>
                            <input type="file" name="documents[request_to_docket]" accept=".pdf" required class="mt-1 block w-full">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Protest Letters (PDF)</label>
                            <input type="file" name="documents[protest_letter][]" accept=".pdf" multiple class="mt-1 block w-full">
                        </div>
                    </div>
                </div>

                <!-- Service List -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Service List</h3>
                    <div id="service-list" class="space-y-4">
                        <div class="border rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <select name="service_list[0][type]" class="border-gray-300 rounded-md">
                                    <option value="individual" {{ old('service_list.0.type') == 'individual' ? 'selected' : '' }}>Individual</option>
                                    <option value="company" {{ old('service_list.0.type') == 'company' ? 'selected' : '' }}>Company</option>
                                </select>
                                <select name="service_list[0][method]" class="border-gray-300 rounded-md">
                                    <option value="email" {{ old('service_list.0.method') == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="mail" {{ old('service_list.0.method') == 'mail' ? 'selected' : '' }}>Mail</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <input type="text" name="service_list[0][first_name]" placeholder="First Name" value="{{ old('service_list.0.first_name') }}" class="border-gray-300 rounded-md">
                                <input type="text" name="service_list[0][last_name]" placeholder="Last Name" value="{{ old('service_list.0.last_name') }}" class="border-gray-300 rounded-md">
                                <input type="text" name="service_list[0][organization]" placeholder="Organization" value="{{ old('service_list.0.organization') }}" class="border-gray-300 rounded-md">
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <input type="email" name="service_list[0][email]" placeholder="Email" value="{{ old('service_list.0.email') }}" class="border-gray-300 rounded-md">
                                <input type="text" name="service_list[0][phone]" placeholder="Phone" value="{{ old('service_list.0.phone') }}" class="border-gray-300 rounded-md">
                            </div>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="text" name="service_list[0][address_line1]" placeholder="Address Line 1" value="{{ old('service_list.0.address_line1') }}" class="border-gray-300 rounded-md">
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="text" name="service_list[0][city]" placeholder="City" value="{{ old('service_list.0.city') }}" class="border-gray-300 rounded-md">
                                    <input type="text" name="service_list[0][state]" placeholder="State" value="{{ old('service_list.0.state') }}" class="border-gray-300 rounded-md">
                                    <input type="text" name="service_list[0][zip]" placeholder="ZIP" value="{{ old('service_list.0.zip') }}" class="border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addServiceEntry()" class="mt-2 text-blue-600 text-sm">+ Add Service Entry</button>
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
                    <button type="submit" name="action" value="validate" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors">
                        Validate
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
        let oseCount = 1, applicantCount = 1, protestantCount = 1, serviceCount = 1;

        function addOseNumber() {
            document.getElementById('ose-numbers').insertAdjacentHTML('beforeend', `
                <div class="flex gap-2">
                    <select name="ose_numbers[${oseCount}][basin_code]" class="border-gray-300 rounded-md">
                        <option value="RG">Rio Grande</option>
                        <option value="PE">Pecos</option>
                        <option value="CA">Canadian</option>
                    </select>
                    <input type="text" name="ose_numbers[${oseCount}][file_no_from]" placeholder="From" class="border-gray-300 rounded-md">
                    <input type="text" name="ose_numbers[${oseCount}][file_no_to]" placeholder="To" class="border-gray-300 rounded-md">
                </div>
            `);
            oseCount++;
        }

        function addParty(type) {
            const count = type === 'applicants' ? applicantCount++ : protestantCount++;
            document.getElementById(type).insertAdjacentHTML('beforeend', 
                `<input type="text" name="${type}[${count}]" placeholder="${type.slice(0, -1)} name" class="block w-full border-gray-300 rounded-md">`
            );
        }

        function addServiceEntry() {
            document.getElementById('service-list').insertAdjacentHTML('beforeend', `
                <div class="border rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <select name="service_list[${serviceCount}][type]" class="border-gray-300 rounded-md">
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                        </select>
                        <select name="service_list[${serviceCount}][method]" class="border-gray-300 rounded-md">
                            <option value="email">Email</option>
                            <option value="mail">Mail</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <input type="text" name="service_list[${serviceCount}][first_name]" placeholder="First Name" class="border-gray-300 rounded-md">
                        <input type="text" name="service_list[${serviceCount}][last_name]" placeholder="Last Name" class="border-gray-300 rounded-md">
                        <input type="text" name="service_list[${serviceCount}][organization]" placeholder="Organization" class="border-gray-300 rounded-md">
                    </div>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <input type="email" name="service_list[${serviceCount}][email]" placeholder="Email" class="border-gray-300 rounded-md">
                        <input type="text" name="service_list[${serviceCount}][phone]" placeholder="Phone" class="border-gray-300 rounded-md">
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        <input type="text" name="service_list[${serviceCount}][address_line1]" placeholder="Address Line 1" class="border-gray-300 rounded-md">
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" name="service_list[${serviceCount}][city]" placeholder="City" class="border-gray-300 rounded-md">
                            <input type="text" name="service_list[${serviceCount}][state]" placeholder="State" class="border-gray-300 rounded-md">
                            <input type="text" name="service_list[${serviceCount}][zip]" placeholder="ZIP" class="border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>
            `);
            serviceCount++;
        }
    </script>
</x-app-layout>