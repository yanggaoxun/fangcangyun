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
        Schema::create('chamber_control_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamber_id');
            $table->enum('control_type', ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting', 'inner_circulation'])->comment('控制类型：温度/湿度/新风/排风/光照/内循环');
            $table->enum('mode', ['off', 'manual', 'auto_cycle', 'auto_threshold', 'auto_schedule'])->default('off')->comment('模式：关闭/手动/自动循环/自动阈值/自动时段');
            $table->boolean('is_enabled')->default(false)->comment('是否启用');

            // 启停循环模式配置
            $table->integer('cycle_run_duration')->nullable()->comment('循环模式运行时长');
            $table->enum('cycle_run_unit', ['seconds', 'minutes'])->default('minutes')->nullable()->comment('循环运行时长单位');
            $table->integer('cycle_stop_duration')->nullable()->comment('循环模式停止时长');
            $table->enum('cycle_stop_unit', ['seconds', 'minutes'])->default('minutes')->nullable()->comment('循环停止时长单位');

            // 上下限模式配置
            $table->decimal('threshold_upper', 8, 2)->nullable()->comment('阈值上限');
            $table->decimal('threshold_lower', 8, 2)->nullable()->comment('阈值下限');
            $table->string('threshold_sensor')->nullable()->comment('阈值关联传感器类型');

            // 联动配置（JSON格式）
            $table->json('linkage_config')->nullable()->comment('联动配置');

            // 延时配置
            $table->integer('delay_seconds')->default(0)->comment('延时启动秒数');

            $table->timestamps();

            // 唯一索引：每个方舱每种控制类型只有一条配置
            $table->unique(['chamber_id', 'control_type']);

            // 外键约束
            $table->foreign('chamber_id')->references('id')->on('chambers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamber_control_configs');
    }
};
