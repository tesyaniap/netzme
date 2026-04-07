# Alur Kerja Projek (Project Flow)

Dokumen ini menjelaskan alur kerja utama dalam sistem backend Tiket Bus ini, mulai dari autentikasi hingga proses transaksi tiket.

## 1. Alur Autentikasi (Authentication Flow)
Sistem menggunakan **Laravel Passport (OAuth2)** untuk mengamankan API.
- **Login**: User mengirimkan email dan password ke `/api/v1/auth/login`. Jika berhasil, sistem mengembalikan `access_token`.
- **Token Usage**: Token ini harus disertakan dalam Header `Authorization: Bearer {token}` untuk mengakses API yang terlindungi.
- **Role Check**: Setelah login, middleware akan memeriksa apakah user memiliki role (Admin atau Mitra) dan permission yang sesuai untuk mengakses endpoint tertentu.

## 2. Alur Admin (Back-Office Flow)
Admin bertanggung jawab mengelola data master yang akan digunakan oleh Mitra untuk bertransaksi.
1. **Manajemen User & Role**: Membuat user baru dan mengatur hak akses (permissions).
2. **Manajemen Mitra**: Mendaftarkan Mitra (Agent), menyetujui (approve) pendaftaran, dan mengatur biaya (fee).
3. **Persiapan Data Perjalanan**:
   - **Cities & Terminals**: Menentukan kota asal/tujuan dan terminalnya.
   - **Vehicles**: Mendata armada bus yang tersedia.
   - **Routes**: Menentukan rute perjalanan (misal: Jakarta -> Surabaya).
   - **Schedules**: Membuat jadwal keberangkatan bus pada rute dan waktu tertentu.
   - **Seats**: Menentukan layout kursi untuk setiap bus/jadwal.
4. **Verifikasi Topup**: Admin menyetujui atau menolak permintaan topup saldo dari Mitra.

## 3. Alur Mitra (Partner/Agent Flow)
Mitra adalah pengguna yang melakukan penjualan tiket kepada penumpang.
1. **Topup Saldo**: Mitra mengajukan topup melalui sistem. Setelah disetujui Admin, saldo Mitra akan bertambah.
2. **Pencarian Jadwal**: Mitra mencari jadwal bus berdasarkan tanggal keberangkatan (`/api/v1/transactions/schedules`).
3. **Pemesanan (Booking)**:
   - Pilih kursi yang tersedia (`/api/v1/transactions/seat-map`).
   - Melakukan booking dengan memasukkan data penumpang (`/api/v1/transactions/book`).
   - Status transaksi menjadi `pending`.
4. **Pembayaran**:
   - Mitra melakukan pembayaran menggunakan saldo mereka (`/api/v1/transactions/pay`).
   - Saldo Mitra akan terpotong secara otomatis.
   - Status transaksi berubah menjadi `paid`.
5. **Pencetakan Tiket**: Setelah dibayar, tiket dapat diterbitkan (`issue`) dan dicetak (`/api/v1/transactions/{trx_code}/print`).

## 4. Alur Transaksi & Saldo (Transaction & Balance Lifecycle)
Setiap transaksi mencatat perjalanan saldo:
- **Balance History**: Setiap pengurangan/penambahan saldo tercatat di tabel riwayat saldo.
- **Fee Ledger**: Sistem secara otomatis menghitung keuntungan/biaya (fee) dari setiap transaksi tiket dan mencatatnya dalam ledger terpisah.
- **Reports**: Admin dan Mitra dapat melihat laporan transaksi, topup, dan mutasi saldo dalam periode tertentu.

## 5. Callback Flow
Sistem menyediakan endpoint callback (`/api/v1/callbacks/...`) yang disiapkan untuk menerima notifikasi otomatis dari provider eksternal (misal: payment gateway atau provider tiket bus) jika ada integrasi di masa depan.
