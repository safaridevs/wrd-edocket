@if(auth()->user()->canAssignAttorneys())
@php
    $availableAttorneys = \App\Models\User::whereAnyCurrentRole(['alu_atty', 'external_attorney'])->orderBy('name')->get();
    $availableClerks = \App\Models\User::whereAnyCurrentRole(['alu_clerk', 'alu_paralegal'])->orderBy('name')->get();
    $selectedAttorneyIds = collect(old('assigned_attorneys', []))
        ->when(auth()->user()->isALUAttorney(), fn ($ids) => $ids->push(auth()->id()))
        ->map(fn ($id) => (string) $id)
        ->unique()
        ->values()
        ->all();
    $selectedClerkIds = collect(old('assigned_clerks', []))->map(fn ($id) => (string) $id)->all();
@endphp

<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Assign ALU Attorneys / WRD Contract Attorneys</label>
    <div class="rounded-lg border bg-gray-50 p-4" data-assignment-multiselect data-placeholder="Select attorneys">
        <div class="relative">
            <button type="button"
                    data-assignment-toggle
                    aria-expanded="false"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <span data-assignment-label>Select attorneys</span>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
            </button>

            <div data-assignment-menu class="absolute z-40 mt-1 hidden w-full rounded-md border border-gray-300 bg-white shadow-lg">
                <div class="border-b border-gray-200 p-2">
                    <input type="search"
                           data-assignment-search
                           placeholder="Search attorneys..."
                           class="block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="max-h-64 overflow-y-auto py-1">
                    @forelse($availableAttorneys as $attorney)
                        @php
                            $attorneyId = (string) $attorney->id;
                            $attorneySearch = strtolower(trim($attorney->name . ' ' . $attorney->email));
                        @endphp
                        <label class="flex cursor-pointer items-start gap-3 px-3 py-2 text-sm hover:bg-white"
                               data-assignment-option
                               data-assignment-search-text="{{ $attorneySearch }}">
                            <input type="checkbox"
                                   name="assigned_attorneys[]"
                                   value="{{ $attorney->id }}"
                                   {{ in_array($attorneyId, $selectedAttorneyIds, true) ? 'checked' : '' }}
                                   {{ auth()->user()->isALUAttorney() && (int) $attorney->id === (int) auth()->id() ? 'disabled' : '' }}
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   data-assignment-checkbox
                                   data-assignment-item-label="{{ $attorney->name }}">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-gray-900">{{ $attorney->name }}</span>
                                <span class="block truncate text-xs text-gray-600">
                                    {{ $attorney->email }}
                                    @if(auth()->user()->isALUAttorney() && (int) $attorney->id === (int) auth()->id())
                                        <span class="ml-1 font-medium text-green-700">Creating attorney</span>
                                    @endif
                                    @if($attorney->isExternalAttorney())
                                        <span class="ml-1 font-medium text-indigo-600">Contract</span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @empty
                        <div class="px-3 py-4 text-sm text-gray-500">No attorneys are available for assignment.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div data-assignment-selected class="mt-3 flex flex-wrap gap-2"></div>
        <p data-assignment-empty class="mt-2 text-sm text-gray-500">No attorneys selected.</p>
        <p class="text-xs text-gray-500 mt-2">Select attorneys representing WRD on this case. Contract attorneys stay limited to assigned cases.</p>
    </div>
    @error('assigned_attorneys')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Assign ALU Clerks / Paralegals</label>
    <div class="rounded-lg border bg-gray-50 p-4" data-assignment-multiselect data-placeholder="Select clerks or paralegals">
        <div class="relative">
            <button type="button"
                    data-assignment-toggle
                    aria-expanded="false"
                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <span data-assignment-label>Select clerks or paralegals</span>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
            </button>

            <div data-assignment-menu class="absolute z-40 mt-1 hidden w-full rounded-md border border-gray-300 bg-white shadow-lg">
                <div class="border-b border-gray-200 p-2">
                    <input type="search"
                           data-assignment-search
                           placeholder="Search clerks or paralegals..."
                           class="block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="max-h-64 overflow-y-auto py-1">
                    @forelse($availableClerks as $clerk)
                        @php
                            $clerkId = (string) $clerk->id;
                            $clerkSearch = strtolower(trim($clerk->name . ' ' . $clerk->email));
                        @endphp
                        <label class="flex cursor-pointer items-start gap-3 px-3 py-2 text-sm hover:bg-white"
                               data-assignment-option
                               data-assignment-search-text="{{ $clerkSearch }}">
                            <input type="checkbox"
                                   name="assigned_clerks[]"
                                   value="{{ $clerk->id }}"
                                   {{ in_array($clerkId, $selectedClerkIds, true) ? 'checked' : '' }}
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   data-assignment-checkbox
                                   data-assignment-item-label="{{ $clerk->name }}">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-gray-900">{{ $clerk->name }}</span>
                                <span class="block truncate text-xs text-gray-600">
                                    {{ $clerk->email }}
                                    @if($clerk->isALUParalegal())
                                        <span class="ml-1 font-medium text-indigo-600">Paralegal</span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @empty
                        <div class="px-3 py-4 text-sm text-gray-500">No clerks or paralegals are available for assignment.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div data-assignment-selected class="mt-3 flex flex-wrap gap-2"></div>
        <p data-assignment-empty class="mt-2 text-sm text-gray-500">No clerks or paralegals selected.</p>
        <p class="text-xs text-gray-500 mt-2">Select one or more ALU clerks or paralegals to assign to this case</p>
    </div>
    @error('assigned_clerks')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-assignment-multiselect]').forEach((root) => {
            const toggle = root.querySelector('[data-assignment-toggle]');
            const menu = root.querySelector('[data-assignment-menu]');
            const label = root.querySelector('[data-assignment-label]');
            const search = root.querySelector('[data-assignment-search]');
            const selectedContainer = root.querySelector('[data-assignment-selected]');
            const emptyMessage = root.querySelector('[data-assignment-empty]');
            const checkboxes = Array.from(root.querySelectorAll('[data-assignment-checkbox]'));
            const options = Array.from(root.querySelectorAll('[data-assignment-option]'));
            const placeholder = root.dataset.placeholder || 'Select people';

            const closeMenu = () => {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            };

            const updateSelected = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                label.textContent = selected.length === 0
                    ? placeholder
                    : `${selected.length} selected`;

                selectedContainer.innerHTML = '';
                selected.forEach((checkbox) => {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex max-w-full items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700';
                    tag.textContent = checkbox.dataset.assignmentItemLabel;
                    selectedContainer.appendChild(tag);
                });

                emptyMessage.classList.toggle('hidden', selected.length > 0);
            };

            toggle.addEventListener('click', () => {
                const isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden', isOpen);
                toggle.setAttribute('aria-expanded', String(!isOpen));
                if (!isOpen) {
                    search.focus();
                }
            });

            search.addEventListener('input', () => {
                const query = search.value.trim().toLowerCase();
                options.forEach((option) => {
                    option.classList.toggle('hidden', !option.dataset.assignmentSearchText.includes(query));
                });
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelected);
            });

            document.addEventListener('click', (event) => {
                if (!root.contains(event.target)) {
                    closeMenu();
                }
            });

            updateSelected();
        });
    });
</script>
@endif
