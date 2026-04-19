<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WaChat;
use App\Models\LeadSummary;

class AiSummaryController extends Controller
{
    public function generate($clientNumber)
    {
        // 1. Ambil seluruh riwayat chat klien tersebut, urutkan dari yang terlama
        $chats = WaChat::where('client_number', $clientNumber)
                        ->orderBy('chat_time', 'asc')
                        ->get();

        if ($chats->isEmpty()) {
            return back()->with('error', 'Tidak ada riwayat chat untuk nomor ini.');
        }

        // 2. Rangkai chat menjadi teks dialog
        $dialogText = "";
        foreach ($chats as $chat) {
            $pengirim = $chat->is_me ? "CS AHCC" : "Pasien";
            $dialogText .= "{$pengirim}: {$chat->message}\n";
        }

        // 3. Prompt instruksi yang dipertegas (Adaptasi gaya penulisan Anda)
        $prompt = "Anda adalah pakar Patient Advisor di Rumah Sakit Kanker. 
        Baca riwayat chat berikut secara seksama:
        
        {$dialogText}

        WAJIB kembalikan jawaban HANYA dalam format JSON murni. Struktur key:
        {
            \"kategori_kanker\": \"Tentukan kategori kanker (misal: Payudara, Serviks, Paru, Darah). Jika belum jelas, tulis 'Belum Diketahui'.\",
            \"minat_layanan\": \"Tentukan layanan yang dituju (misal: Radioterapi, Kemoterapi, Operasi, Konsultasi). Jika belum jelas, tulis 'Tanya Info Umum'.\",
            \"ringkasan\": \"Tulis 1-2 kalimat padat tentang keluhan utama pasien dan status percakapan akhirnya.\"
        }";

        try {
            // 4. Menggunakan arsitektur HTTP API yang sudah terbukti stabil
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=' . env('GEMINI_API_KEY'), [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            // 5. Menerima dan Memproses Jawaban AI
            if ($response->successful()) {
                $hasilTeks = $response->json('candidates.0.content.parts.0.text');
                
                // Membersihkan markdown ```json dengan metode str_replace
                $hasilTeks = str_replace(['```json', '```'], '', $hasilTeks);
                $dataJson = json_decode(trim($hasilTeks), true);

                if ($dataJson) {
                    // Simpan ke Brankas Database
                    LeadSummary::updateOrCreate(
                        ['client_number' => $clientNumber],
                        [
                            'kategori_kanker' => $dataJson['kategori_kanker'] ?? 'Tidak terdeteksi',
                            'minat_layanan'   => $dataJson['minat_layanan'] ?? 'Tidak terdeteksi',
                            'ringkasan'       => $dataJson['ringkasan'] ?? 'Gagal membuat ringkasan.',
                            'status_review'   => 'pending' 
                        ]
                    );

                    return back()->with('success', 'AI berhasil menganalisis percakapan!');
                } else {
                    return back()->with('error', 'Gagal memecah JSON dari AI.');
                }
            } else {
                Log::error("Gemini API Error (CRM): " . $response->body());
                return back()->with('error', 'Google API Error. Silakan cek file laravel.log');
            }

        } catch (\Exception $e) {
            Log::error("Job AI Error (CRM): " . $e->getMessage());
            return back()->with('error', 'Koneksi ke server AI gagal: ' . $e->getMessage());
        }
    }

    // --- FUNGSI BARU UNTUK FASE 4 (PEMBUAT DRAF FOLLOW UP) ---
    public function generateFollowUp($clientNumber)
    {
        // 1. Ambil data ringkasan pasien yang sudah disetujui
        $summary = LeadSummary::where('client_number', $clientNumber)
                              ->where('status_review', 'disetujui')
                              ->first();

        if (!$summary) {
            return back()->with('error', 'Pasien ini belum memiliki rekap AI yang disetujui.');
        }

        // 2. Siapkan Prompt untuk AI (Gaya bahasa ramah & empati khas AHCC)
        $prompt = "Anda adalah Patient Advisor di Rumah Sakit Kanker AHCC (Adi Husada Cancer Center).
        Tugas Anda adalah menulis DRAF PESAN WHATSAPP (Follow-Up) untuk menyapa kembali pasien yang belum memberikan keputusan sejak kemarin.
        
        Berikut adalah data pasien tersebut:
        - Keluhan/Kanker: {$summary->kategori_kanker}
        - Minat Layanan: {$summary->minat_layanan}
        - Riwayat Terakhir: {$summary->ringkasan}

        SYARAT PENULISAN PESAN:
        1. Gunakan bahasa Indonesia yang sopan, hangat, dan penuh empati (tidak kaku/seperti robot).
        2. Awali dengan sapaan ramah.
        3. Tanyakan kabar terkait keluhan yang ada di 'Riwayat Terakhir' secara spesifik agar pasien merasa diperhatikan.
        4. Tawarkan bantuan lebih lanjut atau tanyakan apakah ada kendala (misal kendala biaya/jadwal) terkait layanan yang diminati.
        5. Jangan terlalu panjang, maksimal 3 paragraf pendek.

        WAJIB kembalikan jawaban HANYA dalam format JSON murni. Struktur key:
        {
            \"draf_pesan\": \"Tulis draf pesan WhatsApp di sini...\"
        }";

        try {
            // 3. Tembak ke API Gemini (Boleh pakai 2.5 Pro atau 3 Flash Preview)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=' . env('GEMINI_API_KEY'), [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $hasilTeks = $response->json('candidates.0.content.parts.0.text');
                $hasilTeks = str_replace(['```json', '```'], '', $hasilTeks);
                $dataJson = json_decode(trim($hasilTeks), true);

                if ($dataJson && isset($dataJson['draf_pesan'])) {
                    // Karena kita belum membuat kolom draf di database, 
                    // kita lemparkan draf ini sementara ke Session UI agar bisa langsung di-copy CS
                    return back()->with('followup_draft', $dataJson['draf_pesan'])
                                 ->with('followup_client', $clientNumber);
                }
            }
            return back()->with('error', 'Gagal membuat draf pesan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Koneksi AI gagal: ' . $e->getMessage());
        }
    }
}