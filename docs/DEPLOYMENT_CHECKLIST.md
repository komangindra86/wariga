# Checklist Deployment Backend

## Sebelum rilis

- [ ] Validasi fixture Wariga terhadap sumber ahli; jangan promosikan fixture
  `belum_divalidasi_ahli`.
- [ ] Gunakan PHP 8.2+, MySQL 8, extension wajib Composer, dan Node 20+.
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, serta
  `APP_FORCE_HTTPS=true`.
- [ ] Generate `APP_KEY` sekali; simpan seluruh secret di secret manager.
- [ ] Set database user least-privilege dan kredensial mail production.
- [ ] Set `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`,
  `SESSION_HTTP_ONLY=true`, dan `SESSION_SAME_SITE=lax`.
- [ ] Gunakan Redis untuk `CACHE_STORE` dan queue bila tersedia; beri prefix unik.
- [ ] Set `SANCTUM_EXPIRATION`, `SANCTUM_TOKEN_PREFIX`, rate limit, CORS/stateful
  domain sesuai origin resmi.
- [ ] Pastikan `ADMIN_PASSWORD` tidak berada di environment runtime setelah safe
  seeder pertama selesai.
- [ ] Jalankan test, Pint, audit dependency Composer/NPM, dan build aset.
- [ ] Ambil backup database terenkripsi dan uji restore pada lingkungan terisolasi.

## Rilis

Contoh urutan (sesuaikan release manager):

```sh
php artisan down --render="errors::503"
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci
npm run build
php artisan queue:restart
php artisan up
```

- [ ] Jangan jalankan `db:seed` global di production. Jalankan hanya seeder yang
  telah direview dan idempotent, dengan nama kelas eksplisit.
- [ ] Jalankan `php artisan schedule:run` setiap menit melalui cron/Task Scheduler.
- [ ] Jalankan `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` melalui
  Supervisor/systemd dan restart worker setelah rilis.
- [ ] Aktifkan HTTPS/TLS modern, redirect HTTP, HSTS, Brotli/gzip, dan proxy header
  yang benar.
- [ ] Arahkan document root ke `public/`; larang akses `.env`, `storage`, dan source.
- [ ] Rotasi log Laravel/web server; batasi akses dan jangan log token/password.

## Verifikasi setelah rilis

- [ ] `GET /health` mengembalikan 200 tanpa detail sensitif.
- [ ] Smoke test login admin, API publik, autentikasi mobile, simpan, dan share.
- [ ] Pastikan scheduler serta queue worker memproses job.
- [ ] Periksa error log, p95 latency, koneksi DB, disk, cache hit, dan queue depth.
- [ ] Verifikasi backup berikutnya berhasil dan alerting aktif.

## Rollback

- [ ] Pertahankan artefak release dan backup sebelum migrasi.
- [ ] Jika perubahan hanya kode, arahkan symlink/artefak ke release sebelumnya,
  clear/cache ulang konfigurasi, lalu `queue:restart`.
- [ ] Jalankan `migrate:rollback --step=1 --force` hanya bila migration `down()`
  sudah diuji dan tidak membuang data yang diperlukan.
- [ ] Untuk migration destruktif, lakukan roll-forward atau restore backup sesuai
  runbook; jangan mengandalkan rollback otomatis.
- [ ] Dokumentasikan waktu, penyebab, dampak, dan keputusan rollback.
