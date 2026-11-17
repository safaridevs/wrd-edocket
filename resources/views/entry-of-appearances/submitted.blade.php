@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 text-center sm:px-0"><div class="rounded-lg bg-white p-10 shadow">
    <h1 class="text-2xl font-bold text-gray-900">Check your email</h1>
    <p class="mt-4 text-gray-600">We sent a verification link to the email address you provided. Verify it within 24 hours so the Hearing Unit can review your Entry of Appearance.</p>
    <p class="mt-3 text-sm text-gray-500">If you do not see the message, check your spam or junk folder.</p>
    <a href="{{ route('welcome') }}" class="mt-6 inline-block font-semibold text-blue-700">Return to E-Docket</a>
</div></div>
@endsection
