# Fase 14 — Inisialisasi React Native

Status: selesai secara lokal pada 29 Juli 2026.

## Hasil

- Project React Native CLI 0.86.2 terpisah di `mobile/`.
- TypeScript strict tanpa penggunaan `any` pada source aplikasi.
- Target utama Android dengan application ID `com.balisantih.wariga`.
- Nama tampilan aplikasi `Wariga Bali Santih`.
- Environment `development`, `staging`, dan `production` dengan
  `API_BASE_URL`, `API_TIMEOUT`, serta `APP_ENV`.
- Alias import `@/*` menuju `src/*`.
- ESLint, Prettier, Jest, dan typecheck dikonfigurasi.
- Struktur folder fase 14 tersedia di bawah `mobile/src/`.

Dependency fondasi yang terpasang:

- React Navigation;
- Axios;
- TanStack Query;
- Zustand;
- React Hook Form;
- Zod;
- AsyncStorage;
- date-fns;
- NetInfo;
- react-native-keychain;
- react-native-config.

Navigation shell, provider query, klien HTTP, hook konektivitas, dan service
secure token storage hanya merupakan fondasi teknis. Fitur Wariga belum dibuat.

## Verifikasi

Perintah berikut berhasil:

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
npm run build:android:debug
```

Hasil Android debug:

```text
mobile/android/app/build/outputs/apk/debug/app-debug.apk
```

Build lokal memakai Java 17 dan Android SDK pada `android/local.properties`.
File tersebut diabaikan Git dan setiap developer perlu memakai lokasi SDK
masing-masing.

## Catatan konfigurasi

URL staging dan production masih placeholder. Ganti sebelum distribusi build.
File environment bukan tempat menyimpan secret karena nilainya akan ikut
tertanam di binary aplikasi.
