# Fase 25 — Pengingat dan Notifikasi

## Hasil

- Notifikasi lokal dapat dijadwalkan untuk setiap hasil Dewasa Ayu tersimpan.
- Pengguna dapat memilih pengingat satu hari sebelumnya, pada hari yang sama,
  atau keduanya, serta menentukan waktu notifikasi dalam format 24 jam.
- Izin notifikasi tidak diminta saat aplikasi dibuka. Dialog izin hanya muncul
  ketika pengguna mengaktifkan pengingat dari halaman pengaturan.
- Menonaktifkan fitur membatalkan notifikasi terjadwal. Perubahan waktu atau
  pilihan hari otomatis menjadwalkan ulang pengingat yang masih aktif.
- Tidak ada notifikasi promosi yang diaktifkan secara default.

## Verifikasi

```bash
cd mobile
npm run typecheck
npm run lint
npm test -- --runInBand
```
