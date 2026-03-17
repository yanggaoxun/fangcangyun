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
        Schema::create('environment_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained()->onDelete('cascade');
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2);
            $table->decimal('co2_level', 8, 2);
            $table->decimal('ph_level', 4, 2)->nullable();
            $table->decimal('light_intensity', 8, 2)->nullable();
            $table->decimal('soil_moisture', 5, 2)->nullable();
            $table->datetime('recorded_at');
            $table->boolean('is_anomaly')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['chamber_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_data');
    }
};
