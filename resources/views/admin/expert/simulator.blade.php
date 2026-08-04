@extends('layouts.admin')
@section('title','Simulator Keputusan')
@section('content')
<h1 class="text-3xl font-bold">Simulator Keputusan Sistem Pakar</h1>
<p class="mt-2 max-w-3xl text-stone-500">Lihat fakta kalender, pengetahuan yang cocok, konflik baik dan buruk, serta keputusan akhir. Simulasi tidak menyimpan atau mengubah data.</p>

<form method="POST" action="{{ route('admin.expert.simulate') }}" class="mt-6 grid max-w-3xl gap-4 rounded-2xl bg-white p-6 md:grid-cols-[1fr_220px_auto]">@csrf
    <label class="text-sm">Kegiatan<select name="activity_id" required class="mt-2 w-full rounded-xl border p-3">@foreach($activities as $activity)<option value="{{ $activity->id }}" @selected(old('activity_id',$simulation['activity']->id ?? null)===$activity->id)>{{ $activity->nama }}</option>@endforeach</select></label>
    <label class="text-sm">Tanggal<input type="date" name="date" required value="{{ old('date',$simulation['date'] ?? now()->toDateString()) }}" class="mt-2 w-full rounded-xl border p-3"></label>
    <button class="self-end rounded-xl bg-santih-700 px-5 py-3 font-semibold text-white">Jalankan</button>
</form>

@if($simulation)
@php($result=$simulation['result'])
<section class="mt-6 rounded-2xl bg-white p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><p class="text-sm text-stone-500">{{ $simulation['activity']->nama }} · {{ $simulation['date'] }}</p><h2 class="mt-1 text-2xl font-bold">{{ $result['classification'] }}</h2></div>
        <div class="rounded-2xl px-5 py-3 text-center {{ $result['status']==='recommended'?'bg-emerald-100 text-emerald-800':($result['status']==='avoid'?'bg-red-100 text-red-800':'bg-amber-100 text-amber-800') }}"><p class="text-xs uppercase">Skor keputusan</p><b class="text-2xl">{{ $result['score'] }}</b></div>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-stone-50 p-4"><p class="text-xs text-stone-500">Sapta Wara</p><b>{{ data_get($simulation,'calendar.wewaran.sapta_wara.nama','Tidak tersedia') }}</b></div>
        <div class="rounded-xl bg-stone-50 p-4"><p class="text-xs text-stone-500">Panca Wara</p><b>{{ data_get($simulation,'calendar.wewaran.panca_wara.nama','Tidak tersedia') }}</b></div>
        <div class="rounded-xl bg-stone-50 p-4"><p class="text-xs text-stone-500">Wuku</p><b>{{ data_get($simulation,'calendar.wuku.nama','Tidak tersedia') }}</b></div>
        <div class="rounded-xl bg-stone-50 p-4"><p class="text-xs text-stone-500">Sasih / fase</p><b>{{ data_get($simulation,'lunar.sasih.nama','Tidak tersedia') }} · {{ ucfirst((string) data_get($simulation,'lunar.fase','Tidak tersedia')) }} {{ data_get($simulation,'lunar.nomor_hari','') }}</b></div>
    </div>
</section>

<section class="mt-6">
    <h2 class="text-xl font-bold">Jejak keputusan</h2>
    <div class="mt-3 grid gap-4 lg:grid-cols-2">
        @forelse($result['reasons'] as $reason)
        <article class="rounded-2xl border-l-4 bg-white p-5 {{ $reason['rule_type']==='positive'?'border-emerald-500':'border-red-500' }}">
            <p class="text-xs font-semibold uppercase {{ $reason['rule_type']==='positive'?'text-emerald-700':'text-red-700' }}">{{ $reason['rule_type']==='positive'?'Faktor mendukung':'Faktor menghalangi' }}</p>
            <h3 class="mt-1 text-lg font-bold">{{ $reason['rule_name'] }}</h3>
            <p class="mt-2 text-sm">{{ $reason['reason'] }}</p>
            <div class="mt-3 rounded-xl bg-stone-50 p-3 text-sm"><b>Kondisi yang cocok:</b> {{ $reason['matched_composition'] }}</div>
            <p class="mt-2 text-xs text-stone-500">Tingkat alahing {{ $reason['alahing_priority'] }} · {{ $reason['source_name'] ?? 'Sumber tidak tersedia' }}</p>
        </article>
        @empty<div class="rounded-2xl bg-white p-6 text-stone-500 lg:col-span-2">Tidak ada ala-ayu yang cocok. Tanggal berada pada keputusan netral dan perlu pertimbangan.</div>@endforelse
    </div>
</section>
@endif
@endsection
