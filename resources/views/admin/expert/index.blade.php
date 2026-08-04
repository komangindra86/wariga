@extends('layouts.admin')
@section('title','Sistem Pakar')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">Sistem Pakar & Pembelajaran</h1>
        <p class="mt-2 max-w-3xl text-stone-500">Mesin membandingkan keputusan dengan sumber rujukan, menemukan pola kesalahan, lalu membuat usulan. Hanya validator yang boleh menyetujui usulan.</p>
    </div>
    <a href="{{ route('admin.expert.knowledge') }}" class="rounded-xl bg-santih-700 px-4 py-3 font-semibold text-white">Kelola basis pengetahuan</a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['Kasus tervalidasi',$metrics['total']],
        ['Keputusan cocok',$metrics['correct']],
        ['Akurasi',$metrics['accuracy'] === null ? 'Belum dihitung' : $metrics['accuracy'].'%'],
        ['Rekomendasi terlewat',$metrics['false_negative']],
        ['Rekomendasi berlebih',$metrics['false_positive']],
    ] as [$label,$value])
    <div class="rounded-2xl bg-white p-5"><p class="text-sm text-stone-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $value }}</p></div>
    @endforeach
</div>

<section class="mt-7 rounded-2xl bg-white p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">Pembelajaran dari pengguna</h2>
            <p class="mt-1 text-sm text-stone-500">Identitas pengguna tidak ditampilkan. Masukan hanya membentuk usulan setelah sedikitnya 5 sampel dan tetap memerlukan keputusan validator.</p>
        </div>
        <div class="flex gap-2 text-sm">
            <span class="rounded-full bg-stone-100 px-3 py-2">{{ $metrics['user_feedback_total'] }} masukan layak</span>
            <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-800">{{ $metrics['user_feedback_helpful'] }} membantu</span>
            <span class="rounded-full bg-amber-100 px-3 py-2 text-amber-800">{{ $metrics['user_feedback_not_helpful'] }} kurang tepat</span>
        </div>
    </div>
    <div class="mt-5 grid gap-6 xl:grid-cols-2">
        <div>
            <h3 class="font-semibold">Pola kegiatan, wilayah, dan pakem</h3>
            <div class="mt-3 divide-y">
                @forelse($feedbackGroups as $group)
                    <div class="py-3">
                        <div class="flex justify-between gap-3"><b>{{ $group->activity_name }}</b><span>{{ $group->total }} masukan</span></div>
                        <p class="mt-1 text-sm text-stone-500">{{ $group->region ?: 'Wilayah tidak diisi' }} · {{ $group->tradition ?: 'Pakem tidak diisi' }} · {{ $group->helpful }} membantu · {{ $group->not_helpful }} kurang tepat</p>
                    </div>
                @empty<p class="py-4 text-stone-500">Belum ada pola pengguna yang dapat dianalisis.</p>@endforelse
            </div>
        </div>
        <div>
            <h3 class="font-semibold">Masukan terbaru tanpa identitas</h3>
            <div class="mt-3 divide-y">
                @forelse($recentFeedback as $feedback)
                    <div class="py-3">
                        <div class="flex justify-between gap-3"><b>{{ $feedback->activity_name }} · {{ $feedback->recommendation_date }}</b><span>{{ match($feedback->rating) {'helpful'=>'Membantu','not_helpful'=>'Kurang tepat',default=>'Tidak yakin'} }}</span></div>
                        <p class="mt-1 text-sm text-stone-500">{{ $feedback->region ?: 'Wilayah tidak diisi' }} · {{ $feedback->tradition ?: 'Pakem tidak diisi' }}</p>
                        @if($feedback->consulted_expert)<p class="mt-1 text-xs text-santih-700">Dikonsultasikan kepada {{ $feedback->expert_role }}</p>@endif
                        @if($feedback->reason)<p class="mt-2 text-sm">{{ $feedback->reason }}</p>@endif
                    </div>
                @empty<p class="py-4 text-stone-500">Belum ada umpan balik pengguna.</p>@endforelse
            </div>
        </div>
    </div>
</section>

<section class="mt-7 rounded-2xl bg-white p-6">
    <h2 class="text-xl font-bold">Tambahkan kasus pembanding</h2>
    <p class="mt-1 text-sm text-stone-500">Masukkan hasil sumber dan hasil aplikasi pada tanggal yang sama. Data ini tidak langsung mengubah keputusan pengguna.</p>
    <form method="POST" action="{{ route('admin.expert.cases.store') }}" class="mt-5 grid gap-4 md:grid-cols-3">@csrf
        <label class="text-sm">Kegiatan
            <select name="activity_id" required class="mt-2 w-full rounded-xl border p-3">@foreach($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->nama }}</option>@endforeach</select>
        </label>
        <label class="text-sm">Tanggal kasus<input type="date" name="case_date" required class="mt-2 w-full rounded-xl border p-3"></label>
        <label class="text-sm">Skor aplikasi<input type="number" min="0" max="100" name="predicted_score" class="mt-2 w-full rounded-xl border p-3"></label>
        @php($statuses=['recommended'=>'Direkomendasikan','consideration'=>'Perlu pertimbangan','avoid'=>'Dihindari'])
        <label class="text-sm">Keputusan sumber
            <select name="expected_status" class="mt-2 w-full rounded-xl border p-3">@foreach($statuses as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-sm">Keputusan aplikasi
            <select name="predicted_status" class="mt-2 w-full rounded-xl border p-3">@foreach($statuses as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-sm">Nama referensi eksternal<input name="source_name" value="Kalender Bali Digital — referensi eksternal" required class="mt-2 w-full rounded-xl border p-3"></label>
        <label class="text-sm md:col-span-2">Tautan sumber<input type="url" name="source_url" value="https://kalenderbali.org/pilihdewasa.php" class="mt-2 w-full rounded-xl border p-3"></label>
        <label class="text-sm">Catatan validator<textarea name="validator_note" class="mt-2 w-full rounded-xl border p-3"></textarea></label>
        <button class="rounded-xl bg-stone-900 px-4 py-3 font-semibold text-white md:col-span-3">Simpan sebagai data pembelajaran</button>
    </form>
</section>

<div class="mt-7 grid gap-6 xl:grid-cols-2">
    <section class="rounded-2xl bg-white p-6">
        <h2 class="text-xl font-bold">Kasus terbaru</h2>
        <div class="mt-4 divide-y">
            @forelse($recentCases as $case)
            <div class="py-4"><b>{{ $case->activity_name }} · {{ $case->case_date }}</b><p class="mt-1 text-sm text-stone-500">Sumber: {{ $statuses[$case->expected_status] }} · Aplikasi: {{ $statuses[$case->predicted_status] }}</p></div>
            @empty<p class="py-4 text-stone-500">Belum ada kasus pembanding.</p>@endforelse
        </div>
    </section>
    <section class="rounded-2xl bg-white p-6">
        <div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold">Usulan mesin</h2><p class="text-sm text-stone-500">Usulan tidak pernah aktif otomatis.</p></div>
        @if(auth()->user()->hasAdminRole('validator_wariga'))<form method="POST" action="{{ route('admin.expert.proposals.generate') }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm">Analisis kasus</button></form>@endif</div>
        <div class="mt-4 divide-y">
            @forelse($proposals as $proposal)
            <div class="py-4">
                <div class="flex justify-between gap-3"><b>{{ $proposal->title }}</b><span class="rounded-full bg-stone-100 px-3 py-1 text-xs">{{ $proposal->status }}</span></div>
                <p class="mt-1 text-sm">{{ $proposal->activity_name }} · {{ $proposal->rationale }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $proposal->sample_size }} kasus · keyakinan pola {{ $proposal->confidence }}%</p>
                @if(auth()->user()->hasAdminRole('validator_wariga'))
                <form method="POST" action="{{ route('admin.expert.proposals.transition',$proposal->id) }}" class="mt-3 grid gap-2 sm:grid-cols-[160px_1fr_auto]">@csrf
                    <select name="status" class="rounded border p-2"><option value="review">Perlu ditinjau</option><option value="approved">Setujui usulan</option><option value="rejected">Tolak</option></select>
                    <input name="review_note" required placeholder="Alasan keputusan validator" class="rounded border p-2">
                    <button class="rounded bg-stone-800 px-3 text-white">Simpan</button>
                </form>
                @endif
            </div>
            @empty<p class="py-4 text-stone-500">Belum ada usulan. Tambahkan kasus berbeda lalu jalankan analisis.</p>@endforelse
        </div>
    </section>
</div>
@endsection
