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
        Schema::create('chamber_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamber_id');
            $table->enum('control_type', ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting'])->comment('控制类型');
            $table->tinyInteger('schedule_index')->comment('时间段序号(1-3)');
            $table->boolean('is_enabled')->default(true)->comment('该时段是否启用');
            $table->time('start_time')->comment('开始时间');
            $table->time('end_time')->comment('结束时间');

            // 温度设置参数
            $table->decimal('temp_cooling_upper', 5, 2)->nullable()->comment('温度-制冷上限');
            $table->decimal('temp_cooling_lower', 5, 2)->nullable()->comment('温度-制冷下限');
            $table->decimal('temp_heating_upper', 5, 2)->nullable()->comment('温度-加热上限');
            $table->decimal('temp_heating_lower', 5, 2)->nullable()->comment('温度-加热下限');

            // 湿度设置参数
            $table->decimal('humidity_upper', 5, 2)->nullable()->comment('湿度上限');
            $table->decimal('humidity_lower', 5, 2)->nullable()->comment('湿度下限');

            // CO2设置参数（新风/排风）
            $table->integer('co2_upper')->nullable()->comment('CO2上限(ppm)');
            $table->integer('co2_lower')->nullable()->comment('CO2下限(ppm)');

            // 启停循环参数
            $table->integer('cycle_run_minutes')->nullable()->comment('循环运行分钟');
            $table->integer('cycle_stop_minutes')->nullable()->comment('循环停止分钟');

            $table->timestamps();

            // 索引
            $table->unique(['chamber_id', 'control_type', 'schedule_index']);
            $table->index(['chamber_id', 'control_type']);

            $table->foreign('chamber_id')->references('id')->on('chambers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamber_schedules');
    }
};
