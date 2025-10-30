<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - OSE E-Docket System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex">
        <!-- Left Side - Registration Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mb-6">
                        <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-3">
                            <img src="{{ asset('images/ose-logo.png') }}" alt="OSE Logo" class="h-10 w-auto">
                            <span class="text-xl font-bold text-blue-900">OSE E-Docket</span>
                        </a>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h2>
                    <p class="text-gray-600">Join the OSE E-Docket system to manage your water rights cases</p>
                </div>

                <!-- Registration Form -->
                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" onblur="lookupPerson()"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div id="email-lookup-message" class="mt-2 text-sm"></div>
                    </div>
                     <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>



                    <!-- Terms Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Account Registration</p>
                                <p>By creating an account, you agree to use this system in accordance with New Mexico water rights regulations and OSE policies.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Create Account
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Already have an account?
                        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            Sign in here
                        </a>
                    </p>
                </div>

                <!-- Public Access -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-3">Browse public cases without an account</p>
                        <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-2 text-gray-600 hover:text-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Back to public cases</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Information -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 to-blue-800 relative overflow-hidden">
            <div class="absolute inset-0 bg-black opacity-20"></div>
            <div class="relative z-10 flex flex-col justify-center px-12 text-white">
                <div class="mb-8">
                    <h1 class="text-4xl font-bold mb-4">Join OSE E-Docket</h1>
                    <p class="text-xl text-blue-100 mb-6">Manage your water rights cases electronically</p>
                    <p class="text-lg text-blue-200 leading-relaxed mb-8">
                        Create an account to participate in water rights hearings, upload documents, and receive case notifications through our secure system.
                    </p>
                </div>

                <div class="space-y-6">
                    <div class="bg-blue-800 bg-opacity-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-3">What you can do:</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-blue-100">Upload and manage case documents</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v2H4v-2zM4 15h10v2H4v-2zM4 11h10v2H4v-2z"></path>
                                </svg>
                                <span class="text-blue-100">Track case status and updates</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-blue-100">Receive case notifications</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span class="text-blue-100">Secure access to your cases</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function lookupPerson() {
            const email = document.getElementById('email').value;
            const nameInput = document.getElementById('name');
            const messageDiv = document.getElementById('email-lookup-message');

            if (!email) {
                messageDiv.innerHTML = '';
                nameInput.readOnly = false;
                return;
            }

            messageDiv.innerHTML = '<span class="text-blue-600 flex items-center space-x-2"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Looking up...</span></span>';

            fetch(`/api/lookup-person/${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.found) {
                        nameInput.value = data.name;
                        nameInput.readOnly = true;
                        nameInput.classList.add('bg-green-50', 'border-green-300');
                        messageDiv.innerHTML = `<div class="flex items-center space-x-2 text-green-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span>Found ${data.type} record: ${data.name}</span></div>`;
                    } else {
                        nameInput.value = '';
                        nameInput.readOnly = false;
                        nameInput.classList.remove('bg-green-50', 'border-green-300');
                        messageDiv.innerHTML = `<div class="flex items-center space-x-2 text-amber-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path></svg><span>No existing record found. Please enter your name manually.</span></div>`;
                    }
                })
                .catch(error => {
                    nameInput.readOnly = false;
                    nameInput.classList.remove('bg-green-50', 'border-green-300');
                    messageDiv.innerHTML = `<div class="flex items-center space-x-2 text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span>Lookup failed. Please enter your name manually.</span></div>`;
                });
        }
    </script>
</body>
</html>
