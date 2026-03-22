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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'system_admin', 'base_admin'])->default('base_admin')->comment('角色：超级管理员、系统管理员、基地管理员');
            $table->foreignId('base_id')->nullable()->comment('所属基地，基地管理员使用')->constrained('bases')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['base_id']);
            $table->dropColumn(['role', 'base_id']);
        });
    }
};
