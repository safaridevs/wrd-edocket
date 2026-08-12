<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign Attorney</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Case: {{ $case->case_no }}</h3>
                <p class="text-gray-600 mb-6">{{ $case->caption }}</p>

                <form method="POST" action="{{ route('cases.assign-attorney.store', $case) }}">
                    @csrf
                    
                    @php
                        $selectedAttorneyIds = collect(old('attorney_ids', $case->aluAttorneys->pluck('id')->all()))
                            ->map(fn ($id) => (string) $id)
                            ->all();
                    @endphp

                    <div class="mb-6" data-attorney-multiselect>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign ALU Attorneys / WRD Contract Attorneys</label>

                        <div class="relative">
                            <button type="button"
                                    data-attorney-multiselect-toggle
                                    aria-expanded="false"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <span data-attorney-multiselect-label>Select attorneys</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </button>

                            <div data-attorney-multiselect-menu
                                 class="absolute z-30 mt-1 hidden w-full rounded-md border border-gray-300 bg-white shadow-lg">
                                <div class="border-b border-gray-200 p-2">
                                    <input type="search"
                                           data-attorney-multiselect-search
                                           placeholder="Search attorneys..."
                                           class="block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div class="max-h-64 overflow-y-auto py-1">
                                    @forelse($attorneys as $attorney)
                                        @php
                                            $attorneyId = (string) $attorney->id;
                                            $attorneyName = $attorney->getDisplayName();
                                            $attorneyMeta = trim(($attorney->initials ? "({$attorney->initials}) " : '') . ($attorney->email ?? ''));
                                        @endphp
                                        <label class="flex cursor-pointer items-start gap-3 px-3 py-2 text-sm hover:bg-gray-50"
                                               data-attorney-option
                                               data-attorney-name="{{ strtolower($attorneyName . ' ' . $attorneyMeta) }}">
                                            <input type="checkbox"
                                                   name="attorney_ids[]"
                                                   value="{{ $attorney->id }}"
                                                   {{ in_array($attorneyId, $selectedAttorneyIds, true) ? 'checked' : '' }}
                                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                   data-attorney-checkbox
                                                   data-attorney-label="{{ $attorneyName }}">
                                            <span class="min-w-0">
                                                <span class="block truncate font-medium text-gray-900">{{ $attorneyName }}</span>
                                                <span class="block truncate text-xs text-gray-500">
                                                    {{ $attorneyMeta ?: 'No email listed' }}
                                                    @if($attorney->isContractAttorney())
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

                        <div data-attorney-multiselect-selected class="mt-3 flex flex-wrap gap-2"></div>
                        <p data-attorney-multiselect-empty class="mt-2 text-sm text-gray-500">No attorneys selected.</p>

                        <noscript>
                            <div class="mt-3 space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                            @foreach($attorneys as $attorney)
                                <label class="flex items-center">
                                    <input type="checkbox" name="attorney_ids[]" value="{{ $attorney->id }}"
                                           {{ in_array((string) $attorney->id, $selectedAttorneyIds, true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 mr-2">
                                    <span class="text-sm">
                                        {{ $attorney->getDisplayName() }} ({{ $attorney->initials }})
                                        @if($attorney->isContractAttorney())
                                            <span class="text-xs text-indigo-600">Contract</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                            </div>
                        </noscript>

                        @error('attorney_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Assign Attorney</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-attorney-multiselect]').forEach((root) => {
                const toggle = root.querySelector('[data-attorney-multiselect-toggle]');
                const menu = root.querySelector('[data-attorney-multiselect-menu]');
                const label = root.querySelector('[data-attorney-multiselect-label]');
                const search = root.querySelector('[data-attorney-multiselect-search]');
                const selectedContainer = root.querySelector('[data-attorney-multiselect-selected]');
                const emptyMessage = root.querySelector('[data-attorney-multiselect-empty]');
                const checkboxes = Array.from(root.querySelectorAll('[data-attorney-checkbox]'));
                const options = Array.from(root.querySelectorAll('[data-attorney-option]'));

                const closeMenu = () => {
                    menu.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                };

                const updateSelected = () => {
                    const selected = checkboxes.filter((checkbox) => checkbox.checked);
                    label.textContent = selected.length === 0
                        ? 'Select attorneys'
                        : `${selected.length} attorney${selected.length === 1 ? '' : 's'} selected`;

                    selectedContainer.innerHTML = '';
                    selected.forEach((checkbox) => {
                        const tag = document.createElement('span');
                        tag.className = 'inline-flex max-w-full items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700';
                        tag.textContent = checkbox.dataset.attorneyLabel;
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
                        option.classList.toggle('hidden', !option.dataset.attorneyName.includes(query));
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
</x-app-layout>
