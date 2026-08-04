@extends('layouts.admin')
@section('title','Konfigurasi Engine')
@section('content')
<h1 class="text-3xl font-bold">Konfigurasi Engine</h1>
<p class="mt-2 text-stone-500">Perubahan versi akan menandai snapshot lama sebagai sudah kedaluwarsa.</p>
<form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 grid max-w-2xl gap-5 rounded-2xl bg-white p-6 md:grid-cols-2">@csrf @method('PUT')
@foreach(['engine_version'=>'Versi engine','rule_version'=>'Versi rule','dataset_version'=>'Versi dataset','reference_date'=>'Tanggal acuan','reference_cycle_index'=>'Indeks siklus acuan','initial_score'=>'Skor awal'] as $key=>$label)<label>{{ $label }}<input name="{{ $key }}" value="{{ $settings[$key] }}" class="mt-2 w-full rounded-xl border p-3" @if($key !== 'reference_date') required @endif></label>@endforeach
<label>Cache pencarian (detik)<input type="number" name="search_cache_ttl" value="{{ $settings['search.cache_ttl'] }}" class="mt-2 w-full rounded-xl border p-3" required></label>
<button class="rounded-xl bg-santih-700 px-5 py-3 font-semibold text-white md:col-span-2">Simpan konfigurasi</button>
</form>
@endsection
