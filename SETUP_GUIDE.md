# Setup Guide - Laravel 12 dengan Passport

## 1. Install PHP & Composer
Pastikan PHP 8.2+ dan Composer sudah terinstall:
```bash
php -v
composer -v
```

## 2. Install Dependencies
```bash
composer install
```

## 3. Setup Environment
```bash
copy .env.example .env
php artisan key:generate
```

## 4. Setup Database
Edit file `.env` sesuai database Anda:
- Untuk SQLite (default): biarkan `DB_CONNECTION=sqlite`
- Untuk MySQL: ubah ke konfigurasi MySQL

Jika pakai SQLite, buat file database:
```bash
type nul > database\database.sqlite
```

## 5. Run Migration
```bash
php artisan migrate
```

## 6. Install Passport
```bash
php artisan passport:install
```

Simpan Client ID dan Secret yang muncul!

## 7. Install NPM Dependencies (Optional)
```bash
npm install
npm run build
```

## 8. Run Server
```bash
php artisan serve
```

Server akan jalan di: http://localhost:8000

## Testing API
Gunakan Postman atau tools lain untuk test endpoint API di `/api/*`

## Troubleshooting
- Jika error permission: `chmod -R 777 storage bootstrap/cache`
- Jika error Passport: jalankan ulang `php artisan passport:install`
