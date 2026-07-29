<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Wariga Bali Santih, layanan informasi Kalender Bali dan dewasa ayu.')">
    <title>@yield('title', 'Wariga Bali Santih')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="border-b border-stone-200 bg-white/90">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="font-semibold tracking-wide text-santih-900">Wariga Bali Santih</a>
            <div class="flex gap-5 text-sm text-stone-600">
                <a class="hover:text-santih-700" href="{{ route('privacy-policy') }}">Privasi</a>
                <a class="hover:text-santih-700" href="{{ route('terms') }}">Ketentuan</a>
            </div>
        </nav>
    </header>

    <main>@yield('content')</main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-8 text-sm text-stone-600 sm:flex-row sm:justify-between">
            <p>&copy; {{ now()->year }} Bali Santih.</p>
            <a class="hover:text-santih-700" href="{{ route('account-deletion') }}">Penghapusan akun</a>
        </div>
    </footer>
</body>
</html>
