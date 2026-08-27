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
       Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable()->index();
            $table->string('type')->nullable();
            $table->unsignedTinyInteger('star_rating')->nullable();  

            $table->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('state_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('description')->nullable();

            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('phone')->nullable()->unique();
            $table->string('alternative_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->string('status')
                ->nullable()
                ->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
