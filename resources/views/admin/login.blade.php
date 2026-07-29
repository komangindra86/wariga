<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — Wariga Bali Santih</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-6">
    <main class="w-full max-w-md rounded-3xl border border-stone-200 bg-white p-8 shadow-xl shadow-stone-200/60">
        <p class="text-sm font-semibold uppercase tracking-widest text-santih-500">Panel Admin</p>
        <h1 class="mt-2 text-3xl font-bold text-santih-900">Wariga Bali Santih</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Autentikasi admin akan diaktifkan pada fase admin dan validasi ahli.</p>
        <form class="mt-8 space-y-5" aria-label="Form login admin">
            <label class="block text-sm font-medium text-stone-700">Email
                <input disabled type="email" class="mt-2 w-full rounded-xl border border-stone-300 bg-stone-100 px-4 py-3 text-stone-500" placeholder="admin@contoh.id">
            </label>
            <label class="block text-sm font-medium text-stone-700">Kata sandi
                <input disabled type="password" class="mt-2 w-full rounded-xl border border-stone-300 bg-stone-100 px-4 py-3">
            </label>
            <button disabled class="w-full cursor-not-allowed rounded-xl bg-stone-300 px-4 py-3 font-semibold text-stone-500">Login belum tersedia</button>
        </form>
    </main>
</body>
</html>
