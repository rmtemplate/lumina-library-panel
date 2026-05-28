<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Lumina Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="lumina-shell min-h-screen overflow-x-hidden antialiased">
@php
    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser?->isSuperAdmin();
    $can = fn (array $menu): bool => empty($menu['super']) || $isSuperAdmin;
    $rawMenuGroups = [
        'Dashboard' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'dashboard'],
        ],
        'Master Data' => [
            ['label' => 'Book Catalog', 'route' => 'admin.books.index', 'pattern' => 'admin.books.*', 'icon' => 'database'],
            ['label' => 'Physical Copies', 'route' => 'admin.book-items.index', 'pattern' => 'admin.book-items.*', 'icon' => 'inventory_2'],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'pattern' => 'admin.categories.*', 'icon' => 'sell', 'super' => true],
            ['label' => 'Branches', 'route' => 'admin.branches.index', 'pattern' => 'admin.branches.*', 'icon' => 'location_city', 'super' => true],
            ['label' => 'Racks', 'route' => 'admin.racks.index', 'pattern' => 'admin.racks.*', 'icon' => 'shelves'],
        ],
        'Transaction' => [
            ['label' => 'Borrowings', 'route' => 'admin.borrowings.index', 'pattern' => 'admin.borrowings.*', 'icon' => 'swap_horiz'],
            ['label' => 'Returning', 'route' => 'admin.borrowings.index', 'pattern' => 'admin.returnings.*', 'icon' => 'assignment_return'],
            ['label' => 'Fines', 'route' => 'admin.fines.index', 'pattern' => 'admin.fines.*', 'icon' => 'payments'],
        ],
        'Management' => [
            ['label' => 'Users', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'group'],
            ['label' => 'Profile Types', 'route' => 'admin.profile-types.index', 'pattern' => 'admin.profile-types.*', 'icon' => 'badge', 'super' => true],
            ['label' => 'Roles', 'route' => 'admin.roles.index', 'pattern' => 'admin.roles.*', 'icon' => 'admin_panel_settings', 'super' => true],
            ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'pattern' => 'admin.permissions.*', 'icon' => 'key', 'super' => true],
            ['label' => 'Banners', 'route' => 'admin.banners.index', 'pattern' => 'admin.banners.*', 'icon' => 'image'],
        ],
        'Settings' => [
            ['label' => 'System Settings', 'route' => 'admin.settings.index', 'pattern' => 'admin.settings.*', 'icon' => 'settings', 'super' => true],
        ],
    ];
    $menuGroups = collect($rawMenuGroups)
        ->map(fn ($menus) => collect($menus)->filter($can)->values()->all())
        ->filter(fn ($menus) => count($menus) > 0)
        ->all();
    $mobileMenus = collect([
        ['label' => 'Dash', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'dashboard'],
        ['label' => 'Data', 'route' => 'admin.books.index', 'pattern' => 'admin.books.*', 'icon' => 'database'],
        ['label' => 'Trans', 'route' => 'admin.borrowings.index', 'pattern' => 'admin.borrowings.*', 'icon' => 'swap_horiz'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'group'],
        ['label' => 'Set', 'route' => 'admin.settings.index', 'pattern' => 'admin.settings.*', 'icon' => 'settings', 'super' => true],
    ])->filter($can)->values()->all();
@endphp
<div class="min-h-screen">
    <div class="mobile-sidebar-backdrop fixed inset-0 z-40 hidden bg-slate-950/40 backdrop-blur-sm lg:hidden" data-mobile-sidebar-close></div>

    <aside class="admin-sidebar fixed inset-y-0 left-0 z-50 hidden flex-col overflow-hidden bg-[#001828] text-white transition-all duration-300 lg:flex">
        <div class="mb-2 flex h-20 items-center gap-3 px-5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-2 shadow-lg shadow-black/10">
                <img src="{{ asset('lumina-logo.png') }}" alt="Lumina" class="h-8 w-8 object-contain">
            </div>
            <div class="sidebar-brand-text min-w-0">
                <div class="truncate text-lg font-bold tracking-tight text-white">Lumina Library</div>
                <div class="truncate text-xs font-medium text-[#70869a]">Admin Terminal</div>
            </div>
        </div>

        <nav class="custom-scrollbar flex-1 overflow-y-auto px-4 py-3">
            @foreach($menuGroups as $group => $menus)
                <div class="mb-5">
                    <div class="sidebar-group-label mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-white/35">{{ $group }}</div>
                    <div class="space-y-1">
                        @foreach($menus as $menu)
                            @php($active = request()->routeIs($menu['pattern']))
                            <a href="{{ route($menu['route']) }}" class="sidebar-link group flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition {{ $active ? 'bg-[#006c49] text-white shadow-lg shadow-black/10' : 'text-[#e4e2e5] hover:bg-white/10 hover:text-white' }}">
                                <x-admin.icon :name="$menu['icon']" class="h-5 w-5" />
                                <span class="sidebar-label min-w-0 flex-1 truncate">{{ $menu['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-[#70869a]/20 p-4">
            <div class="sidebar-user mb-4 flex items-center gap-3 rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#6cf8bb] text-sm font-bold text-[#002113]">{{ strtoupper(substr($currentUser?->fullname ?? 'A', 0, 1)) }}</div>
                <div class="min-w-0">
                    <div class="truncate text-xs font-bold text-white">{{ $currentUser?->fullname }}</div>
                    <div class="truncate text-[10px] uppercase tracking-wider text-[#70869a]">{{ $currentUser?->isSuperAdmin() ? 'System Root' : ($currentUser?->branch?->code ?? 'Branch Admin') }}</div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" data-sidebar-toggle class="btn-ghost flex-1 bg-white/5 text-white hover:bg-white/10"><x-admin.icon name="left_panel_close" class="h-5 w-5" /><span class="sidebar-footer-label">Collapse</span></button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn-danger h-full px-3"><x-admin.icon name="logout" class="h-5 w-5" /></button></form>
            </div>
        </div>
    </aside>

    <aside class="mobile-sidebar fixed inset-y-0 left-0 z-50 flex w-[86vw] max-w-[320px] -translate-x-full flex-col overflow-hidden bg-[#001828] text-white transition-transform duration-300 lg:hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
            <div class="flex items-center gap-3"><img src="{{ asset('lumina-logo.png') }}" class="h-10 w-10 rounded-xl bg-white p-2" alt="Lumina"><div><div class="text-sm font-bold">Lumina Library</div><div class="text-xs text-[#70869a]">Admin Terminal</div></div></div>
            <button type="button" data-mobile-sidebar-close class="rounded-lg p-2 text-white/70 hover:bg-white/10"><x-admin.icon name="close" class="h-5 w-5" /></button>
        </div>
        <nav class="flex-1 overflow-y-auto px-4 py-5">
            @foreach($menuGroups as $group => $menus)
                <div class="mb-5">
                    <div class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-white/35">{{ $group }}</div>
                    @foreach($menus as $menu)
                        @php($active = request()->routeIs($menu['pattern']))
                        <a href="{{ route($menu['route']) }}" class="mb-1 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium {{ $active ? 'bg-[#006c49] text-white' : 'text-[#e4e2e5] hover:bg-white/10 hover:text-white' }}"><x-admin.icon :name="$menu['icon']" class="h-5 w-5" />{{ $menu['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <main class="admin-main min-h-screen pb-20 transition-all duration-300 lg:pb-0">
        <header class="sticky top-0 z-30 h-16 border-b border-[#c5c6ce] bg-[#f8f9fa]/95 backdrop-blur-xl">
            <div class="flex h-full items-center justify-between px-5 sm:px-8">
                <div class="flex min-w-0 items-center gap-4">
                    <button type="button" data-mobile-sidebar-open class="icon-button border border-[#c5c6ce] bg-white lg:hidden"><x-admin.icon name="menu" class="h-5 w-5" /></button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <h2 class="truncate text-lg font-bold text-[#000000]">Lumina Library</h2>
                            <span class="hidden h-4 w-px bg-[#c5c6ce] sm:block"></span>
                            <nav class="hidden items-center gap-2 text-xs font-medium text-[#44474d] md:flex">
                                <span>@yield('eyebrow', 'Library')</span>
                                <x-admin.icon name="chevron_right" class="h-4 w-4 text-[#c5c6ce]" />
                                <span class="font-bold text-[#000000]">@yield('page-title', 'Dashboard')</span>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <form action="{{ route('admin.books.index') }}" method="GET" class="relative hidden lg:block">
                        <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#44474d]" />
                        <input name="search" class="w-64 rounded-full border border-[#c5c6ce] bg-white py-2 pl-10 pr-4 text-sm transition focus:w-80 focus:border-[#000000] focus:ring-0" placeholder="Search catalog...">
                    </form>
                    <button type="button" class="icon-button"><x-admin.icon name="notifications" class="h-5 w-5" /></button>
                    <button type="button" class="icon-button"><x-admin.icon name="help" class="h-5 w-5" /></button>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[#c5c6ce] bg-[#6cf8bb] text-xs font-bold text-[#002113]">{{ strtoupper(substr($currentUser?->fullname ?? 'A', 0, 2)) }}</div>
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-[1500px] px-5 py-6 sm:px-8 lg:py-8">
            <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#006c49]">@yield('eyebrow', 'Admin Panel')</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-[-0.02em] text-[#000000] sm:text-3xl">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('page-description')<p class="mt-2 max-w-3xl text-sm leading-6 text-[#44474d]">@yield('page-description')</p>@endif
                </div>
                <div class="flex flex-wrap gap-2 no-print">@yield('page-actions')</div>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="flex items-center gap-2 font-bold"><x-admin.icon name="warning" class="h-5 w-5" />Periksa kembali input kamu</div>
                    <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

<nav class="fixed bottom-0 left-0 z-40 flex h-16 w-full items-center justify-around border-t border-[#c5c6ce] bg-[#e4e2e5]/95 px-4 backdrop-blur lg:hidden">
    @foreach($mobileMenus as $menu)
        @php($active = request()->routeIs($menu['pattern']))
        <a href="{{ route($menu['route']) }}" class="flex flex-col items-center gap-1 text-[10px] font-bold {{ $active ? 'text-[#006c49]' : 'text-[#44474d]' }}">
            <x-admin.icon :name="$menu['icon']" class="h-6 w-6" />
            <span>{{ $menu['label'] }}</span>
        </a>
    @endforeach
</nav>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if (session('success'))<script>document.addEventListener('DOMContentLoaded', () => window.LuminaAdmin?.toast('success', @json(session('success'))));</script>@endif
@if (session('error'))<script>document.addEventListener('DOMContentLoaded', () => window.LuminaAdmin?.toast('error', @json(session('error'))));</script>@endif
@if (session('warning'))<script>document.addEventListener('DOMContentLoaded', () => window.LuminaAdmin?.toast('warning', @json(session('warning'))));</script>@endif
@if (session('info'))<script>document.addEventListener('DOMContentLoaded', () => window.LuminaAdmin?.toast('info', @json(session('info'))));</script>@endif
@stack('scripts')
</body>
</html>
