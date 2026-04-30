<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadSummary extends Model
{
    use HasFactory;

    // WAJIB: Mendaftarkan kolom yang diizinkan untuk diisi (Mass Assignment)
    protected $fillable = [
        'client_number',
        'kategori_kanker',
        'ringkasan',
        'pipeline_status',
        'is_human_validated',
        'perlu_follow_up',
        'alasan_follow_up',
        'topik_follow_up',
        // --- DATA INTELIJEN JARVIS ---
        'lead_score',
        'minat_treatment',
        'metode_bayar',
        'profil_pengirim',
        'status_medis',
        'sentimen_emosi',
        'kendala_utama',
        'gclid',
        'fbclid',
        'follow_up_count',
        'tunda_sampai_tanggal',
        'google_sheet_sent_at', // <-- Kolom baru sudah masuk
    ];

    // OPSIONAL TAPI DIREKOMENDASIKAN: Memastikan tipe data konsisten
    protected $casts = [
        'is_human_validated'   => 'boolean',
        'perlu_follow_up'      => 'boolean',
        'google_sheet_sent_at' => 'datetime', // <-- Cast jadi datetime
    ];

    // Relasi ke tabel WaChat jika suatu saat dibutuhkan
    public function chats()
    {
        return $this->hasMany(WaChat::class, 'client_number', 'client_number');
    }

    protected static function booted()
    {
        // 1. BUNGKUS FUNGSI PENGIRIMAN
        $sendToGoogleSheet = function ($lead) {
            $webhookUrl = 'https://script.google.com/macros/s/AKfycbx8xwnHaAHUySysIBo4xl8gwfFjMXgHGffvyHH1QnahIglf_T8L-CKLXQTBo2aMl1lkRw/exec'; 
            
            $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $lead->client_number);
            $conversionTime = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:sP');

            try {
                // Tembak API Google
                Http::post($webhookUrl, [
                    'gclid'           => $lead->gclid,
                    'client_number'   => $cleanPhoneNumber,
                    'kategori_kanker' => $lead->kategori_kanker ?? 'Belum Terdeteksi',
                    'conversion_time' => $conversionTime
                ]);
            } catch (\Exception $e) {
                // Jangan buat aplikasi crash jika Google gagal, cukup catat lognya
                Log::error("Gagal mengirim GCLID untuk Pasien {$lead->client_number}: " . $e->getMessage());
            }
        };

        // 2. SENSOR TUNGGAL UNTUK SEMUA PERUBAHAN (BARU MAUPUN LAMA)
        static::saved(function ($lead) use ($sendToGoogleSheet) {
            
            // Cek 3 Syarat Utama:
            // 1. Status harus konsultasi
            // 2. GCLID tidak boleh kosong
            // 3. Kolom google_sheet_sent_at HARUS masih kosong (belum pernah dikirim)
            if (
                $lead->pipeline_status === 'konsultasi' && 
                !empty($lead->gclid) && 
                is_null($lead->google_sheet_sent_at)
            ) {
                
                // Eksekusi pengiriman webhook
                $sendToGoogleSheet($lead);

                // Kunci dengan tanggal agar tidak terjadi duplikasi pengiriman
                // Gunakan saveQuietly() agar event 'saved' tidak berulang dan menyebabkan Infinite Loop
                $lead->forceFill([
                    'google_sheet_sent_at' => now()
                ])->saveQuietly();
            }
        });
    }
}