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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')->index();
            $table->string('type')->index();

            $table->unsignedTinyInteger('bedroom')->default(1);

            $table->decimal('size', 8, 2)->nullable();
            $table->string('size_unit')->default('sq_ft');

            $table->unsignedInteger('max_adults');
            $table->unsignedInteger('max_children')->default(0);
            $table->unsignedInteger('max_occupancy');

            $table->string('view')->nullable();

            $table->text('description')->nullable();

            $table->string('default_bed_type')->nullable();
            $table->unsignedInteger('default_bed_quantity')->nullable();

            $table->decimal('base_price', 10, 2)
                ->index()
                ->nullable();

            $table->string('status')
                ->index()
                ->default('active');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
