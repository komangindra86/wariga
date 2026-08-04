# Fase 22 — Login, Register, dan Akun Pengguna

## Hasil

- Login dan registrasi terhubung ke Laravel Sanctum.
- Lupa kata sandi dan reset kata sandi tersedia.
- Token disimpan melalui Android Keychain dan tidak pernah dicatat ke log.
- Sesi dipulihkan saat aplikasi dibuka dengan memvalidasi token ke endpoint user.
- Respons `401` menghapus token serta state pengguna.
- Profil menampilkan identitas akun dan menyediakan logout serta hapus akun permanen.
- Guest tetap dapat membuka seluruh alur kalender dan pencarian.

## Navigasi

Menu **Tentang → Akun Saya** membuka profil/guest account gateway. Halaman
**Tersimpan** tetap menawarkan login kepada guest; isi hasil tersimpan dikerjakan
pada Fase 23.

## Verifikasi

```bash
cd mobile
npm run typecheck
npm run lint -- --max-warnings=0
npm test -- --runInBand
```

Reset kata sandi menerima token dan email dari tautan reset atau melalui form
manual. Konfigurasi deep link universal/app-link dapat ditambahkan bersama
konfigurasi distribusi Android.
