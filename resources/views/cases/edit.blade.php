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
                    <p class="text-sm text-amber-600 mb-3">⚠️ Changing OSE file numbers will affect the naming convention of uploaded documents.</p>
                    <div id="ose-numbers" class="space-y-2">
                        @forelse($case->oseFileNumbers as $index => $oseNumber)
                            @php
                                $fromParts = $oseNumber->file_no_from ? explode('-', $oseNumber->file_no_from, 2) : ['', ''];
                                $toParts = $oseNumber->file_no_to ? explode('-', $oseNumber->file_no_to, 2) : ['', ''];
                                $fromBasin = $fromParts[0] ?? '';
                                $fromNumber = $fromParts[1] ?? '';
                                $toBasin = $toParts[0] ?? '';
                                $toNumber = $toParts[1] ?? '';
                            @endphp
                            <div class="grid grid-cols-5 gap-2">
                                <select name="ose_numbers[{{ $index }}][basin_code_from]" class="border-gray-300 rounded-md">
                                    <option value="">Basin</option>
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}" {{ $fromBasin == $code->initial ? 'selected' : '' }}>{{ $code->initial }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="ose_numbers[{{ $index }}][file_no_from]" placeholder="From" value="{{ $fromNumber }}" class="border-gray-300 rounded-md">
                                <span class="flex items-center justify-center text-gray-500">to</span>
                                <select name="ose_numbers[{{ $index }}][basin_code_to]" class="border-gray-300 rounded-md">
                                    <option value="">Basin</option>
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}" {{ $toBasin == $code->initial ? 'selected' : '' }}>{{ $code->initial }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="ose_numbers[{{ $index }}][file_no_to]" placeholder="To" value="{{ $toNumber }}" class="border-gray-300 rounded-md">
                            </div>
                        @empty
                            <div class="grid grid-cols-5 gap-2">
                                <select name="ose_numbers[0][basin_code_from]" class="border-gray-300 rounded-md">
                                    <option value="">Basin</option>
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}">{{ $code->initial }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="ose_numbers[0][file_no_from]" placeholder="From" class="border-gray-300 rounded-md">
                                <span class="flex items-center justify-center text-gray-500">to</span>
                                <select name="ose_numbers[0][basin_code_to]" class="border-gray-300 rounded-md">
                                    <option value="">Basin</option>
                                    @foreach($basinCodes as $code)
                                        <option value="{{ $code->initial }}">{{ $code->initial }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="ose_numbers[0][file_no_to]" placeholder="To" class="border-gray-300 rounded-md">
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Management Links -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Case Management</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('cases.parties.manage', $case) }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Manage Parties</h4>
                                    <p class="text-sm text-gray-600">{{ $case->parties->count() }} parties</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                        <a href="{{ route('cases.documents.manage', $case) }}" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Manage Documents</h4>
                                    <p class="text-sm text-gray-600">{{ $case->documents->count() }} documents</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    </div>
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


</x-app-layout>