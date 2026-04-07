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
        Schema::create('chamber_control_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamber_id');
            $table->enum('control_type', ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting', 'inner_circulation'])->comment('控制类型');
            $table->enum('trigger_type', ['auto', 'manual', 'linkage'])->comment('触发类型：自动/手动/联动');
            $table->string('trigger_reason')->nullable()->comment('触发原因描述');
            $table->string('action', 50)->comment('执行动作：turn_on / turn_off');
            $table->json('sensor_data')->nullable()->comment('当时的传感器数据');
            $table->json('config_snapshot')->nullable()->comment('当时的配置快照');
            $table->timestamp('executed_at')->comment('执行时间');
            $table->unsignedBigInteger('executed_by')->nullable()->comment('操作人（手动时）');
            $table->timestamps();

            // 索引
            $table->index(['chamber_id', 'executed_at']);
            $table->index(['chamber_id', 'control_type', 'executed_at']);

            $table->foreign('chamber_id')->references('id')->on('chambers')->onDelete('cascade');
            $table->foreign('executed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamber_control_logs');
    }
};
