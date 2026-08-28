<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/hsp.css') }}?v={{ filemtime(public_path('css/hsp.css')) }}">
</head>
<body class="login-page">
    <main class="login-card password-card">
        <header class="login-intro">
            <div class="login-brand-row"><div class="brand-mark">HSP</div><span>Regional III</span></div>
            @yield('form')
        </header>
    </main>
</body>
</html>
