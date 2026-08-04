# Fase 19 — Hasil Pencarian Dewasa Ayu

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

- Layar hasil terhubung ke `GET /api/v1/dewasa/search`.
- Ringkasan menampilkan kegiatan, rentang tanggal, tanggal diperiksa, rekomendasi, pertimbangan, dan hindari.
- Card tanggal menampilkan tanggal Masehi, Sapta Wara, Panca Wara, Wuku, skor, status, dan maksimal tiga alasan utama.
- Filter Semua, Rekomendasi, Cukup baik, Pertimbangan, dan Hindari tersedia dalam bottom sheet.
- Sorting Skor tertinggi, Tanggal terdekat, dan Tanggal terlama diteruskan ke API.
- Tersedia skeleton, retry, empty state, serta navigasi menuju detail rekomendasi Fase 20.
- Daftar tidak memuat detail rule penuh.

## Verifikasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
```
