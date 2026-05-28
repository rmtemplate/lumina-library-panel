<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Lumina Library Staff</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased" x-data="adminLayout()">

    {{-- Mobile Overlay --}}
    <div class="fixed inset-0 bg-black/40 z-30 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <div class="sidebar-logo">L</div>
            <div>
                <div class="sidebar-brand">Lumina Library</div>
                <div class="sidebar-brand-sub">Panel Staff</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-group">
                <div class="sidebar-group-label">Menu</div>
                <a href="{{ route('staff.dashboard') }}" class="sidebar-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                    <svg class="sidebar-item-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('staff.borrowings.index') }}" class="sidebar-item {{ request()->routeIs('staff.borrowings.*') ? 'active' : '' }}">
                    <svg class="sidebar-item-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m0-3l-3-3m0 0l-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75" />
                    </svg>
                    Peminjaman
                </a>
                <a href="{{ route('staff.fines.index') }}" class="sidebar-item {{ request()->routeIs('staff.fines.*') ? 'active' : '' }}">
                    <svg class="sidebar-item-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Denda
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="flex items-center gap-3">
                <div class="avatar avatar-sm">{{ strtoupper(substr(auth()->user()->fullname ?? 'S', 0, 1)) }}</div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-white/90 truncate">{{ auth()->user()->fullname }}</div>
                    <div class="text-xs text-white/40 truncate">{{ auth()->user()->primaryRoleName() }}</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Topbar --}}
    <header class="topbar">
        <div class="flex items-center gap-4">
            <button class="lg:hidden p-2 rounded-xl hover:bg-gray-100" @click="sidebarOpen = !sidebarOpen">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <div class="hidden sm:block text-sm" style="color: var(--color-on-surface-variant)">
                @yield('breadcrumb', 'Staff Panel')
            </div>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
            </form>
        </div>
    </header>

    <div class="admin-main">
        <main class="admin-content">
            @if(session('success') || session('error'))
                <div class="mb-4">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                    @endif
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script>
    function adminLayout() { return { sidebarOpen: false }; }
    </script>
</body>
</html>
