<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadSummary;
use App\Models\DailyBriefing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class HanaMorningScan extends Command
{
    protected $signature = 'hana:morning-scan';
    protected $description = 'Generate Narasi Morning Briefing & Atur Penjadwalan FU PA';

    public function handle()
    {
        $this->info('Memulai pengecekan jadwal H.A.N.A...');
        $now = Carbon::now('Asia/Jakarta');

        // ==========================================
        // GERBANG 1: CEK SABTU & MINGGU
        // ==========================================
        if ($now->isWeekend()) {
            $this->info('Hari ini akhir pekan (Sabtu/Minggu). H.A.N.A libur dan tidak mengirim briefing.');
            return 0; // Menghentikan eksekusi script di sini
        }

        // ==========================================
        // GERBANG 2: CEK LIBUR NASIONAL / CUTI KLINIK
        // ==========================================
        $tanggalHariIni = $now->format('Y-m-d');
        
        // Isi array ini dengan tanggal merah tahun 2026 atau tanggal cuti klinik Anda
        $hariLibur = [
            '2026-05-01', // Hari Buruh
            '2026-05-14', // Kenaikan Isa Almasih
            '2026-05-24', // Waisak
            '2026-06-01', // Hari Lahir Pancasila
            '2026-08-17', // Hari Kemerdekaan RI
            '2026-12-25', // Hari Raya Natal
        ];

        if (in_array($tanggalHariIni, $hariLibur)) {
            $this->info("Hari ini Libur Nasional ($tanggalHariIni). H.A.N.A libur.");
            return 0; // Menghentikan eksekusi script di sini
        }

        // ==========================================
        // PROSES UTAMA (JIKA BUKAN HARI LIBUR)
        // ==========================================
        $this->info('Jadwal aktif. Mengumpulkan data untuk Morning Briefing...');
        
        // Set variabel "kemarin" baru dilakukan di sini karena kita sudah yakin hari ini aktif
        $kemarin = $now->copy()->subDay();

        // 1. REKAP DATA KEMARIN
        $leadsKemarin = LeadSummary::whereDate('updated_at', $kemarin->toDateString())->get();
        $totalChat = $leadsKemarin->count();
        
        $tanyaKemo = 0; $tanyaRT = 0; $tanyaKonsul = 0; $tanyaAwam = 0;
        foreach($leadsKemarin as $lead) {
            $minat = strtolower($lead->minat_treatment ?? '');
            if(str_contains($minat, 'kemo')) $tanyaKemo++;
            elseif(str_contains($minat, 'radio') || str_contains($minat, 'rt')) $tanyaRT++;
            elseif(str_contains($minat, 'konsul')) $tanyaKonsul++;
            else $tanyaAwam++;
        }

        // 2. MENCARI "MANGSA BARU"
        $leadsAktif = LeadSummary::whereIn('pipeline_status', ['leads_baru', 'edukasi', 'konsultasi'])
            ->where('perlu_follow_up', false)
            ->get();

        $batalOtomatis = [];
        foreach ($leadsAktif as $lead) {
            if ($lead->tunda_sampai_tanggal && $now->lt(Carbon::parse($lead->tunda_sampai_tanggal))) continue;

            $hariPasif = $now->diffInDays($lead->updated_at);
            $count = $lead->follow_up_count;

            // --- TAMBAHAN LOGIKA ANTI-SPAM (CEK PENGIRIM TERAKHIR) ---
            $lastChat = \App\Models\WaChat::where('client_number', $lead->client_number)
                                          ->orderBy('chat_time', 'desc')
                                          ->first();
            
            $isGhosting = false;
            // Jika chat terakhir ada, dan itu dari CS (is_me = 1), berarti pasien sedang ghosting
            if ($lastChat && $lastChat->is_me == 1) {
                $isGhosting = true;
            }
            // ---------------------------------------------------------

            // Jika pasien ghosting, JANGAN beri peringatan H+2 atau H+5. Biarkan sampai H+14 lalu buang.
            if ($isGhosting) {
                if ($hariPasif >= 14) {
                    $lead->update(['pipeline_status' => 'batal', 'perlu_follow_up' => false]);
                    $batalOtomatis[] = $lead->client_number;
                }
                continue; // Lewati proses di bawahnya, jangan beri stempel merah!
            }

            // Jika TIDAK ghosting (berarti pasien yang chat terakhir, atau belum ada chat), jalankan logika normal
            if ($count == 0 && $hariPasif >= 2) {
                $lead->update(['perlu_follow_up' => true, 'follow_up_count' => 1, 'alasan_follow_up' => 'Follow up pertama (H+2)']);
            } elseif ($count == 1 && $hariPasif >= 5) {
                $lead->update(['perlu_follow_up' => true, 'follow_up_count' => 2, 'alasan_follow_up' => 'Follow up kedua (H+5)']);
            } elseif ($count >= 2 && $hariPasif >= 14) {
                $lead->update(['pipeline_status' => 'batal', 'perlu_follow_up' => false]);
                $batalOtomatis[] = $lead->client_number;
            }
        }

        // 3. AMBIL DETAIL SEMUA TUNGGAKAN
        $pasienHutang = LeadSummary::whereIn('pipeline_status', ['leads_baru', 'edukasi', 'konsultasi'])
            ->where('perlu_follow_up', true)
            ->get();
            
        $totalHutang = $pasienHutang->count();

        $detailHutang = "";
        if ($totalHutang > 0) {
            foreach ($pasienHutang as $p) {
                $nomor = $p->client_number;
                $riwayat = $p->kendala_utama ?? 'Belum ada data detail.';
                $tugas = $p->alasan_follow_up ?? 'Butuh disapa kembali.';
                $detailHutang .= "- Pasien $nomor | Riwayat: $riwayat | Status: $tugas\n";
            }
        }

        // 4. SUSUN PROMPT UNTUK GEMINI (Pecah 2 Pesan)
        $tanggalSekarang = $now->translatedFormat('d F Y');
        $prompt = "Anda adalah H.A.N.A, Kepala Patient Advisor di Klinik Kanker. Anda harus membuat DUA pesan yang dipisahkan SECARA KETAT dengan teks penanda: |||SPLIT|||\n\n" .
                  "BAGIAN 1: Briefing Pagi\n" .
                  "- Ringkas data kemarin: Total $totalChat interaksi ($tanyaKemo Kemo, $tanyaRT RT, $tanyaKonsul Konsul, $tanyaAwam Awam).\n" .
                  "- Sebutkan bahwa ada $totalHutang pasien yang wajib di-follow up hari ini.\n" .
                  "- Tulis paragraf pembuka yang ramah dan memotivasi tim PA. JANGAN menuliskan daftar pasien di Bagian 1 ini.\n\n" .
                  "|||SPLIT|||\n\n" .
                  "BAGIAN 2: Daftar Follow Up\n" .
                  "- Berikut adalah data pasien yang butuh dihubungi:\n$detailHutang\n" .
                  "- Buatkan list (daftar) yang rapi. Tulis nomor WA-nya, jelaskan riwayatnya secara singkat, lalu buatkan 💡 Saran Chat copywriting yang pas dan empatik agar tinggal di-copy paste oleh PA.\n" .
                  "- Jika data pasien kosong, cukup tulis: 'Semua aman! Tidak ada pasien yang menggantung hari ini.'\n\n" .
                  "ATURAN FORMAT: Gunakan tag HTML <b>teks</b> untuk menebalkan teks yang penting.";

        $apiKey = env('GEMINI_API_KEY');
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

        $narasi = "Gagal memuat narasi dari AI.";
        if ($response->successful()) {
            $result = $response->json('candidates.0.content.parts.0.text');
            if ($result) $narasi = $result;
        }

        // 5. PECAH PESAN MENJADI 2 BAGIAN
        $pesanArray = explode('|||SPLIT|||', $narasi);
        $pesanBriefing = trim($pesanArray[0] ?? $narasi);
        $pesanFollowUp = trim($pesanArray[1] ?? '');

        // Simpan ke Database untuk Riwayat di Dasbor
        $narasiDb = str_replace('|||SPLIT|||', "\n\n=== DAFTAR TUGAS FOLLOW UP ===\n\n", $narasi);
        $briefing = DailyBriefing::updateOrCreate(
            ['tanggal_briefing' => $now->toDateString()],
            [
                'narasi_teks' => trim($narasiDb),
                'data_mentah' => [
                    'total'  => $totalChat, 
                    'hutang' => $totalHutang, 
                    'batal'  => count($batalOtomatis)
                ],
                'is_wa_sent' => false
            ]
        );

        $this->info('Data disimpan. Mengirim ke WhatsApp...');

        // 6. PENGIRIMAN WHATSAPP VIA FONNTE
        $fonnteToken = 'ktLApTVPLY96LLz9u1wx'; 
        $targetWa = trim('083831169957'); 
        
        // ---- KIRIM PESAN 1 (BRIEFING) ----
        $pesanWa1 = str_replace(['<b>', '</b>', '<strong>', '</strong>'], '*', $pesanBriefing);
        $teksFinal1 = "👩‍⚕️ *H.A.N.A MORNING BRIEFING*\n🗓️ _{$now->translatedFormat('l, d F Y')}_\n──────────────────\n\n" . strip_tags($pesanWa1);

        try {
            $resp1 = Http::withHeaders(['Authorization' => $fonnteToken])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $targetWa,
                'message' => $teksFinal1,
            ]);

            // ---- KIRIM PESAN 2 (DAFTAR EKSEKUSI) ----
            // Pesan 2 hanya dikirim jika teksnya ada dan tunggakan > 0
            if (!empty($pesanFollowUp) && $totalHutang > 0) {
                // Beri jeda 2 detik agar pesan di grup WA tidak tertumpuk acak
                sleep(2); 
                
                $pesanWa2 = str_replace(['<b>', '</b>', '<strong>', '</strong>'], '*', $pesanFollowUp);
                $teksFinal2 = "🎯 *DAFTAR EKSEKUSI FOLLOW-UP HARI INI*\n──────────────────\n\n" . strip_tags($pesanWa2) . "\n\n──────────────────\n💡 _Ayo tim, selesaikan hari ini agar pasien merasa diperhatikan!_ 💪";
                
                Http::withHeaders(['Authorization' => $fonnteToken])->asForm()->post('https://api.fonnte.com/send', [
                    'target' => $targetWa,
                    'message' => $teksFinal2,
                ]);
            }

            if ($resp1->successful()) {
                $briefing->update(['is_wa_sent' => true]);
                $this->info('✅ BINGO! Dua pesan WA berhasil dikirim!');
            }
        } catch (\Exception $e) {
            $this->error('Sistem error saat mengirim WA: ' . $e->getMessage());
        }

        return 0;
    }
}