<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('E-Docket Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-2">My Cases</h3>
                        <p class="text-3xl font-bold text-blue-600">
                            @if(auth()->user()->role === 'party')
                                {{ \App\Models\CaseModel::whereHas('parties.person', function($query) {
                                    $query->where('email', auth()->user()->email);
                                })->count() }}
                            @else
                                {{ auth()->user()->createdCases()->count() }}
                            @endif
                        </p>
                        <a href="{{ route('cases.index') }}" class="text-blue-500 hover:underline">View All</a>
                    </div>
                </div>

                @if(auth()->user()->isHearingUnit())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-2">Pending Review</h3>
                        <p class="text-3xl font-bold text-orange-600">{{ auth()->user()->assignedCases()->where('status', 'submitted_to_hu')->count() }}</p>
                        <a href="{{ route('cases.index') }}" class="text-orange-500 hover:underline">Review Cases</a>
                    </div>
                </div>
                @endif

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-2">Documents</h3>
                        <p class="text-3xl font-bold text-red-600">{{ auth()->user()->documents()->count() }}</p>
                        <a href="{{ route('cases.index') }}" class="text-red-500 hover:underline">View All</a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="flex space-x-4">
                        @if(auth()->user()->canCreateCase())
                        <a href="{{ route('cases.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create New Case
                        </a>
                        @endif
                        <a href="{{ route('cases.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            View Cases
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
