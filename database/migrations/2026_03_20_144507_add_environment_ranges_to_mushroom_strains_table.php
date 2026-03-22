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
        Schema::table('mushroom_strains', function (Blueprint $table) {
            $table->decimal('temp_min', 5, 2)->nullable()->comment('最低温度');
            $table->decimal('temp_max', 5, 2)->nullable()->comment('最高温度');
            $table->decimal('humidity_min', 5, 2)->nullable()->comment('最低湿度');
            $table->decimal('humidity_max', 5, 2)->nullable()->comment('最高湿度');
            $table->decimal('co2_min', 8, 2)->nullable()->comment('最低CO2浓度');
            $table->decimal('co2_max', 8, 2)->nullable()->comment('最高CO2浓度');
            $table->decimal('ph_min', 4, 2)->nullable()->comment('最低pH值');
            $table->decimal('ph_max', 4, 2)->nullable()->comment('最高pH值');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mushroom_strains', function (Blueprint $table) {
            $table->dropColumn(['temp_min', 'temp_max', 'humidity_min', 'humidity_max', 'co2_min', 'co2_max', 'ph_min', 'ph_max']);
        });
    }
};
