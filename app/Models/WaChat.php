<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WaChat extends Model
{
    // Daftar kolom yang boleh diisi
    protected $fillable = [
        'message_id', 'advisor_number', 'client_number', 
        'owner', 'sender', 'is_me', 'message', 'chat_time'
    ];

    protected $casts = [
        'chat_time' => 'datetime',
    ];

    // =========================================================================
    // SENSOR TRACKING AUTO FOLLOW-UP (DIJALANKAN OTOMATIS SAAT ADA CHAT BARU)
    // =========================================================================
    protected static function booted()
    {
        static::created(function ($chat) {
            // Cari data pasien di tabel lead_summaries berdasarkan nomor WA
            $lead = \App\Models\LeadSummary::where('client_number', $chat->client_number)->first();
            
            if ($lead) {
                // Tentukan waktu chat (gunakan now() sebagai fallback jika chat_time kosong)
                $waktuChat = $chat->chat_time ?? now('Asia/Jakarta');

                if ($chat->is_me == 1) {
                    // JIKA CS YANG MENGIRIM PESAN
                    $lead->last_cs_reply_at = $waktuChat;
                } else {
                    // JIKA PASIEN YANG MENGIRIM PESAN
                    $lead->last_patient_reply_at = $waktuChat;
                    // Reset step follow up ke 0 karena pasien sudah merespons (tidak ghosting lagi)
                    $lead->follow_up_step = 0; 
                }

                // SANGAT PENTING: Gunakan saveQuietly() bukan save() biasa.
                // Ini mencegah terpancingnya Event 'saved' di LeadSummary yang bisa memicu
                // pengiriman ulang webhook ke Google Sheets tanpa alasan.
                $lead->saveQuietly(); 
            }
        });
    }

    // --- TAMBAHKAN KODE INI ---
    // Logika untuk membuat tulisan Pembatas Tanggal
    public function getTanggalPembatasAttribute()
    {
        $date = $this->chat_time;
        $now = Carbon::now('Asia/Jakarta');

        // Jika chat terjadi hari ini
        if ($date->isToday()) {
            return 'Hari ini';
        }
        
        // Jika chat terjadi kemarin
        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        // Jika chat terjadi dalam 6 hari ke belakang (Masih minggu yang sama)
        if ($now->diffInDays($date) < 7) {
            // Ubah bahasa ke Indonesia (id) lalu ambil nama harinya saja ('l')
            return $date->locale('id')->translatedFormat('l'); 
        }

        // Jika sudah lebih dari 1 minggu (Tampilkan tanggalnya saja)
        return $date->format('d/m/Y'); 
    }
    // ---------------------------
}