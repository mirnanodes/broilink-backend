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
        Schema::create('iot_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farm_id');
            $table->timestamp('timestamp')->useCurrent();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('ammonia', 8, 2)->nullable();
            $table->enum('data_source', ['sensor', 'manual', 'system_seed'])->default('sensor');
            $table->timestamps();

            $table->foreign('farm_id')->references('farm_id')->on('farms')->onDelete('cascade');
            $table->index(['farm_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_data');
    }
};
