@extends('layouts.admin')
@section('title','Audit Log')
@section('content')
<h1 class="text-3xl font-bold">Audit Log</h1>
<div class="mt-6 overflow-x-auto rounded-2xl bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-4">Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>IP</th></tr></thead><tbody class="divide-y">
@forelse($logs as $log)<tr><td class="p-4">{{ $log->created_at }}</td><td>{{ $log->user?->name ?? 'Sistem' }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</td><td>{{ $log->ip_address }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center">Belum ada audit log.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $logs->links() }}</div>
@endsection
