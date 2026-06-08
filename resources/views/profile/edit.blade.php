@php
    $currentRole = ucwords(str_replace('_', ' ', $user->getCurrentRole()));
    $initials = $user->initials ?: collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');

    $activePanel = match (true) {
        session('status') === 'legal-service-profile-updated' => 'legal',
        session('status') === 'password-updated' || $errors->updatePassword->isNotEmpty() => 'security',
        default => 'account',
    };

    $legalContactName = $person?->full_name ?: $person?->organization;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">
                    Profile
                </h2>
                <p class="text-sm text-slate-500">
                    {{ $user->email }}
                </p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                Active {{ $currentRole }}
            </span>
        </div>
    </x-slot>

    <div class="bg-slate-50 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                x-data="{ activePanel: @js($activePanel) }"
                class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)]"
            >
                <aside class="space-y-4">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-base font-semibold text-white">
                                {{ $initials ?: 'U' }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-950">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $currentRole }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account Email</dt>
                                <dd class="mt-1 break-all text-slate-800">{{ $user->email }}</dd>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Initials</dt>
                                    <dd class="mt-1 text-slate-800">{{ $user->initials ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Member Since</dt>
                                    <dd class="mt-1 text-slate-800">{{ $user->created_at->format('M d, Y') }}</dd>
                                </div>
                            </div>
                        </dl>
                    </section>

                    <nav class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm" aria-label="Profile sections">
                        <button
                            type="button"
                            x-on:click="activePanel = 'account'"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-medium transition"
                            x-bind:class="activePanel === 'account' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                        >
                            <span>Account</span>
                            <span class="text-xs" x-bind:class="activePanel === 'account' ? 'text-slate-300' : 'text-slate-400'">Identity</span>
                        </button>

                        <button
                            type="button"
                            x-on:click="activePanel = 'legal'"
                            class="mt-1 flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-medium transition"
                            x-bind:class="activePanel === 'legal' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                        >
                            <span>Contact Information</span>
                            <span class="text-xs" x-bind:class="activePanel === 'legal' ? 'text-slate-300' : 'text-slate-400'">Service</span>
                        </button>

                        <button
                            type="button"
                            x-on:click="activePanel = 'security'"
                            class="mt-1 flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-medium transition"
                            x-bind:class="activePanel === 'security' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                        >
                            <span>Security</span>
                            <span class="text-xs" x-bind:class="activePanel === 'security' ? 'text-slate-300' : 'text-slate-400'">Password</span>
                        </button>
                    </nav>

                    <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
                        <p class="font-semibold text-amber-950">Service Contact</p>
                        <p class="mt-1 text-amber-900">{{ $legalContactName ?: 'Not linked' }}</p>
                        @if($person?->phone_office || $person?->phone_mobile)
                            <p class="mt-2 text-xs text-amber-800">
                                {{ $person->phone_office ?: $person->phone_mobile }}
                            </p>
                        @endif
                    </section>
                </aside>

                <main class="min-w-0">
                    <section
                        x-show="activePanel === 'account'"
                        x-cloak
                        class="rounded-lg border border-slate-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-950">Account Identity</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_260px]">
                            <div>
                                @include('profile.partials.update-profile-information-form')
                            </div>
                            <aside class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <dl class="space-y-4 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt>
                                        <dd class="mt-1 text-slate-900">{{ $currentRole }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account Status</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Active</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</dt>
                                        <dd class="mt-1 text-slate-900">{{ $user->updated_at->format('M d, Y') }}</dd>
                                    </div>
                                </dl>
                            </aside>
                        </div>
                    </section>

                    <section
                        x-show="activePanel === 'legal'"
                        x-cloak
                        class="rounded-lg border border-slate-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-950">Contact Information</h3>
                        </div>
                        <div class="p-6">
                            @include('profile.partials.update-legal-service-profile-form')
                        </div>
                    </section>

                    <section
                        x-show="activePanel === 'security'"
                        x-cloak
                        class="rounded-lg border border-slate-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-950">Security</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_260px]">
                            <div>
                                @include('profile.partials.update-password-form')
                            </div>
                            <aside class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <dl class="space-y-4 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Access Type</dt>
                                        <dd class="mt-1 text-slate-900">{{ $user->is_ldap_user ? 'OSE Network Account' : 'Local Password Account' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Account Update</dt>
                                        <dd class="mt-1 text-slate-900">{{ $user->updated_at->format('M d, Y g:i A') }}</dd>
                                    </div>
                                </dl>
                            </aside>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
