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
        Schema::create('chambers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->integer('capacity');
            $table->enum('type', ['oyster', 'shiitake', 'enoki', 'other'])->default('oyster');
            $table->enum('status', ['idle', 'planting', 'maintenance'])->default('idle');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->decimal('target_temperature', 5, 2)->nullable();
            $table->decimal('target_humidity', 5, 2)->nullable();
            $table->decimal('target_co2', 8, 2)->nullable();
            $table->decimal('target_ph', 4, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chambers');
    }
};
