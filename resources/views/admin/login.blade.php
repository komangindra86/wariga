<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login Admin — Wariga Bali Santih</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center px-6">
<main class="w-full max-w-md rounded-3xl border border-stone-200 bg-white p-8 shadow-xl">
    <p class="text-sm font-semibold uppercase tracking-widest text-santih-500">Panel Admin</p>
    <h1 class="mt-2 text-3xl font-bold text-santih-900">Wariga Bali Santih</h1>
    <p class="mt-3 text-sm text-stone-600">Masuk untuk mengelola dan memvalidasi data Wariga.</p>
    @if($errors->any())<p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">@csrf
        <label class="block text-sm font-medium">Email<input name="email" value="{{ old('email') }}" required type="email" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3"></label>
        <label class="block text-sm font-medium">Kata sandi<input name="password" required type="password" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3"></label>
        <label class="flex gap-2 text-sm"><input name="remember" value="1" type="checkbox"> Ingat saya</label>
        <button class="w-full rounded-xl bg-santih-700 px-4 py-3 font-semibold text-white">Masuk</button>
    </form>
</main></body></html>
