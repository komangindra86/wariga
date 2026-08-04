# Fase 16 — API Client dan State Management

Status: selesai secara lokal pada 30 Juli 2026.

## API client

- Axios menggunakan base URL dan timeout dari environment.
- Request interceptor memeriksa koneksi dan menambahkan bearer token dari
  Keychain.
- Response interceptor menormalisasi offline, timeout, 401, 403, 404, 422,
  429, 500, dan 503/maintenance menjadi pesan aman untuk pengguna.
- Token lokal dihapus saat API mengembalikan 401.
- Endpoint read API v1 tersedia terpusat di `mobile/src/api/endpoints.ts`.

## Type dan state

- Kontrak envelope, error, pagination, dan domain Wariga tersedia di
  `mobile/src/types/`.
- Query key factory mencakup activity, kalender, pencarian/detail dewasa,
  hasil tersimpan, hasil berbagi, dan profil.
- TanStack Query memiliki kebijakan stale time, garbage collection, network,
  dan retry yang tidak mengulang error autentikasi, otorisasi, not found, atau
  validasi.
- Zustand hanya menyimpan pencarian sementara, activity, rentang tanggal,
  mode pencarian, session UI, dan filter hasil. Response API tetap dikelola
  TanStack Query.

## Verifikasi

Perintah berikut berhasil:

```shell
npm run typecheck
npm run lint
npm run format:check
npm test -- --runInBand
```

Test Fase 16 memvalidasi normalisasi status API tanpa membocorkan pesan teknis,
query key, dan reset state pencarian.
