<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kita bikin tabel baru
        Schema::create('user_telegram', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Ini nyambung ke tabel users
            $table->string('telegram_chat_id');    // Ini ID Chat Telegram
            $table->timestamps();

            // Sambungkan ke tabel users (Foreign Key)
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_telegram');
    }
};
