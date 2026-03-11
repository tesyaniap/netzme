<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Topup;
use App\Models\Mitra;

class TopupSeeder extends Seeder
{
    public function run(): void
    {
        $mitra = Mitra::where('status', 'active')->first();

        if ($mitra) {
            Topup::create([
                'mitra_id' => $mitra->id,
                'amount' => 1000000,
                'status' => 'pending',
                'payment_method' => 'transfer',
                'proof_file' => 'proof_' . time() . '.jpg',
            ]);
        }
    }
}
