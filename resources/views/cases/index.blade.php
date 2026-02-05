<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if(auth()->user()->isHUAdmin() || auth()->user()->isHULawClerk())
                    Filed Pleadings
                @else
                    My Cases
                @endif
            </h2>
            @if(auth()->user()->canCreateCase())
            <a href="{{ route('cases.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md">New Case</a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($cases as $case)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $case->case_no }}</div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($case->caption, 60) }}</div>
                                    @php
                                        $userRole = null;
                                        // Check if direct party
                                        $isDirectParty = $case->parties()->whereHas('person', function($query) {
                                            $query->where('email', auth()->user()->email);
                                        })->first();

                                        if ($isDirectParty) {
                                            $userRole = ucfirst($isDirectParty->role) . ' (Self)';
                                        } else {
                                            // Check if attorney representing a party
                                            $attorney = \App\Models\Attorney::where('email', auth()->user()->email)->first();
                                            if ($attorney) {
                                                // Find counsel party record for this attorney
                                                $counselParty = $case->parties()->where('role', 'counsel')
                                                    ->whereHas('person', function($query) {
                                                        $query->where('email', auth()->user()->email);
                                                    })->first();

                                                if ($counselParty && $counselParty->client_party_id) {
                                                    // Get the client this attorney represents
                                                    $clientParty = $case->parties()->find($counselParty->client_party_id);
                                                    if ($clientParty) {
                                                        $userRole = 'Attorney for ' . $clientParty->person->full_name;
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    @if($userRole)
                                        <div class="text-xs text-blue-600 font-medium mt-1">{{ $userRole }}</div>
                                    @else
                                        <div class="text-xs text-gray-500 font-medium mt-1">View Only</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst($case->case_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $case->status === 'active' ? 'bg-green-100 text-green-800' :
                                       ($case->status === 'submitted_to_hu' ? 'bg-yellow-100 text-yellow-800' :
                                        ($case->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800')) }}">
                                    {{ $case->status === 'approved' ? 'Accepted' : ucfirst(str_replace('_', ' ', $case->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $case->submitted_at?->format('M j, Y') ?? 'Not submitted' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('cases.show', $case) }}" class="text-blue-600 hover:text-blue-900">View</a>

                                {{-- @if($case->status === 'submitted_to_hu' && auth()->user()->canAcceptFilings())
                                <a href="{{ route('cases.hu-review', $case) }}" class="text-green-600 hover:text-green-900">Review</a>
                                @endif --}}

                                @if(($case->status === 'active' || $case->status === 'approved') && auth()->user()->canFileToCase() && auth()->user()->canAccessCase($case))
                                <a href="{{ route('cases.documents.upload', $case) }}" class="text-purple-600 hover:text-purple-900">File Doc</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($cases->isEmpty())
                <div class="text-center py-12">
                    <div class="text-gray-500">No cases found.</div>
                    @if(auth()->user()->canCreateCase())
                    <a href="{{ route('cases.create') }}" class="mt-2 text-blue-600 hover:text-blue-800">Create your first case</a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
