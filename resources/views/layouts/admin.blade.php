<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Admin Wariga</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100">
<div class="min-h-screen lg:flex">
    <aside class="bg-santih-900 p-5 text-white lg:w-64">
        <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">Wariga Bali Santih</a>
        <p class="mt-1 text-xs text-stone-300">{{ auth()->user()->name }} · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
        <nav class="mt-7 grid gap-1 text-sm">
            @foreach([
                ['Dashboard', route('admin.dashboard')], ['Master Wewaran', route('admin.master','wewaran')],
                ['Master Wuku', route('admin.master','wuku')], ['Master Sasih', route('admin.master','sasih')],
                ['Data Kalender Lunar', route('admin.lunar')], ['Master Kegiatan', route('admin.activities')],
                ['Kontribusi Pengguna', route('admin.contributions')],
                ['Sistem Pakar', route('admin.expert.index')], ['Basis Pengetahuan', route('admin.expert.knowledge')],
                ['Simulator Keputusan', route('admin.expert.simulator')],
                ['Import Data', route('admin.import')],
                ['Audit Log', route('admin.audits')], ['Konfigurasi Engine', route('admin.settings')],
            ] as [$label,$url])
                <a href="{{ $url }}" class="rounded-lg px-3 py-2 hover:bg-white/10">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-6">@csrf
            <button class="rounded-lg border border-white/30 px-3 py-2 text-sm">Keluar</button>
        </form>
    </aside>
    <main class="min-w-0 flex-1 p-5 lg:p-9">
        @if(session('success'))<div class="mb-5 rounded-xl bg-emerald-100 p-4 text-emerald-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-xl bg-red-100 p-4 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
        <footer class="mt-10 border-t border-stone-200 pt-5 text-xs leading-5 text-stone-500">
            {{ config('wariga.reference_disclosure') }}
        </footer>
    </main>
</div>
</body>
</html>
