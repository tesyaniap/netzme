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
            ['name' => 'Kota Cimahi', 'province' => 'Jawa Barat'],
            ['name' => 'Banda Aceh', 'province' => 'Aceh'],
            ['name' => 'Jakarta', 'province' => 'DKI Jakarta'],
            ['name' => 'Bandung', 'province' => 'Jawa Barat'],
            ['name' => 'Surabaya', 'province' => 'Jawa Timur'],
        ];

        foreach ($cities as $cityData) {
            City::firstOrCreate(
                ['name' => $cityData['name']],
                $cityData
            );
        }

        // Create terminals
        $cimahi = City::where('name', 'Kota Cimahi')->first();
        $bandaAceh = City::where('name', 'Banda Aceh')->first();
        $jakarta = City::where('name', 'Jakarta')->first();
        $bandung = City::where('name', 'Bandung')->first();
        $surabaya = City::where('name', 'Surabaya')->first();

        $terminals = [
            ['city_id' => $cimahi->id, 'name' => 'Terminal Cimahi', 'address' => 'Jl. Terminal Cimahi'],
            ['city_id' => $bandaAceh->id, 'name' => 'Terminal Batoh', 'address' => 'Jl. Terminal Batoh'],
            ['city_id' => $jakarta->id, 'name' => 'Terminal Kampung Rambutan', 'address' => 'Jl. Raya Bogor KM 23'],
            ['city_id' => $bandung->id, 'name' => 'Terminal Leuwi Panjang', 'address' => 'Jl. Soekarno Hatta'],
            ['city_id' => $surabaya->id, 'name' => 'Terminal Purabaya', 'address' => 'Jl. Ir. H. Juanda'],
        ];

        foreach ($terminals as $terminalData) {
            Terminal::firstOrCreate(
                ['name' => $terminalData['name'], 'city_id' => $terminalData['city_id']],
                $terminalData
            );
        }

        $this->command->info('✅ Cities and Terminals seeded successfully');
    }
}
