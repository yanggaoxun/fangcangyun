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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['temperature', 'humidity', 'co2', 'ph', 'device_error', 'stock_low', 'growth_anomaly']);
            $table->enum('level', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->decimal('trigger_value', 10, 2)->nullable();
            $table->decimal('threshold_value', 10, 2)->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('acknowledged_at')->nullable();
            $table->text('acknowledgement_note')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->datetime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['chamber_id', 'created_at']);
            $table->index(['is_acknowledged', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
