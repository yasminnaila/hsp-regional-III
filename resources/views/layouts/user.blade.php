<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HSP Konstruksi Umum')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/hsp.css') }}?v={{ filemtime(public_path('css/hsp.css')) }}">
</head>
<body>
<header class="public-header">
    <div class="public-brand">
        <img src="/images/telkom-property.webp" alt="Telkom Property" class="public-telkom-logo">
        <span>Regional III</span>
    </div>
    <div class="user-menu">
        <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();" style="color:#dc2626;font-size:13px;">Keluar</a>
    </div>
</header>
<main class="public-content">
    @if (session('success'))
        <div class="alert success fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert danger fade-in">{{ session('error') }}</div>
    @endif
    @yield('content')
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
</main>
<script>
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() { document.body.classList.add('app-loading'); });
});
</script>
</body>
</html>
