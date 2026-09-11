<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            $table->string('image_url');
            $table->string('image_name');
            $table->string('image_hash', 64)->index();

            $table->unsignedBigInteger('imageable_id')->index();
            $table->string('imageable_type', 100)->index();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};