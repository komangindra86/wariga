# Fase 24 — Offline Cache dan Penanganan Koneksi

## Hasil

- Banner offline global menampilkan bahwa data berasal dari penyimpanan terakhir
  di perangkat.
- Cache menyimpan kalender, detail Dewasa Ayu, hasil pencarian yang pernah
  dibuka, daftar kegiatan terakhir, dan data tersimpan.
- Halaman navigasi terakhir dipulihkan selama belum melewati masa berlaku.
- Cache publik berlaku 7 hari; cache akun dan navigasi berlaku 24 jam.
- Data akun memakai namespace berdasarkan ID pengguna dan tidak pernah
  dihidrasi ke akun lain.
- Cache privat dan halaman terakhir akun dihapus saat logout atau penghapusan
  akun.
- Versi engine disimpan bersama cache. Perubahan versi membuang cache hasil
  Dewasa Ayu dan snapshot privat yang tidak lagi cocok.
- Tombol pencarian baru dinonaktifkan ketika offline. React Query tetap memakai
  `networkMode: online`, sehingga aplikasi tidak menghitung atau membuat hasil
  baru tanpa respons server.

## Verifikasi

```bash
cd mobile
npm run typecheck
npm run lint
npm test -- --runInBand
```

Pengujian fase 24 mencakup pemulihan cache publik, isolasi antar akun,
pembersihan cache privat, dan invalidasi ketika engine version berubah.
