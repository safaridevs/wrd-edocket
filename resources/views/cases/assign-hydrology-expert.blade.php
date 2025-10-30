<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign Hydrology Expert - Case {{ $case->case_no }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('cases.assign-hydrology-expert.store', $case) }}" method="POST">
                    @csrf
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Hydrology Experts</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                            @foreach($experts as $expert)
                                <label class="flex items-center">
                                    <input type="checkbox" name="expert_ids[]" value="{{ $expert->id }}" 
                                           {{ $case->hydrologyExperts->contains($expert->id) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 mr-2">
                                    <span class="text-sm">{{ $expert->name }} ({{ $expert->email }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('expert_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            Assign Expert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>