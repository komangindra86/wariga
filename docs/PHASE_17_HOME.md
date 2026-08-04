# Fase 17 — Beranda dan Kalender Bali Hari Ini

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

-   Beranda menampilkan nama aplikasi, tanggal lokal, dan Kalender Bali hari ini.
-   Card kalender memuat Sapta Wara, Panca Wara, Wuku, data lunar yang tersedia,
    Purnama/Tilem, serta status verifikasi.
-   Ketidaktersediaan data lunar disampaikan apa adanya.
-   Tombol utama mengarah ke pencarian Dewasa Ayu dan tab Kalender.
-   Kegiatan populer dan hasil tersimpan terakhir memiliki loading, error, dan
    empty state yang sesuai.
-   Hasil tersimpan hanya diminta ketika token autentikasi tersedia.
-   Beranda mendukung skeleton, retry, pull-to-refresh, dan banner offline.
-   Informasi singkat Wariga dan disclaimer budaya tersedia di bagian bawah.

## Verifikasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
```
