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
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Attorney</label>
                        <select name="attorney_id" required class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">Choose an attorney...</option>
                            @foreach($attorneys as $attorney)
                                <option value="{{ $attorney->id }}" {{ $case->assigned_attorney_id == $attorney->id ? 'selected' : '' }}>
                                    {{ $attorney->name }} ({{ $attorney->initials }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('cases.show', $case) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Assign Attorney</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>