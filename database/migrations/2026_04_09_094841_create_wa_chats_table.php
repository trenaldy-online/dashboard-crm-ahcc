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
    Schema::create('wa_chats', function (Blueprint $table) {
        $table->id();
        $table->string('message_id')->unique(); // GEMBOK ANTI GANDA
        $table->string('advisor_number'); // Menyimpan 0822296600
        $table->string('client_number');  // Menyimpan nomor pasien
        $table->string('owner')->nullable(); // Nama pembuat (Trenaldy)
        $table->string('sender'); // 'Saya' atau 'Klien'
        $table->boolean('is_me'); // true atau false
        $table->text('message'); // Isi pesan chat
        $table->dateTime('chat_time'); // Waktu pesan (misal: 14:30)
        $table->timestamps(); // Otomatis mencatat kapan data ini masuk ke sistem
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_chats');
    }
};
