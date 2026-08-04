# Wariga Bali Santih Mobile

Fondasi aplikasi Android menggunakan React Native CLI dan TypeScript strict.
Fase ini belum memuat fitur Wariga.

## Environment

Konfigurasi tersedia pada:

-   `.env.development`
-   `.env.staging`
-   `.env.production`

Nilai staging dan production masih berupa placeholder dan wajib diganti sebelum
dipakai. Jangan menyimpan secret di file environment karena nilai React Native
akan tertanam di aplikasi.

## Menjalankan

Pastikan Android SDK, Java 17, emulator/perangkat, dan Metro tersedia.

```shell
npm install
npm start
npm run android:development
```

Pilihan environment lain:

```shell
npm run android:staging
npm run android:production
```

## Validasi

```shell
npm run typecheck
npm run lint
npm run format:check
npm run test:ci
npm run build:android:debug
```

APK debug dihasilkan pada
`android/app/build/outputs/apk/debug/app-debug.apk`.

## Release Android

Release AAB membutuhkan API HTTPS produksi dan upload key lokal. Ikuti
[`../docs/PHASE_28_ANDROID_RELEASE.md`](../docs/PHASE_28_ANDROID_RELEASE.md)
untuk setup signing, backup key, validasi, dan lokasi output AAB.
