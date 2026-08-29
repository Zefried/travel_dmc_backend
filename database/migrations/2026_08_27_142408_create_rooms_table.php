<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('room_no', 100)->index();

            $table->string('status')
                ->index()
                ->default('active');

            $table->timestamps();

            $table->unique(['room_type_id', 'room_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};