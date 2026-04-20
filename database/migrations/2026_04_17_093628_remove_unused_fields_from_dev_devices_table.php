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
        Schema::table('dev_devices', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'brand',
                'model',
                'specifications',
                'is_automated',
                'last_maintenance_at',
                'installed_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dev_devices', function (Blueprint $table) {
            $table->enum('type', ['air_conditioner', 'humidifier', 'dehumidifier', 'ventilation', 'led_light', 'sprinkler', 'co2_generator', 'sensor'])->after('name');
            $table->string('brand')->nullable()->after('status');
            $table->string('model')->nullable()->after('brand');
            $table->json('specifications')->nullable()->after('serial_number');
            $table->boolean('is_automated')->default(true)->after('specifications');
            $table->dateTime('last_maintenance_at')->nullable()->after('is_automated');
            $table->dateTime('installed_at')->nullable()->after('last_maintenance_at');
        });
    }
};
