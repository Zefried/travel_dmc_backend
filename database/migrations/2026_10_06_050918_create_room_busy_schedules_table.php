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
        Schema::create('room_busy_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->onDelete('cascade');
            $table->foreignId('hotel_admin_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_busy_schedules');
    }
};
