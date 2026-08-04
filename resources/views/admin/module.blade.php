@extends('layouts.admin')
@section('title',$title)
@section('content')
<h1 class="text-3xl font-bold">{{ $title }}</h1>
<div class="mt-6 overflow-x-auto rounded-2xl bg-white p-5">
@if(count($rows))
<table class="w-full text-left text-sm"><thead><tr>@foreach(array_keys((array)$rows->first()) as $key)<th class="p-3">{{ str_replace('_',' ',ucfirst($key)) }}</th>@endforeach</tr></thead><tbody class="divide-y">
@foreach($rows as $row)<tr>@foreach((array)$row as $value)<td class="max-w-xs truncate p-3">{{ is_scalar($value)||is_null($value)?$value:json_encode($value) }}</td>@endforeach</tr>@endforeach
</tbody></table>@if(method_exists($rows,'links'))<div class="mt-5">{{ $rows->links() }}</div>@endif
@else<p class="py-8 text-center text-stone-500">Modul siap. Belum ada data untuk ditampilkan.</p>@endif
</div>
@endsection
