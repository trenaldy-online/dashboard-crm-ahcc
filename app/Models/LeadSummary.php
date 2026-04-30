<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

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
    ];

    // OPSIONAL TAPI DIREKOMENDASIKAN: Memastikan tipe data konsisten
    protected $casts = [
        'is_human_validated' => 'boolean',
        'perlu_follow_up'    => 'boolean',
    ];

    // (Opsional) Relasi ke tabel WaChat jika suatu saat dibutuhkan
    public function chats()
    {
        return $this->hasMany(WaChat::class, 'client_number', 'client_number');
    }

    protected static function booted()
    {
        // 1. BUNGKUS FUNGSI PENGIRIMAN AGAR BISA DIPAKAI BERULANG
        $sendToGoogleSheet = function ($lead) {
            // PASTE URL APPS SCRIPT ANDA DI SINI
            $webhookUrl = 'https://script.google.com/macros/s/AKfycbx8xwnHaAHUySysIBo4xl8gwfFjMXgHGffvyHH1QnahIglf_T8L-CKLXQTBo2aMl1lkRw/exec'; 
            
            $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $lead->client_number);
            $conversionTime = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:sP');

            \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'gclid'           => $lead->gclid,
                'client_number'   => $cleanPhoneNumber,
                'kategori_kanker' => $lead->kategori_kanker ?? 'Belum Terdeteksi',
                'conversion_time' => $conversionTime
            ]);
        };

        // 2. SENSOR UNTUK DATA BARU (Hasil Ekstrak H.A.N.A)
        static::created(function ($lead) use ($sendToGoogleSheet) {
            // Jika data baru langsung masuk ke konsultasi dan punya GCLID
            if ($lead->pipeline_status === 'konsultasi' && !empty($lead->gclid)) {
                $sendToGoogleSheet($lead);
            }
        });

        // 3. SENSOR UNTUK DATA LAMA (Geser Kartu Manual)
        static::updated(function ($lead) use ($sendToGoogleSheet) {
            // Jika status BERUBAH menjadi konsultasi dan punya GCLID
            if ($lead->isDirty('pipeline_status') && 
                $lead->pipeline_status === 'konsultasi' && 
                !empty($lead->gclid)) {
                $sendToGoogleSheet($lead);
            }
        });
    }
}