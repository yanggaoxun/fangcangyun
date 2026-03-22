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
        Schema::table('mushroom_strains', function (Blueprint $table) {
            $table->integer('growth_cycle')->nullable()->comment('成长周期（天）')->after('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mushroom_strains', function (Blueprint $table) {
            $table->dropColumn('growth_cycle');
        });
    }
};
