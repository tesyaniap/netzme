<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string("trx_code")->unique();
            $table->foreignId("mitra_id")->constrained('mitra');
            $table->foreignId("user_id")->constrained('users');
            $table->string("provider_code");
            $table->string('route');
            $table->date('travel_date');
            $table->enum('payment_type', ['deposit', 'direct']);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'issued', 'failed']);
            $table->json('provider_response');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
