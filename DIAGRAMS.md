# Visualisasi Sistem: Flowchart & DFD

Dokumen ini berisi visualisasi sistem yang dirancang untuk memberikan pemahaman mendalam mengenai alur kerja dan aliran data pada aplikasi Backend Tiket Bus.

---

## 1. Flowchart: Alur Reservasi & Pembayaran Tiket (Core Business Logic)
**Analisis**: Alur ini menggunakan sistem *atomic transaction* di mana pemotongan saldo mitra hanya dilakukan setelah validasi kursi dan saldo berhasil, guna mencegah kegagalan sinkronisasi data keuangan.

```mermaid
graph TD
    A([Mulai]) --> B[Pencarian Jadwal Perjalanan]
    B --> C{Jadwal Ditemukan?}
    C -- Tidak --> B
    C -- Ya --> D[Pilih Kursi dari Seat Map]
    D --> E{Kursi Tersedia?}
    E -- Tidak --> D
    E -- Ya --> F[Input Data Penumpang & Reservasi]
    F --> G[Sistem Generate Kode Transaksi - Status: Pending]
    G --> H[Proses Pembayaran via Saldo Mitra]
    H --> I{Saldo Mencukupi?}
    I -- Tidak --> J[Transaksi Gagal - Saldo Tidak Cukup]
    I -- Ya --> K[Potong Saldo Mitra & Catat Mutasi Saldo]
    K --> L[Update Status Transaksi: Paid]
    L --> M[Penerbitan Tiket Digital - Issue Ticket]
    M --> N[Cetak Tiket / Unduh PDF]
    N --> O([Selesai])
```

---

## 2. DFD Level 0 (Context Diagram)
**Analisis**: Diagram ini menunjukkan bahwa sistem bertindak sebagai entitas pusat yang mengelola pertukaran informasi terenkripsi antara Admin (manajer data) dan Mitra (operator penjualan) secara real-time.

```mermaid
graph LR
    subgraph Sistem_Pemesanan_Tiket_Bus
        S[((Aplikasi Backend Tiket Bus))]
    end

    Admin[Entitas: Admin] -- Kelola Master Data,\nApprove Topup,\nMonitor Laporan Keuangan --> S
    S -- Laporan Rekapitulasi,\nNotifikasi Sistem --> Admin

    Mitra[Entitas: Mitra] -- Request Topup Saldo,\nReservasi Tiket,\nOtorisasi Pembayaran --> S
    S -- E-Ticket,\nInformasi Sisa Saldo,\nStatus Transaksi --> Mitra
```

---

## 3. DFD Level 1 (Diagram Proses Internal)
**Analisis**: Pemisahan modul menjadi lima proses utama memastikan sistem memiliki *high cohesion* dan *low coupling*, sehingga memudahkan skalabilitas dan audit pada setiap fungsi bisnis (Auth, Master Data, Finance, Transaction, Reporting).

```mermaid
graph TD
    %% Entitas Luar
    Admin[Admin]
    Mitra[Mitra]

    %% Proses-Proses Utama
    P1((1.0\nManajemen\nAutentikasi))
    P2((2.0\nManajemen\nMaster Data))
    P3((3.0\nManajemen\nSaldo & Topup))
    P4((4.0\nProses\nTransaksi))
    P5((5.0\nPelaporan &\nUpdate Database))

    %% Data Stores
    D1[(Users Table)]
    D2[(Master Data Table\nCities, Routes, etc)]
    D3[(Transactions & \nTopups Table)]
    D4[(Balance Histories & \nFees Table)]

    %% Aliran Data Admin
    Admin --> P1
    Admin --> P2
    Admin --> P3
    Admin --> P5
    P2 <--> D2
    P3 <--> D3

    %% Aliran Data Mitra
    Mitra --> P1
    Mitra --> P3
    Mitra --> P4
    P1 <--> D1
    P4 <--> D2
    P4 <--> D3
    D3 --> P5
    P4 --> D4
    P5 --> Admin
```

---

## Standar Penamaan & Istilah (Glossary)
Untuk menjaga konsistensi pada laporan, gunakan istilah berikut:
- **Admin**: Pengguna dengan hak akses penuh untuk mengelola data master dan keuangan.
- **Mitra**: Agen atau pihak ketiga yang melakukan penjualan tiket menggunakan sistem saldo.
- **Master Data**: Kumpulan data dasar (Kota, rute, bus, jadwal).
- **Topup**: Proses pengisian ulang saldo Mitra.
- **Reservasi**: Proses pemesanan kursi sebelum pembayaran dilakukan.
- **Issue Ticket**: Proses penerbitan tiket resmi setelah pembayaran dikonfirmasi.

---

## Penjelasan Singkat
1.  **Flowchart**: Menekankan pada validasi saldo dan ketersediaan kursi sebelum transaksi dianggap sukses.
2.  **DFD Level 0**: Memposisikan aplikasi sebagai pusat aliran informasi antara Admin (penyedia data/pengawas) dan Mitra (pengguna/penjual).
3.  **DFD Level 1**: Membagi beban kerja sistem ke 5 area utama (Auth, Master Data, Finance, Transaction, Reporting) yang semuanya saling terintegrasi melalui database.
