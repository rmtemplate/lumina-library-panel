<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Lumina Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased" style="background: var(--color-background)">

    {{-- Simple Topbar --}}
    <header class="sticky top-0 z-30 px-6 flex items-center justify-between"
            style="height: 60px; background: white; border-bottom: 1px solid var(--color-outline-variant)">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-8 h-8 rounded-xl font-bold text-xs text-white"
                 style="background-color: var(--color-primary)">L</div>
            <span class="font-semibold text-sm" style="color: var(--color-on-surface)">Lumina Library</span>
        </div>
        <nav class="hidden sm:flex items-center gap-1 text-sm">
            <a href="{{ route('user.dashboard') }}" class="px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors {{ request()->routeIs('user.dashboard') ? 'font-semibold' : '' }}"
               style="color: {{ request()->routeIs('user.dashboard') ? 'var(--color-primary)' : 'var(--color-on-surface-variant)' }}">
                Dashboard
            </a>
            <a href="{{ route('user.borrowings.index') }}" class="px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors {{ request()->routeIs('user.borrowings.*') ? 'font-semibold' : '' }}"
               style="color: {{ request()->routeIs('user.borrowings.*') ? 'var(--color-primary)' : 'var(--color-on-surface-variant)' }}">
                Pinjaman Saya
            </a>
            <a href="{{ route('user.notifications.index') }}" class="px-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors"
               style="color: var(--color-on-surface-variant)">
                Notifikasi
            </a>
        </nav>
        <div class="flex items-center gap-2" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                <div class="avatar avatar-sm text-xs" style="background-color: var(--color-primary-container); color: var(--color-on-primary-container)">
                    {{ strtoupper(substr(auth()->user()->fullname ?? 'U', 0, 1)) }}
                </div>
                <svg class="w-4 h-4" style="color: var(--color-on-surface-variant)" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div x-show="open" @click.outside="open = false" class="absolute top-14 right-4 w-44 rounded-xl shadow-lg py-1 z-50"
                 style="background: white; border: 1px solid var(--color-outline-variant)">
                <a href="{{ route('user.profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50"
                   style="color: var(--color-on-surface)">Profil Saya</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50 text-left"
                            style="color: var(--color-error)">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main class="max-w-4xl mx-auto px-4 py-8">
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

    @stack('scripts')
</body>
</html>
