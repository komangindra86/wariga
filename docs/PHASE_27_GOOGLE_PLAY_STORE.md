# Fase 27 — Persiapan Google Play Store

## Identitas rilis

| Item | Nilai |
| --- | --- |
| Nama aplikasi | Wariga Bali Santih |
| Application ID | `com.balisantih.wariga` |
| Version name | `1.0.0` |
| Version code | `1` |
| Kategori yang disarankan | Books & Reference |
| Target audiens | Pengguna umum, usia 13 tahun ke atas |

## Aset tersedia

- Ikon launcher dan adaptive icon Android: `mobile/android/app/src/main/res/`.
- Ikon notifikasi monokrom: `drawable/ic_notification.xml`.
- Splash screen native: `drawable/launch_screen.xml`.
- Ikon listing Play Store 512 × 512: `mobile/store-assets/play-store-icon-512.png`,
  menggunakan interpretasi mandala modern Dewata Nawa Sanga.
- Feature graphic 1024 × 500: `mobile/store-assets/feature-graphic-1024x500.png`.
- Empat screenshot ponsel ber-frame dan siap untuk listing:
  `mobile/store-assets/playstore-01-hari-ini.png` sampai
  `mobile/store-assets/playstore-04-transparan.png`. Masing-masing berasal dari
  layar asli build debug yang berjalan pada emulator Android.

Screenshot tablet belum disediakan karena rilis 1.0.0 tidak dipasarkan sebagai
pengalaman tablet tersendiri. Jika nantinya tablet ditargetkan, ambil screenshot
aktual dari build release pada ukuran tablet sebelum mengirim listing.

## Dokumen dan URL

Ganti `https://<domain-produksi>` dengan domain HTTPS produksi yang sebenarnya
sebelum mengirim aplikasi untuk ditinjau.

| Keperluan Play Console | URL / jawaban awal |
| --- | --- |
| Privacy policy | `https://<domain-produksi>/privacy-policy` |
| Terms of service | `https://<domain-produksi>/terms` |
| Account deletion | `https://<domain-produksi>/account-deletion` |
| App access | Tidak ada akses khusus; reviewer dapat menggunakan fitur kalender dan pencarian tanpa akun. Untuk alur akun, daftar dengan email sendiri. |
| Iklan | Tidak memuat iklan. |

## Data safety — draf deklarasi

Jawaban akhir di Play Console harus dicocokkan lagi dengan build yang dikirim.

| Data | Dikumpulkan | Dibagikan | Tujuan | Dapat dihapus |
| --- | --- | --- | --- | --- |
| Nama dan email | Ya, saat pendaftaran | Tidak | Pengelolaan akun | Ya |
| Kredensial akun | Ya, dalam bentuk hash | Tidak | Autentikasi | Ya |
| Hasil tersimpan dan preferensi | Ya, jika pengguna memakai fitur akun | Tidak | Fungsionalitas aplikasi | Ya |
| Umpan balik sukarela | Ya, bila dikirim pengguna | Tidak | Analitik produk dan peninjauan aturan | Ya |

Data dikirim menggunakan koneksi terenkripsi. Aplikasi tidak menjual data,
memakai SDK iklan, atau meminta kontak, lokasi, kamera, mikrofon, maupun
penyimpanan perangkat.

## Content rating dan target audience

- Isi: informasi kalender, tradisi Wariga, dan rekomendasi kegiatan; tanpa
  kekerasan, seksualitas, perjudian, atau konten menakutkan.
- Target audience: 13+; bukan aplikasi khusus anak-anak.
- Content rating questionnaire yang disarankan: jawab “Tidak” untuk kategori
  konten terlarang/tidak relevan, lalu telaah jawaban Play Console saat submit.

## Disclaimer wajib

> Wariga Bali Santih menyediakan informasi berdasarkan data dan aturan Wariga
> yang tersedia dalam sistem. Hasil tidak dimaksudkan sebagai kepastian mutlak
> atau pengganti konsultasi dengan sulinggih, pemangku, maupun ahli Wariga.

Disclaimer ini harus ditampilkan pada listing dan tetap tersedia di aplikasi.

## Checklist sebelum submit

- [x] Identitas package dan versi 1.0.0/1 dikonfigurasi.
- [x] Permission manifest hanya meminta `INTERNET` dan `POST_NOTIFICATIONS`.
- [x] Aset Android dan aset listing disiapkan.
- [x] Privacy policy, terms, dan halaman penghapusan akun tersedia.
- [x] Draf data safety, content rating, app access, target audience, dan iklan tersedia.
- [ ] Ganti URL placeholder dengan domain HTTPS produksi yang dapat diakses publik.
- [ ] Ambil screenshot dari build release pada perangkat nyata/emulator sebagai validasi akhir.
- [ ] Buat upload key/keystore release dan aktifkan Play App Signing; jangan commit keystore.
- [ ] Buat Android App Bundle pada Fase 28 dan uji di internal testing track.
