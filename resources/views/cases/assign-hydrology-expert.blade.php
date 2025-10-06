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
                        <label for="expert_id" class="block text-sm font-medium text-gray-700 mb-2">Select Hydrology Expert</label>
                        <select name="expert_id" id="expert_id" required class="block w-full border-gray-300 rounded-md">
                            <option value="">Choose an expert...</option>
                            @foreach($experts as $expert)
                                <option value="{{ $expert->id }}" {{ $case->assigned_hydrology_expert_id == $expert->id ? 'selected' : '' }}>
                                    {{ $expert->name }} ({{ $expert->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('expert_id')
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