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
        Schema::table('chambers', function (Blueprint $table) {
            $table->dropColumn(['location', 'type', 'target_temperature', 'target_humidity', 'target_co2', 'target_ph']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chambers', function (Blueprint $table) {
            //
        });
    }
};
