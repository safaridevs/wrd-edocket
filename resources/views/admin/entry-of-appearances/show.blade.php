<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h2 class="text-xl font-semibold leading-tight text-gray-800">Review Entry of Appearance</h2><a href="{{ route('admin.entry-of-appearances.index') }}" class="text-sm font-medium text-blue-700">Back to requests</a></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))<div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-md bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>@endif

        <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><h3 class="text-lg font-semibold text-gray-900">Case {{ $appearance->case->case_no }}</h3><p class="mt-1 text-gray-600">Representing: <strong>{{ $appearance->representedParty?->person?->full_name }}</strong></p></div><span class="rounded-full px-3 py-1 text-sm font-semibold {{ $appearance->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($appearance->status === 'approved' ? 'bg-green-100 text-green-800' : ($appearance->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700')) }}">{{ ucfirst($appearance->status) }}</span></div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg bg-white p-6 shadow"><h3 class="mb-4 text-lg font-semibold text-gray-900">Attorney</h3><dl class="space-y-3 text-sm"><div><dt class="text-gray-500">Name</dt><dd class="font-medium text-gray-900">{{ $appearance->attorney_name }}</dd></div><div><dt class="text-gray-500">Law firm</dt><dd>{{ $appearance->law_firm }}</dd></div><div><dt class="text-gray-500">Email</dt><dd>{{ $appearance->email }} @if($appearance->email_verified_at)<span class="ml-2 text-green-700">Verified</span>@endif</dd></div><div><dt class="text-gray-500">Phone</dt><dd>{{ $appearance->phone }}</dd></div><div><dt class="text-gray-500">Effective date</dt><dd>{{ $appearance->effective_date?->format('M j, Y') ?? 'Date of approval' }}</dd></div></dl></div>
            <div class="rounded-lg bg-white p-6 shadow"><h3 class="mb-4 text-lg font-semibold text-gray-900">Contact and submission</h3><address class="text-sm not-italic text-gray-700">{{ $appearance->address_line1 }}<br>@if($appearance->address_line2){{ $appearance->address_line2 }}<br>@endif{{ $appearance->city }}, {{ $appearance->state }} {{ $appearance->zip }}</address><p class="mt-4 text-sm text-gray-500">Submitted {{ $appearance->created_at->format('M j, Y g:i A') }}</p>@if($appearance->notes)<div class="mt-4 border-t pt-4"><div class="text-sm text-gray-500">Notes</div><p class="mt-1 whitespace-pre-line text-sm text-gray-800">{{ $appearance->notes }}</p></div>@endif</div>
        </div>

        @if($appearance->status === 'pending')
            <div class="grid gap-6 md:grid-cols-2">
                <form method="POST" action="{{ route('admin.entry-of-appearances.approve', $appearance) }}" class="rounded-lg border border-green-200 bg-green-50 p-6" onsubmit="return confirm('Approve this appearance and add the attorney as counsel for this party?')">@csrf<h3 class="font-semibold text-green-900">Approve</h3><p class="mt-2 text-sm text-green-800">This creates or updates the Person record, representation, and service-list entry.</p><button class="mt-4 rounded-md bg-green-700 px-4 py-2 font-semibold text-white hover:bg-green-800">Approve Appearance</button></form>
                <form method="POST" action="{{ route('admin.entry-of-appearances.reject', $appearance) }}" class="rounded-lg border border-red-200 bg-red-50 p-6">@csrf<h3 class="font-semibold text-red-900">Reject</h3><label class="mt-3 block text-sm font-medium text-red-900">Reason</label><textarea name="rejection_reason" required rows="3" class="mt-1 block w-full rounded-md border-red-300">{{ old('rejection_reason') }}</textarea><button class="mt-4 rounded-md bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-800">Reject Appearance</button></form>
            </div>
        @elseif($appearance->reviewed_at)
            <div class="rounded-lg bg-white p-6 shadow"><p class="text-sm text-gray-700">Reviewed by {{ $appearance->reviewer?->name ?? 'Unknown user' }} on {{ $appearance->reviewed_at->format('M j, Y g:i A') }}.</p>@if($appearance->rejection_reason)<p class="mt-2 text-sm text-red-700"><strong>Reason:</strong> {{ $appearance->rejection_reason }}</p>@endif</div>
        @endif
    </div></div>
</x-app-layout>
