# Fase 28 — Build Release Android App Bundle

## Status

Konfigurasi release Android telah disiapkan agar build tidak pernah memakai
debug key. Build release hanya berjalan bila API produksi adalah HTTPS yang
bukan placeholder dan `android/keystore.properties` lokal lengkap.

API produksi menggunakan `https://wariga.balisantih.com/api/v1`. Upload key
tetap merupakan kredensial lokal dan tidak pernah disimpan di repository.

## Menyiapkan upload key

Simpan keystore di lokasi terenkripsi di luar repository, lalu buat salinan
backup terenkripsi kedua. Catat alias dan password pada password manager tim;
jangan menyimpannya di Git, tiket publik, atau file `.env` React Native.

Untuk rilis pertama, generator lokal dapat membuat upload key dan password acak
yang kuat. Jalankan sekali dari root repository:

```powershell
powershell -ExecutionPolicy Bypass -File mobile\scripts\generate-upload-keystore.ps1
```

Generator menolak menimpa berkas yang sudah ada. Keystore,
`keystore.properties`, dan catatan backup sementara semuanya diabaikan Git.
Segera pindahkan keystore beserta isi `upload-key-backup.txt` ke password manager
atau penyimpanan terenkripsi, buat backup terenkripsi kedua, kemudian hapus
catatan plaintext tersebut setelah backup diverifikasi.

Simpan SHA-256 sertifikat upload dan cadangan terenkripsi di password manager.
Untuk mengambil fingerprint:

```powershell
keytool -list -v -keystore mobile\android\upload-keystore.keystore -alias wariga-upload
```

Aktifkan Play App Signing saat unggahan pertama. Upload key dapat diganti
melalui Play Console, tetapi kehilangan keystore tanpa backup akan menghambat
rilis sampai proses reset key selesai.

## Konfigurasi produksi

Ganti `API_BASE_URL` di `mobile/.env.production` dengan endpoint API HTTPS
publik yang nyata, misalnya `https://api.example-domain.tld/api/v1`. Jangan
masukkan secret: nilai environment React Native ikut dibundel ke aplikasi.

## Validasi dan build

Jalankan dari folder `mobile`:

```powershell
npm run clean:android
npm run lint
npm run lint:android:release
npm run typecheck
npm test -- --runInBand
npm run test:android:release
npm run bundle:android:release
```

Bundle yang berhasil berada di
`mobile/android/app/build/outputs/bundle/release/app-release.aab`.

## Pemeriksaan sebelum internal testing

- Verifikasi application ID `com.balisantih.wariga`, version code `1`, dan
  version name `1.0.0`.
- Pastikan hanya permission `INTERNET` dan `POST_NOTIFICATIONS` termuat.
- Instal APK/bundle hasil release dan uji startup, login, pencarian, detail,
  share, serta penghapusan akun.
- Pastikan icon dan splash tampil benar, tidak ada HTTP cleartext, dan catat
  ukuran AAB.
- Jangan unggah ke Play Console tanpa instruksi pemilik rilis.
