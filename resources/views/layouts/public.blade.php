<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OSE E-Docket') }} - Public Case Search</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .search-focus:focus { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-50 to-blue-50">
    <!-- Header -->
    <header class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/ose-logo.png') }}" alt="OSE Logo" class="h-12 w-auto">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">New Mexico OSE</h1>
                            <p class="text-sm text-blue-600 font-medium">Public Case Search</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('public.cases.index') }}" class="hidden sm:block text-gray-600 hover:text-blue-600 font-medium transition-colors">Search Cases</a>
                    @guest
                        <a href="{{ route('login') }}" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2.5 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-semibold text-sm whitespace-nowrap">Login</a>
                    @endguest
                    @auth
                        <a href="{{ route('dashboard') }}" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2.5 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg font-semibold text-sm whitespace-nowrap">Dashboard</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="gradient-bg text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="{{ asset('images/ose-logo.png') }}" alt="OSE Logo" class="h-8 w-auto opacity-90">
                        <h3 class="text-lg font-bold">New Mexico OSE</h3>
                    </div>
                    <p class="text-blue-100 text-sm leading-relaxed">
                        Protecting, conserving, and managing New Mexico's water resources for current and future generations.
                    </p>
                </div>
                <div>
                    <h4 class="text-md font-semibold mb-4 text-white">Contact Information</h4>
                    <div class="text-blue-100 text-sm space-y-2">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span>1220 South St. Francis Drive, Santa Fe, NM 87505</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                            <span>(505) 827-6091</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-md font-semibold mb-4 text-white">Quick Links</h4>
                    <div class="text-blue-100 text-sm space-y-2">
                        <a href="{{ route('public.cases.index') }}" class="block hover:text-white transition-colors">🔍 Search Cases</a>
                        @guest
                            <a href="{{ route('login') }}" class="block hover:text-white transition-colors">👤 Login</a>
                            <a href="{{ route('register') }}" class="block hover:text-white transition-colors">📝 Register Account</a>
                        @endguest
                        @auth
                            <a href="{{ route('dashboard') }}" class="block hover:text-white transition-colors">📋 Dashboard</a>
                        @endauth
                        <a href="#" class="block hover:text-white transition-colors">📋 Forms & Applications</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-blue-400 border-opacity-30 mt-8 pt-6 text-center">
                <p class="text-blue-100 text-sm">
                    &copy; {{ date('Y') }} New Mexico Office of the State Engineer. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
