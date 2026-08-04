@extends('layouts.admin')
@section('title','Import Data')
@section('content')
<h1 class="text-3xl font-bold">Import Data Lunar</h1>
<p class="mt-2 max-w-3xl text-stone-500">Unggah CSV dari sumber yang dapat dilacak. Data berstatus review harus diperiksa melalui menu Validator sebelum digunakan API publik.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.lunar.import') }}" class="mt-6 max-w-3xl rounded-2xl bg-white p-6">@csrf
    <input type="file" name="csv" accept=".csv,text/csv" required class="block w-full rounded-xl border p-3">
    <p class="mt-3 break-all text-xs text-stone-500">{{ implode(',', \App\Services\Lunar\LunarCsvImporter::HEADERS) }}</p>
    <button class="mt-5 rounded-xl bg-santih-700 px-5 py-3 font-semibold text-white">Validasi dan impor</button>
</form>
@if(session('import_result'))
<div class="mt-6 rounded-2xl bg-white p-6"><h2 class="font-bold">Hasil import</h2>
<p class="mt-2">Gagal: {{ count(session('import_result.failed',[])) }} · Duplikat: {{ count(session('import_result.duplicates',[])) }}</p></div>
@endif
@endsection
