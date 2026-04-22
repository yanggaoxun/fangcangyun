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
        Schema::table('chambers_control_logs', function (Blueprint $table) {
            $table->string('command_id', 64)->nullable()->after('action')->comment('MQTT 命令 ID');
            $table->string('ack_status', 20)->nullable()->after('command_id')->comment('ACK 状态：pending/success/failed/timeout');
            $table->timestamp('ack_at')->nullable()->after('ack_status')->comment('ACK 接收时间');
            $table->index('command_id');
            $table->index(['chamber_id', 'ack_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chambers_control_logs', function (Blueprint $table) {
            $table->dropColumn(['command_id', 'ack_status', 'ack_at']);
        });
    }
};
