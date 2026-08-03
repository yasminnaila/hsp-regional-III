<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin HSP')</title>
    <link rel="stylesheet" href="/css/hsp.css?v=4">
</head>
<body>
<div class="app-shell">

    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                <div class="logo">
                    HSP
                    <span class="logo-badge">v1</span>
                </div>
                <div class="logo-sub">Regional III</div>
            </div>

            <div class="sidebar-section-label">Menu</div>

            <nav class="sidebar-nav">
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
                    <a href="{{ route('admin.hsp.index') }}">Beranda</a>
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
</body>
</html>
