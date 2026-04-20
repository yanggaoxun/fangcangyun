<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dev_devices', function (Blueprint $table) {
            $table->foreignId('base_id')
                ->nullable()
                ->after('chamber_id')
                ->constrained('chambers_bases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dev_devices', function (Blueprint $table) {
            $table->dropForeign(['base_id']);
            $table->dropColumn('base_id');
        });
    }
};
