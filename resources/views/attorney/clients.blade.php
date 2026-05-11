<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Clients') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($relationships->isEmpty())
                        <p class="text-sm text-gray-600">No active client representations were found.</p>
                    @else
                        <div class="divide-y divide-gray-200">
                            @foreach($relationships as $relationship)
                                <div class="py-4 first:pt-0 last:pb-0">
                                    <div class="font-medium text-gray-900">
                                        {{ $relationship->client?->person?->full_name ?? 'Unknown client' }}
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        Case: {{ $relationship->case?->case_no ?? 'Unknown case' }}
                                    </div>
                                    @if($relationship->case)
                                        <a href="{{ route('cases.show', $relationship->case) }}" class="mt-2 inline-block text-sm text-blue-600 hover:text-blue-800">
                                            View case
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
