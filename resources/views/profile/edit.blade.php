<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Profile') }}
            </h2>
            <div class="text-sm text-gray-600">
                Welcome, {{ auth()->user()->name }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Profile Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>
                    <p class="text-sm text-gray-600 mb-6">Update your account's profile information, contact details, and email address.</p>
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Security Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Update Password</h3>
                    <p class="text-sm text-gray-600 mb-6">Ensure your account is using a long, random password to stay secure.</p>
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Account Management -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Account</h3>
                    <p class="text-sm text-gray-600 mb-6">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
