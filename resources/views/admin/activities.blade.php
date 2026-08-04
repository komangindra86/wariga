@extends('layouts.admin')
@section('title','Master Kegiatan')
@section('content')
<h1 class="text-3xl font-bold">Master Kegiatan</h1>
<p class="mt-2 text-stone-500">Kegiatan yang dapat dipilih pada pencarian dewasa ayu.</p>
@if(auth()->user()->hasAdminRole('admin_data'))
<form method="POST" action="{{ route('admin.activities.store') }}" class="mt-6 grid gap-3 rounded-2xl bg-white p-5 md:grid-cols-3">@csrf
    <input name="kode" required placeholder="Kode" class="rounded-xl border p-3">
    <input name="nama" required placeholder="Nama kegiatan" class="rounded-xl border p-3">
    <input name="kategori" required placeholder="Kategori" class="rounded-xl border p-3">
    <textarea name="deskripsi" required placeholder="Deskripsi" class="rounded-xl border p-3 md:col-span-2"></textarea>
    <textarea name="disclaimer" required placeholder="Disclaimer" class="rounded-xl border p-3"></textarea>
    <input name="urutan" type="number" min="0" value="0" class="rounded-xl border p-3">
    <div class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Kegiatan baru dibuat nonaktif. Isi Basis Pengetahuan sebelum mengaktifkannya.</div>
    <button class="rounded-xl bg-santih-700 px-4 py-3 font-semibold text-white">Tambah kegiatan</button>
</form>
@endif
<div class="mt-6 grid gap-4">
@foreach($activities as $activity)
<form method="POST" action="{{ route('admin.activities.update',$activity) }}" class="grid gap-3 rounded-2xl bg-white p-5 md:grid-cols-6">@csrf @method('PUT')
    <input name="kode" value="{{ $activity->kode }}" class="rounded border p-2" required>
    <input name="nama" value="{{ $activity->nama }}" class="rounded border p-2" required>
    <input name="kategori" value="{{ $activity->kategori }}" class="rounded border p-2" required>
    <input name="urutan" type="number" value="{{ $activity->urutan }}" class="rounded border p-2" required>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($activity->is_active)> Aktif</label>
    <button class="rounded bg-stone-800 px-3 py-2 text-white">Simpan</button>
    <textarea name="deskripsi" class="rounded border p-2 md:col-span-3" required>{{ $activity->deskripsi }}</textarea>
    <textarea name="disclaimer" class="rounded border p-2 md:col-span-3" required>{{ $activity->disclaimer }}</textarea>
</form>
@endforeach
</div>
<div class="mt-5">{{ $activities->links() }}</div>
@endsection
