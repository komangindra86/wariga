@extends('layouts.admin')
@section('title','Kalender Lunar')
@section('content')
<div>
    <h1 class="text-3xl font-bold">Kalender Lunar</h1>
    <p class="mt-2 text-stone-500">Hasil rumus berjalan otomatis. Halaman ini digunakan untuk membandingkan sumber dan menangani pengecualian.</p>
</div>

<div class="mt-6 rounded-2xl bg-white p-5">
    <form method="GET" action="{{ route('admin.lunar') }}" class="flex flex-wrap items-end gap-3">
        <label class="grid gap-1 text-sm"><span class="text-stone-600">Tahun yang diperiksa</span>
            <input name="year" type="number" min="1900" max="2100" value="{{ $year }}" class="rounded-xl border-stone-300">
        </label>
        <button class="rounded-xl border border-stone-300 px-4 py-2.5">Tampilkan</button>
    </form>

    <div class="mt-5 grid gap-3 sm:grid-cols-4">
        @foreach([['Tanggal sumber',$summary['total']],['Terverifikasi',$summary['verified']],['Perlu ditinjau',$summary['disputed']],['Nampih Sasih',$summary['nampih']]] as [$label,$value])
            <div class="rounded-xl bg-stone-50 p-4"><p class="text-xs text-stone-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="mt-5 flex flex-wrap gap-3">
        @if(auth()->user()->hasAdminRole('validator_wariga'))
            <form method="POST" action="{{ route('admin.lunar.validate') }}">@csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button class="rounded-xl bg-santih-700 px-4 py-3 text-white">Validasi rumus tahun {{ $year }}</button>
            </form>
        @endif
        @if(auth()->user()->hasAdminRole('admin_data'))
            <form method="POST" action="{{ route('admin.lunar.sync') }}" onsubmit="return confirm('Sinkronisasi mengambil data pembanding dari KalenderBali.org dan dapat memerlukan beberapa menit. Lanjutkan?')">@csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button class="rounded-xl border border-santih-700 px-4 py-3 text-santih-800">Sinkronkan referensi pembanding</button>
            </form>
        @endif
        <a href="{{ route('admin.import') }}" class="rounded-xl border border-stone-300 px-4 py-3">Impor data koreksi</a>
    </div>
    <p class="mt-3 text-xs text-stone-500">Sinkronisasi bersifat atomik: bila ada tanggal yang gagal diambil, data yang sudah ada tidak ditimpa.</p>
</div>
<div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
    {{ config('wariga.reference_disclosure') }}
</div>

@if(session('validation_result'))
    @php($validation = session('validation_result'))
    <div class="mt-6 rounded-2xl {{ count($validation['differences']) ? 'bg-amber-50' : 'bg-emerald-50' }} p-5">
        <h2 class="font-bold">Hasil validasi {{ $year }}</h2>
        <p class="mt-1">{{ $validation['checked'] }} tanggal diperiksa, {{ $validation['matched'] }} cocok, {{ count($validation['differences']) }} berbeda.</p>
        @if(count($validation['differences']))
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm">
                <thead><tr><th class="p-2">Tanggal</th><th>Data sumber</th><th>Hasil rumus</th></tr></thead>
                <tbody>@foreach($validation['differences'] as $difference)<tr class="border-t"><td class="p-2">{{ $difference['date'] }}</td><td>{{ json_encode($difference['data'], JSON_UNESCAPED_UNICODE) }}</td><td>{{ json_encode($difference['formula'], JSON_UNESCAPED_UNICODE) }}</td></tr>@endforeach</tbody>
            </table></div>
        @endif
    </div>
@endif

<div class="mt-6 rounded-2xl bg-white p-5">
    <h2 class="text-lg font-bold">Pengecualian Nampih Sasih</h2>
    <p class="mt-1 text-sm text-stone-500">Tanggal berikut berasal dari data pembanding dan mengoreksi siklus Sasih otomatis.</p>
    @if($nampihRows->isEmpty())
        <p class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500">Tidak ada Nampih Sasih yang tercatat untuk {{ $year }}.</p>
    @else
        <div class="mt-4 flex flex-wrap gap-2">@foreach($nampihRows as $row)
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-900">{{ $row->tanggal_masehi->format('d M Y') }} · {{ $row->sasih?->nama }}</span>
        @endforeach</div>
    @endif
</div>

<div class="mt-6 overflow-x-auto rounded-2xl bg-white">
    <table class="w-full text-left text-sm"><thead><tr><th class="p-3">Tanggal</th><th>Sasih</th><th>Fase</th><th>Hari</th><th>Penanda</th><th>Sumber</th><th>Status</th></tr></thead>
    <tbody class="divide-y">@forelse($rows as $row)<tr>
        <td class="p-3">{{ $row->tanggal_masehi->format('d-m-Y') }}</td>
        <td>{{ $row->sasih?->nama ?? 'Tidak tersedia' }} @if($row->is_nampih_sasih)<span class="rounded bg-amber-100 px-2 py-1 text-xs">Nampih</span>@endif</td>
        <td>{{ ucfirst($row->fase) }}</td><td>{{ $row->nomor_hari }}</td>
        <td>{{ $row->is_purnama?'Purnama':($row->is_tilem?'Tilem':'—') }}</td><td>{{ $row->sumber }}</td>
        <td>{{ match($row->verification_status) {'verified'=>'Terverifikasi','disputed'=>'Perlu ditinjau','archived'=>'Diarsipkan','draft'=>'Belum diverifikasi',default=>'Hasil perhitungan'} }}</td>
    </tr>@empty<tr><td colspan="7" class="p-8 text-center text-stone-500">Belum ada data pembanding untuk {{ $year }}. Kalender tetap dihitung otomatis; gunakan sinkronisasi untuk memverifikasinya.</td></tr>@endforelse</tbody></table>
</div>
<div class="mt-5">{{ $rows->links() }}</div>
@endsection
