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
        Schema::create('farms', function (Blueprint $table) {
            $table->id('farm_id');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('peternak_id')->nullable();
            $table->string('farm_name');
            $table->string('location')->nullable();
            $table->integer('initial_population')->nullable();
            $table->decimal('initial_weight', 10, 2)->nullable();
            $table->decimal('farm_area', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('owner_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('peternak_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
