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
        Schema::create('bases', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('基地编号');
            $table->string('name')->comment('基地名称');
            $table->string('location')->comment('基地位置');
            $table->string('manager')->nullable()->comment('负责人');
            $table->string('phone')->nullable()->comment('联系电话');
            $table->text('description')->nullable()->comment('基地描述');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->comment('状态');
            $table->decimal('latitude', 10, 8)->nullable()->comment('纬度');
            $table->decimal('longitude', 11, 8)->nullable()->comment('经度');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bases');
    }
};
