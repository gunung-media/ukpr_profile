# Instalasi UKPR melalui cPanel

## 1. Prasyarat

PHP 8.3–8.5; MySQL 8 atau MariaDB 10.6+; Apache/LiteSpeed dengan rewrite. Aktifkan bcmath, ctype, curl, dom, fileinfo, filter, GD dengan WebP, hash, mbstring, openssl, PDO/pdo_mysql, session, tokenizer, XML. ZIP diperlukan jika membangun ulang paket dengan PHP lokal. Gunakan HTTPS/AutoSSL sebelum mengaktifkan admin. Rekomendasi memory_limit 256M, upload_max_filesize 8M, post_max_size 12M, max_execution_time 60.

Vendor produksi dan aset build disertakan. Tidak perlu npm, Node, Composer, worker queue, cron, atau symlink storage di hosting.

## 2. Folder dan subdomain

Contoh struktur akun:

```
/home/ACCOUNT/
  ukpr_profile_app/           aplikasi privat, vendor, storage, .env
  public_html/
    ukpr/                    document root subdomain
      index.php
      app-path.php
      .htaccess
      build/
      favicon.svg
```

Di cPanel → Domains buat subdomain, misalnya profil.domainanda.id, dengan document root `public_html/ukpr`. Aktifkan SSL. Jangan arahkan document root ke folder aplikasi.

Upload ZIP melalui File Manager ke `/home/ACCOUNT`, kemudian Extract di sana. Aktifkan Show Hidden Files untuk melihat .htaccess dan .env.example. Jangan menimpa folder milik situs lain. Folder `installation/` dan `MULAI-DI-SINI.txt` berisi panduan/SQL; tetap berada di luar public_html atau hapus setelah instalasi.

Nama folder aplikasi/publik bisa diubah. Jika folder aplikasi diubah, edit `public_html/ukpr/app-path.php`. Untuk struktur standar, isi:

```php
<?php
return dirname(__DIR__, 2).'/NAMA_FOLDER_APLIKASI_UNIK';
```

Jika cPanel menetapkan document root pada kedalaman berbeda, gunakan path absolut: `return '/home/ACCOUNT/NAMA_FOLDER_APLIKASI_UNIK';`. Domain tidak ditempel di PHP. Setiap subdomain harus memakai folder aplikasi, document root, database, APP_KEY, dan SETUP_TOKEN berbeda.

Membangun ulang ZIP di komputer lokal:

```
php scripts/prepare-hosting-stage.php
composer install --working-dir=".runtime/hosting-build-XXXX/ukpr_profile_app" --no-dev --no-scripts --no-interaction --prefer-dist
php scripts/make-hosting-package.php --app-folder=ukpr_profil_baru --public-subdir=profil_baru
```

Script persiapan memvalidasi file dan build lalu mencetak path staging. Jalankan Composer lokal dari path tersebut, lalu hasilkan ZIP. Composer sengaja memakai PSR-4 portable tanpa classmap dengan path absolut sehingga autoloader tetap berfungsi setelah upload/pemindahan. Script paket memvalidasi nama folder, menggunakan staging unik, dan tidak menghapus deployment/subdomain lain. Variabel kedua adalah folder document root, bukan nama domain lengkap. Ganti XXXX dengan ID acak yang dicetak script persiapan.

## 3. Database melalui phpMyAdmin

Di MySQL Database Wizard buat database BARU dan KOSONG serta pengguna khusus. Berikan ALL PRIVILEGES pada database tersebut. Catat nama lengkap dengan prefix akun cPanel. Buka phpMyAdmin → pilih database → Import → pilih `installation/ukpr.sql` dari ZIP → Go.

SQL berisi schema, tabel migrations, dan konten awal. Tidak ada akun admin, password default, data uji, sessions, atau kredensial. Tidak perlu menjalankan migrasi/seeder setelah impor. Jangan impor ke database berisi aplikasi lain.

## 4. Konfigurasi .env

Di folder aplikasi, salin `.env.example` menjadi `.env`. Isi:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://profil.domainanda.id
APP_KEY=base64:NILAI_ACAK_32_BYTE_DARI_SCRIPT_LOKAL
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=PREFIX_database
DB_USERNAME=PREFIX_user
DB_PASSWORD="PASSWORD_DATABASE_ANDA"
SESSION_SECURE_COOKIE=true
SETUP_TOKEN=TOKEN_ACAK_DARI_SCRIPT_LOKAL
```

APP_KEY dan token harus dibuat untuk instalasi Anda, bukan memakai string contoh. Di komputer lokal yang memiliki PHP, jalankan `php scripts/installation-values.php` (atau file yang sama di `installation/`). Salin kedua nilai hasilnya ke .env melalui File Manager. Script ini hanya menghasilkan nilai acak; tidak membutuhkan database/Composer. Jangan upload hasil nilai rahasia ke public_html. Jangan gunakan APP_KEY lokal. Password dengan spasi/# dibungkus tanda kutip dan karakter khusus harus sesuai sintaks dotenv.

Gunakan host/port database dari penyedia hosting jika berbeda. Pertahankan session dan cache berbasis file serta queue sync. APP_URL harus berisi URL HTTPS final tanpa slash penutup. Jangan mengaktifkan APP_DEBUG di produksi.

## 5. Permission

Folder umum 755 dan file 644. `.env` idealnya 600 atau 640 sesuai pemilik PHP hosting. `storage/`, seluruh subfoldernya, dan `bootstrap/cache/` harus writable oleh PHP: biasanya 755, atau 775 jika hosting memakai grup berbeda. Jangan memakai 777. Jika PHP tidak dapat membaca .env atau menulis storage, sesuaikan owner/permission bersama penyedia hosting.

## 6. Admin pertama tanpa terminal hosting

Buka `https://profil.domainanda.id/setup`. Masukkan SETUP_TOKEN yang Anda buat, nama, email, dan password pilihan Anda (minimal 12 karakter, huruf besar/kecil, angka, simbol). Setelah berhasil, login melalui `/login` atau `/admin`.

Hapus nilai SETUP_TOKEN dari .env sesudah admin dibuat. Halaman setup otomatis tidak tersedia ketika admin sudah ada. Token salah ditolak; aktivasi dibatasi dan dilindungi CSRF. Tidak ada username/password bawaan produksi.

Jika terminal tersedia, alternatif dari folder aplikasi: `php artisan ukpr:admin email@domainanda.id --name="Admin UKPR"`; password diminta interaktif. Untuk reset akun gunakan proses pemilik melalui terminal atau phpMyAdmin dengan hash yang dibuat di PHP; jangan simpan password polos.

## 7. Pemeriksaan setelah upload

1. Buka beranda, profil, fakultas/prodi, PMB, berita, agenda, pengumuman, galeri, fasilitas, kontak.
2. Login dan coba tambah/edit/hapus konten contoh, gambar, album/foto, menu, banner, serta pengaturan.
3. Upload JPG/PNG/WebP ≤5 MB; gambar diproses ulang, nama acak, maksimal 8 juta piksel. Upload disimpan di `storage/app/private/media`, disajikan melalui `/media/ID`; tidak perlu `storage:link`.
4. Coba URL admin tanpa login: harus diarahkan login. Media draft harus 404 bagi pengunjung.
5. Buka `/sitemap.xml` dan `/robots.txt`; pastikan URL domain final dan aset `/build/assets/…` berhasil dimuat.
6. Lengkapi placeholder dan ganti dokumentasi/foto sesuai informasi kampus. Keterangan PMB mengikuti sumber 2026/2027; pengelola memperbarui periode selanjutnya.

Editor menerima Markdown; HTML mentah dibuang. Slug berita/prodi menghasilkan URL publik. Foto wajib memilih album, prodi wajib memilih fakultas. Menu maksimal dua tingkat; hapus/pindahkan anak sebelum menghapus induk. Konten dapat dibuat draft atau dijadwalkan pada tanggal publikasi WIB. Pengaturan memakai kunci yang sudah disediakan; jangan mengubah slug kecuali menambah kunci yang diperlukan template.

## 8. Penanganan masalah

- 503 path: periksa app-path.php dan letak vendor/autoload.php.
- 500: baca `storage/logs/laravel.log` melalui File Manager; periksa PHP/ekstensi, APP_KEY, database, dan permission. Jangan menampilkan log di web publik.
- Route 404 selain beranda: pastikan .htaccess ikut ekstrak dan mod_rewrite aktif.
- Aset tidak ditemukan: document root harus folder publik yang benar, dengan build/manifest.json dan build/assets.
- Login kembali ke form: HTTPS dan SESSION_SECURE_COOKIE=true harus sesuai; periksa permission storage/framework/sessions. Hapus cookie domain lama jika URL berubah.
- Upload gagal: periksa GD WebP, fileinfo, ukuran request, memory limit, dan permission media. Maksimal 5 MB serta 8 juta piksel.
- Perubahan .env tidak terbaca setelah hosting melakukan cache: dengan terminal jalankan `php artisan optimize:clear`; tanpa terminal, hapus file cache konfigurasi/routes yang dibuat di bootstrap/cache melalui File Manager. Paket awal tidak berisi cache konfigurasi lokal.

Backup database melalui phpMyAdmin dan folder aplikasi/storage sebelum perubahan besar. Simpan ZIP/SQL di luar area publik. Pengujian aktual pada akun hosting tetap diperlukan karena konfigurasi penyedia belum tersedia dalam sesi ini.
