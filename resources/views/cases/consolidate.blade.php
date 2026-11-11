<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Consolidate Cases</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <strong>{{ $case->case_no }}</strong> and the cases selected below will become read-only historical records.
                    A new active case will receive all future filings. Existing documents will not be moved or copied.
                </div>

                <form method="POST" action="{{ route('cases.consolidate.store', $case) }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Additional source cases</label>
                        <select id="sourceCaseIds" name="source_case_ids[]" multiple required size="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($eligibleCases as $eligibleCase)
                                <option value="{{ $eligibleCase->id }}" @selected(in_array($eligibleCase->id, old('source_case_ids', [])))>
                                    {{ $eligibleCase->case_no }} — {{ $eligibleCase->caption }} ({{ $eligibleCase->workflow_status_label }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Use Ctrl/Cmd to select more than one additional case.</p>
                        @error('source_case_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('source_case_ids.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Combined case number</label>
                            <input id="combinedCaseNumber" value="" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Generated automatically from the source cases, oldest first.</p>
                            @error('case_no') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Case type</label>
                            <select name="case_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach(['aggrieved' => 'Aggrieved', 'protested' => 'Protested', 'compliance' => 'Compliance'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('case_type', $case->case_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Combined caption</label>
                        <textarea id="combinedCaption" rows="4" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm"></textarea>
                        <p class="mt-1 text-xs text-gray-500">Includes every source caption, oldest first, followed by Consolidated.</p>
                        @error('caption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Consolidation order date</label>
                        <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('order_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason or order reference</label>
                        <textarea name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('reason') }}</textarea>
                        @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-md border border-gray-200 p-4">
                        <input type="checkbox" name="confirmation" value="1" required class="mt-1 rounded border-gray-300">
                        <span class="text-sm text-gray-700">I confirm that the selected source cases will become read-only and that all future filings must use the new combined case.</span>
                    </label>
                    @error('confirmation') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('cases.show', $case) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</a>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create Combined Case</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const primaryCase = @js([
                'id' => $case->id,
                'case_no' => $case->case_no,
                'caption' => $case->caption,
                'created_at' => $case->created_at?->toIso8601String(),
            ]);
            const eligibleCases = @js($eligibleCases->map(fn ($eligibleCase) => [
                'id' => $eligibleCase->id,
                'case_no' => $eligibleCase->case_no,
                'caption' => $eligibleCase->caption,
                'created_at' => $eligibleCase->created_at?->toIso8601String(),
            ])->values());
            const casesById = new Map(eligibleCases.map(item => [String(item.id), item]));
            const sourceSelect = document.getElementById('sourceCaseIds');
            const caseNumber = document.getElementById('combinedCaseNumber');
            const caption = document.getElementById('combinedCaption');

            function refreshCombinedIdentity() {
                const selected = Array.from(sourceSelect.selectedOptions)
                    .map(option => casesById.get(option.value))
                    .filter(Boolean);
                const sources = [primaryCase, ...selected].sort((left, right) => {
                    const dateComparison = String(left.created_at).localeCompare(String(right.created_at));
                    return dateComparison || Number(left.id) - Number(right.id);
                });

                caseNumber.value = `${sources.map(item => item.case_no).join(' & ')} (Consol.)`;
                caption.value = `${sources.map(item => item.caption.trim()).join(' | ')} | Consolidated`;
            }

            sourceSelect.addEventListener('change', refreshCombinedIdentity);
            refreshCombinedIdentity();
        });
    </script>
</x-app-layout>
