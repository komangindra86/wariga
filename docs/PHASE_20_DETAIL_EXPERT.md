# Fase 20 — Detail Rekomendasi dan Mode Ahli

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

- Layar detail terhubung ke `GET /api/v1/dewasa/{activity}/{date}`.
- Ringkasan menampilkan kegiatan, tanggal, skor, status, alasan utama, dan disclaimer budaya.
- Accordion tersedia untuk alasan rekomendasi, pertimbangan, Kalender Bali, sumber/status verifikasi, dan Mode Ahli.
- Mode Ahli menampilkan kode/nama rule, atribut, operator, nilai pembanding dan aktual, kecocokan, dampak skor, severity, sumber, halaman, status verifikasi, engine version, dan rule version.
- Endpoint mode ahli kini menyertakan field explainability lengkap tanpa memperbesar payload mode simple/complete.
- Aksi Simpan dan Bagikan tersedia sebagai sticky action. Bagikan memakai share sheet perangkat; penyimpanan akun dilanjutkan pada fase autentikasi/hasil tersimpan.
- Tersedia skeleton, retry, dan pesan data kosong.

## Verifikasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
php artisan test
```
