<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Lumina Library</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f4f5] font-sans text-[#191c1d]">
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <section class="w-full max-w-md rounded-2xl border border-[#e1e3e4] bg-white p-8 text-center shadow-[0_4px_24px_rgba(0,0,0,0.04)]">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl {{ $status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                @if ($status === 'success')
                    <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                @else
                    <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-[#031632]">
                {{ $title }}
            </h1>

            <p class="mt-3 text-sm leading-6 text-[#44474d]">
                {{ $message }}
            </p>

            <div class="mt-8 rounded-xl bg-[#f8f9fa] px-4 py-3 text-xs leading-5 text-[#75777e]">
                Silakan kembali ke aplikasi Lumina Library dan login menggunakan email serta password yang sudah dibuat.
            </div>
        </section>
    </main>
</body>
</html>