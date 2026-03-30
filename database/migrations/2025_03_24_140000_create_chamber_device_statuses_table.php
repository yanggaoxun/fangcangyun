<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamber_device_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained('chambers')->onDelete('cascade');

            // 设备开关状态
            $table->boolean('inner_circulation')->default(false)->comment('内循环');
            $table->boolean('cooling')->default(false)->comment('制冷');
            $table->boolean('heating')->default(false)->comment('加热');
            $table->boolean('fan')->default(false)->comment('风机');
            $table->boolean('four_way_valve')->default(false)->comment('四通阀');
            $table->boolean('fresh_air')->default(false)->comment('新风');
            $table->boolean('humidification')->default(false)->comment('加湿');
            $table->boolean('lighting_supplement')->default(false)->comment('补光');
            $table->boolean('lighting')->default(false)->comment('光照');

            // 设备数值（可选）
            $table->decimal('temperature_setting', 5, 2)->nullable()->comment('温度设定值');
            $table->decimal('humidity_setting', 5, 2)->nullable()->comment('湿度设定值');
            $table->integer('light_intensity_setting')->nullable()->comment('光照强度设定值');

            $table->timestamp('last_updated_at')->nullable()->comment('最后更新时间');
            $table->string('last_control_source', 50)->default('system')->comment('最后控制来源:system,api,manual');
            $table->timestamps();

            $table->unique('chamber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamber_device_statuses');
    }
};
