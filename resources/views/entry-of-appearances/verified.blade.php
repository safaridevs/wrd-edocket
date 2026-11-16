@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 text-center sm:px-0"><div class="rounded-lg bg-white p-10 shadow">
    @if($expired)
        <h1 class="text-2xl font-bold text-red-700">Verification link expired</h1>
        <p class="mt-4 text-gray-600">This request was not verified within 24 hours. Please submit a new Entry of Appearance.</p>
        <a href="{{ route('entry-of-appearances.create') }}" class="mt-6 inline-block font-semibold text-blue-700">Start a new request</a>
    @else
        <h1 class="text-2xl font-bold text-green-700">Email verified</h1>
        <p class="mt-4 text-gray-600">Your Entry of Appearance for case {{ $appearance->case->case_no }} is awaiting Hearing Unit review. We will email you after a decision is made.</p>
        <a href="{{ route('welcome') }}" class="mt-6 inline-block font-semibold text-blue-700">Return to E-Docket</a>
    @endif
</div></div>
@endsection
