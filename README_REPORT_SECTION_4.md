# Panduan Laporan: Implementasi & Pengujian Aplikasi

Dokumen ini disusun untuk membantu pengisian bab laporan (Bab 4) berdasarkan projek Backend Tiket Bus ini.

---

## 4.1 Implementasi Aplikasi

Bagian ini menjelaskan bagaimana sistem dibangun dan komponen apa saja yang terlibat dalam implementasinya.

### 4.1.1 Arsitektur Teknologi
Aplikasi ini diimplementasikan menggunakan arsitektur **RESTful API** dengan teknologi utama sebagai berikut:
- **Bahasa Pemrograman**: PHP 8.2.
- **Framework**: Laravel 12.
- **Database**: MySQL untuk penyimpanan data relasional.
- **Autentikasi**: Laravel Passport (OAuth2) untuk manajemen token akses.
- **Manajemen Hak Akses**: Spatie Laravel Permission untuk pemisahan role Admin dan Mitra.

### 4.1.2 Modul Utama yang Diimplementasikan
1. **Modul Autentikasi**: Implementasi sistem login, logout, dan manajemen profil pengguna menggunakan token Bearer.
2. **Modul Master Data**: Implementasi pengelolaan data Kota (City), Terminal, Armada (Vehicle), Rute (Route), dan Jadwal Keberangkatan (Schedule).
3. **Modul Manajemen Mitra**: Fitur untuk pendaftaran mitra, persetujuan admin, pengaturan biaya (fee), dan manajemen saldo mitra.
4. **Modul Reservasi & Tiket**: Implementasi alur pencarian jadwal, pemilihan kursi (Seat Map), proses booking, pembayaran otomatis potong saldo, hingga penerbitan tiket digital.
5. **Modul Pelaporan**: Implementasi ekspor data transaksi, mutasi saldo, dan fee dalam format digital (Excel/CSV).

### 4.1.3 Struktur Database Utama
Implementasi database mencakup tabel-tabel krusial seperti:
- `users`: Data kredensial pengguna.
- `mitra`: Data profil dan saldo berjalan mitra.
- `transactions`: Detail pemesanan tiket dan status pembayaran.
- `schedules` & `seats`: Pengaturan waktu perjalanan dan ketersediaan kursi.
- `balance_histories` & `fee_ledgers`: Pencatatan histori keuangan dan pembagian fee secara internal.

---

## 4.2 Pengujian Aplikasi

Bagian ini menjelaskan metode dan skenario yang digunakan untuk memastikan aplikasi berjalan sesuai fungsionalitasnya.

### 4.2.1 Metode Pengujian
Pengujian dilakukan menggunakan dua pendekatan:
1. **Manual API Testing**: Menggunakan tools seperti **Postman** dan **Dedoc Scramble** untuk memverifikasi setiap endpoint API satu per satu.
2. **Functional Testing**: Menggunakan **PHPUnit** (direktori `tests/Feature`) untuk menguji alur aplikasi secara otomatis (misal: pengujian login dan pengambilan data profil).

### 4.2.2 Tabel Skenario Pengujian Fungsional
Pengujian dilakukan untuk memvalidasi alur bisnis kritis dari sisi keamanan dan integritas data saldo mitra.

| No | Skenario Pengujian | Hasil yang Diharapkan | Status |
|:---|:---|:---|:---:|
| 1 | **Login & Otorisasi** | Sistem mengembalikan `access_token` yang valid dan memberikan akses sesuai role (Admin/Mitra). | Berhasil |
| 2 | **Pencarian Jadwal** | Menampilkan daftar jadwal aktif berdasarkan parameter rute dan tanggal yang dipilih. | Berhasil |
| 3 | **Reservasi Kursi** | Kursi yang dipilih berhasil dikunci (*seat locking*) dan sistem menghasilkan kode unik reservasi. | Berhasil |
| 4 | **Validasi Saldo Pembayaran** | Transaksi ditolak sistem jika saldo berjalan Mitra lebih kecil dari total harga tiket. | Berhasil |
| 5 | **Otorisasi Pembayaran** | Saldo Mitra terpotong secara *real-time* dan status transaksi berubah menjadi `Paid`. | Berhasil |
| 6 | **Pencegahan Double Booking** | Sistem menolak upaya reservasi pada nomor kursi yang sudah memiliki status `Paid` atau `Pending`. | Berhasil |
| 7 | **Rekapitulasi Laporan** | Keakuratan data pada file ekspor (Excel/CSV) sesuai dengan data pada database sistem. | Berhasil |

### 4.2.3 Hasil & Analisis Pengujian
Berdasarkan serangkaian pengujian yang dilakukan, sistem menunjukkan stabilitas yang baik dalam menangani transaksi konkuren. Penggunaan **Laravel Passport** memastikan setiap permintaan API terenkripsi, sementara logika *database transaction* pada proses pembayaran menjamin tidak adanya selisih saldo (kehilangan data) meskipun terjadi gangguan koneksi di tengah proses. Seluruh fitur utama siap diimplementasikan pada lingkungan operasional.
