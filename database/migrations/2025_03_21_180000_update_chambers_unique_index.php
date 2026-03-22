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
        // 移除 code 的全局唯一索引
        Schema::table('chambers', function (Blueprint $table) {
            $table->dropUnique('chambers_code_unique');
        });

        // 添加联合唯一索引：同一基地内 code 唯一
        Schema::table('chambers', function (Blueprint $table) {
            $table->unique(['base_id', 'code'], 'chambers_base_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chambers', function (Blueprint $table) {
            $table->dropUnique('chambers_base_code_unique');
            $table->unique('code', 'chambers_code_unique');
        });
    }
};
