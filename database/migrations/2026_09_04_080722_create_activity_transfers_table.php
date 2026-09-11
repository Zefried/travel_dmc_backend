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
        Schema::create('activity_transfers', function (Blueprint $table) {
         $table->id();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('transfer_type');

            $table->decimal('transfer_duration', 5, 2);
            $table->string('transfer_duration_unit')->default('minutes');

            $table->decimal('transfer_price', 10, 2);

            $table->string('pickup_type')->nullable();
            $table->text('pickup_description')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_transfers');
    }
};
