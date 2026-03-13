<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndAdminSeeder::class,
            PermissionSeeder::class,
            CitySeeder::class,
            TerminalSeeder::class,
            TopupSeeder::class,
        ]);
    }
}
