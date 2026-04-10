<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Vehicle;
use App\Models\Route;
use App\Models\City;
use App\Models\Terminal;
use App\Models\Seat;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample cities if they don't exist
        $jakarta = City::firstOrCreate(['name' => 'Jakarta', 'code' => 'JKT'], ['province' => 'DKI Jakarta']);
        $bandung = City::firstOrCreate(['name' => 'Bandung', 'code' => 'BDG'], ['province' => 'Jawa Barat']);
        $surabaya = City::firstOrCreate(['name' => 'Surabaya', 'code' => 'SBY'], ['province' => 'Jawa Timur']);

        // Create sample terminals
        $terminalJakarta = Terminal::firstOrCreate([
            'city_id' => $jakarta->id,
            'name' => 'Terminal Kampung Rambutan',
            'code' => 'KR'
        ], [
            'address' => 'Jl. Raya Bogor KM 20, Jakarta Timur',
            'latitude' => -6.3095,
            'longitude' => 106.8748
        ]);

        $terminalBandung = Terminal::firstOrCreate([
            'city_id' => $bandung->id,
            'name' => 'Terminal Leuwi Panjang',
            'code' => 'LP'
        ], [
            'address' => 'Jl. A.H. Nasution, Bandung',
            'latitude' => -6.9389,
            'longitude' => 107.7186
        ]);

        $terminalSurabaya = Terminal::firstOrCreate([
            'city_id' => $surabaya->id,
            'name' => 'Terminal Bungurasih',
            'code' => 'BG'
        ], [
            'address' => 'Jl. Raya Waru, Surabaya',
            'latitude' => -7.3447,
            'longitude' => 112.7297
        ]);

        // Create sample routes
        $routeJktBdg = Route::firstOrCreate([
            'origin_terminal_id' => $terminalJakarta->id,
            'destination_terminal_id' => $terminalBandung->id,
        ], [
            'name' => 'Jakarta - Bandung',
            'distance_km' => 150.5,
            'estimated_duration_minutes' => 180,
            'base_price' => 75000
        ]);

        $routeBdgSby = Route::firstOrCreate([
            'origin_terminal_id' => $terminalBandung->id,
            'destination_terminal_id' => $terminalSurabaya->id,
        ], [
            'name' => 'Bandung - Surabaya',
            'distance_km' => 450.0,
            'estimated_duration_minutes' => 480,
            'base_price' => 150000
        ]);

        $routeJktSby = Route::firstOrCreate([
            'origin_terminal_id' => $terminalJakarta->id,
            'destination_terminal_id' => $terminalSurabaya->id,
        ], [
            'name' => 'Jakarta - Surabaya',
            'distance_km' => 600.0,
            'estimated_duration_minutes' => 660,
            'base_price' => 200000
        ]);

        // Create sample vehicles
        $vehicle1 = Vehicle::firstOrCreate([
            'license_plate' => 'B 1234 ABC'
        ], [
            'brand' => 'Mercedes-Benz',
            'model' => 'OH 1626',
            'year' => 2022,
            'seat_capacity' => 40,
            'class' => 'eksekutif',
            'facilities' => json_encode(['AC', 'WiFi', 'TV', 'Reclining Seat']),
            'is_active' => true
        ]);

        $vehicle2 = Vehicle::firstOrCreate([
            'license_plate' => 'B 5678 DEF'
        ], [
            'brand' => 'Hino',
            'model' => 'RK8',
            'year' => 2021,
            'seat_capacity' => 45,
            'class' => 'bisnis',
            'facilities' => json_encode(['AC', 'TV', 'USB Charger']),
            'is_active' => true
        ]);

        $vehicle3 = Vehicle::firstOrCreate([
            'license_plate' => 'B 9012 GHI'
        ], [
            'brand' => 'Isuzu',
            'model' => 'ELF',
            'year' => 2020,
            'seat_capacity' => 30,
            'class' => 'ekonomi',
            'facilities' => json_encode(['AC', 'Music']),
            'is_active' => true
        ]);

        // Generate seats for vehicles
        $this->generateSeatsForVehicle($vehicle1);
        $this->generateSeatsForVehicle($vehicle2);
        $this->generateSeatsForVehicle($vehicle3);

        // Create sample schedules
        $schedules = [
            // Jakarta - Bandung
            [
                'vehicle_id' => $vehicle1->id,
                'route_id' => $routeJktBdg->id,
                'departure_time' => '06:00:00',
                'arrival_time' => '09:00:00',
                'price' => 85000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'), // Tomorrow
            ],
            [
                'vehicle_id' => $vehicle2->id,
                'route_id' => $routeJktBdg->id,
                'departure_time' => '08:00:00',
                'arrival_time' => '11:00:00',
                'price' => 75000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'vehicle_id' => $vehicle3->id,
                'route_id' => $routeJktBdg->id,
                'departure_time' => '10:00:00',
                'arrival_time' => '13:00:00',
                'price' => 65000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            
            // Bandung - Surabaya
            [
                'vehicle_id' => $vehicle1->id,
                'route_id' => $routeBdgSby->id,
                'departure_time' => '14:00:00',
                'arrival_time' => '22:00:00',
                'price' => 175000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'vehicle_id' => $vehicle2->id,
                'route_id' => $routeBdgSby->id,
                'departure_time' => '16:00:00',
                'arrival_time' => '00:00:00',
                'price' => 160000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            
            // Jakarta - Surabaya
            [
                'vehicle_id' => $vehicle1->id,
                'route_id' => $routeJktSby->id,
                'departure_time' => '20:00:00',
                'arrival_time' => '07:00:00',
                'price' => 225000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'vehicle_id' => $vehicle2->id,
                'route_id' => $routeJktSby->id,
                'departure_time' => '22:00:00',
                'arrival_time' => '09:00:00',
                'price' => 210000,
                'travel_date' => now()->addDays(1)->format('Y-m-d'),
            ],

            // Template schedules (travel_date = null) - Daily recurring
            [
                'vehicle_id' => $vehicle1->id,
                'route_id' => $routeJktBdg->id,
                'departure_time' => '07:00:00',
                'arrival_time' => '10:00:00',
                'price' => 85000,
                'travel_date' => null, // Template schedule
            ],
            [
                'vehicle_id' => $vehicle2->id,
                'route_id' => $routeJktBdg->id,
                'departure_time' => '15:00:00',
                'arrival_time' => '18:00:00',
                'price' => 75000,
                'travel_date' => null, // Template schedule
            ],
            [
                'vehicle_id' => $vehicle3->id,
                'route_id' => $routeJktSby->id,
                'departure_time' => '18:00:00',
                'arrival_time' => '05:00:00',
                'price' => 195000,
                'travel_date' => null, // Template schedule
            ],
        ];

        foreach ($schedules as $scheduleData) {
            Schedule::firstOrCreate([
                'vehicle_id' => $scheduleData['vehicle_id'],
                'route_id' => $scheduleData['route_id'],
                'departure_time' => $scheduleData['departure_time'],
                'travel_date' => $scheduleData['travel_date'],
            ], $scheduleData);
        }

        $this->command->info('Sample schedules created successfully!');
    }

    private function generateSeatsForVehicle($vehicle)
    {
        // Skip if seats already exist
        if ($vehicle->seats()->count() > 0) {
            return;
        }

        $seatLayout = $vehicle->class === 'eksekutif' ? '2-1' : ($vehicle->class === 'bisnis' ? '2-2' : '2-3');
        $seatsPerRow = $seatLayout === '2-1' ? 3 : ($seatLayout === '2-2' ? 4 : 5);
        $rows = ceil($vehicle->seat_capacity / $seatsPerRow);

        $seatNumber = 1;
        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= $seatsPerRow; $col++) {
                if ($seatNumber > $vehicle->seat_capacity) break;

                Seat::create([
                    'vehicle_id' => $vehicle->id,
                    'seat_number' => str_pad($seatNumber, 2, '0', STR_PAD_LEFT),
                    'row' => $row,
                    'column' => $col,
                    'is_available' => true
                ]);

                $seatNumber++;
            }
        }
    }
}