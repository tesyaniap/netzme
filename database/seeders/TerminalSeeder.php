<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Terminal;
use App\Models\City;

class TerminalSeeder extends Seeder
{
    public function run()
    {
        $terminals = [
            // JAKARTA
            ['city' => 'Jakarta', 'terminals' => [
                ['name' => 'Terminal Kampung Rambutan', 'address' => 'Jl. Raya Bogor KM 23, Jakarta Timur'],
                ['name' => 'Terminal Lebak Bulus', 'address' => 'Jl. Lebak Bulus Raya, Jakarta Selatan'],
                ['name' => 'Terminal Pulo Gadung', 'address' => 'Jl. Perintis Kemerdekaan, Jakarta Timur'],
                ['name' => 'Terminal Kalideres', 'address' => 'Jl. Daan Mogot KM 18, Jakarta Barat'],
                ['name' => 'Terminal Tanjung Priok', 'address' => 'Jl. Enggano, Jakarta Utara'],
            ]],

            // SURABAYA
            ['city' => 'Surabaya', 'terminals' => [
                ['name' => 'Terminal Bungurasih', 'address' => 'Jl. Raya Waru, Sidoarjo'],
                ['name' => 'Terminal Joyoboyo', 'address' => 'Jl. Joyoboyo, Surabaya'],
                ['name' => 'Terminal Osowilangun', 'address' => 'Jl. Osowilangun, Surabaya Utara'],
                ['name' => 'Terminal Bratang', 'address' => 'Jl. Bratang Gede, Surabaya'],
            ]],

            // BANDUNG
            ['city' => 'Bandung', 'terminals' => [
                ['name' => 'Terminal Leuwi Panjang', 'address' => 'Jl. Soekarno Hatta, Bandung'],
                ['name' => 'Terminal Cicaheum', 'address' => 'Jl. A.H. Nasution, Bandung'],
                ['name' => 'Terminal Dago', 'address' => 'Jl. Ir. H. Juanda, Bandung'],
                ['name' => 'Terminal Kebon Kelapa', 'address' => 'Jl. Kebon Kelapa, Bandung'],
            ]],

            // MEDAN
            ['city' => 'Medan', 'terminals' => [
                ['name' => 'Terminal Amplas', 'address' => 'Jl. Sisingamangaraja, Medan'],
                ['name' => 'Terminal Pinang Baris', 'address' => 'Jl. Pinang Baris, Medan'],
                ['name' => 'Terminal Titi Kuning', 'address' => 'Jl. Titi Kuning, Medan'],
            ]],

            // SEMARANG
            ['city' => 'Semarang', 'terminals' => [
                ['name' => 'Terminal Terboyo', 'address' => 'Jl. Kaligawe, Semarang'],
                ['name' => 'Terminal Mangkang', 'address' => 'Jl. Raya Mangkang, Semarang'],
                ['name' => 'Terminal Banyumanik', 'address' => 'Jl. Banyumanik, Semarang'],
            ]],

            // YOGYAKARTA
            ['city' => 'Yogyakarta', 'terminals' => [
                ['name' => 'Terminal Giwangan', 'address' => 'Jl. Imogiri Timur, Yogyakarta'],
                ['name' => 'Terminal Jombor', 'address' => 'Jl. Magelang KM 7, Sleman'],
                ['name' => 'Terminal Condong Catur', 'address' => 'Jl. Kaliurang, Sleman'],
            ]],

            // SOLO
            ['city' => 'Solo', 'terminals' => [
                ['name' => 'Terminal Tirtonadi', 'address' => 'Jl. A. Yani, Solo'],
                ['name' => 'Terminal Kartasura', 'address' => 'Jl. Slamet Riyadi, Kartasura'],
                ['name' => 'Terminal Palur', 'address' => 'Jl. Raya Solo-Sragen, Karanganyar'],
            ]],

            // MALANG
            ['city' => 'Malang', 'terminals' => [
                ['name' => 'Terminal Arjosari', 'address' => 'Jl. Raya Kepanjen, Malang'],
                ['name' => 'Terminal Landungsari', 'address' => 'Jl. Raya Malang-Batu, Malang'],
                ['name' => 'Terminal Gadang', 'address' => 'Jl. Raya Gadang, Malang'],
            ]],

            // DENPASAR
            ['city' => 'Denpasar', 'terminals' => [
                ['name' => 'Terminal Mengwi', 'address' => 'Jl. Raya Mengwi, Badung'],
                ['name' => 'Terminal Ubung', 'address' => 'Jl. Cokroaminoto, Denpasar'],
                ['name' => 'Terminal Batubulan', 'address' => 'Jl. Raya Batubulan, Gianyar'],
            ]],

            // MAKASSAR
            ['city' => 'Makassar', 'terminals' => [
                ['name' => 'Terminal Daya', 'address' => 'Jl. Perintis Kemerdekaan, Makassar'],
                ['name' => 'Terminal Mallengkeri', 'address' => 'Jl. Mallengkeri Raya, Makassar'],
                ['name' => 'Terminal Sungguminasa', 'address' => 'Jl. Poros Makassar-Sungguminasa, Gowa'],
            ]],

            // PALEMBANG
            ['city' => 'Palembang', 'terminals' => [
                ['name' => 'Terminal Karya Jaya', 'address' => 'Jl. Karya Jaya, Palembang'],
                ['name' => 'Terminal Alang-Alang Lebar', 'address' => 'Jl. Alang-Alang Lebar, Palembang'],
                ['name' => 'Terminal Km 12', 'address' => 'Jl. Lintas Sumatera KM 12, Palembang'],
            ]],

            // PEKANBARU
            ['city' => 'Pekanbaru', 'terminals' => [
                ['name' => 'Terminal Mayang Terurai', 'address' => 'Jl. Lintas Timur Sumatera, Pekanbaru'],
                ['name' => 'Terminal Bandar Raya Payung Sekaki', 'address' => 'Jl. Payung Sekaki, Pekanbaru'],
            ]],

            // BANDAR LAMPUNG
            ['city' => 'Bandar Lampung', 'terminals' => [
                ['name' => 'Terminal Rajabasa', 'address' => 'Jl. Lintas Sumatera, Bandar Lampung'],
                ['name' => 'Terminal Kemiling', 'address' => 'Jl. Pramuka, Bandar Lampung'],
            ]],

            // PADANG
            ['city' => 'Padang', 'terminals' => [
                ['name' => 'Terminal Anak Air', 'address' => 'Jl. By Pass, Padang'],
                ['name' => 'Terminal Lubuk Buaya', 'address' => 'Jl. Lubuk Buaya, Padang'],
            ]],

            // JAMBI
            ['city' => 'Jambi', 'terminals' => [
                ['name' => 'Terminal Alam Barajo', 'address' => 'Jl. Lintas Sumatera, Jambi'],
                ['name' => 'Terminal Angkutan Umum', 'address' => 'Jl. Sultan Agung, Jambi'],
            ]],

            // BENGKULU
            ['city' => 'Bengkulu', 'terminals' => [
                ['name' => 'Terminal Panorama', 'address' => 'Jl. Panorama, Bengkulu'],
                ['name' => 'Terminal Malabero', 'address' => 'Jl. Malabero, Bengkulu'],
            ]],

            // PONTIANAK
            ['city' => 'Pontianak', 'terminals' => [
                ['name' => 'Terminal Batu Layang', 'address' => 'Jl. Ahmad Yani, Pontianak'],
                ['name' => 'Terminal Siantan', 'address' => 'Jl. Siantan, Pontianak'],
            ]],

            // BANJARMASIN
            ['city' => 'Banjarmasin', 'terminals' => [
                ['name' => 'Terminal Km 6', 'address' => 'Jl. A. Yani KM 6, Banjarmasin'],
                ['name' => 'Terminal Antasan Kecil', 'address' => 'Jl. Antasan Kecil, Banjarmasin'],
            ]],

            // SAMARINDA
            ['city' => 'Samarinda', 'terminals' => [
                ['name' => 'Terminal Sungai Kunjang', 'address' => 'Jl. Sungai Kunjang, Samarinda'],
                ['name' => 'Terminal Loa Janan', 'address' => 'Jl. Loa Janan, Samarinda'],
            ]],

            // BALIKPAPAN
            ['city' => 'Balikpapan', 'terminals' => [
                ['name' => 'Terminal Batu Ampar', 'address' => 'Jl. Soekarno Hatta, Balikpapan'],
                ['name' => 'Terminal Damai', 'address' => 'Jl. Damai, Balikpapan'],
            ]],

            // MANADO
            ['city' => 'Manado', 'terminals' => [
                ['name' => 'Terminal Malalayang', 'address' => 'Jl. Raya Malalayang, Manado'],
                ['name' => 'Terminal Karombasan', 'address' => 'Jl. Karombasan, Manado'],
            ]],

            // PALU
            ['city' => 'Palu', 'terminals' => [
                ['name' => 'Terminal Mamboro', 'address' => 'Jl. Trans Sulawesi, Palu'],
                ['name' => 'Terminal Masomba', 'address' => 'Jl. Masomba, Palu'],
            ]],

            // KENDARI
            ['city' => 'Kendari', 'terminals' => [
                ['name' => 'Terminal Baruga', 'address' => 'Jl. Baruga, Kendari'],
                ['name' => 'Terminal Puuwatu', 'address' => 'Jl. Puuwatu, Kendari'],
            ]],

            // AMBON
            ['city' => 'Ambon', 'terminals' => [
                ['name' => 'Terminal Mardika', 'address' => 'Jl. Mardika, Ambon'],
                ['name' => 'Terminal Pattimura', 'address' => 'Jl. Pattimura, Ambon'],
            ]],

            // JAYAPURA
            ['city' => 'Jayapura', 'terminals' => [
                ['name' => 'Terminal Entrop', 'address' => 'Jl. Entrop, Jayapura'],
                ['name' => 'Terminal Waena', 'address' => 'Jl. Waena, Jayapura'],
            ]],

            // MATARAM
            ['city' => 'Mataram', 'terminals' => [
                ['name' => 'Terminal Mandalika', 'address' => 'Jl. Langko, Mataram'],
                ['name' => 'Terminal Bertais', 'address' => 'Jl. Pejanggik, Mataram'],
            ]],

            // KUPANG
            ['city' => 'Kupang', 'terminals' => [
                ['name' => 'Terminal Oeba', 'address' => 'Jl. Timor Raya, Kupang'],
                ['name' => 'Terminal Bolok', 'address' => 'Jl. Bolok, Kupang'],
            ]],

            // KOTA-KOTA JAWA BARAT
            ['city' => 'Bogor', 'terminals' => [
                ['name' => 'Terminal Baranangsiang', 'address' => 'Jl. Raya Pajajaran, Bogor'],
                ['name' => 'Terminal Merdeka', 'address' => 'Jl. Merdeka, Bogor'],
            ]],

            ['city' => 'Depok', 'terminals' => [
                ['name' => 'Terminal Jatijajar', 'address' => 'Jl. Raya Bogor, Depok'],
                ['name' => 'Terminal Margonda', 'address' => 'Jl. Margonda Raya, Depok'],
            ]],

            ['city' => 'Bekasi', 'terminals' => [
                ['name' => 'Terminal Bekasi', 'address' => 'Jl. Ahmad Yani, Bekasi'],
                ['name' => 'Terminal Harapan Indah', 'address' => 'Jl. Boulevard Harapan Indah, Bekasi'],
            ]],

            ['city' => 'Tangerang', 'terminals' => [
                ['name' => 'Terminal Poris Plawad', 'address' => 'Jl. Daan Mogot, Tangerang'],
                ['name' => 'Terminal Bitung', 'address' => 'Jl. Bitung Raya, Tangerang'],
            ]],

            ['city' => 'Cirebon', 'terminals' => [
                ['name' => 'Terminal Harjamukti', 'address' => 'Jl. Bypass, Cirebon'],
                ['name' => 'Terminal Drajat', 'address' => 'Jl. Drajat, Cirebon'],
            ]],

            // KOTA-KOTA JAWA TENGAH
            ['city' => 'Purwokerto', 'terminals' => [
                ['name' => 'Terminal Purwokerto', 'address' => 'Jl. S. Parman, Purwokerto'],
                ['name' => 'Terminal Ajibarang', 'address' => 'Jl. Raya Ajibarang, Banyumas'],
            ]],

            ['city' => 'Tegal', 'terminals' => [
                ['name' => 'Terminal Tegal', 'address' => 'Jl. Pancasila, Tegal'],
                ['name' => 'Terminal Slawi', 'address' => 'Jl. Raya Slawi, Tegal'],
            ]],

            ['city' => 'Pekalongan', 'terminals' => [
                ['name' => 'Terminal Pekalongan', 'address' => 'Jl. Raya Pekalongan, Pekalongan'],
                ['name' => 'Terminal Batang', 'address' => 'Jl. Raya Batang, Batang'],
            ]],

            // KOTA-KOTA JAWA TIMUR
            ['city' => 'Kediri', 'terminals' => [
                ['name' => 'Terminal Brawijaya', 'address' => 'Jl. Brawijaya, Kediri'],
                ['name' => 'Terminal Tamanan', 'address' => 'Jl. Tamanan, Kediri'],
            ]],

            ['city' => 'Blitar', 'terminals' => [
                ['name' => 'Terminal Patria', 'address' => 'Jl. A. Yani, Blitar'],
                ['name' => 'Terminal Kademangan', 'address' => 'Jl. Kademangan, Blitar'],
            ]],

            ['city' => 'Probolinggo', 'terminals' => [
                ['name' => 'Terminal Bayuangga', 'address' => 'Jl. Raya Bayuangga, Probolinggo'],
                ['name' => 'Terminal Mayangan', 'address' => 'Jl. Mayangan, Probolinggo'],
            ]],

            ['city' => 'Jember', 'terminals' => [
                ['name' => 'Terminal Tawang Alun', 'address' => 'Jl. PB. Sudirman, Jember'],
                ['name' => 'Terminal Arjasa', 'address' => 'Jl. Arjasa, Jember'],
            ]],

            ['city' => 'Banyuwangi', 'terminals' => [
                ['name' => 'Terminal Brawijaya', 'address' => 'Jl. Brawijaya, Banyuwangi'],
                ['name' => 'Terminal Sri Tanjung', 'address' => 'Jl. Raya Situbondo, Banyuwangi'],
            ]],
        ];

        foreach ($terminals as $cityData) {
            $city = City::where('name', $cityData['city'])->first();
            
            if ($city) {
                foreach ($cityData['terminals'] as $terminalData) {
                    Terminal::create([
                        'name' => $terminalData['name'],
                        'address' => $terminalData['address'],
                        'city_id' => $city->id
                    ]);
                }
            }
        }
    }
}