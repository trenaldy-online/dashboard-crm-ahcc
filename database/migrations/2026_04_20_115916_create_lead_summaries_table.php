<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_summaries', function (Blueprint $table) {
            $table->id();
            
            // Nomor klien sebagai penghubung (kunci) dengan tabel wa_chats
            $table->string('client_number')->unique(); 
            
            // Data hasil ekstraksi AI
            $table->string('kategori_kanker')->nullable();
            $table->text('ringkasan')->nullable();
            
            // Kolom penentu di kolom mana kartu pasien ini berada (Kanban)
            $table->enum('pipeline_status', [
                'leads_baru', 
                'edukasi', 
                'konsultasi', 
                'deal', 
                'batal'
            ])->default('leads_baru');
            
            // Kolom penanda: apakah SPV/Manusia sudah memvalidasi kerjaan AI?
            // 0 = Baru dari AI, 1 = Sudah divalidasi/digeser SPV
            $table->boolean('is_human_validated')->default(false); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_summaries');
    }
};