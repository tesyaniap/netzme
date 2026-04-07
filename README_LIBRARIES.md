# Teknologi & Library (Tech Stack)

Dokumen ini mencantumkan teknologi dan library utama yang digunakan dalam pengembangan backend Tiket Bus ini.

## Core Technologies
- **PHP 8.2**: Versi terkini yang mendukung fitur-fitur baru dan performa optimal.
- **Laravel 12 (Framework)**: Framework PHP modern yang digunakan sebagai basis aplikasi.
- **MySQL (Database)**: Sistem manajemen basis data relasional untuk menyimpan data master, transaksi, dan log.

## Utama (Required Libraries)

### 1. Laravel Passport (`laravel/passport`)
**Fungsi**: Digunakan untuk sistem **Autentikasi API** berbasis OAuth2.
- Mengelola `access_token` yang diberikan kepada Mitra/Admin setelah login.
- Memungkinkan login yang aman dan terenkripsi.
- Mendukung fitur `refresh_token` dan pencabutan akses (`revoke`).

### 2. Spatie Laravel Permission (`spatie/laravel-permission`)
**Fungsi**: Digunakan untuk **Role & Permission Management**.
- Memisahkan hak akses antara **Admin** dan **Mitra**.
- Memberikan fleksibilitas untuk menentukan aksi apa saja yang boleh dilakukan oleh setiap role (misal: `transactions.create`, `users.delete`).
- Terintegrasi dengan middleware untuk perlindungan API.

### 3. Laravel Excel (`maatwebsite/excel`)
**Fungsi**: Digunakan untuk **Ekspor dan Impor Data**.
- Membantu pembuatan laporan dalam format Excel (`.xlsx`) atau CSV.
- Mempermudah Admin dalam mengunduh riwayat transaksi atau daftar Mitra.

### 4. Dedoc Scramble (`dedoc/scramble`)
**Fungsi**: Digunakan untuk **Dokumentasi API otomatis**.
- Secara otomatis mendeteksi endpoint API dan mendokumentasikannya.
- Alternatif yang lebih ringan dan modern dibandingkan L5-Swagger.
- Dapat diakses melalui `/docs/api` (jika sudah dikonfigurasi).

## Development & Utility Tools
- **Laravel Tinker**: Untuk berinteraksi langsung dengan aplikasi melalui shell command.
- **Laravel Pail**: Untuk melihat log sistem secara real-time.
- **Laravel Pint**: Sebagai PHP styling fixer untuk menjaga konsistensi penulisan kode.
- **Laravel Sail**: Menyediakan environment Docker untuk mempermudah setup di mesin lokal.
- **Faker**: Digunakan di dalam data seeder untuk menghasilkan data contoh yang acak.

## Middleware Khusus
- **RolePermissionMiddleware**: Middleware khusus untuk memvalidasi apakah user yang login memiliki role dan permission yang tepat sebelum mengakses endpoint API.
- **BalanceCheckMiddleware** (opsional/logic based): Untuk memastikan saldo cukup sebelum melakukan pembayaran.

## Struktur Database Penting
- **Users**: Menyimpan data login.
- **Mitra**: Menyimpan data profil mitra dan saldo saat ini.
- **Transactions**: Menyimpan detail reservasi tiket.
- **Topups**: Riwayat permintaan pengisian saldo.
- **Fee Ledgers**: Pencatatan detil keuntungan atau biaya layanan.
