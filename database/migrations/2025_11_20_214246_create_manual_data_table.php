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
        Schema::create('manual_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farm_id');
            $table->unsignedBigInteger('user_id_input');
            $table->date('report_date');
            $table->decimal('konsumsi_pakan', 10, 2)->nullable();
            $table->decimal('konsumsi_air', 10, 2)->nullable();
            $table->decimal('rata_rata_bobot', 10, 2)->nullable();
            $table->integer('jumlah_kematian')->default(0);
            $table->timestamps();

            $table->foreign('farm_id')->references('farm_id')->on('farms')->onDelete('cascade');
            $table->foreign('user_id_input')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['farm_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_data');
    }
};
