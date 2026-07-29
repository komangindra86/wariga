# Wariga Bali Santih

Backend Laravel dan fondasi panel admin untuk layanan Kalender Bali serta
pencarian dewasa ayu.

Fase 3 menyediakan mesin dasar konversi tanggal Masehi menjadi indeks siklus
Kalender Bali 0–209. Tanggal acuan wajib dikonfigurasi melalui environment dan
tidak disediakan secara default agar aplikasi tidak mengarang rujukan Wariga.
Fase ini belum menghitung Wewaran, Wuku, Sasih, Penanggal, atau Pangelong.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- MySQL 8
- Node.js 20 atau lebih baru

## Menjalankan secara lokal

1. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi MySQL.
2. Buat database bernama `wariga_bali_santih`.
3. Isi `WARIGA_REFERENCE_DATE` dan `WARIGA_REFERENCE_CYCLE_INDEX` menggunakan
   tanggal acuan yang sudah diverifikasi.
4. Jalankan `composer install`.
5. Jalankan `php artisan key:generate`.
6. Jalankan `php artisan migrate`.
7. Jalankan `php artisan db:seed`.
8. Jalankan `npm install`.
9. Jalankan `composer run dev`, atau jalankan `php artisan serve` dan `npm run dev`
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
