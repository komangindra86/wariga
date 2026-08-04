@extends('layouts.public')

@section('title', 'Kebijakan Privasi — Wariga Bali Santih')

@section('content')
    <article class="prose prose-stone mx-auto min-h-[65vh] max-w-3xl px-6 py-16">
        <p class="text-sm font-semibold uppercase tracking-widest text-santih-500">Dokumen publik</p>
        <h1 class="mt-3 text-4xl font-bold text-santih-900">Kebijakan Privasi</h1>
        <div class="mt-8 space-y-8 leading-8 text-stone-600">
            <section><h2 class="text-xl font-bold text-santih-900">Data yang diproses</h2>
                <p class="mt-2">Aplikasi memproses nama, alamat email, kredensial akun yang sudah dilindungi, hasil Dewasa Ayu tersimpan, pengaturan pribadi, dan umpan balik yang dikirim secara sukarela. Umpan balik dapat memuat kegiatan, tanggal rekomendasi yang sedang dilihat, penilaian, alasan, wilayah, pakem, serta keterangan konsultasi ahli.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Tujuan penggunaan</h2>
                <p class="mt-2">Data akun digunakan untuk autentikasi, menyimpan hasil, membuat tautan berbagi, dan menjaga fitur pribadi tetap terhubung dengan pemiliknya. Umpan balik digunakan untuk mengukur kegunaan rekomendasi, menemukan perbedaan menurut kegiatan atau tradisi, dan menyusun usulan yang dapat ditinjau validator.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Persetujuan dan data opsional</h2>
                <p class="mt-2">Persetujuan eksplisit diperlukan sebelum umpan balik digunakan untuk pembelajaran. Alasan, wilayah, pakem, serta keterangan konsultasi ahli bersifat opsional. Pengguna dapat memakai pencarian Dewasa Ayu tanpa mengirim umpan balik.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Pembelajaran dan akses validator</h2>
                <p class="mt-2">Umpan balik dianalisis secara agregat. Identitas akun tidak ditampilkan pada panel validator atau bukti usulan. Masukan tidak langsung mengubah skor, aturan, ataupun rekomendasi; setiap usulan tetap memerlukan pemeriksaan manusia.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Penyimpanan dan keamanan</h2>
                <p class="mt-2">Kata sandi disimpan dalam bentuk hash dan token masuk perangkat menggunakan penyimpanan aman. Akses administratif dibatasi berdasarkan peran. Pengiriman berulang dibatasi dan pola pengiriman berlebihan dapat dikeluarkan dari kumpulan pembelajaran.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Berbagi hasil</h2>
                <p class="mt-2">Tautan berbagi hanya dibuat atas tindakan pengguna. Tautan memuat salinan hasil dan tidak menampilkan identitas akun. Siapa pun yang memperoleh tautan dapat melihat isinya sampai tautan kedaluwarsa.</p>
            </section>
            <section><h2 class="text-xl font-bold text-santih-900">Penghapusan dan kendali pengguna</h2>
                <p class="mt-2">Pengguna dapat menghapus akun melalui aplikasi. Penghapusan akun turut menghapus hasil tersimpan dan umpan balik yang terhubung. Tautan hasil yang telah dibagikan dapat tetap tersedia sebagai salinan anonim sampai kedaluwarsa.</p>
            </section>
        </div>
    </article>
@endsection
