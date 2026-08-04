# Fase 26 — Testing React Native

## Pengujian otomatis

Jalankan dari direktori `mobile`:

```powershell
npm test -- --runInBand
npm run typecheck
npm run lint
```

Suite fase 26 mencakup formatter tanggal, validator, API error mapper (termasuk
HTTP 422, 429, dan 500), query key builder, status mapper, Zustand store, auth
session, komponen UI utama, serta smoke test delapan layar utama.

## Checklist manual Android

Build yang diuji:

- [ ] layar kecil;
- [ ] layar besar;
- [ ] ukuran font besar;
- [ ] koneksi lambat;
- [ ] offline;
- [ ] timeout;
- [ ] HTTP 422;
- [ ] HTTP 429;
- [ ] HTTP 500;
- [ ] token kedaluwarsa;
- [ ] logout;
- [ ] dark mode jika tersedia.

Alur utama:

- [ ] Buka aplikasi.
- [ ] Pilih kegiatan.
- [ ] Pilih rentang.
- [ ] Cari.
- [ ] Buka hasil.
- [ ] Buka detail.
- [ ] Bagikan.

Checklist manual sengaja tidak ditandai sebelum diverifikasi pada perangkat atau
emulator Android. Catat model perangkat, versi Android, ukuran layar/font, jenis
koneksi, hasil, dan bukti kegagalan saat eksekusi.
