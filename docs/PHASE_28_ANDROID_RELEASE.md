# Fase 28 — Build Release Android App Bundle

## Status

Konfigurasi release Android telah disiapkan agar build tidak pernah memakai
debug key. Build release hanya berjalan bila API produksi adalah HTTPS yang
bukan placeholder dan `android/keystore.properties` lokal lengkap.

Pada saat dokumen ini dibuat, kedua nilai produksi tersebut belum tersedia di
workspace. Karena itu AAB produksi belum dibuat; tidak aman membuat atau
mengirim bundle yang ditandatangani debug key.

## Menyiapkan upload key

Simpan keystore di lokasi terenkripsi di luar repository, lalu buat salinan
backup terenkripsi kedua. Catat alias dan password pada password manager tim;
jangan menyimpannya di Git, tiket publik, atau file `.env` React Native.

Contoh pembuatan upload key (jalankan sekali, dengan password yang kuat):

```powershell
keytool -genkeypair -v -keystore C:\secure\wariga-upload-keystore.jks -alias wariga-upload -keyalg RSA -keysize 4096 -validity 10000
```

Salin `mobile/android/keystore.properties.example` menjadi
`mobile/android/keystore.properties`, lalu isi path ke keystore dan kredensial
lokal. Berkas itu dan `*.keystore` diabaikan Git.

Simpan SHA-256 sertifikat upload dan cadangan terenkripsi di password manager.
Untuk mengambil fingerprint:

```powershell
keytool -list -v -keystore C:\secure\wariga-upload-keystore.jks -alias wariga-upload
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
