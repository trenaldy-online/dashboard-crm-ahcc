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