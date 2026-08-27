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
       Schema::create('room_configurations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('meal_code', 10)->nullable()->index();

            $table->decimal('extra_price', 10, 2)->nullable();

            $table->string('status', 50)
                ->default('active');

            $table->timestamps();

            $table->index('room_type_id');
            $table->index('type');
            $table->index('name');
            $table->index('status');

            $table->unique(['room_type_id', 'type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_configurations');
    }
};
