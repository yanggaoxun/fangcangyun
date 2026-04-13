<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('chambers_environment_data', 'chambers_monitor');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('chambers_monitor', 'chambers_environment_data');
    }
};
