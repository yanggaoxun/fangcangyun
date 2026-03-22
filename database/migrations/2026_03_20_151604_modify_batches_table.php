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
        Schema::table('batches', function (Blueprint $table) {
            // 删除旧字段
            $table->dropColumn(['stage', 'substrate_quantity', 'status']);

            // 添加菌类数量字段
            $table->integer('strain_quantity')->default(0)->after('strain_id');

            // 修改日期字段为日期时间类型
            $table->dateTime('inoculation_date')->change();
            $table->dateTime('expected_harvest_date')->nullable()->change();
            $table->dateTime('actual_harvest_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            // 恢复旧字段
            $table->enum('stage', ['spawning', 'pinning', 'fruiting', 'harvested'])->default('spawning');
            $table->integer('substrate_quantity')->default(0);
            $table->enum('status', ['active', 'completed', 'failed'])->default('active');

            // 删除新字段
            $table->dropColumn('strain_quantity');

            // 恢复日期字段类型
            $table->date('inoculation_date')->change();
            $table->date('expected_harvest_date')->nullable()->change();
            $table->date('actual_harvest_date')->nullable()->change();
        });
    }
};
