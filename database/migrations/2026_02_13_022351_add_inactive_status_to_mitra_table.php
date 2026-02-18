<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah enum status untuk menambah 'inactive'
        DB::statement("ALTER TABLE mitra MODIFY COLUMN status ENUM('pending', 'active', 'rejected', 'inactive') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum lama
        DB::statement("ALTER TABLE mitra MODIFY COLUMN status ENUM('pending', 'active', 'rejected') DEFAULT 'pending'");
    }
};
