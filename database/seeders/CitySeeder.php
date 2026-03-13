<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\Terminal;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            // Existing cities
            ['name' => 'Kota Cimahi', 'province' => 'Jawa Barat'],
            ['name' => 'Banda Aceh', 'province' => 'Aceh'],
            ['name' => 'Jakarta', 'province' => 'DKI Jakarta'],
            ['name' => 'Bandung', 'province' => 'Jawa Barat'],
            ['name' => 'Surabaya', 'province' => 'Jawa Timur'],
            
            // Additional major cities
            ['name' => 'Medan', 'province' => 'Sumatera Utara'],
            ['name' => 'Semarang', 'province' => 'Jawa Tengah'],
            ['name' => 'Yogyakarta', 'province' => 'DI Yogyakarta'],
            ['name' => 'Solo', 'province' => 'Jawa Tengah'],
            ['name' => 'Malang', 'province' => 'Jawa Timur'],
            ['name' => 'Denpasar', 'province' => 'Bali'],
            ['name' => 'Makassar', 'province' => 'Sulawesi Selatan'],
            ['name' => 'Palembang', 'province' => 'Sumatera Selatan'],
            ['name' => 'Pekanbaru', 'province' => 'Riau'],
            ['name' => 'Bandar Lampung', 'province' => 'Lampung'],
            ['name' => 'Padang', 'province' => 'Sumatera Barat'],
            ['name' => 'Jambi', 'province' => 'Jambi'],
            ['name' => 'Bengkulu', 'province' => 'Bengkulu'],
            ['name' => 'Pontianak', 'province' => 'Kalimantan Barat'],
            ['name' => 'Banjarmasin', 'province' => 'Kalimantan Selatan'],
            ['name' => 'Samarinda', 'province' => 'Kalimantan Timur'],
            ['name' => 'Balikpapan', 'province' => 'Kalimantan Timur'],
            ['name' => 'Manado', 'province' => 'Sulawesi Utara'],
            ['name' => 'Palu', 'province' => 'Sulawesi Tengah'],
            ['name' => 'Kendari', 'province' => 'Sulawesi Tenggara'],
            ['name' => 'Ambon', 'province' => 'Maluku'],
            ['name' => 'Jayapura', 'province' => 'Papua'],
            ['name' => 'Mataram', 'province' => 'Nusa Tenggara Barat'],
            ['name' => 'Kupang', 'province' => 'Nusa Tenggara Timur'],
            ['name' => 'Bogor', 'province' => 'Jawa Barat'],
            ['name' => 'Depok', 'province' => 'Jawa Barat'],
            ['name' => 'Bekasi', 'province' => 'Jawa Barat'],
            ['name' => 'Tangerang', 'province' => 'Banten'],
            ['name' => 'Cirebon', 'province' => 'Jawa Barat'],
            ['name' => 'Purwokerto', 'province' => 'Jawa Tengah'],
            ['name' => 'Tegal', 'province' => 'Jawa Tengah'],
            ['name' => 'Pekalongan', 'province' => 'Jawa Tengah'],
            ['name' => 'Kediri', 'province' => 'Jawa Timur'],
            ['name' => 'Blitar', 'province' => 'Jawa Timur'],
            ['name' => 'Probolinggo', 'province' => 'Jawa Timur'],
            ['name' => 'Jember', 'province' => 'Jawa Timur'],
            ['name' => 'Banyuwangi', 'province' => 'Jawa Timur'],
        ];

        foreach ($cities as $cityData) {
            City::firstOrCreate(
                ['name' => $cityData['name']],
                $cityData
            );
        }

        // Create basic terminals for existing cities only
        $cimahi = City::where('name', 'Kota Cimahi')->first();
        $bandaAceh = City::where('name', 'Banda Aceh')->first();
        $jakarta = City::where('name', 'Jakarta')->first();
        $bandung = City::where('name', 'Bandung')->first();
        $surabaya = City::where('name', 'Surabaya')->first();

        $basicTerminals = [
            ['city_id' => $cimahi->id, 'name' => 'Terminal Cimahi', 'address' => 'Jl. Terminal Cimahi'],
            ['city_id' => $bandaAceh->id, 'name' => 'Terminal Batoh', 'address' => 'Jl. Terminal Batoh'],
            ['city_id' => $jakarta->id, 'name' => 'Terminal Kampung Rambutan', 'address' => 'Jl. Raya Bogor KM 23'],
            ['city_id' => $bandung->id, 'name' => 'Terminal Leuwi Panjang', 'address' => 'Jl. Soekarno Hatta'],
            ['city_id' => $surabaya->id, 'name' => 'Terminal Purabaya', 'address' => 'Jl. Ir. H. Juanda'],
        ];

        foreach ($basicTerminals as $terminalData) {
            Terminal::firstOrCreate(
                ['name' => $terminalData['name'], 'city_id' => $terminalData['city_id']],
                $terminalData
            );
        }

        $this->command->info('✅ Cities and basic terminals seeded successfully');
        $this->command->info('📍 Total cities: ' . City::count());
    }
}
