<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign ALU Clerk / Paralegal - Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium">Case Information</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $case->caption }}</p>
                    <p class="text-sm text-gray-500">Status: {{ $case->workflow_status_label }}</p>
                </div>

                <form action="{{ route('cases.assign-alu-clerk.store', $case) }}" method="POST">
                    @csrf
                    @php
                        $selectedSupportIds = $case->assignments()
                            ->whereIn('assignment_type', ['alu_clerk', 'alu_paralegal'])
                            ->pluck('user_id');
                    @endphp
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select ALU Clerks / Paralegals</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                            @foreach($clerks as $clerk)
                                <label class="flex items-center">
                                    <input type="checkbox" name="clerk_ids[]" value="{{ $clerk->id }}" 
                                           {{ $selectedSupportIds->contains($clerk->id) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 mr-2">
                                    <span class="text-sm">{{ $clerk->name }} ({{ $clerk->email }}) @if($clerk->isALUParalegal()) <span class="text-xs text-indigo-600">Paralegal</span> @endif</span>
                                </label>
                            @endforeach
                        </div>
                        @error('clerk_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            {{ $case->assignedAluClerk ? 'Update Assignment' : 'Assign ALU Clerk / Paralegal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
