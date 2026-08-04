# Fase 13 — Audit Produksi Backend

Tanggal audit: 29 Juli 2026. Cakupan: source backend Laravel; deployment tidak
dilakukan.

## Keamanan

Status setelah hardening:

- Query aplikasi memakai Query Builder/Eloquent dan binding parameter. Nama tabel
  dinamis pada panel admin berasal dari allow-list, bukan input bebas.
- Blade memakai escaping `{{ }}`. Tidak ditemukan output pengguna dengan `{!! !!}`.
- Route web memakai middleware CSRF Laravel. API bearer-token tidak memakai
  autentikasi cookie.
- Panel admin dilindungi autentikasi dan allow-list role. Otorisasi kepemilikan
  diterapkan pada hasil tersimpan; rule tervalidasi memiliki pembatas workflow.
- Login, registrasi, forgot password, dan reset password memiliki limiter khusus.
  API umum tetap memiliki limiter yang dapat dikonfigurasi.
- Token Sanctum disimpan sebagai hash oleh Sanctum, diberi prefix deteksi secret,
  dan kedaluwarsa default tujuh hari. Token dicabut saat logout/hapus akun.
- Model memakai allow-list `$fillable`; password di-cast `hashed`.
- Impor hanya dapat dilakukan `admin_data`/`super_admin`, dibatasi 10 MiB, diperiksa
  sebagai file CSV/TXT, header harus persis, dan setiap nilai dinormalisasi serta
  divalidasi sebelum transaksi.
- Respons diberi `nosniff`, anti-framing, Referrer-Policy, Permissions-Policy, dan
  HSTS pada koneksi HTTPS.
- Exception production tidak menampilkan detail jika `APP_DEBUG=false`. Health
  check tidak mengembalikan exception/database credential.
- Cookie production wajib encrypted, Secure, HttpOnly, dan SameSite. Nilai lokal
  di `.env.example` sengaja tetap kompatibel dengan HTTP lokal.
- Email adalah data pribadi. Akses admin, audit, backup terenkripsi, retensi, serta
  hak hapus akun harus masuk SOP operasional.

Risiko residual/operasional:

- `APP_KEY`, database password, mail credential, dan admin password harus berasal
  dari secret manager dan tidak boleh disimpan di repository.
- CSP ketat belum dipasang karena panel masih memuat Tailwind dari CDN. Untuk CSP
  tanpa `unsafe-inline`, bundle aset tersebut secara lokal terlebih dahulu.
- WAF, TLS, backup encryption, restore drill, malware scanning upload, dan akses
  infrastruktur berada di luar source aplikasi dan wajib diverifikasi di staging.

## Performa

- Lookup kalender/rule sudah mempunyai indeks. Migrasi Fase 13 menambah indeks
  untuk daftar simpanan per pengguna, audit terbaru, dan rule efektif.
- Daftar admin/mobile memakai pagination; halaman validator dibatasi 100 record.
- Pencarian maksimum 366 hari diproses per chunk dan hasilnya di-cache berdasarkan
  versi engine/rule serta parameter pencarian.
- API memakai Resource/envelope dan field terpilih untuk relasi aktivitas.
- Query relasi pada daftar memakai eager loading; query master memakai Query
  Builder.
- Scheduler membersihkan token kedaluwarsa serta record queue gagal/batch lama.
- Compression harus diaktifkan di reverse proxy (Brotli/gzip), bukan middleware
  PHP, agar file statis dan respons dinamis ditangani efisien.

Validasi beban masih perlu dilakukan di staging dengan volume data produksi.
Target awal yang disarankan: p95 API baca < 300 ms dan error rate < 1%.

## Keputusan audit

Source telah diperkeras untuk kandidat staging. Status **belum siap produksi**
sampai checklist deployment, backup/restore drill, validasi ahli Wariga, dan load
test selesai.
