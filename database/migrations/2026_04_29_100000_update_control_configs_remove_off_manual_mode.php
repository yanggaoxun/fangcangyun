<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 先将 off 和 manual 模式的记录转换为 auto_schedule
        DB::table('chambers_control_configs')
            ->whereIn('mode', ['off', 'manual'])
            ->update([
                'mode' => 'auto_schedule',
                'is_enabled' => false,
            ]);

        // 2. 修改 ENUM 定义（MySQL 直接修改）
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chambers_control_configs MODIFY COLUMN mode ENUM('auto_cycle', 'auto_threshold', 'auto_schedule') DEFAULT 'auto_schedule' COMMENT '模式：自动循环/自动阈值/自动时段'");
        } else {
            // SQLite 不支持修改 ENUM，需要重建表
            // 这里简化为修改列类型
            Schema::table('chambers_control_configs', function (Blueprint $table) {
                $table->string('mode', 50)->default('auto_schedule')->comment('模式：自动循环/自动阈值/自动时段')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chambers_control_configs MODIFY COLUMN mode ENUM('off', 'manual', 'auto_cycle', 'auto_threshold', 'auto_schedule') DEFAULT 'off' COMMENT '模式：关闭/手动/自动循环/自动阈值/自动时段'");
        } else {
            Schema::table('chambers_control_configs', function (Blueprint $table) {
                $table->string('mode', 50)->default('off')->comment('模式：关闭/手动/自动循环/自动阈值/自动时段')->change();
            });
        }
    }
};
