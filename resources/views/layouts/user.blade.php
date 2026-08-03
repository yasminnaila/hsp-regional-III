<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HSP Konstruksi Umum')</title>
    <link rel="stylesheet" href="/css/hsp.css?v=2">
</head>
<body>
<header class="public-header">
    <div class="logo" style="-webkit-text-fill-color:var(--text);background:none;">HSP <small style="-webkit-text-fill-color:var(--text-secondary);">Regional III</small></div>
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
</body>
</html>
