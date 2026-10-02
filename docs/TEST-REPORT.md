# Laporan verifikasi

Tanggal pengujian: 2 Oktober 2026. Aplikasi Laravel 13.34.0, PHP 8.3, dan MariaDB 11.4.10.

## Hasil

- PHPUnit: 20 test lulus, 304 assertion. Mencakup tiap kelompok CRUD, akses tamu/admin, hash password, login/logout, CSRF, penyimpanan upload privat, pembatasan ukuran dan format, isolasi jenis/induk konten, visibilitas draft, escaping Markdown, jadwal publikasi, dan penyiapan akun.
- Uji HTTP memakai PHP cURL: 281 assertion lulus atas 37 URL publik, 13 jenis CRUD (buat, baca, edit, hapus), IDOR, CSRF, login/otorisasi, upload, serta perlindungan media draft.
- Pemeriksaan browser manual: navigasi dan beranda desktop; viewport ponsel 390 px; menu ponsel dan overflow horizontal.
- Build Vite produksi lokal dan Composer produksi tanpa paket development berhasil; paket berisi 7.454 berkas tanpa `.env` atau akun admin.
- Import skema MySQL/MariaDB lokal dan seeding berhasil. SQL yang diekspor berhasil diimpor ke database baru menggunakan klien MariaDB; berisi 53 konten awal pada 13 jenis dan 0 akun admin.
- Validasi ZIP: ekstraksi ke direktori terpisah, PSR-4 autoload setelah relokasi, path aplikasi untuk subdomain, serta ketiadaan symlink storage lulus.
- Smoke test HTTP pada hasil ekstraksi: 19 halaman/aset lulus; pembuatan admin pertama, login, dashboard, dan penguncian ulang setup lulus.

Server cPanel penyedia hosting belum tersedia dalam lingkungan pengujian; sesudah upload, pemilik perlu memeriksa versi PHP/ekstensi, rewrite Apache, document root, permission, koneksi MySQL, dan sertifikat HTTPS sesuai `CPANEL.md`.
