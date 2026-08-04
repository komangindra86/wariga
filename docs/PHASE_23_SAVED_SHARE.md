# Fase 23 — Hasil Tersimpan dan Fitur Bagikan

## Hasil

- Tombol **Simpan** pada detail Dewasa Ayu menyimpan snapshot untuk pengguna
  yang sudah masuk.
- Tab **Tersimpan** menampilkan kegiatan, tanggal, skor dan status snapshot,
  waktu simpan, versi engine, serta catatan.
- Detail tersimpan tetap menampilkan snapshot lama dan memberi peringatan jika
  versi aturan, engine, atau dataset sudah berubah.
- **Hitung ulang** membuka perhitungan terkini untuk kegiatan dan tanggal yang
  sama.
- **Hapus simpanan** memakai konfirmasi dan memperbarui daftar setelah berhasil.
- **Bagikan** membuat tautan publik dari backend lalu membuka native share sheet
  dengan kegiatan, tanggal, Kalender Bali, Wuku, status, skor, dan tautan.
- Pengguna tamu tetap dapat menghitung hasil, tetapi diarahkan masuk ketika
  mencoba menyimpan atau membuat tautan bagikan.

## Verifikasi

```bash
cd mobile
npm run typecheck
npm run lint -- --max-warnings=0
npm test -- --runInBand
```

Backend penyimpanan dan tautan publik tetap dilindungi Laravel Sanctum. Snapshot
yang dibagikan tidak berubah ketika aturan Wariga diperbarui.
