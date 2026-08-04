@extends('layouts.admin')
@section('title','Kontribusi Pengguna')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><h1 class="text-3xl font-bold text-santih-900">Kontribusi Pengguna</h1><p class="mt-2 text-sm text-stone-600">Semua kiriman adalah usulan dan tidak mengubah aturan secara otomatis.</p></div>
    <form method="GET"><select name="status" onchange="this.form.submit()" class="rounded-xl border-stone-300"><option value="">Semua status</option>@foreach(['pending_review'=>'Menunggu','reviewing'=>'Ditinjau','accepted'=>'Diterima','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" @selected($status===$key)>{{ $label }}</option>@endforeach</select></form>
</div>
<div class="mt-6 grid gap-4">
@forelse($rows as $item)
<article class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex flex-wrap justify-between gap-3"><div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold">{{ str_replace('_',' ', $item->type) }}</span><h2 class="mt-3 text-lg font-bold">{{ $item->subject }}</h2></div><p class="text-xs text-stone-500">{{ $item->created_at->format('d M Y H:i') }} · {{ $item->is_anonymous ? 'Anonim' : ($item->contact_email ?? 'Pengguna terdaftar') }}</p></div>
    <p class="mt-3 whitespace-pre-line text-sm text-stone-700">{{ $item->message }}</p>
    @if($item->activity_name || $item->related_date || $item->region || $item->tradition || $item->source_reference)<dl class="mt-4 grid gap-2 rounded-xl bg-stone-50 p-4 text-sm md:grid-cols-2">@foreach(['activity_name'=>'Kegiatan','related_date'=>'Tanggal','region'=>'Wilayah','tradition'=>'Pakem','source_reference'=>'Sumber'] as $field=>$label)@if($item->$field)<div><dt class="text-xs text-stone-500">{{ $label }}</dt><dd>{{ $field==='related_date' ? $item->$field->format('d-m-Y') : $item->$field }}</dd></div>@endif @endforeach</dl>@endif
    <form method="POST" action="{{ route('admin.contributions.transition',$item) }}" class="mt-4 flex gap-2">@csrf<select name="status" class="rounded-xl border-stone-300 text-sm">@foreach(['pending_review'=>'Menunggu','reviewing'=>'Ditinjau','accepted'=>'Diterima','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" @selected($item->status===$key)>{{ $label }}</option>@endforeach</select><button class="rounded-xl bg-santih-900 px-4 py-2 text-sm text-white">Simpan status</button></form>
</article>
@empty<p class="rounded-2xl bg-white p-6 text-stone-500">Belum ada kontribusi.</p>@endforelse
</div><div class="mt-6">{{ $rows->links() }}</div>
@endsection
