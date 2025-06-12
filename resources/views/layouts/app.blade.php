{{-- filepath: packages/ShubhKansara/php-quickbooks-connector/resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'QuickBooks Sync Monitor')</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow mb-6">
        <div class="container mx-auto px-4 py-4 flex items-center">
            <span class="text-xl font-bold text-blue-700">QuickBooks Sync Monitor</span>
        </div>
    </nav>
    <main class="container mx-auto px-4">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
