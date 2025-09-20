<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Please fix the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('cases.update', $case) }}" enctype="multipart/form-data" class="bg-white shadow-sm rounded-lg p-6">
                @csrf
                @method('PUT')
                
                <!-- Case Type -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Case Type *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="aggrieved" {{ old('case_type', $case->case_type) == 'aggrieved' ? 'checked' : '' }} required class="mr-2">
                            Aggrieved
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="protested" {{ old('case_type', $case->case_type) == 'protested' ? 'checked' : '' }} required class="mr-2">
                            Protested
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="case_type" value="compliance" {{ old('case_type', $case->case_type) == 'compliance' ? 'checked' : '' }} required class="mr-2">
                            Compliance Action
                        </label>
                    </div>
                </div>

                <!-- Caption -->
                <div class="mb-6">
                    <label for="caption" class="block text-sm font-medium text-gray-700">Caption *</label>
                    <textarea name="caption" id="caption" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('caption', $case->caption) }}</textarea>
                </div>

                <!-- OSE File Numbers -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">OSE File Numbers</label>
                    <div id="ose-numbers" class="space-y-2">
                        @forelse($case->oseFileNumbers as $index => $oseNumber)
                            <div class="flex gap-2">
                                <select name="ose_numbers[{{ $index }}][basin_code]" class="border-gray-300 rounded-md">
                                    <option value="RG" {{ $oseNumber->basin_code == 'RG' ? 'selected' : '' }}>Rio Grande</option>
                                    <option value="PE" {{ $oseNumber->basin_code == 'PE' ? 'selected' : '' }}>Pecos</option>
                                    <option value="CA" {{ $oseNumber->basin_code == 'CA' ? 'selected' : '' }}>Canadian</option>
                                </select>
                                <input type="text" name="ose_numbers[{{ $index }}][file_no_from]" placeholder="From" value="{{ $oseNumber->file_no_from }}" class="border-gray-300 rounded-md">
                                <input type="text" name="ose_numbers[{{ $index }}][file_no_to]" placeholder="To" value="{{ $oseNumber->file_no_to }}" class="border-gray-300 rounded-md">
                            </div>
                        @empty
                            <div class="flex gap-2">
                                <select name="ose_numbers[0][basin_code]" class="border-gray-300 rounded-md">
                                    <option value="RG">Rio Grande</option>
                                    <option value="PE">Pecos</option>
                                    <option value="CA">Canadian</option>
                                </select>
                                <input type="text" name="ose_numbers[0][file_no_from]" placeholder="From" class="border-gray-300 rounded-md">
                                <input type="text" name="ose_numbers[0][file_no_to]" placeholder="To" class="border-gray-300 rounded-md">
                            </div>
                        @endforelse
                    </div>
                    <button type="button" onclick="addOseNumber()" class="mt-2 text-blue-600 text-sm">+ Add Another</button>
                </div>

                <!-- Parties -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Parties</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Applicants *</label>
                        <div id="applicants" class="space-y-2">
                            @php $partyData = $case->metadata ?? []; @endphp
                            @if(isset($partyData['applicants']) && count($partyData['applicants']) > 0)
                                @foreach($partyData['applicants'] as $index => $applicant)
                                    <input type="text" name="applicants[{{ $index }}]" placeholder="Applicant name" value="{{ $applicant }}" required class="block w-full border-gray-300 rounded-md">
                                @endforeach
                            @else
                                <input type="text" name="applicants[0]" placeholder="Applicant name" required class="block w-full border-gray-300 rounded-md">
                            @endif
                        </div>
                        <button type="button" onclick="addParty('applicants')" class="mt-2 text-blue-600 text-sm">+ Add Applicant</button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Protestants</label>
                        <div id="protestants" class="space-y-2">
                            @if(isset($partyData['protestants']) && count($partyData['protestants']) > 0)
                                @foreach($partyData['protestants'] as $index => $protestant)
                                    <input type="text" name="protestants[{{ $index }}]" placeholder="Protestant name" value="{{ $protestant }}" class="block w-full border-gray-300 rounded-md">
                                @endforeach
                            @else
                                <input type="text" name="protestants[0]" placeholder="Protestant name" class="block w-full border-gray-300 rounded-md">
                            @endif
                        </div>
                        <button type="button" onclick="addParty('protestants')" class="mt-2 text-blue-600 text-sm">+ Add Protestant</button>
                    </div>
                </div>

                <!-- Existing Documents -->
                @if($case->documents->count() > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-4">Existing Documents</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($case->documents as $document)
                                <div class="p-3 border rounded-md">
                                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</p>
                                    <p class="text-sm text-gray-600">{{ $document->filename }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Document Uploads -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Upload New Documents (Optional)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Application (PDF)</label>
                            <input type="file" name="documents[application]" accept=".pdf" class="mt-1 block w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notice of Publication (DOCX)</label>
                            <input type="file" name="documents[notice_publication]" accept=".docx" class="mt-1 block w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Request to Docket (PDF)</label>
                            <input type="file" name="documents[request_to_docket]" accept=".pdf" class="mt-1 block w-full">
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
                    <div id="service-list" class="space-y-2">
                        @forelse($case->serviceList as $index => $service)
                            <div class="grid grid-cols-4 gap-2">
                                <input type="text" name="service_list[{{ $index }}][name]" placeholder="Name" value="{{ $service->person->first_name }} {{ $service->person->last_name }}" class="border-gray-300 rounded-md">
                                <input type="email" name="service_list[{{ $index }}][email]" placeholder="Email" value="{{ $service->email }}" class="border-gray-300 rounded-md">
                                <input type="text" name="service_list[{{ $index }}][address]" placeholder="Address" value="{{ $service->person->address_line1 }}" class="border-gray-300 rounded-md">
                                <select name="service_list[{{ $index }}][method]" class="border-gray-300 rounded-md">
                                    <option value="email" {{ $service->service_method == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="mail" {{ $service->service_method == 'mail' ? 'selected' : '' }}>Mail</option>
                                </select>
                            </div>
                        @empty
                            <div class="grid grid-cols-4 gap-2">
                                <input type="text" name="service_list[0][name]" placeholder="Name" class="border-gray-300 rounded-md">
                                <input type="email" name="service_list[0][email]" placeholder="Email" class="border-gray-300 rounded-md">
                                <input type="text" name="service_list[0][address]" placeholder="Address" class="border-gray-300 rounded-md">
                                <select name="service_list[0][method]" class="border-gray-300 rounded-md">
                                    <option value="email">Email</option>
                                    <option value="mail">Mail</option>
                                </select>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" onclick="addServiceEntry()" class="mt-2 text-blue-600 text-sm">+ Add Service Entry</button>
                </div>

                <!-- Affirmation -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="affirmation" required class="mr-2">
                        <span class="text-sm">Information provided is complete and correct *</span>
                    </label>
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
                    <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let oseCount = {{ $case->oseFileNumbers->count() ?: 1 }};
        let applicantCount = {{ isset($partyData['applicants']) ? count($partyData['applicants']) : 1 }};
        let protestantCount = {{ isset($partyData['protestants']) ? count($partyData['protestants']) : 1 }};
        let serviceCount = {{ $case->serviceList->count() ?: 1 }};

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
                <div class="grid grid-cols-4 gap-2">
                    <input type="text" name="service_list[${serviceCount}][name]" placeholder="Name" class="border-gray-300 rounded-md">
                    <input type="email" name="service_list[${serviceCount}][email]" placeholder="Email" class="border-gray-300 rounded-md">
                    <input type="text" name="service_list[${serviceCount}][address]" placeholder="Address" class="border-gray-300 rounded-md">
                    <select name="service_list[${serviceCount}][method]" class="border-gray-300 rounded-md">
                        <option value="email">Email</option>
                        <option value="mail">Mail</option>
                    </select>
                </div>
            `);
            serviceCount++;
        }
    </script>
</x-app-layout>