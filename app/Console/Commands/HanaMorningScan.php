<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadSummary;
use App\Models\DailyBriefing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            return 0; 
        }

        // ==========================================
        // GERBANG 2: CEK LIBUR NASIONAL / CUTI KLINIK
        // ==========================================
        $tanggalHariIni = $now->format('Y-m-d');
        
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
            return 0; 
        }

        // ==========================================
        // PROSES UTAMA
        // ==========================================
        $this->info('Jadwal aktif. Mengumpulkan data untuk Morning Briefing...');
        
        $kemarin = $now->copy()->subDay();

        // 1. REKAP DATA KEMARIN (Berdasarkan updated_at karena ini cuma untuk statistik, bukan acuan timer)
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

        // 2. MENCARI "MANGSA BARU" & PROSES TIMER ANTI-BOCOR
        $batalOtomatis = [];
        $activePipelines = ['leads_baru', 'edukasi', 'konsultasi'];

        // Gunakan chunkById untuk performa stabil jika data jutaan
        LeadSummary::whereIn('pipeline_status', $activePipelines)
            ->chunkById(100, function ($leads) use ($now, &$batalOtomatis) {
                
                foreach ($leads as $lead) {
                    // Abaikan jika pasien minta ditunda
                    if ($lead->tunda_sampai_tanggal && $now->lt(Carbon::parse($lead->tunda_sampai_tanggal))) {
                        continue;
                    }

                    // Logika: Apakah CS yang membalas terakhir? (Berarti pasien ghosting)
                    $isGhosting = false;
                    $baselineTime = null;

                    if ($lead->last_cs_reply_at && (!$lead->last_patient_reply_at || $lead->last_cs_reply_at > $lead->last_patient_reply_at)) {
                        $isGhosting = true;
                        $baselineTime = Carbon::parse($lead->last_cs_reply_at);
                    } elseif ($lead->last_cs_reply_at === null && $lead->last_patient_reply_at === null) {
                        // Fallback aman untuk data lama yang belum punya jam chat (gunakan updated_at)
                        $isGhosting = true;
                        $baselineTime = Carbon::parse($lead->updated_at);
                    }

                    if ($isGhosting && $baselineTime) {
                        $daysGhosting = $now->copy()->startOfDay()->diffInDays($baselineTime->copy()->startOfDay());
                        $step = $lead->follow_up_step ?? 0; // Default ke 0 jika null

                        // MUTLAK: H+14 -> Buang ke Batal
                        if ($daysGhosting >= 14) {
                            $lead->update([
                                'pipeline_status' => 'batal', 
                                'perlu_follow_up' => false,
                                'ringkasan' => $lead->ringkasan . "\n[SISTEM]: Auto-batal karena ghosting 14 hari."
                            ]);
                            $batalOtomatis[] = $lead->client_number;
                            Log::info("Lead {$lead->client_number} batal (H+14).");
                            continue;
                        }

                        // JIKA GHOSTING, BUKAN BERARTI TIDAK DI-FOLLOW UP!
                        // Justru karena ghosting, sistem harus kasih stempel merah untuk mengingatkan PA.
                        
                        // H+5 -> Stempel Merah FU Kedua
                        if ($daysGhosting >= 5 && $step == 1) {
                            $lead->update([
                                'perlu_follow_up' => true, 
                                'follow_up_step' => 2, // Naikkan step agar tidak dikirimi H+5 berkali-kali
                                'alasan_follow_up' => 'Follow up kedua (H+5)'
                            ]);
                            continue;
                        }

                        // H+2 -> Stempel Merah FU Pertama
                        if ($daysGhosting >= 2 && $step == 0) {
                            $lead->update([
                                'perlu_follow_up' => true, 
                                'follow_up_step' => 1, // Naikkan step
                                'alasan_follow_up' => 'Follow up pertama (H+2)'
                            ]);
                            continue;
                        }
                    } else {
                        // Jika pasien yang chat terakhir, pastikan stempelnya bersih dan stepnya 0
                        // (Meskipun di model WaChat sudah direset, ini untuk double check)
                        if ($lead->perlu_follow_up == true || $lead->follow_up_step != 0) {
                             $lead->update(['perlu_follow_up' => false, 'follow_up_step' => 0, 'alasan_follow_up' => null]);
                        }
                    }
                }
            });

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

        // 4. SUSUN PROMPT UNTUK GEMINI
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

        // 5. PECAH PESAN
        $pesanArray = explode('|||SPLIT|||', $narasi);
        $pesanBriefing = trim($pesanArray[0] ?? $narasi);
        $pesanFollowUp = trim($pesanArray[1] ?? '');

        // Simpan ke Database
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
        
        $pesanWa1 = str_replace(['<b>', '</b>', '<strong>', '</strong>'], '*', $pesanBriefing);
        $teksFinal1 = "👩‍⚕️ *H.A.N.A MORNING BRIEFING*\n🗓️ _{$now->translatedFormat('l, d F Y')}_\n──────────────────\n\n" . strip_tags($pesanWa1);

        try {
            // KIRIM PESAN 1
            $resp1 = Http::withHeaders(['Authorization' => $fonnteToken])->asForm()->post('https://api.fonnte.com/send', [
                'target' => $targetWa,
                'message' => $teksFinal1,
            ]);

            $pesanKeduaTerkirim = false;

            // KIRIM PESAN 2 (HANYA JIKA ADA TUNGGAKAN)
            if (!empty($pesanFollowUp) && $totalHutang > 0) {
                sleep(2); 
                
                $pesanWa2 = str_replace(['<b>', '</b>', '<strong>', '</strong>'], '*', $pesanFollowUp);
                $teksFinal2 = "🎯 *DAFTAR EKSEKUSI FOLLOW-UP HARI INI*\n──────────────────\n\n" . strip_tags($pesanWa2) . "\n\n──────────────────\n💡 _Ayo tim, selesaikan hari ini agar pasien merasa diperhatikan!_ 💪";
                
                $resp2 = Http::withHeaders(['Authorization' => $fonnteToken])->asForm()->post('https://api.fonnte.com/send', [
                    'target' => $targetWa,
                    'message' => $teksFinal2,
                ]);

                if ($resp2->successful()) {
                    $pesanKeduaTerkirim = true;
                } else {
                    $this->error('⚠️ Pesan kedua gagal dikirim ke Fonnte: ' . $resp2->body());
                }
            } else {
                $this->info("ℹ️ Pesan Daftar Follow Up di-skip karena tidak ada tunggakan (Total Hutang: $totalHutang).");
            }

            // UPDATE STATUS & LOG TERMINAL
            if ($resp1->successful()) {
                $briefing->update(['is_wa_sent' => true]);
                
                if ($pesanKeduaTerkirim) {
                    $this->info('✅ BINGO! Dua pesan WA (Briefing & Follow Up) berhasil dikirim!');
                } else {
                    $this->info('✅ BINGO! Hanya pesan pertama (Briefing) yang dikirim.');
                }
            } else {
                $this->error('❌ Pesan pertama gagal dikirim ke Fonnte: ' . $resp1->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Sistem error saat mengirim WA: ' . $e->getMessage());
        }

        return 0;
    }
}