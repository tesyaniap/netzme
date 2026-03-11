<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Tambah field untuk sistem bus
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('set null');
            $table->integer('passenger_count')->default(1);
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('admin_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Update existing columns
            $table->string('route')->nullable()->change();
            $table->json('provider_response')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn([
                'schedule_id',
                'passenger_count',
                'base_price',
                'admin_fee',
                'service_fee',
                'customer_name',
                'customer_phone',
                'customer_email',
                'notes',
                'booked_at',
                'paid_at',
                'issued_at',
                'cancelled_at',
                'updated_at'
            ]);
        });
    }
};