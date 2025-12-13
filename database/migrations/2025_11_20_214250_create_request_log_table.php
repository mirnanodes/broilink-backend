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
        Schema::create('request_log', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('sender_name')->nullable();
            $table->string('request_type', 100);
            $table->text('request_content');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('sent_time')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_log');
    }
};
