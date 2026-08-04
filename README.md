# Wariga Bali Santih

## Atribusi referensi eksternal

Perhitungan dilakukan oleh mesin Wariga Bali Santih. Sebagian hasil dibandingkan
dengan [Kalender Bali Digital](https://kalenderbali.org) sebagai referensi
eksternal. Proyek ini tidak berafiliasi dengan KalenderBali.org. Data eksternal
dipakai untuk pembandingan dan validasi; keputusan adat penting tetap perlu
dikonfirmasi kepada pemangku atau ahli Wariga.

Backend Laravel dan fondasi panel admin untuk layanan Kalender Bali serta
pencarian dewasa ayu.

Fase 4 menyediakan mesin konversi tanggal Masehi menjadi indeks siklus Kalender
Bali 0–209 beserta Wuku dan seluruh Wewaran (Eka sampai Dasa Wara). Perhitungan
memuat jeda khusus pada Catur Wara, Asta Wara, dan Sanga Wara serta turunan urip
untuk Eka, Dwi, dan Dasa Wara.

Tanggal acuan wajib dikonfigurasi melalui environment dan tidak disediakan
secara default agar aplikasi tidak mengarang rujukan Wariga.

Fase 5 menambahkan Sasih, Penanggal, Pangelong, Purnama, dan Tilem dari dataset
yang dapat diverifikasi. Tanggal yang tidak ada di dataset dilaporkan sebagai
tidak tersedia, bukan dihitung memakai perkiraan.

Fase 6 menambahkan master kegiatan dan mesin aturan Wariga. Evaluasi publik
hanya memakai aturan aktif berstatus `verified`, menghormati periode berlaku,
menyimpan alasan setiap aturan yang cocok, membatasi skor ke 0–100, dan
mengklasifikasikan hasil memakai ambang pada `config/wariga.php`. Aturan demo
hasil seeder berstatus draft, nonaktif, dan tidak memuat klaim budaya.

Fase 7 menambahkan mesin pencarian rekomendasi tanggal untuk rentang hingga 366
hari. Hasil diproses per chunk, dirangking berdasarkan status, skor, rule
positif, pantangan, dan kedekatan tanggal, serta di-cache berdasarkan kegiatan,
rentang, versi engine, versi rule, mode, filter, dan urutan. Tersedia mode
`simple`, `complete`, dan `expert`; REST API pencarian disediakan pada fase 8.

Fase 9 menyediakan panel admin Blade/Tailwind di `/admin/login` dengan role
`super_admin`, `admin_data`, `validator_wariga`, dan `viewer`. Panel mencakup
dashboard, master data, workflow validasi rule, rule tester, audit log, import,
dan konfigurasi engine. Rule verified tidak dapat dihapus permanen dan pembuat
rule tidak dapat memverifikasi rule miliknya sendiri kecuali super admin.

Fase 10 menyediakan autentikasi mobile berbasis Sanctum, penyimpanan snapshot
hasil dewasa ayu, tautan publik yang tetap tersedia setelah akun pembuat
dihapus, penghitung kunjungan, dan penanda ketika versi engine atau aturan telah
berubah sejak snapshot disimpan.

Fase 11 menambahkan audit log terstruktur untuk siklus hidup rule, import
dataset, dan perubahan konfigurasi engine. Setiap hasil Wariga membawa
`engine_version`, `rule_version`, dan `dataset_version`; snapshot tersimpan
mempertahankan ketiga versi tersebut agar perubahan dapat dilacak.

Fase 12 menambahkan regression suite backend dan fixture diagnostik satu siklus
210 hari. Fixture bawaan berstatus `belum_divalidasi_ahli`; status tersebut tidak
berubah hanya karena test otomatis berhasil.

Fase 13 menambahkan hardening produksi, limiter autentikasi, masa berlaku token,
security headers, indeks query produksi, scheduler housekeeping, laporan audit,
dan checklist rilis. Deployment belum dilakukan. Baca
[`docs/PHASE_13_PRODUCTION_AUDIT.md`](docs/PHASE_13_PRODUCTION_AUDIT.md) dan
[`docs/DEPLOYMENT_CHECKLIST.md`](docs/DEPLOYMENT_CHECKLIST.md).

Fase 14 menambahkan project React Native CLI terpisah di [`mobile`](mobile)
dengan TypeScript strict, konfigurasi development/staging/production, dependency
fondasi mobile, navigation shell, secure token storage, dan Android debug build
yang terverifikasi. Belum ada fitur Wariga pada aplikasi mobile. Baca
[`docs/PHASE_14_MOBILE_FOUNDATION.md`](docs/PHASE_14_MOBILE_FOUNDATION.md).

Fase 15 menambahkan design system mobile terpusat, komponen UI reusable,
layout responsif, bottom tab Beranda/Kalender/Tersimpan/Tentang, serta root
stack untuk seluruh alur mobile yang akan dikerjakan pada fase berikutnya. Baca
[`docs/PHASE_15_DESIGN_SYSTEM_NAVIGATION.md`](docs/PHASE_15_DESIGN_SYSTEM_NAVIGATION.md).

## Validasi siklus 210 hari

Bangun ulang fixture dari tanggal acuan:

```shell
php artisan wariga:generate-cycle-fixture
```

Bandingkan hasil sistem dengan CSV yang sudah diberikan oleh ahli:

```shell
php artisan wariga:validate-cycle storage/app/testing/referensi-ahli.csv
```

Perintah validasi menghasilkan laporan JSON berisi hasil sistem, referensi ahli,
status `cocok`/`berbeda`, dan catatan kolom yang berbeda. Exit code tetap gagal
bila satu tanggal berbeda atau tidak memiliki referensi.

## REST API v1 (Fase 8)

API mobile tersedia di `/api/v1`, memakai envelope respons konsisten, rate limit,
pagination, dan token Laravel Sanctum. Kontrak lengkap dapat dibaca di
[`public/openapi.yaml`](public/openapi.yaml).

## Dataset lunar

Header CSV wajib:

```text
tanggal_masehi,sasih_kode,fase,nomor_hari,is_purnama,is_tilem,is_nampih_sasih,sumber,referensi,catatan,verification_status
```

```shell
php artisan wariga:import-lunar storage/app/lunar.csv --preview
php artisan wariga:import-lunar storage/app/lunar.csv
```

Hasil tersedia melalui `GET /api/calendar/lunar/{YYYY-MM-DD}`. Provider formula
tetap nonpublik dan tidak menghasilkan data sampai rumusnya diverifikasi.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- MySQL 8
- Node.js 20 atau lebih baru

## Menjalankan secara lokal

1. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi MySQL.
2. Buat database bernama `wariga_bali_santih`.
3. Isi `WARIGA_REFERENCE_DATE` dan `WARIGA_REFERENCE_CYCLE_INDEX` menggunakan
   tanggal acuan yang sudah diverifikasi.
4. Jalankan `composer install`.
5. Jalankan `php artisan key:generate`.
6. Jalankan `php artisan migrate`.
7. Isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` untuk membuat super admin saat seeding,
   lalu jalankan `php artisan db:seed`.
8. Jalankan `npm install`.
9. Jalankan `composer run dev`, atau jalankan `php artisan serve` dan `npm run dev`
   pada dua terminal.

Jika Laragon masih memakai PHP lama, pilih PHP 8.2+ melalui menu Laragon atau
jalankan perintah dengan executable PHP 8.2 secara eksplisit.

## Pemeriksaan

```shell
php artisan test
./vendor/bin/pint --test
npm run build
```

Endpoint pemeriksaan layanan tersedia di `GET /health`.

## Mobile sampai Fase 27

Aplikasi React Native kini mendukung cache offline terbatas untuk halaman dan
data yang pernah dibuka. Cache memiliki masa berlaku, mengenali versi engine,
memisahkan data setiap akun, dan dibersihkan saat logout. Pencarian rekomendasi
baru tetap membutuhkan koneksi. Rincian ada di
[`docs/PHASE_24_OFFLINE_CACHE.md`](docs/PHASE_24_OFFLINE_CACHE.md).

Pengujian otomatis mobile mencakup unit, komponen, dan smoke test layar utama.
Checklist pengujian Android manual tersedia di
[`docs/PHASE_26_REACT_NATIVE_TESTING.md`](docs/PHASE_26_REACT_NATIVE_TESTING.md).

Persiapan listing Google Play Store, aset Android, dan draf deklarasi publikasi
tersedia di [`docs/PHASE_27_GOOGLE_PLAY_STORE.md`](docs/PHASE_27_GOOGLE_PLAY_STORE.md).
