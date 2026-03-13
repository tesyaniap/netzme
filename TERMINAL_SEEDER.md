# Terminal Seeder Documentation

## Overview
Seeder ini berisi data terminal bus untuk kota-kota besar di seluruh Indonesia, memudahkan pencarian dan pemilihan terminal saat membuat rute perjalanan.

## Coverage Area

### Pulau Jawa
**Jakarta (5 terminals)**
- Terminal Kampung Rambutan
- Terminal Lebak Bulus  
- Terminal Pulo Gadung
- Terminal Kalideres
- Terminal Tanjung Priok

**Jawa Barat**
- Bandung (4 terminals): Leuwi Panjang, Cicaheum, Dago, Kebon Kelapa
- Bogor (2 terminals): Baranangsiang, Merdeka
- Depok (2 terminals): Jatijajar, Margonda
- Bekasi (2 terminals): Bekasi, Harapan Indah
- Tangerang (2 terminals): Poris Plawad, Bitung
- Cirebon (2 terminals): Harjamukti, Drajat

**Jawa Tengah**
- Semarang (3 terminals): Terboyo, Mangkang, Banyumanik
- Solo (3 terminals): Tirtonadi, Kartasura, Palur
- Yogyakarta (3 terminals): Giwangan, Jombor, Condong Catur
- Purwokerto (2 terminals): Purwokerto, Ajibarang
- Tegal (2 terminals): Tegal, Slawi
- Pekalongan (2 terminals): Pekalongan, Batang

**Jawa Timur**
- Surabaya (4 terminals): Bungurasih, Joyoboyo, Osowilangun, Bratang
- Malang (3 terminals): Arjosari, Landungsari, Gadang
- Kediri (2 terminals): Brawijaya, Tamanan
- Blitar (2 terminals): Patria, Kademangan
- Probolinggo (2 terminals): Bayuangga, Mayangan
- Jember (2 terminals): Tawang Alun, Arjasa
- Banyuwangi (2 terminals): Brawijaya, Sri Tanjung

### Sumatera
- **Medan** (3 terminals): Amplas, Pinang Baris, Titi Kuning
- **Palembang** (3 terminals): Karya Jaya, Alang-Alang Lebar, Km 12
- **Pekanbaru** (2 terminals): Mayang Terurai, Bandar Raya Payung Sekaki
- **Bandar Lampung** (2 terminals): Rajabasa, Kemiling
- **Padang** (2 terminals): Anak Air, Lubuk Buaya
- **Jambi** (2 terminals): Alam Barajo, Angkutan Umum
- **Bengkulu** (2 terminals): Panorama, Malabero

### Kalimantan
- **Pontianak** (2 terminals): Batu Layang, Siantan
- **Banjarmasin** (2 terminals): Km 6, Antasan Kecil
- **Samarinda** (2 terminals): Sungai Kunjang, Loa Janan
- **Balikpapan** (2 terminals): Batu Ampar, Damai

### Sulawesi
- **Makassar** (3 terminals): Daya, Mallengkeri, Sungguminasa
- **Manado** (2 terminals): Malalayang, Karombasan
- **Palu** (2 terminals): Mamboro, Masomba
- **Kendari** (2 terminals): Baruga, Puuwatu

### Bali & Nusa Tenggara
- **Denpasar** (3 terminals): Mengwi, Ubung, Batubulan
- **Mataram** (2 terminals): Mandalika, Bertais
- **Kupang** (2 terminals): Oeba, Bolok

### Maluku & Papua
- **Ambon** (2 terminals): Mardika, Pattimura
- **Jayapura** (2 terminals): Entrop, Waena

## Total Coverage
- **34 kota** di seluruh Indonesia
- **85+ terminal** bus utama
- Mencakup **semua provinsi** di Indonesia

## Usage

### Run Seeder
```bash
php artisan db:seed --class=TerminalSeeder
```

### Run All Seeders (including Terminal)
```bash
php artisan db:seed
```

## Database Structure
Setiap terminal memiliki:
- `name`: Nama terminal
- `address`: Alamat lengkap terminal
- `city_id`: Foreign key ke tabel cities

## Benefits
1. **Comprehensive Coverage**: Terminal dari Sabang sampai Merauke
2. **Real Data**: Menggunakan nama terminal yang benar-benar ada
3. **Easy Search**: Mudah dicari berdasarkan kota
4. **Route Planning**: Memudahkan pembuatan rute antar kota
5. **Scalable**: Mudah ditambah terminal baru

## Integration with Route System
Terminal ini akan digunakan untuk:
- Membuat rute perjalanan (departure_terminal_id, arrival_terminal_id)
- Pencarian jadwal berdasarkan terminal
- Informasi lokasi keberangkatan dan kedatangan
- Integrasi dengan sistem booking

## Maintenance
- Data terminal dapat diupdate melalui admin panel
- Alamat dapat diperbarui sesuai perubahan lokasi
- Terminal baru dapat ditambahkan sesuai kebutuhan

## Notes
- Seeder ini harus dijalankan setelah CitySeeder
- Pastikan tabel cities sudah terisi sebelum menjalankan TerminalSeeder
- Terminal dapat dikelola melalui API endpoint yang tersedia