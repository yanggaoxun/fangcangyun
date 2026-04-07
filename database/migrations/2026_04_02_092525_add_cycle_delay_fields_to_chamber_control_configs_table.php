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
        Schema::table('chamber_control_configs', function (Blueprint $table) {
            $table->integer('delay_cooling_heating')->default(0)->after('delay_seconds')->comment('延时制冷/加热(秒)');
            $table->integer('delay_stop_cycle')->default(0)->after('delay_cooling_heating')->comment('延时停止循环(秒)');
            $table->integer('inner_cycle_run')->default(0)->after('delay_stop_cycle')->comment('内循环运行(分钟)');
            $table->integer('inner_cycle_stop')->default(0)->after('inner_cycle_run')->comment('内循环停止(分钟)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamber_control_configs', function (Blueprint $table) {
            $table->dropColumn(['delay_cooling_heating', 'delay_stop_cycle', 'inner_cycle_run', 'inner_cycle_stop']);
        });
    }
};
