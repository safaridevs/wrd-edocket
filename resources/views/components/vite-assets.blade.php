@if(app()->environment('production') && !file_exists(public_path('build/manifest.json')))
    <!-- Fallback CSS for production when Vite build is missing -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles to match the application design */
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f1f5f9' fill-opacity='0.4'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif