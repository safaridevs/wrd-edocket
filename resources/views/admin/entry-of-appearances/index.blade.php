<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800">Appearance Requests</h2></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-4 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'unverified' => 'Unverified'] as $value => $label)
                <a href="{{ route('admin.entry-of-appearances.index', array_filter(['status' => $value])) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ request('status', '') === $value ? 'bg-blue-700 text-white' : 'bg-white text-gray-700 shadow' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Submitted</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Case / Party</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Attorney</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($appearances as $appearance)
                    <tr><td class="px-5 py-4 text-sm text-gray-600">{{ $appearance->created_at->format('M j, Y g:i A') }}</td><td class="px-5 py-4"><div class="font-medium text-gray-900">{{ $appearance->case->case_no }}</div><div class="text-sm text-gray-500">{{ $appearance->representedParty?->person?->full_name }}</div></td><td class="px-5 py-4"><div class="font-medium text-gray-900">{{ $appearance->attorney_name }}</div><div class="text-sm text-gray-500">{{ $appearance->law_firm }}</div></td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $appearance->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($appearance->status === 'approved' ? 'bg-green-100 text-green-800' : ($appearance->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700')) }}">{{ ucfirst($appearance->status) }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('admin.entry-of-appearances.show', $appearance) }}" class="font-medium text-blue-700 hover:text-blue-900">Review</a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No appearance requests found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($appearances->hasPages())<div class="border-t px-5 py-4">{{ $appearances->links() }}</div>@endif
        </div>
    </div></div>
</x-app-layout>
