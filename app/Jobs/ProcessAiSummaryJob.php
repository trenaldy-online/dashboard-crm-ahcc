<?php

namespace App\Jobs;

use App\Models\WaChat;
use App\Models\LeadSummary;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // <-- WAJIB DITAMBAHKAN UNTUK CCTV
use Carbon\Carbon;

class ProcessAiSummaryJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientNumber;

    public function __construct($clientNumber)
    {
        $this->clientNumber = $clientNumber;
    }

    public function handle(): void
    {
        // Jika antrean dibatalkan oleh sistem, hentikan proses
        if ($this->batch()->cancelled()) {
            return;
        }

        // 1. Ambil transkrip obrolan pasien ini
        $chats = WaChat::where('client_number', $this->clientNumber)->orderBy('chat_time', 'asc')->get();
        if ($chats->isEmpty()) return;

        $transcript = "";
        foreach ($chats as $chat) {
            $sender = $chat->is_me ? "CS AHCC" : "Pasien";
            $waktu = Carbon::parse($chat->chat_time)->timezone('Asia/Jakarta')->format('d M Y, H:i');
            $transcript .= "[{$waktu}] {$sender}: {$chat->message}\n";
        }

        $existingLead = LeadSummary::where('client_number', $this->clientNumber)->first();
        $statusLama = $existingLead ? $existingLead->pipeline_status : 'Belum Terdaftar (Pasien Baru)';
        $hariIni = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');

        // 2. Siapkan Prompt untuk AI (Gemini) - DENGAN CHAIN OF THOUGHT
        $prompt = "Anda adalah H.A.N.A, Direktur CRM, Ahli Data Medis, sekaligus Senior Patient Advisor di RS Kanker AHCC.
        Tugas Anda adalah membaca transkrip obrolan WA pasien ini dan mengekstrak datanya ke dalam format JSON yang valid.

        TRANSKRIP CHAT:
        \"\"\"
        {$transcript}
        \"\"\"

        INFO PENTING: Status pasien sebelumnya adalah '{$statusLama}'. Hari ini adalah: {$hariIni}.
        ATURAN MUTLAK: JANGAN MENEBAK! Jika informasi tidak disebutkan secara eksplisit di chat, isi dengan 'Belum Diketahui' atau null.

        ====================================================
        EVALUASI STATUS FOLLOW-UP (WAJIB DIPATUHI 100%):
        Evaluasi 3 kondisi di bawah ini secara berurutan. Jika salah satu kondisi terpenuhi, BERHENTI dan gunakan hasilnya!

        KONDISI 1 - CS SUDAH MENUTUP PERCAKAPAN:
        Lihat baris PALING BAWAH transkrip. Jika pengirim terakhir adalah 'CS AHCC', berarti CS sudah merespons.
        -> TINDAKAN: Set \"perlu_follow_up\": false, \"alasan_follow_up\": \"CS sudah merespons.\", \"topik_follow_up\": null.

        KONDISI 2 - PASIEN MINTA WAKTU / PENOLAKAN HALUS (SOFT REJECTION):
        Jika pesan terakhir pasien mengandung makna \"nanti kami kabari\", \"nanti saya hubungi lagi\", \"tunggu musyawarah keluarga\", atau \"masih pikir-pikir biaya\":
        -> TINDAKAN: Set \"perlu_follow_up\": false, \"alasan_follow_up\": \"Pasien meminta waktu untuk berdiskusi/memutuskan.\", \"topik_follow_up\": null. 

        KONDISI 3 - PASIEN BERTANYA / MENGGANTUNG:
        HANYA JIKA pengirim terakhir adalah 'Pasien' DAN pesannya belum dibalas oleh CS.
        -> TINDAKAN: Set \"perlu_follow_up\": true, lalu isi \"topik_follow_up\" menggunakan STRATEGI COPYWRITING di bawah.
        ====================================================

        STRATEGI COPYWRITING (HANYA JIKA perlu_follow_up adalah TRUE):
        1. Kendala BIAYA: Tawarkan konsultasi awal yang terjangkau untuk Second Opinion, jangan paksa tindakan mahal.
        2. Kendala JARAK: Tawarkan Video Call / Telekonsultasi dengan dokter.
        3. TAKUT EFEK SAMPING: Edukasi keamanan LINAC AHCC, ajak konsul untuk menenangkan pikiran.
        4. GHOSTING JADWAL: Beri 2 pilihan jadwal (misal Selasa/Rabu).

        KEMBALIKAN HANYA OBJEK JSON BERIKUT (TANPA MARKDOWN):
        {
            \"kategori_kanker\": \"Jenis kanker\",
            \"ringkasan\": \"Rangkuman obrolan (Maks 2 kalimat)\",
            \"pipeline_status\": \"WAJIB PILIH SALAH SATU: 'leads_baru' (jika baru menyapa), 'edukasi' (jika masih tanya-tanya), 'konsultasi' (jika bahas jadwal/dokter), 'deal' (jika setuju datang). PILIH 'batal' HANYA JIKA pasien secara TEGAS menolak (misal: 'tidak jadi', 'berobat di RS lain', 'mundur karena biaya', 'meninggal'). JIKA pasien hanya bilang 'nanti dikabari/mikir dulu', JANGAN pilih batal, biarkan di edukasi/konsultasi.\",
            
            \"analisa_logika\": \"WAJIB JAWAB STEP-BY-STEP DI SINI: 1. Siapa pengirim baris paling bawah? 2. Apakah pasien meminta waktu/menolak halus? 3. Berdasarkan Kondisi 1, 2, 3 di instruksi, apakah perlu_follow_up harus true atau false?\",
            
            \"perlu_follow_up\": true/false (Isi berdasarkan kesimpulan di analisa_logika),
            \"alasan_follow_up\": \"(Isi berdasarkan analisa_logika)\",
            \"topik_follow_up\": \"JIKA perlu_follow_up = false, ISI DENGAN null. JIKA true, buatkan kalimat saran.\",
            \"lead_score\": Angka 0-100,
            \"minat_treatment\": \"Radioterapi, Kemo, dll\",
            \"metode_bayar\": \"Pribadi, BPJS, dll\",
            \"profil_pengirim\": \"Pasien Sendiri, Anak, dll\",
            \"status_medis\": \"Sedang Kemo, Pasca Operasi, dll\",
            \"sentimen_emosi\": \"Panik, Cemas, Biasa, dll\",
            \"kendala_utama\": \"Biaya / Jarak / Efek Samping / Ragu\",
            \"gclid\": \"Ekstrak jika ada, atau null\",
            \"fbclid\": \"Ekstrak jika ada, atau null\",
            \"tunda_sampai_tanggal\": \"Konversi ke YYYY-MM-DD jika pasien minta tunda, atau null\",
            \"pasien_membalas\": true/false (true JIKA chat TERAKHIR dikirim oleh Pasien. False jika dikirim oleh CS)
        }";

        // --- MULAI REKAM CCTV (LOG) SEBELUM DIKIRIM ---
        // Log::info("=== MENGIRIM PROMPT KE GEMINI (PASIEN: {$this->clientNumber}) ===");
        // Log::info($prompt);

        // 3. Tembak API Gemini
        $apiKey = env('GEMINI_API_KEY');
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json']
            ]);

        if ($response->successful()) {
            $rawAiResponse = $response->json('candidates.0.content.parts.0.text');
            
            // --- REKAM CCTV (LOG) JAWABAN MENTAH AI ---
            // Log::info("=== JAWABAN MENTAH DARI GEMINI (PASIEN: {$this->clientNumber}) ===");
            // Log::info($rawAiResponse);

            $result = json_decode($rawAiResponse, true);

            if ($result) {
                // Ambil status validasi sebelumnya (False jika pasien baru)
                $isHumanValidated = $existingLead?->is_human_validated ?? false;

                LeadSummary::updateOrCreate(
                    ['client_number' => $this->clientNumber],
                    [
                        // 1. Terapkan masukan brilian Anda (Cegah Reset Validasi)
                        'is_human_validated' => $isHumanValidated,
                        
                        // 2. LOGIKA KUNCI KANBAN: 
                        // Jika sudah digeser manusia, pertahankan status lamanya. 
                        // Jika belum disentuh manusia, biarkan AI yang menentukan kolomnya.
                        'pipeline_status' => $isHumanValidated ? $existingLead->pipeline_status : ($result['pipeline_status'] ?? 'leads_baru'),
                        
                        'kategori_kanker' => $result['kategori_kanker'] ?? 'Belum Terdeteksi',
                        'ringkasan' => $result['ringkasan'] ?? 'Tidak ada ringkasan',
                        
                        'perlu_follow_up' => $result['perlu_follow_up'] ?? false,
                        'alasan_follow_up' => $result['alasan_follow_up'] ?? null,
                        'topik_follow_up' => $result['topik_follow_up'] ?? null,

                        'lead_score' => $result['lead_score'] ?? 0,
                        'minat_treatment' => $result['minat_treatment'] ?? 'Belum Diketahui',
                        'metode_bayar' => $result['metode_bayar'] ?? 'Belum Diketahui',
                        'profil_pengirim' => $result['profil_pengirim'] ?? 'Belum Diketahui',
                        'status_medis' => $result['status_medis'] ?? 'Belum Diketahui',
                        'sentimen_emosi' => $result['sentimen_emosi'] ?? 'Biasa',
                        'kendala_utama' => $result['kendala_utama'] ?? 'Belum Ada',
                        'gclid' => $result['gclid'] ?? null,
                        'fbclid' => $result['fbclid'] ?? null,

                        'follow_up_count' => ($result['pasien_membalas'] ?? false) ? 0 : ($existingLead?->follow_up_count ?? 0),
                        'tunda_sampai_tanggal' => $result['tunda_sampai_tanggal'] ?? null,
                    ]
                );
            } else {
                Log::error("Gagal mendecode JSON dari Gemini untuk Pasien {$this->clientNumber}.");
            }
        } else {
            Log::error("API Gemini Error (Pasien {$this->clientNumber}): " . $response->body());
        }
    }
}