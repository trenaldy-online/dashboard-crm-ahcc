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
        Schema::create('lead_summaries', function (Blueprint $table) {
            $table->id();
            // Kita hubungkan dengan nomor WA Klien
            $table->string('client_number')->unique(); 
            
            // Kolom hasil tebakan AI
            $table->string('kategori_kanker')->nullable(); // cth: Payudara, Serviks
            $table->string('minat_layanan')->nullable(); // cth: Radioterapi, Konsultasi
            $table->text('ringkasan')->nullable(); // Paragraf kesimpulan
            
            // Kolom Validasi CS (Human-in-the-loop)
            $table->enum('status_review', ['pending', 'disetujui', 'ditolak'])->default('pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_summaries');
    }
};
