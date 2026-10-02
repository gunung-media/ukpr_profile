# Panduan Upload Website UKPR ke cPanel

Oct 2, 2026 · @Meycelino WORK

Website UKPR dipasang dengan mengunggah satu file ZIP, mengimpor satu file SQL, lalu mengisi file `.env`. Hosting tidak perlu Composer, Node, atau terminal: semua pustaka sudah ada di dalam ZIP.

## Persiapan

File yang dipakai adalah `dist/UKPR-cPanel-ukpr.zip` (8,45 MB). Isinya:

| Folder / file di ZIP | Fungsi | Diletakkan di |
| --- | --- | --- |
| `ukpr_profile_app/` | Aplikasi, pustaka, penyimpanan gambar, `.env` | Di luar `public_html` (privat) |
| `public_html/ukpr/` | File publik: `index.php`, `.htaccess`, `build/`, `fonts/` | Document root website |
| `installation/` | `ukpr.sql`, panduan, pembuat kunci rahasia | Hapus setelah instalasi |
| `MULAI-DI-SINI.txt` | Ringkasan langkah | Hapus setelah instalasi |

Kebutuhan hosting:

- PHP 8.3 sampai 8.5, dengan ekstensi bcmath, ctype, curl, dom, fileinfo, filter, GD (dengan WebP), mbstring, openssl, pdo\_mysql, tokenizer, xml.
- MySQL 8 atau MariaDB 10.6 ke atas.
- Apache atau LiteSpeed dengan mod\_rewrite aktif.
- Disarankan: memory\_limit 256M, upload\_max\_filesize 8M, post\_max\_size 12M.

Siapkan juga sebelum mulai:

- [ ] Login cPanel akun hosting
- [ ] Nama subdomain atau domain, misalnya `profil.ukpr.ac.id`
- [ ] Komputer dengan PHP untuk membuat APP\_KEY dan SETUP\_TOKEN (langkah 4)

## Langkah 1 — Buat subdomain dan atur document root

Document root harus mengarah tepat ke `public_html/ukpr`. Tanpa ini, font dan halaman tidak termuat dengan benar.

1. Di cPanel buka **Domains** (atau **Subdomains** di cPanel lama), klik **Create A New Domain**.
2. Isi domain, misalnya `profil.ukpr.ac.id`.
3. Hilangkan centang "Share document root", lalu isi **Document Root** dengan `public_html/ukpr`. Klik **Submit**.
4. Buka **SSL/TLS Status**, lalu jalankan **Run AutoSSL** agar domain memakai HTTPS. Login admin hanya berjalan lewat HTTPS.

Struktur akhir di akun hosting:

```
/home/ACCOUNT/
  ukpr_profile_app/     aplikasi (privat), berisi .env
  public_html/
    ukpr/               document root domain
      index.php  app-path.php  .htaccess
      build/  fonts/  favicon.svg
```

Jangan arahkan document root ke folder `ukpr_profile_app`.

## Langkah 2 — Upload dan ekstrak ZIP

ZIP diekstrak di folder home akun (`/home/ACCOUNT`), bukan di dalam `public_html`.

1. Buka **File Manager**. Klik **Settings** (kanan atas), centang **Show Hidden Files (dotfiles)**, lalu **Save**. Ini perlu agar `.htaccess` dan `.env` terlihat.
2. Klik folder paling atas (ikon rumah, `/home/ACCOUNT`).
3. Klik **Upload**, pilih `UKPR-cPanel-ukpr.zip`, tunggu sampai 100%.
4. Kembali ke File Manager, klik kanan file ZIP, pilih **Extract**, pastikan tujuannya `/home/ACCOUNT`, lalu **Extract Files**.
5. Periksa bahwa folder `ukpr_profile_app` dan `public_html/ukpr` sudah ada.
6. Hapus file ZIP setelah ekstrak berhasil.

Jika `public_html/ukpr` sudah berisi situs lain, jangan timpa. Pakai nama folder lain dan sesuaikan `app-path.php` (Langkah 4).

## Langkah 3 — Buat database dan impor SQL

Gunakan database baru yang kosong. File `ukpr.sql` sudah berisi semua tabel dan konten awal, jadi tidak perlu menjalankan migrasi.

1. Buka **MySQL® Database Wizard**.
2. Isi nama database, misalnya `ukpr`, lalu **Next Step**. cPanel menambah awalan akun, misalnya `akun_ukpr`.
3. Buat user database dengan password kuat (pakai **Password Generator**), lalu **Create User**.
4. Centang **ALL PRIVILEGES**, lalu **Next Step**.
5. Catat tiga nilai ini untuk Langkah 4: nama database lengkap, nama user lengkap, dan password-nya.
6. Buka **phpMyAdmin**, klik nama database tadi di panel kiri.
7. Buka tab **Import**, klik **Choose File**, pilih `ukpr.sql`. File ini ada di `/home/ACCOUNT/installation/`; unduh dulu ke komputer lewat File Manager jika perlu.
8. Klik **Import** (atau **Go**) dan tunggu pesan berhasil.

SQL tidak berisi akun admin atau password bawaan. Akun pertama dibuat di Langkah 5.

## Langkah 4 — Isi file .env

File `.env` menyimpan alamat website, akses database, dan dua kunci rahasia. Isinya tidak boleh dibagikan.

**a. Buat APP\_KEY dan SETUP\_TOKEN di komputer Anda.** Buka terminal di folder proyek UKPR, lalu jalankan:

```
php scripts/installation-values.php
```

Hasilnya dua baris acak, `APP_KEY=base64:...` dan `SETUP_TOKEN=...`. Buat baru untuk setiap instalasi; jangan memakai APP\_KEY dari komputer lokal.

**b. Buat file .env di hosting.**

1. Di File Manager buka `ukpr_profile_app`.
2. Klik kanan `.env.example`, pilih **Copy**, beri nama tujuan `/ukpr_profile_app/.env`.
3. Klik kanan `.env`, pilih **Edit**, lalu ubah baris berikut:

```
APP_URL=https://profil.ukpr.ac.id
APP_KEY=base64:HASIL_DARI_LANGKAH_A
DB_DATABASE=akun_ukpr
DB_USERNAME=akun_ukpruser
DB_PASSWORD="PASSWORD_DATABASE"
SETUP_TOKEN=HASIL_DARI_LANGKAH_A
```

4. Klik **Save Changes**.

Catatan pengisian:

- APP\_URL memakai `https://` dan tanpa garis miring di akhir.
- Bungkus password dengan tanda kutip, terutama jika berisi spasi atau `#`.
- Biarkan `APP_DEBUG=false` dan `APP_ENV=production`.
- DB\_HOST tetap `localhost` kecuali hosting memberi alamat lain.

**c. Atur permission.** Folder 755, file 644, `.env` 600 atau 640. Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis (755, atau 775 jika diminta hosting). Jangan pakai 777.

**d. Hanya jika nama folder diubah.** Edit `public_html/ukpr/app-path.php`:

```php
<?php
return dirname(__DIR__,2).'/ukpr_profile_app';
```

Ganti `ukpr_profile_app` dengan nama folder aplikasi Anda. Jika struktur folder berbeda, pakai path lengkap, misalnya `return '/home/ACCOUNT/ukpr_profile_app';`.

## Langkah 5 — Buat super admin dan akun fakultas/prodi

Akun pertama dibuat lewat halaman `/setup` memakai SETUP\_TOKEN. Halaman ini otomatis terkunci setelah super admin ada.

1. Buka `https://profil.ukpr.ac.id/setup`.
2. Isi token instalasi (nilai SETUP\_TOKEN), nama, email, dan password. Password minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.
3. Klik **Buat admin**, lalu login di `/login`.
4. Kembali ke File Manager, edit `.env`, kosongkan baris `SETUP_TOKEN=`, lalu simpan.

Membuat akun pengelola unit:

1. Di panel admin buka **Akun pengelola**, klik **+ Tambah akun**.
2. Isi nama, email, dan password.
3. Pilih **Level akun** dan **Unit**:

| Level | Pilih unit | Bisa mengelola |
| --- | --- | --- |
| Super admin | Tidak ada | Seluruh website dan akun |
| Admin fakultas | Fakultas | Halaman fakultas, prodi di bawahnya, pengumuman unit |
| Admin program studi | Program studi | Halaman prodi dan pengumumannya |

4. Klik **Simpan akun**, lalu kirim email dan password ke pengelola lewat jalur yang aman.

## Langkah 6 — Cek akhir dan keamanan

Centang daftar ini sebelum website diumumkan.

- [ ] Versi PHP domain 8.3 atau lebih baru (**MultiPHP Manager** atau **Select PHP Version**)
- [ ] Beranda tampil dengan font, ikon, foto hero, dan kartu akses cepat
- [ ] Halaman profil, fakultas, prodi, berita, agenda, pengumuman, galeri, dan kontak terbuka
- [ ] Menu dropdown dan menu HP (tombol tiga garis) berfungsi
- [ ] Login admin berhasil; coba tambah, edit, dan hapus satu berita dengan gambar
- [ ] Login sebagai admin fakultas hanya melihat fakultasnya sendiri
- [ ] Membuka `/admin` tanpa login diarahkan ke halaman login
- [ ] `/sitemap.xml` memakai alamat domain yang benar
- [ ] Gembok HTTPS tampil di browser
- [ ] SETUP\_TOKEN di `.env` sudah dikosongkan
- [ ] Folder `installation/` dan `MULAI-DI-SINI.txt` sudah dihapus
- [ ] Backup database (phpMyAdmin, tab **Export**) dan folder `ukpr_profile_app/storage` sudah dibuat

## Pemecahan masalah

Sebagian besar masalah berasal dari document root, `.env`, atau permission. Detail error tercatat di `ukpr_profile_app/storage/logs/laravel.log`.

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| Pesan "Path aplikasi belum dikonfigurasi" (503) | `app-path.php` menunjuk folder yang salah | Perbaiki path di `public_html/ukpr/app-path.php` (Langkah 4d) |
| Error 500 | APP\_KEY kosong, data database salah, versi PHP lama, atau storage tidak bisa ditulis | Baca `laravel.log`, cek `.env`, ubah PHP ke 8.3+, atur permission storage |
| Hanya beranda yang bisa dibuka, halaman lain 404 | `.htaccess` tidak ikut terekstrak atau mod\_rewrite mati | Aktifkan Show Hidden Files, pastikan `public_html/ukpr/.htaccess` ada |
| Tampilan polos tanpa warna | Document root salah sehingga `build/` tidak ditemukan | Arahkan document root ke `public_html/ukpr` (Langkah 1) |
| Font tidak berubah | Website dibuka sebagai sub-folder (`domain/ukpr`), bukan domain/subdomain sendiri | Gunakan domain/subdomain dengan document root `public_html/ukpr` |
| Login kembali ke form login | Website dibuka lewat HTTP atau folder sessions tidak bisa ditulis | Pakai HTTPS (AutoSSL), cek permission `storage/framework/sessions`, hapus cookie lama |
| Upload gambar gagal | GD WebP atau fileinfo mati, file > 5 MB, atau batas upload kecil | Aktifkan ekstensi, naikkan upload\_max\_filesize 8M dan post\_max\_size 12M |
| Perubahan `.env` tidak terbaca | Cache konfigurasi | Hapus file `config.php` dan `routes-*.php` di `bootstrap/cache/` |
