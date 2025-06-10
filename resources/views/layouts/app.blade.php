{{-- filepath: packages/ShubhKansara/php-quickbooks-connector/resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'QuickBooks Sync Monitor')</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Use Bootstrap from CDN for quick styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-light mb-4">
        <div class="container">
            <span class="navbar-brand mb-0 h1">QuickBooks Sync Monitor</span>
        </div>
    </nav>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
