<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference')
                ->unique();

            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedInteger('adults');
            $table->unsignedInteger('children')->default(0);

            $table->string('status')
                ->index()
                ->default('pending');

            $table->decimal('total_amount', 10, 2)
                ->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};