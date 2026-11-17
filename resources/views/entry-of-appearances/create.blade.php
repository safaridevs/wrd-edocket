@extends('layouts.public')

@section('content')
<div class="mx-auto max-w-4xl px-4 sm:px-0">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Entry of Appearance</h1>
        <p class="mt-2 text-gray-600">Request recognition as counsel for a party in an active E-Docket case. No account is required.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Please correct the highlighted information.</p>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('entry-of-appearances.store') }}" class="space-y-6">
        @csrf
        <section class="rounded-lg bg-white p-6 shadow">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-700 font-bold text-white">1</span>
                <div><h2 class="text-xl font-semibold text-gray-900">Match the case and party</h2><p class="text-sm text-gray-500">Both values must match the E-Docket record.</p></div>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label for="case_number" class="block text-sm font-medium text-gray-700">Case number</label><input id="case_number" name="case_number" value="{{ old('case_number', $caseNumber) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('case_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="party_name" class="block text-sm font-medium text-gray-700">Name of party being represented</label><input id="party_name" name="party_name" value="{{ old('party_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('party_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-700 font-bold text-white">2</span>
                <div><h2 class="text-xl font-semibold text-gray-900">Attorney information</h2><p class="text-sm text-gray-500">Approval notices will be sent to the email below.</p></div>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="grid grid-cols-2 gap-3 md:col-span-2 md:grid-cols-5">
                    <div><label class="block text-sm font-medium text-gray-700">Prefix</label><input name="attorney_prefix" value="{{ old('attorney_prefix') }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">First name</label><input name="attorney_first_name" value="{{ old('attorney_first_name') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Middle name</label><input name="attorney_middle_name" value="{{ old('attorney_middle_name') }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Last name</label><input name="attorney_last_name" value="{{ old('attorney_last_name') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Suffix</label><input name="attorney_suffix" value="{{ old('attorney_suffix') }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Law firm</label><input name="law_firm" value="{{ old('law_firm') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Email</label><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300">@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-700">Phone</label><input name="phone" value="{{ old('phone') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Effective date <span class="text-gray-400">(optional)</span></label><input type="date" name="effective_date" value="{{ old('effective_date') }}" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Address</label><input name="address_line1" value="{{ old('address_line1') }}" required class="mt-1 block w-full rounded-md border-gray-300"><input name="address_line2" value="{{ old('address_line2') }}" placeholder="Suite, unit, etc. (optional)" class="mt-2 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">City</label><input name="city" value="{{ old('city') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-medium text-gray-700">State</label><input name="state" value="{{ old('state') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div><div><label class="block text-sm font-medium text-gray-700">ZIP</label><input name="zip" value="{{ old('zip') }}" required class="mt-1 block w-full rounded-md border-gray-300"></div></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Notes <span class="text-gray-400">(optional)</span></label><textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('notes') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow">
            <div class="mb-4 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-700 font-bold text-white">3</span><h2 class="text-xl font-semibold text-gray-900">Review and verify</h2></div>
            <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="certification" value="1" required class="mt-1 rounded border-gray-300 text-blue-700"><span>I certify that I am authorized to enter an appearance for the named party and that the information provided is accurate.</span></label>
            <p class="mt-4 text-sm text-gray-500">You must verify your email address before the Hearing Unit can review this request.</p>
            <button type="submit" class="mt-5 rounded-md bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">Submit Entry of Appearance</button>
        </section>
    </form>
</div>
@endsection
