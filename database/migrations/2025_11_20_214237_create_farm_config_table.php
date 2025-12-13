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
        Schema::create('farm_config', function (Blueprint $table) {
            $table->id('config_id');
            $table->unsignedBigInteger('farm_id');
            $table->string('parameter_name', 100);
            $table->string('value');
            $table->timestamps();

            $table->foreign('farm_id')->references('farm_id')->on('farms')->onDelete('cascade');
            $table->unique(['farm_id', 'parameter_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_config');
    }
};
