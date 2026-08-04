# Fase 15 — Design System dan Navigasi Mobile

Status: selesai secara lokal pada 30 Juli 2026.

## Hasil

- Design token terpusat untuk warna, tipografi, spacing, radius, shadow,
  ukuran ikon, ukuran layar, dan z-index di `mobile/src/theme/`.
- Palet hangat bernuansa Bali dengan merah bata dan emas sebagai aksen.
- Seluruh warna screen dan navigasi menggunakan token, bukan kode warna lokal.
- Konten memiliki lebar maksimum 720 px agar tetap nyaman pada ponsel dan
  tablet.
- Area sentuh minimum komponen interaktif adalah 44 px.

Komponen reusable di `mobile/src/components/`:

- `AppText`, `AppButton`, `AppInput`, `AppCard`, dan `AppBadge`;
- `AppHeader`, `AppDivider`, `AppLoader`, dan `AppSkeleton`;
- `AppErrorState`, `AppEmptyState`, `AppAccordion`, dan `AppBottomSheet`;
- `ScreenContainer` dan `OfflineBanner`.

## Navigasi

Bottom tab menyediakan:

- Beranda;
- Kalender;
- Tersimpan;
- Tentang.

Root stack menyediakan:

- ActivityList, DateRange, SearchResult, dan DewasaDetail;
- CalendarDetail;
- Login, Register, dan Profile;
- SavedDetail;
- PrivacyPolicy dan Disclaimer.

Screen fitur yang menjadi lingkup fase berikutnya masih berupa placeholder
terarah. Fase 15 hanya menyiapkan hierarki navigasi dan komponen visual; belum
menghubungkan data API.

## Verifikasi

Perintah berikut berhasil:

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
npm run build:android:debug
```

Test smoke memastikan aplikasi dapat dirender serta memvalidasi token warna,
spacing, dan target sentuh minimum.

APK debug tersedia di `mobile/android/app/build/outputs/apk/debug/app-debug.apk`.
