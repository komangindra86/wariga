# Audit Rumus Engine Wariga

Tanggal audit: 3 November 2026. Cakupan: engine kalender (pawukon, wewaran, sasih,
purnama/tilem, rerainan). Aturan ala-ayuning dewasa tidak ikut diaudit di sini.

## Ringkasan

| Komponen | Sebelum perbaikan | Sesudah perbaikan | Status |
| --- | --- | --- | --- |
| Wuku + 10 wewaran | 108/108 tanggal cocok (2024–2032) | sama | Valid, berlaku tanpa batas tahun |
| Penanggal/pangelong, purnama, tilem | 318/322 cocok (2024–2037) | 322/322 (2024–2037), 198/198 (2000–2007) | Valid |
| Nama sasih | 34/108 cocok; meleset di luar Jul 2024–Mei 2027 | 1064/1064 purnama-tilem (2003–2045) | Valid sejak 2003, tanpa batas atas |
| Rerainan pawukon (Saraswati, Pagerwesi, dst.) | hanya muncul untuk 2026 (hardcode) | dihitung tiap 210 hari | Valid |
| Libur nasional (SKB 3 Menteri) | hanya 2026 (hardcode) | dihitung: 2026 sama persis dengan SKB; hari besar Islam 35/35 awal bulan resmi 2018–2026; Imlek 31/31 (2000–2030); Waisak 13/13 (2014–2026) | Valid; cuti bersama tidak dihitung |
| Rerainan sasih (Siwa Ratri, Tawur Kesanga, Nyepi, Ngembak Geni) | hanya muncul untuk 2026 (hardcode) | dihitung dari Tilem Kapitu/Kasanga; cocok dengan Nyepi 2023–2026 dan Siwa Ratri 2026–2027 | Valid sejak 2003 |

Pembanding: halaman harian `kalenderbali.org/index.php?bulan=&tanggal=&tahun=`
(108 tanggal) dan `kalenderbali.org/purnamatilem.php?tahun=` (520 purnama/tilem,
tahun 2000–2007, 2024, 2026–2037).

## Rumus yang dipakai

### Pawukon (siklus 210 hari)

`i = (115 + selisih_hari(2026-07-29, tanggal)) mod 210`, dengan `i = 0` = Redite Paing Sinta.

| Unsur | Rumus (urutan mulai 1) | Urutan master |
| --- | --- | --- |
| Wuku | `floor(i / 7) + 1` | Sinta … Watugunung |
| Sapta wara | `i mod 7 + 1` | Redite … Saniscara |
| Panca wara | `i mod 5 + 1` | **Paing**, Pon, Wage, Kliwon, Umanis |
| Sad wara | `i mod 6 + 1` | Tungleh … Maulu |
| Tri wara | `i mod 3 + 1` | Pasah, Beteng, Kajeng |
| Asta wara | `i mod 8 + 1` untuk i < 70; **Kala** untuk i = 70–72; `(i − 2) mod 8 + 1` untuk i > 72 | Sri … Uma |
| Catur wara | sama seperti asta wara dengan mod 4; **Jaya** untuk i = 70–72 | Sri, Laba, Jaya, Menala |
| Sanga wara | **Dangu** untuk i = 0–3; `(i − 3) mod 9 + 1` untuk i > 3 | Dangu … Dadi |
| Dasa wara | `(urip panca + urip sapta) mod 10 + 1` | Pandita … Raksasa |
| Dwi wara | urip ganjil → Pepet, genap → Menga | |
| Eka wara | urip ganjil → Luang, genap → kosong | |

Urip: Paing 9, Pon 7, Wage 4, Kliwon 8, Umanis 5; Redite 5, Soma 4, Anggara 3,
Buda 7, Wraspati 8, Sukra 6, Saniscara 9.

Pengecekan titik acuan: 17-06-2026 = Buda Kliwon Dungulan (Galungan, i = 73) dan
30-08-2026 = Redite Wage Uye (i = 147).

### Pengalantaka (sasih dan fase bulan)

- 1 sasih = 30 tithi: penanggal 1–15 (15 = purnama), lalu pangelong 1–15 (15 = tilem).
- **Pengunalatri** tiap 63 hari (9 wuku): satu hari menelan dua tithi. Rata-rata
  panjang sasih = 30 × 63/64 = 29,53125 hari (bulan sinodis 29,53059), sehingga
  rumus ini hanya bergeser sekitar 1 hari per ±120 tahun.
- Anchor: 02-02-2026 = Purnama Kawolu; pengunalatri pertama 03-02-2026.
- Kalau pengunalatri menelan tilem + penanggal 1, hari itu tetap disebut **Tilem**
  (contoh: Tilem Kanem 28-12-2027).
- **Sasih nampih (Mala)** mengulang nama sasih sebelumnya, mis. Sadha → Mala Sadha → Kasa.
  Penempatannya mengikuti siklus Meton 19 tahun (7 nampih per siklus). Dengan
  k = (tahun − 2005) mod 19: k = 0, 6, 8, 14 → Mala Sadha; k = 3, 11, 17 → Mala Jiyestha.
  Contoh: Mala Sadha 2005/2024/2043, Mala Jiyestha 2008/2027/2046. Aturan ini cocok
  dengan seluruh purnama-tilem kalenderbali.org 2003–2045. Sebelum 2003 penempatan
  nampih belum baku (mis. Nampih Katiga 2001), dan `catatan` pada hasil memberi tahu hal itu.

### Libur nasional

`NationalHolidayCalculator` menghitung libur SKB 3 Menteri tanpa daftar manual:

- Tanggal tetap: Tahun Baru, Hari Buruh, Hari Lahir Pancasila, Proklamasi, Natal.
- Paskah: komputus Gregorian (Meeus/Jones/Butcher); Wafat = Paskah − 2, Kenaikan = Paskah + 39.
- Imlek: bulan baru pertama (waktu Beijing) antara 21 Januari dan 20 Februari.
- Waisak: purnama yang saatnya (UT) jatuh 6 Mei – 4 Juni, tanggal WIB.
- Hari besar Islam: hisab imkanur rukyat seperti taqwim Kemenag. Hilal dihitung saat
  Matahari terbenam di Banda Aceh pada hari konjungsi; MABIMS baru (tinggi ≥ 3°,
  elongasi ≥ 6,4°) sejak 2022, MABIMS lama (tinggi ≥ 2°, elongasi ≥ 3° atau umur ≥ 8 jam)
  sebelumnya. Bila tidak terpenuhi, bulan digenapkan (istikmal).
- Posisi Matahari dan Bulan: `App\Services\Astronomy\Ephemeris` (Meeus bab 25, 47, 49).
- Nyepi ditandai libur nasional dari perhitungan sasih.

Yang tidak bisa dihitung: cuti bersama, pergeseran libur oleh pemerintah, dan hasil
sidang isbat yang berbeda dari hisab (jarang, mis. bila hilal tepat di batas kriteria).

### Rerainan pawukon

| Rerainan | Sapta–Panca–Wuku |
| --- | --- |
| Penampahan Galungan | Anggara Wage Dungulan |
| Galungan | Buda Kliwon Dungulan |
| Umanis Galungan | Wraspati Umanis Dungulan |
| Penampahan Kuningan | Sukra Wage Kuningan |
| Kuningan | Saniscara Kliwon Kuningan |
| Saraswati | Saniscara Umanis Watugunung |
| Banyu Pinaruh | Redite Paing Sinta |
| Pagerwesi | Buda Kliwon Sinta |
| Tumpek (Landep, Wariga, Krulut, Uye, Wayang) | Saniscara Kliwon wuku terkait |
| Kajeng Kliwon / Anggara Kasih / Buda Cemeng | Kajeng+Kliwon / Anggara+Kliwon / Buda+Wage |

## Perubahan dalam audit ini

1. `PengalantakaLunarCalculator`: tabel sasih nampih 2024–2037, aturan tilem saat
   pengunalatri, memakai `PositiveModulo` bersama (duplikat dihapus).
2. `KalenderBaliLunarSyncService`: awalan "Mala" dikenali sebagai sasih nampih.
   Sebelumnya sinkronisasi gagal ("Sasih Mala Sadha tidak dikenal").
3. `CalendarObservanceService`: rerainan pawukon pindah ke satu tabel dan
   dihitung untuk semua tahun.
4. **Epoch dikunci** sebagai konstanta `CycleCalculator::EPOCH_DATE` / `EPOCH_CYCLE_INDEX`.
   Sebelumnya epoch dibaca dari env dan bisa diubah dari panel admin: salah ketik
   satu angka menggeser seluruh kalender, dan instalasi dari `.env.example` langsung
   gagal karena nilainya kosong. Field epoch dihapus dari panel admin, config, dan
   `.env.example`. Migrasi `2026_11_03_090000` membuang nilai lama di `engine_settings`.
5. **Test dan fixture memakai tanggal asli.** Sebelumnya banyak test menganggap
   29-07-2026 = indeks 0 (Redite Sinta), padahal hari itu Buda Paing Krulut.
   `storage/app/testing/wariga-cycle-210.csv` dibuat ulang mulai 05-04-2026
   (Redite Paing Sinta).
6. Test baru: dasa/sanga wara 30-08-2026, 9 rerainan resmi 2026, nampih 2027,
   tilem saat pengunalatri, parser "Mala", anchor Galungan, dan epoch yang tidak
   bisa ditimpa config.

## Risiko yang masih ada

1. Dataset lunar terverifikasi hanya berisi 25 baris (2026). Untuk tahun lain bisa
   dijalankan `php artisan wariga:sync-kalender-bali-lunar {tahun}`.
2. Nama sasih sebelum 2003 belum memperhitungkan nampih gaya lama.
3. Ingkel, Watek, Lintang, Pararasan, Panca Suda, Pratiti, dan Eka Jala Resi
   (yang ditampilkan kalenderbali.org) belum dihitung engine.
4. Libur nasional dihitung; cuti bersama dan keputusan isbat yang menyimpang dari
   hisab tetap harus dicek ke SKB tahun berjalan. Deepavali tidak lagi ditampilkan
   karena tanggal dari Bimas Hindu (07-11-2026) tidak mengikuti aturan yang bisa dihitung.

## Rujukan

- **Kalender Bali Digital** — kalenderbali.org (halaman harian, `purnamatilem.php`,
  `referensialaayu.php`, dan `pustaka.php`). Pembanding utama audit ini.
  Daftar pustakanya antara lain:
  - Ida Bagus Suparta Ardhana, *Pokok-Pokok Wariga*, Paramita, 2006.
  - Sri Reshi Ananda Kusuma, *Wariga Dewasa*, Morodadi, 1979; *Prembon Bali Agung*, 1998.
  - W. Simpen AB, *Pelajaran Dewasa (Wariga)*, Toko Buku Muria.
  - I Ketut Guweng, *Sarining Wariga*.
  - I Wayan Tusan, *Penuntun Indik Padewasan/Wariga*, 1972.
  - Kalender Bali cetak: I Ketut Bangbang Gde Rawi, I Made Bidja, I Wayan Gina,
    I Nyoman Singgin Wikarman, Made Suatjana, I Gede Marayana.
- **Lontar Wariga Gemet** — rujukan tradisional ala-ayuning dewasa. Naskahnya
  tidak dibaca langsung dalam audit ini. Aturan dewasa di aplikasi berasal dari
  impor kalenderbali.org, bukan transkripsi langsung lontar.
- E. M. Reingold & N. Dershowitz, *Calendrical Calculations*, bab "The Balinese
  Pawukon Calendar". Rumus algoritmik untuk asta/catur/sanga wara di atas sama
  dengan rumus di buku ini.
- SKB 3 Menteri No. 1497/2025, 2/2025, 5/2025 (libur nasional 2026) dan Surat
  Ditjen Bimas Hindu B-253/DJ.VI/Dt.VI.I.3/BA.03/09/2025 (hari raya Hindu 2026).
