@extends('layouts.admin')
@section('title','Dashboard')
@section('content')
<h1 class="text-3xl font-bold text-santih-900">Dashboard</h1>
<section class="mt-6 rounded-2xl border border-santih-200 bg-santih-50 p-6">
    <h2 class="text-lg font-bold">Cara menggunakan panel</h2>
    <ol class="mt-3 grid gap-2 text-sm text-stone-700 md:grid-cols-2">
        <li><b>1. Master Kegiatan:</b> tentukan kegiatan yang dicari pengguna.</li>
        <li><b>2. Import Data:</b> unggah CSV penanggal/panglong dari sumber tepercaya.</li>
        <li><b>3. Basis Pengetahuan:</b> petakan Ala Ayu yang mendukung atau menghalangi kegiatan.</li>
        <li><b>4. Sistem Pakar:</b> bandingkan hasil aplikasi dengan sumber dan tinjau usulan mesin.</li>
        <li><b>5. Simulator Keputusan:</b> lihat fakta, konflik, alahing, dan keputusan tanpa mengubah data.</li>
        <li><b>6. Konfigurasi Engine:</b> naikkan versi ketika aturan publik berubah.</li>
    </ol>
</section>
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
@foreach($stats as $label=>$value)<div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-stone-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ $value }}</p></div>@endforeach
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <section class="rounded-2xl bg-white p-6"><h2 class="text-lg font-bold">Perubahan terbaru</h2>
        <div class="mt-4 divide-y">@forelse($recentChanges as $log)<div class="py-3 text-sm"><b>{{ $log->action }}</b><p class="text-stone-500">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at?->diffForHumans() }}</p></div>@empty<p class="py-4 text-stone-500">Belum ada perubahan.</p>@endforelse</div>
    </section>
    <section class="rounded-2xl bg-white p-6"><h2 class="text-lg font-bold">Validasi terbaru</h2>
        <div class="mt-4 divide-y">@forelse($recentValidations as $item)<div class="py-3 text-sm"><b>{{ $item->title }}</b><p class="text-stone-500">{{ $item->activity_name }} · {{ $item->reviewer_name ?? 'Validator' }} · {{ $item->status }}</p></div>@empty<p class="py-4 text-stone-500">Belum ada validasi pembelajaran.</p>@endforelse</div>
    </section>
</div>
@endsection
