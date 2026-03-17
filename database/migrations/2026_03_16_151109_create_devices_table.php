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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['air_conditioner', 'humidifier', 'dehumidifier', 'ventilation', 'led_light', 'sprinkler', 'co2_generator', 'sensor']);
            $table->enum('status', ['active', 'inactive', 'maintenance', 'error'])->default('active');
            $table->foreignId('chamber_id')->constrained()->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->json('specifications')->nullable();
            $table->boolean('is_automated')->default(true);
            $table->datetime('last_maintenance_at')->nullable();
            $table->datetime('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
