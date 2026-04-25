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
        Schema::table('chambers_monitor', function (Blueprint $table) {
            $table->boolean('is_online')->default(true)->after('notes')->comment('设备在线状态');
            $table->timestamp('last_heartbeat_at')->nullable()->after('is_online')->comment('最后心跳时间');
        });
    }

    public function down(): void
    {
        Schema::table('chambers_monitor', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'last_heartbeat_at']);
        });
    }
};
