# Fase 21 — Kalender Bali Bulanan

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

- Tab Kalender menampilkan satu bulan penuh dari `GET /api/v1/calendar/month`.
- Setiap sel menampilkan tanggal Masehi dan Panca Wara.
- Purnama, Tilem, atau rahina penting ditandai pada kalender ketika datanya tersedia.
- Pengguna dapat berpindah bulan dengan tombol atau gestur geser dan kembali ke bulan berjalan lewat tombol Hari ini.
- Menekan tanggal membuka rincian Sapta Wara, Panca Wara, Wuku, Sasih, fase lunar, rahina, dan status verifikasi.
- React Query menyimpan cache per bulan. Bulan sebelum dan sesudah baru dimuat setelah bulan aktif berhasil tampil.
- Endpoint bulanan mengirim data kalender dan lunar seluruh tanggal dalam satu respons; aplikasi tidak meminta setiap tanggal secara terpisah.

## Verifikasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
php artisan test
```
