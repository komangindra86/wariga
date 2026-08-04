# Fase 18 — Pemilihan Kegiatan dan Rentang Waktu

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

- Daftar kegiatan terhubung ke API, dapat dicari, dan difilter berdasarkan kategori.
- Setiap kegiatan menampilkan ikon, nama, kategori, dan deskripsi.
- Kegiatan terpilih diteruskan ke halaman rentang waktu.
- Tersedia pilihan 7 hari, 30 hari, bulan ini, bulan depan, dan rentang kustom.
- Perhitungan hari ini menggunakan zona waktu `Asia/Makassar`.
- Rentang kustom memvalidasi format, urutan tanggal, dan batas maksimal 366 hari.
- Tombol pencarian nonaktif saat kegiatan atau rentang tidak valid.
- Parameter `activityId`, `startDate`, dan `endDate` siap diteruskan ke hasil Fase 19.

## Verifikasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
```
