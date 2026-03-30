<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 将设备状态字段添加到 environment_data 表
        Schema::table('environment_data', function (Blueprint $table) {
            $table->boolean('inner_circulation')->default(false)->comment('内循环')->after('notes');
            $table->boolean('cooling')->default(false)->comment('制冷')->after('inner_circulation');
            $table->boolean('heating')->default(false)->comment('加热')->after('cooling');
            $table->boolean('fan')->default(false)->comment('风机')->after('heating');
            $table->boolean('four_way_valve')->default(false)->comment('四通阀')->after('fan');
            $table->boolean('fresh_air')->default(false)->comment('新风')->after('four_way_valve');
            $table->boolean('humidification')->default(false)->comment('加湿')->after('fresh_air');
            $table->boolean('lighting_supplement')->default(false)->comment('补光')->after('humidification');
            $table->boolean('lighting')->default(false)->comment('光照')->after('lighting_supplement');

            // 设定值字段
            $table->decimal('temperature_setting', 5, 2)->nullable()->comment('温度设定值')->after('lighting');
            $table->decimal('humidity_setting', 5, 2)->nullable()->comment('湿度设定值')->after('temperature_setting');
            $table->integer('light_intensity_setting')->nullable()->comment('光照强度设定值')->after('humidity_setting');
        });

        // 2. 将 chamber_device_statuses 表的数据迁移到 environment_data 表
        // 注意：这里假设每个 chamber 最新的 environment_data 记录会关联设备状态
        // 实际数据迁移需要根据业务逻辑调整

        // 3. 删除 chamber_device_statuses 表
        Schema::dropIfExists('chamber_device_statuses');
    }

    public function down(): void
    {
        // 恢复 chamber_device_statuses 表
        Schema::create('chamber_device_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained('chambers')->onDelete('cascade');
            $table->boolean('inner_circulation')->default(false);
            $table->boolean('cooling')->default(false);
            $table->boolean('heating')->default(false);
            $table->boolean('fan')->default(false);
            $table->boolean('four_way_valve')->default(false);
            $table->boolean('fresh_air')->default(false);
            $table->boolean('humidification')->default(false);
            $table->boolean('lighting_supplement')->default(false);
            $table->boolean('lighting')->default(false);
            $table->decimal('temperature_setting', 5, 2)->nullable();
            $table->decimal('humidity_setting', 5, 2)->nullable();
            $table->integer('light_intensity_setting')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->string('last_control_source', 50)->default('system');
            $table->timestamps();
            $table->unique('chamber_id');
        });

        // 移除 environment_data 表的设备字段
        Schema::table('environment_data', function (Blueprint $table) {
            $table->dropColumn([
                'inner_circulation',
                'cooling',
                'heating',
                'fan',
                'four_way_valve',
                'fresh_air',
                'humidification',
                'lighting_supplement',
                'lighting',
                'temperature_setting',
                'humidity_setting',
                'light_intensity_setting',
            ]);
        });
    }
};
