<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Case {{ $case->case_no }}</h2>
    </x-slot>

    @php
        $openRejection = $case->rejections->firstWhere('status', 'open');
        $rejectionCategoryLabels = [
            'missing_document' => 'Missing Document',
            'caption_issue' => 'Caption Issue',
            'party_issue' => 'Party Issue',
            'service_issue' => 'Service Issue',
            'ose_issue' => 'OSE Issue',
            'document_issue' => 'Document Issue',
            'filing_issue' => 'Filing Issue',
            'other' => 'Other',
        ];
    @endphp

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

            @if($openRejection)
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-red-900">Open Correction Cycle</h3>
                            <p class="text-sm text-red-800 mt-1">{{ $openRejection->reason_summary }}</p>
                            <p class="text-xs text-red-700 mt-2">
                                Rejected {{ $openRejection->rejected_at?->format('M j, Y g:i A') }}
                                @if($openRejection->rejectedBy)
                                    by {{ $openRejection->rejectedBy->getDisplayName() }}
                                @endif
                            </p>
                        </div>
                        <div class="text-xs font-medium text-red-700">
                            {{ $openRejection->openItems->count() }} item{{ $openRejection->openItems->count() === 1 ? '' : 's' }} still open
                        </div>
                    </div>
                    <p class="text-sm text-red-700 mt-4">Before you resubmit, document the fix for every item below and mark each one resolved.</p>
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

                @if($openRejection)
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Correction Items</h3>
                    <div class="space-y-4">
                        @foreach($openRejection->items as $item)
                            <div class="border rounded-lg p-4 {{ $item->resolved_at ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide {{ $item->resolved_at ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $rejectionCategoryLabels[$item->category] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $item->category)) }}
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 mt-1">{{ $item->item_note }}</div>
                                        @if($item->required_action)
                                            <div class="text-sm text-gray-700 mt-2"><strong>Required Action:</strong> {{ $item->required_action }}</div>
                                        @endif
                                    </div>
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $item->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $item->resolved_at ? 'Resolved' : 'Open' }}
                                    </span>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Note *</label>
                                    <textarea
                                        name="rejection_items[{{ $item->id }}][resolution_note]"
                                        rows="3"
                                        class="block w-full border-gray-300 rounded-md shadow-sm"
                                        placeholder="Document the exact correction you made for this item."
                                    >{{ old("rejection_items.{$item->id}.resolution_note", $item->resolution_note) }}</textarea>
                                    @error("rejection_items.{$item->id}.resolution_note")
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-3">
                                    <label class="inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            name="rejection_items[{{ $item->id }}][mark_resolved]"
                                            value="1"
                                            class="mr-2"
                                            {{ old("rejection_items.{$item->id}.mark_resolved", $item->resolved_at ? '1' : null) ? 'checked' : '' }}
                                        >
                                        <span class="text-sm text-gray-700">This correction item has been fully addressed and is ready for HU review.</span>
                                    </label>
                                    @error("rejection_items.{$item->id}.mark_resolved")
                                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif





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
