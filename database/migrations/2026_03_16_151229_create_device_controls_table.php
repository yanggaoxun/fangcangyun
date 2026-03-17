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
        Schema::create('device_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action', ['on', 'off', 'adjust', 'configure']);
            $table->json('parameters')->nullable();
            $table->enum('source', ['manual', 'automatic', 'scheduled', 'api'])->default('manual');
            $table->enum('status', ['pending', 'executed', 'failed'])->default('pending');
            $table->datetime('executed_at')->nullable();
            $table->text('result')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
            $table->index(['status', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_controls');
    }
};
