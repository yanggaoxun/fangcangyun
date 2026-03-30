<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 重命名 environment_data 表为 chamber_environment_data
        Schema::rename('environment_data', 'chamber_environment_data');
    }

    public function down(): void
    {
        Schema::rename('chamber_environment_data', 'environment_data');
    }
};
