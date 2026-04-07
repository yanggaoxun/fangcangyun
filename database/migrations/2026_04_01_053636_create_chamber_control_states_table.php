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
        Schema::create('chamber_control_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chamber_id');
            $table->enum('control_type', ['temperature', 'humidity', 'fresh_air', 'exhaust', 'lighting', 'inner_circulation'])->comment('控制类型');
            $table->boolean('current_state')->default(false)->comment('当前开关状态');
            $table->enum('current_mode', ['off', 'manual', 'auto'])->default('off')->comment('当前模式');
            $table->timestamp('last_switch_at')->nullable()->comment('上次切换时间');
            $table->timestamp('next_switch_at')->nullable()->comment('下次应该切换时间（循环模式）');
            $table->boolean('is_manual_override')->default(false)->comment('是否被手动覆盖');
            $table->timestamp('override_until')->nullable()->comment('手动覆盖到期时间');
            $table->timestamps();

            // 唯一索引
            $table->unique(['chamber_id', 'control_type']);
            $table->index(['chamber_id']);

            $table->foreign('chamber_id')->references('id')->on('chambers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamber_control_states');
    }
};
