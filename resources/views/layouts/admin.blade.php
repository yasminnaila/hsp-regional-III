<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin HSP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="/css/hsp.css?v=42">
</head>
<body>
<div class="app-shell">

    {{-- Topbar mobile (hanya tampil < 900px) --}}
    <header class="mobile-topbar">
        <button id="sidebar-toggle" class="hamburger" aria-label="Buka menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="mobile-logo">
            <img src="/images/telkom-property.webp" alt="Telkom Property" class="mobile-telkom-logo">
            <span>HSP Regional III</span>
        </div>
    </header>
    <div id="sidebar-overlay" class="sidebar-overlay" hidden></div>

    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                <div class="telkom-brand-panel">
                    <img src="/images/telkom-property.webp" alt="Telkom Property" class="telkom-logo">
                </div>
                <div class="sidebar-app-name">Regional III</div>
            </div>

            <div class="sidebar-section-label">Menu</div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    </span>
                    <span class="nav-label">Dashboard</span>
                </a>

                <a href="{{ route('admin.hsp.index') }}"
                   class="nav-item {{ request()->routeIs('admin.hsp.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </span>
                    <span class="nav-label">Data HSP</span>
                </a>

                <a href="{{ route('admin.basic-items.index') }}"
                   class="nav-item {{ request()->routeIs('admin.basic-items.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </span>
                    <span class="nav-label">Upah, Bahan & Alat</span>
                </a>

                <a href="{{ route('admin.import.index') }}"
                   class="nav-item {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                    <span class="nav-label">Import Excel</span>
                </a>
            </nav>

            <div class="sidebar-spacer"></div>

            <div class="sidebar-user">
                <div class="sidebar-avatar">{{ substr(auth()->user()?->name ?? 'A', 0, 1) }}</div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">{{ auth()->user()?->name ?? 'Admin' }}</div>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                       class="sidebar-logout">Keluar</a>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <section class="main-content">

        {{-- Page Header --}}
        <header class="page-hero">
            <div class="page-hero-left">
                <div class="page-hero-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Beranda</a>
                    @hasSection('breadcrumb')
                        <span>/</span>
                        @yield('breadcrumb')
                    @endif
                </div>
                <div class="page-hero-title">
                    <h1>@yield('page-title', 'Admin')</h1>
                </div>
            </div>
            <div class="page-hero-actions">
                @yield('header-actions')
            </div>
        </header>

        {{-- Content --}}
        <main class="content">

            @if (session('success'))
                <div class="alert success fade-in">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert danger fade-in">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert danger fade-in">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </section>
</div>
<script>
(function() {
    var toggle = document.getElementById('sidebar-toggle');
    var overlay = document.getElementById('sidebar-overlay');
    if (!toggle || !overlay) return;
    function open() {
        document.body.classList.add('sidebar-open');
        overlay.hidden = false;
        requestAnimationFrame(function() { overlay.classList.add('show'); });
    }
    function close() {
        document.body.classList.remove('sidebar-open');
        overlay.classList.remove('show');
        setTimeout(function() { overlay.hidden = true; }, 250);
    }
    toggle.addEventListener('click', function() {
        document.body.classList.contains('sidebar-open') ? close() : open();
    });
    overlay.addEventListener('click', close);
    document.querySelectorAll('.sidebar a').forEach(function(a) { a.addEventListener('click', close); });
})();
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() { document.body.classList.add('app-loading'); });
});
</script>
</body>
</html>
