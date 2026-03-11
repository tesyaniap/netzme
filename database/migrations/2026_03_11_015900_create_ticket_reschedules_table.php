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
        Schema::create('ticket_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('old_schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('new_schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('old_seat_id')->constrained('seats')->onDelete('cascade');
            $table->foreignId('new_seat_id')->constrained('seats')->onDelete('cascade');
            $table->decimal('reschedule_fee', 10, 2)->default(0);
            $table->timestamp('rescheduled_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_reschedules');
    }
};
