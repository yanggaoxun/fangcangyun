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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('chamber_id')->constrained()->onDelete('cascade');
            $table->foreignId('strain_id')->constrained('mushroom_strains')->onDelete('cascade');
            $table->date('inoculation_date');
            $table->date('expected_harvest_date')->nullable();
            $table->date('actual_harvest_date')->nullable();
            $table->enum('stage', ['spawning', 'pinning', 'fruiting', 'harvested'])->default('spawning');
            $table->integer('substrate_quantity')->default(0);
            $table->decimal('expected_yield', 8, 2)->default(0);
            $table->decimal('actual_yield', 8, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'failed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
