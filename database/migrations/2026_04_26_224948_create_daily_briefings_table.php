<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('daily_briefings', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal_briefing');
        $table->text('narasi_teks'); // Teks laporan yang akan muncul di UI & dikirim ke WA
        $table->json('data_mentah')->nullable(); // Menyimpan rekap angka aslinya
        $table->boolean('is_wa_sent')->default(false); // TEKNOLOGI PERSIAPAN WA: Penanda apakah sudah dikirim ke WA atau belum
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_briefings');
    }
};
