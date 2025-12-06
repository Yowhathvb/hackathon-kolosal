
# 🎯 **Hackathon Kolosal – PHP Native + AI Kolosal Integration**

Project ini merupakan aplikasi **Full PHP Native** yang memanfaatkan **AI Kolosal API** untuk menghasilkan konten otomatis seperti caption, strategy, story, dan berbagai fitur AI lainnya. Aplikasi ini dirancang untuk kebutuhan hackathon, eksperimen, dan implementasi AI pada sistem berbasis PHP.

---

## 🚀 **Features**

* ✅ Full PHP Native (tanpa framework)
# Kolosal E-commerce — Panduan Penerapan Lengkap

Panduan ini menjelaskan langkah demi langkah cara memasang, mengonfigurasi, menjalankan, dan men-deploy aplikasi e-commerce berbasis PHP Native yang ada di repository ini. Semua instruksi ditulis dalam Bahasa Indonesia dan dibuat untuk lingkungan pengembangan Windows (Laragon/XAMPP) serta hosting berbagi (shared hosting / cPanel).

## Ringkasan singkat

Aplikasi ini adalah PHP native (no framework) dengan integrasi API AI (Kolosal). Fitur utama meliputi: katalog produk, keranjang, checkout, manajemen toko, dan fitur generator konten AI.

## Isi panduan ini
- Prasyarat
- Struktur proyek singkat
- Instalasi lokal (Laragon/XAMPP dan built-in PHP server)
- Konfigurasi database & import schema
- Mengatur `includes/config.php` (DB + API Key)
- Penyesuaian upload & permission
- Menjalankan aplikasi dan testing fitur AI
- Troubleshooting umum
- Keamanan & langkah deploy production

---

## Prasyarat

- PHP 7.4+ (disarankan PHP 8)
- MySQL / MariaDB
- Ekstensi PHP: pdo_mysql, curl, mbstring, fileinfo
- Web server (Apache/nginx) atau Laragon/XAMPP
- Akses ke phpMyAdmin atau mysql client untuk import database

Jika menggunakan Laragon, sebagian besar prasyarat sudah terpasang.

---

## Struktur proyek (penting)

Beberapa file/folder penting:

- `index.php` — halaman beranda
- `products/` — manajemen dan detail produk
- `shop/` — fitur toko/penjual
- `admin/` — dashboard admin & verifikasi
- `includes/config.php` — konfigurasi (DB, API key, BASE_URL)
- `database/schema.sql` — struktur dan data awal database
- `uploads/` — file upload (products, shop_logos, verification_docs)

---

## Langkah 1 — Clone atau salin kode

Jika belum punya repo secara lokal, clone:

```powershell
git clone https://github.com/Yowhathvb/hackathon-kolosal.git
cd hackathon-kolosal/ecommerce
```

Jika menggunakan Laragon: pindahkan folder project ke `C:\laragon\www\` atau gunakan fitur Quick app untuk menambah virtual host.

---

## Langkah 2 — Siapkan database

1. Buat database baru (misal `ecommerce_db`) via phpMyAdmin atau mysql CLI.

Contoh menggunakan mysql CLI (PowerShell):

```powershell
mysql -u root -p
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

2. Import struktur dan data awal yang ada di `database/schema.sql`:

```powershell
mysql -u root -p ecommerce_db < "D:\laragon\www\hackathon\kolosal\ecommerce\database\schema.sql"
```

Jika menggunakan phpMyAdmin: pilih database -> Import -> pilih file `database/schema.sql` -> Go.

---

## Langkah 3 — Konfigurasi aplikasi (`includes/config.php`)

Buka file `includes/config.php` dan atur nilai berikut sesuai lingkungan Anda:

- DB_HOST (default `localhost`)
- DB_USER (misal `root`)
- DB_PASS (password MySQL Anda)
- DB_NAME (nama database, misal `ecommerce_db`)
- KOLOSAL_API_KEY (ganti dengan API key Kolosal Anda)
- BASE_URL (misal `http://localhost/ecommerce` atau `http://ecommerce.test` jika pakai virtual host)

Contoh (potongan):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

define('KOLOSAL_API_KEY', 'MASUKKAN_API_KEY_DI_SINI');
define('KOLOSAL_API_URL', 'https://api.kolosal.ai/v1/chat/completions');
define('BASE_URL', 'http://localhost/ecommerce');
```

Catatan keamanan: jangan commit API key ke Git. Untuk lingkungan production, pertimbangkan menggunakan variable environment (`getenv`) atau file yang tidak di-commit.

---

## Langkah 4 — Hak akses folder upload (Windows/Laragon)

Folder upload ada di `uploads/` dan subfolder-nya (`products`, `shop_logos`, `verification_docs`, `temp`). Pastikan folder ini dapat ditulis oleh web server.

Di Windows (Laragon/XAMPP) biasanya tidak perlu mengubah permission. Jika Anda mengalami error upload, periksa permissions dan pastikan PHP dapat menulis ke folder tersebut.

Jika perlu, di PowerShell jalankan (contoh memberikan full control untuk pengguna saat debugging — gunakan lebih aman di production):

```powershell
icacls "D:\laragon\www\hackathon\kolosal\ecommerce\uploads" /grant "IIS_IUSRS:(OI)(CI)F" /T
```

Ganti `IIS_IUSRS` dengan user yang sesuai (atau gunakan File Explorer > Properties > Security).

---

## Langkah 5 — Aktifkan ekstensi PHP penting

Pastikan ekstensi berikut aktif di `php.ini` (atau melalui control panel Laragon/XAMPP):

- `pdo_mysql`
- `curl`
- `mbstring`
- `fileinfo`

Restart Apache / PHP-FPM setelah mengubah konfigurasi.

---

## Langkah 6 — Menjalankan aplikasi

Pilihan A — Laragon/XAMPP

1. Letakkan folder di web root (`C:\laragon\www\` atau `C:\xampp\htdocs\`).
2. Buka Laragon/XAMPP, start Apache & MySQL.
3. Akses via browser `http://localhost/<folder>` atau virtual host yang Anda buat.

Pilihan B — Built-in PHP server (development only)

Jalankan dari folder project:

```powershell
php -S localhost:8000
# lalu akses http://localhost:8000
```

Jika Anda menggunakan built-in server, set `BASE_URL` di `includes/config.php` ke `http://localhost:8000`.

---

## Langkah 7 — Tes fitur AI (Kolosal)

Setelah `KOLOSAL_API_KEY` diisi, tes endpoint cURL dari terminal untuk memastikan akses:

```powershell
curl -X POST "https://api.kolosal.ai/v1/chat/completions" -H "Content-Type: application/json" -H "Authorization: Bearer <API_KEY>" -d '{"model":"kolossal-ai","messages":[{"role":"user","content":"Hello"}]}'
```

Jika respons OK, fitur generate caption/story/strategy pada aplikasi harusnya berfungsi.

---

## Troubleshooting umum

- Database connection failed: periksa `includes/config.php` (DB_HOST, DB_USER, DB_PASS, DB_NAME) dan pastikan database sudah di-import.
- cURL errors: pastikan ekstensi `curl` aktif dan server dapat mengakses internet (bila behind proxy, atur proxy di PHP atau server).
- Upload errors: cek `php.ini` (upload_max_filesize, post_max_size), dan cek permission folder `uploads/`.
- Error terkait `BASE_URL`: pastikan BASE_URL sesuai URL yang Anda gunakan.

Untuk melihat log PHP, periksa file `logs/error.log` (file path ditetapkan di `includes/config.php`).

---

## Keamanan & Best Practices

- Jangan menyimpan API keys atau secrets di repo; gunakan environment variables pada production.
- Batasi akses database user (buat user non-root untuk aplikasi dan berikan hanya hak yang diperlukan).
- Gunakan HTTPS di production.
- Validasi & sanitasi semua input pengguna.

---

## Deploy ke production (ringkasan)

1. Siapkan server (Ubuntu/NGINX/Apache) atau hosting yang mendukung PHP.
2. Upload file ke `public_html` atau direktori web server.
3. Buat database dan import `database/schema.sql`.
4. Atur `includes/config.php` dengan kredensial production.
5. Atur permission folder `uploads/` dan `logs/` agar dapat ditulis oleh web server tanpa memberikan akses berlebih.
6. Gunakan SSL (Let's Encrypt) dan cek konfigurasi keamanan.

---

## Penggunaan singkat fitur (file & titik masuk)

- `index.php` — Beranda / katalog
- `products/create.php` — Form tambah produk (upload gambar)
- `product/add_to_cart.php` — Menambah produk ke keranjang
- `checkout.php` — Proses checkout (cek file terkait untuk integrasi pembayaran)
- `admin/dashboard.php` — Panel admin
- `includes/functions.php` — utilitas helper yang dipakai aplikasi

Telusuri folder untuk alur lebih detail.

---

## Kontribusi

1. Fork repository
2. Buat branch fitur: `git checkout -b feature/nama-fitur`
3. Commit & push
4. Buat pull request

Terima kontribusi perbaikan bug, dokumentasi, atau fitur baru.

---

## License

Project ini dilisensikan di bawah MIT License.

---

Jika Anda ingin, saya bisa:

- Membuat panduan instalasi otomatis (script)
- Menambahkan file `.env.example` dan implementasi `vlucas/phpdotenv`
- Membuat skrip migrasi sederhana untuk DB

Beritahu apa yang ingin Anda tambahkan berikutnya.
✔ Sistem auth login

✔ Dashboard admin

