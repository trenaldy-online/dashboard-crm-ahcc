<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\WaChat; // Memanggil model database

class WaChatController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        
        $owner = $data['owner'] ?? 'Unknown';
        $advisorNum = $data['advisor_number'] ?? 'Unknown Advisor';
        $clientNum = $data['client_number'] ?? 'Unknown Client';

        $insertedCount = 0;
        
        if (isset($data['chats']) && is_array($data['chats'])) {
            foreach ($data['chats'] as $chat) {
                
                // --- JARING PENGAMAN WAKTU (SUDAH DIREVISI) ---
                $waktuChat = now('Asia/Jakarta'); // Default ke waktu Indonesia (WIB)
                try {
                    // Jika jam/tanggal dari Chrome valid, bersihkan dan gunakan
                    if (isset($chat['time']) && $chat['time'] !== "00:00") {
                        
                        // 1. Usir "Karakter Gaib" (Invisible Chars) bawaan WhatsApp
                        // Hanya menyisakan huruf, angka, dan spasi yang normal
                        $cleanTime = preg_replace('/[^\x20-\x7E]/', '', $chat['time']);
                        
                        // 2. Hapus tanda koma yang suka bikin Carbon bingung
                        $cleanTime = str_replace(',', '', $cleanTime);
                        $cleanTime = trim($cleanTime);
                        
                        // 3. Ubah teks yang sudah bersih menjadi Objek Waktu di zona WIB
                        $waktuChat = Carbon::parse($cleanTime, 'Asia/Jakarta');
                    }
                } catch (\Exception $e) {
                    // Jika Carbon masih gagal membaca formatnya, abaikan errornya
                    // dan biarkan $waktuChat tetap menggunakan now('Asia/Jakarta')
                }
                // ----------------------------------------------

                $chatRecord = WaChat::updateOrCreate(
                    ['message_id' => $chat['message_id']], // ID Unik Anti-Ganda
                    [
                        'advisor_number' => $advisorNum,
                        'client_number'  => $clientNum,
                        'owner'          => $owner,
                        'sender'         => $chat['sender'],
                        'is_me'          => $chat['isMe'],
                        'message'        => $chat['text'],
                        'chat_time'      => $waktuChat, // Masukkan waktu yang sudah super bersih
                    ]
                );

                // Cek apakah ini pesan baru atau pesan lama yang diabaikan
                if ($chatRecord->wasRecentlyCreated) {
                    $insertedCount++;
                }
            }
        }

        // Response ini hanya dipanggil SATU KALI di akhir fungsi
        return response()->json([
            'status' => 'success',
            'message' => "$insertedCount pesan BARU berhasil ditambahkan (pesan lama diabaikan)."
        ], 200);
    }
}