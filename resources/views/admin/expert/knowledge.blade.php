@extends('layouts.admin')
@section('title','Basis Pengetahuan')
@section('content')
<h1 class="text-3xl font-bold">Basis Pengetahuan Wariga</h1>
<p class="mt-2 max-w-3xl text-stone-500">Pilih kegiatan, lalu tentukan ala-ayu yang mendukung atau menghalangi. Anda tidak perlu mengisi JSON, operator, atribut teknis, atau efek skor.</p>

<form method="GET" class="mt-6 flex max-w-2xl gap-3 rounded-2xl bg-white p-5">
    <select name="activity_id" required class="min-w-0 flex-1 rounded-xl border p-3">
        <option value="">Pilih kegiatan…</option>
        @foreach($activities as $activity)<option value="{{ $activity->id }}" @selected($selectedActivity?->id===$activity->id)>{{ $activity->nama }}</option>@endforeach
    </select>
    <button class="rounded-xl bg-stone-900 px-5 font-semibold text-white">Tampilkan</button>
</form>

@if($selectedActivity)
<section class="mt-6 rounded-2xl bg-white p-6">
    <h2 class="text-xl font-bold">Tambahkan pengetahuan untuk {{ $selectedActivity->nama }}</h2>
    @if(auth()->user()->hasAdminRole('admin_data'))
    <form method="POST" action="{{ route('admin.expert.mappings.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">@csrf
        <input type="hidden" name="activity_id" value="{{ $selectedActivity->id }}">
        <label class="text-sm md:col-span-2">Ala Ayuning Dewasa
            <select name="ala_ayu_master_id" required class="mt-2 w-full rounded-xl border p-3">
                @foreach($masters as $master)<option value="{{ $master->id }}">{{ $master->nama }} — {{ \Illuminate\Support\Str::limit($master->deskripsi,90) }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm">Pengaruh terhadap kegiatan
            <select name="effect" class="mt-2 w-full rounded-xl border p-3"><option value="positive">Baik / mendukung</option><option value="negative">Buruk / menghalangi</option></select>
        </label>
        <label class="text-sm">Tingkat yang dipakai bila bertentangan
            <select name="alahing_priority" class="mt-2 w-full rounded-xl border p-3">
                <option value="">Tentukan otomatis dari komposisi</option><option value="1">1 — Sasih (terkuat)</option><option value="2">2 — Penanggal/Pangelong</option><option value="3">3 — Wuku</option><option value="4">4 — Wewaran</option>
            </select>
        </label>
        <label class="text-sm md:col-span-2">Tautan sumber<input type="url" name="source_url" value="https://kalenderbali.org/pilihdewasa.php" class="mt-2 w-full rounded-xl border p-3"></label>
        <button class="rounded-xl bg-santih-700 px-4 py-3 font-semibold text-white md:col-span-2">Simpan pengetahuan</button>
    </form>
    @endif
</section>

<section class="mt-6">
    <div class="mb-3 flex items-center justify-between"><h2 class="text-xl font-bold">Pengetahuan aktif</h2><span class="text-sm text-stone-500">{{ $mappings->count() }} pemetaan</span></div>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($mappings as $mapping)
        <article class="rounded-2xl border-l-4 bg-white p-5 {{ $mapping->effect === 'positive' ? 'border-emerald-500' : 'border-red-500' }}">
            <div class="flex items-start justify-between gap-3">
                <div><span class="text-xs font-semibold uppercase {{ $mapping->effect === 'positive' ? 'text-emerald-700' : 'text-red-700' }}">{{ $mapping->effect === 'positive' ? 'Mendukung' : 'Menghalangi' }}</span><h3 class="mt-1 text-lg font-bold">{{ $mapping->nama }}</h3></div>
                @if(auth()->user()->hasAdminRole('admin_data'))<form method="POST" action="{{ route('admin.expert.mappings.destroy',$mapping->id) }}" onsubmit="return confirm('Hapus pemetaan ini?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Hapus</button></form>@endif
            </div>
            <p class="mt-3 text-sm">{{ $mapping->deskripsi }}</p>
            <div class="mt-3 rounded-xl bg-stone-50 p-3 text-sm"><b>Terjadi ketika:</b> {{ $mapping->komposisi_teks }}</div>
            <p class="mt-2 text-xs text-stone-500">Alahing: {{ $mapping->alahing_priority ?: 'otomatis' }}</p>
        </article>
        @empty<div class="rounded-2xl bg-white p-6 text-stone-500">Belum ada pengetahuan untuk kegiatan ini.</div>@endforelse
    </div>
</section>
@endif
@endsection
