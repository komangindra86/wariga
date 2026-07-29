# Wariga Bali Santih

Backend Laravel dan fondasi panel admin untuk layanan Kalender Bali serta
pencarian dewasa ayu.

Fase 2 menyediakan 13 tabel master Kalender Bali, repository berbasis Query
Builder, serta data awal Sapta Wara, Panca Wara, dan 30 Wuku. Seluruh data awal
masih berstatus `draft` sampai diverifikasi menggunakan sumber Wariga yang dapat
dipertanggungjawabkan. Fase ini belum berisi rumus perhitungan Wariga.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- MySQL 8
- Node.js 20 atau lebih baru

## Menjalankan secara lokal

1. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi MySQL.
2. Buat database bernama `wariga_bali_santih`.
3. Jalankan `composer install`.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate`.
6. Jalankan `php artisan db:seed`.
7. Jalankan `npm install`.
8. Jalankan `composer run dev`, atau jalankan `php artisan serve` dan `npm run dev`
   pada dua terminal.

Jika Laragon masih memakai PHP lama, pilih PHP 8.2+ melalui menu Laragon atau
jalankan perintah dengan executable PHP 8.2 secara eksplisit.

## Pemeriksaan

```shell
php artisan test
./vendor/bin/pint --test
npm run build
```

Endpoint pemeriksaan layanan tersedia di `GET /health`.
