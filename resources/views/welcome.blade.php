<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lumina Library</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-950 text-white flex items-center justify-center">
    <div class="text-center">
        <img src="{{ asset('lumina-logo.png') }}" class="mx-auto h-20 w-20 rounded-3xl" alt="Lumina">
        <h1 class="mt-6 text-3xl font-bold">Lumina Library</h1>
        <p class="mt-2 text-slate-400">Admin web console</p>
        <a href="{{ route('login') }}" class="mt-6 inline-block rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-950">Masuk Admin</a>
    </div>
</body>
</html>
