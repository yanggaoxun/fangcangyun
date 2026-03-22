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
        Schema::create('mushroom_strains', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            //$table->string('name');
            $table->string('scientific_name')->nullable();
            $table->enum('type', ['oyster', 'shiitake', 'enoki', 'other']);
            $table->string('supplier')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('unit')->default('bag');
            $table->text('description')->nullable();
            $table->json('storage_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mushroom_strains');
    }
};
