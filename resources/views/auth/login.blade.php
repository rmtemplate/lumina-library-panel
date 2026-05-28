<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Lumina Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8f9fa] font-sans antialiased">
    <main class="min-h-screen lg:grid lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-[#031632] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(108,248,187,0.20),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(42,154,217,0.20),transparent_34%)]"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-2 shadow-xl"><img src="{{ asset('lumina-logo.png') }}" class="h-10 w-10 object-contain" alt="Lumina"></div>
                <div><p class="text-lg font-bold">Lumina Library</p><p class="text-sm text-white/45">Admin Management System</p></div>
            </div>
            <div class="relative z-10 max-w-xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-[#6cf8bb]"><span class="h-2 w-2 rounded-full bg-[#6cf8bb]"></span>Admin-only secure portal</div>
                <h1 class="text-5xl font-bold leading-tight tracking-[-0.03em]">Quiet, powerful library operations.</h1>
                <p class="mt-6 max-w-lg text-base leading-8 text-white/65">Kelola buku, anggota, peminjaman, denda, rak, dan insight sistem dengan tampilan modern akademik yang fokus dan rapi.</p>
                <div class="mt-10 grid max-w-lg grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur"><div class="text-2xl font-bold">24/7</div><p class="mt-1 text-sm text-white/60">Operational visibility</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur"><div class="text-2xl font-bold">Admin</div><p class="mt-1 text-sm text-white/60">Web access only</p></div>
                </div>
            </div>
            <div class="relative z-10 text-xs text-white/40">© {{ date('Y') }} Lumina Library. Modern Academic Admin.</div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <div class="mb-8 flex justify-center lg:hidden"><div class="flex items-center gap-3"><img src="{{ asset('lumina-logo.png') }}" class="h-12 w-12 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-[#e1e3e4]" alt="Lumina"><div><p class="font-bold text-[#191c1d]">Lumina Library</p><p class="text-xs text-[#44474d]">Admin Console</p></div></div></div>
                <div class="rounded-[1.5rem] border border-[#e1e3e4] bg-white p-8 shadow-[0_12px_28px_rgba(3,22,50,0.06)] sm:p-10">
                    <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#006c49]">Secure Login</p><h2 class="mt-2 text-2xl font-bold tracking-[-0.02em] text-[#191c1d]">Masuk Admin</h2><p class="mt-2 text-sm leading-6 text-[#44474d]">Gunakan akun administrator untuk mengakses dashboard.</p></div>
                    @if (session('status'))<div class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ session('status') }}</div>@endif
                    @if ($errors->any())<div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><div class="font-bold">Login gagal</div><ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf
                        <div><label for="email" class="form-label">Email Admin</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="super@lumina.ac.id" class="form-input"></div>
                        <div><label for="password" class="form-label">Password</label><div class="relative"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="form-input pr-12"><button type="button" data-toggle-password="#password" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-[#44474d] hover:bg-[#f3f4f5]">Show</button></div></div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-[#44474d]"><input id="remember" name="remember" type="checkbox" class="form-check"><span>Ingat saya</span></label>
                        <button type="submit" data-submit-label="Memproses" class="btn-primary w-full">Masuk ke Dashboard Admin</button>
                    </form>
                </div>
                <p class="mt-6 text-center text-xs text-[#75777e]">Protected admin access · Staff/User access disiapkan via mobile API.</p>
            </div>
        </section>
    </main>
</body>
</html>
