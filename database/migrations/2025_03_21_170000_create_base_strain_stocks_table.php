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
        Schema::create('base_strain_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_id')->constrained('bases')->onDelete('cascade');
            $table->foreignId('strain_id')->constrained('mushroom_strains')->onDelete('cascade');
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0); // 预留/锁定数量
            $table->timestamps();

            // 每个基地的每种菌种唯一
            $table->unique(['base_id', 'strain_id']);
            $table->index(['strain_id', 'stock_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_strain_stocks');
    }
};
