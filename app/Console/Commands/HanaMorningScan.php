<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadSummary;
use App\Models\DailyBriefing;
use Carbon\Carbon;
use App\Models\WaChat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HanaMorningScan extends Command
{
    protected $signature = 'hana:morning-scan';
    protected $description = 'Generate Narasi Morning Briefing & Atur Penjadwalan FU PA';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');

        $xrayText = "=========================================\n";
        $xrayText .= "🚀 LOG X-RAY H.A.N.A (" . $now->format('d M Y H:i') . ")\n";
        $xrayText .= "=========================================\n\n";

        $this->info('=========================================');
        $this->info('🚀 MEMULAI H.A.N.A X-RAY MODE');
        $this->info('=========================================');

        if ($now->isWeekend()) {
            $this->info('Hari ini akhir pekan. H.A.N.A libur.');
            return 0;
        }

        $holidayCheck = $this->isIndonesianHoliday($now);

        if ($holidayCheck['is_holiday']) {
            $holidayName = implode(', ', $holidayCheck['holiday_names']);

            $this->info("Hari ini Libur Nasional/Cuti Bersama: {$holidayName}. H.A.N.A libur.");
            return 0;
        }

        $kemarinStart = $now->copy()->subDay()->startOfDay();
        $kemarinEnd = $now->copy()->subDay()->endOfDay();

        /**
         * 1. Total chat aktif yang ditangani kemarin:
         * Nomor unik yang memiliki pesan dari CS/PA atau pasien pada hari kemarin.
         */
        $totalChatDitanggapi = WaChat::whereBetween('chat_time', [$kemarinStart, $kemarinEnd])
            ->distinct('client_number')
            ->count('client_number');

        /**
         * 2. Ambil chat pasien yang masuk kemarin.
         */
        $chatPasienKemarin = WaChat::whereBetween('chat_time', [$kemarinStart, $kemarinEnd])
            ->where('is_me', false)
            ->orderBy('chat_time', 'asc')
            ->get();

        $nomorPasienKemarin = $chatPasienKemarin
            ->pluck('client_number')
            ->unique()
            ->values();

        /**
         * 3. Chat unik baru masuk kemarin
         * Artinya: nomor yang first incoming chat-nya terjadi kemarin.
         */
        $nomorBaruKemarin = collect();

        foreach ($nomorPasienKemarin as $nomor) {
            $firstPatientChat = WaChat::where('client_number', $nomor)
                ->where('is_me', false)
                ->orderBy('chat_time', 'asc')
                ->first();

            if (
                $firstPatientChat &&
                Carbon::parse($firstPatientChat->chat_time, 'Asia/Jakarta')->betweenIncluded($kemarinStart, $kemarinEnd)
            ) {
                $nomorBaruKemarin->push($nomor);
            }
        }

        $totalChatUnikBaru = $nomorBaruKemarin->count();

        /**
         * 4. Klasifikasi sumber chat baru:
         * Google  = ada G-ID
         * Facebook = ada F-ID
         * Organik = tidak ada G-ID/F-ID
         */
        $totalGoogle = 0;
        $totalFacebook = 0;
        $totalOrganik = 0;

        foreach ($nomorBaruKemarin as $nomor) {
            $textGabungan = WaChat::where('client_number', $nomor)
                ->where('is_me', false)
                ->pluck('message')
                ->implode(' ');

            $textGabungan = strtoupper($textGabungan);

            if (str_contains($textGabungan, 'G-ID')) {
                $totalGoogle++;
            } elseif (str_contains($textGabungan, 'F-ID')) {
                $totalFacebook++;
            } else {
                $totalOrganik++;
            }
        }

        /**
         * 5. Kategori minat dari chat baru kemarin
         * Diambil dari LeadSummary berdasarkan nomor baru kemarin.
         */
        $leadsBaruKemarin = LeadSummary::whereIn('client_number', $nomorBaruKemarin)->get();

        $tanyaKemo = 0;
        $tanyaRT = 0;
        $tanyaKonsul = 0;
        $tanyaAwam = 0;

        foreach ($leadsBaruKemarin as $lead) {
            $minat = strtolower($lead->minat_treatment ?? '');

            if (str_contains($minat, 'kemo')) {
                $tanyaKemo++;
            } elseif (
                str_contains($minat, 'radioterapi') ||
                str_contains($minat, 'radio terapi') ||
                str_contains($minat, 'radiasi') ||
                str_contains($minat, 'radio')
            ) {
                $tanyaRT++;
            } elseif (str_contains($minat, 'konsul')) {
                $tanyaKonsul++;
            } else {
                $tanyaAwam++;
            }
        }

        $xrayText .= "📊 Rekap Kemarin:\n";
        $xrayText .= "- Total chat ditanggapi CS/PA: {$totalChatDitanggapi}\n";
        $xrayText .= "- Chat unik baru masuk: {$totalChatUnikBaru}\n";
        $xrayText .= "- Google Ads (G-ID): {$totalGoogle}\n";
        $xrayText .= "- Facebook Ads (F-ID): {$totalFacebook}\n";
        $xrayText .= "- Organik: {$totalOrganik}\n\n";

        $batalOtomatis = [];
        $activePipelines = ['leads_baru', 'edukasi', 'konsultasi'];

        $msg = 'Memulai pemindaian data pasien aktif eligible H.A.N.A...';
        $this->info($msg);
        $xrayText .= $msg . "\n";

        $leadsAktif = LeadSummary::whereIn('pipeline_status', $activePipelines)
            ->where('is_eligible_for_hana', true)
            ->get();

        foreach ($leadsAktif as $lead) {
            $xrayText .= "-------------------------------------------------\n";
            $this->info('-------------------------------------------------');

            $msg = "🔎 Cek Pasien: {$lead->client_number}";
            $this->info($msg);
            $xrayText .= $msg . "\n";

            if ($lead->tunda_sampai_tanggal && $now->lt(Carbon::parse($lead->tunda_sampai_tanggal, 'Asia/Jakarta'))) {
                $msg = "   ⏭️ Di-skip: Pasien minta tunda sampai {$lead->tunda_sampai_tanggal}";
                $this->info($msg);
                $xrayText .= $msg . "\n";
                continue;
            }

            if (empty($lead->last_cs_reply_at)) {
                $msg = '   ⏭️ Di-skip: Belum pernah dibalas CS.';
                $this->info($msg);
                $xrayText .= $msg . "\n";
                continue;
            }

            $isGhosting = false;
            $waktuCS = Carbon::parse($lead->last_cs_reply_at, 'Asia/Jakarta');

            $msg = '   Waktu CS Balas: ' . $waktuCS->format('Y-m-d H:i');
            $this->info($msg);
            $xrayText .= $msg . "\n";

            if (empty($lead->last_patient_reply_at)) {
                $isGhosting = true;

                $msg = '   Waktu Pasien Balas: KOSONG. Ghosting terkonfirmasi.';
                $this->info($msg);
                $xrayText .= $msg . "\n";
            } else {
                $waktuPasien = Carbon::parse($lead->last_patient_reply_at, 'Asia/Jakarta');

                $msg = '   Waktu Pasien Balas: ' . $waktuPasien->format('Y-m-d H:i');
                $this->info($msg);
                $xrayText .= $msg . "\n";

                if ($waktuCS->gt($waktuPasien)) {
                    $isGhosting = true;

                    $msg = '   Status: Ghosting. CS lebih baru dari pasien.';
                    $this->info($msg);
                    $xrayText .= $msg . "\n";
                } else {
                    $msg = '   Status: Aman. Pasien yang terakhir membalas.';
                    $this->info($msg);
                    $xrayText .= $msg . "\n";
                }
            }

            if (!$isGhosting) {
                if ($lead->perlu_follow_up == true) {
                    $lead->perlu_follow_up = false;
                    $lead->alasan_follow_up = null;
                    $lead->save();

                    $msg = '   🧹 Membersihkan status perlu_follow_up karena pasien sudah membalas.';
                    $this->info($msg);
                    $xrayText .= $msg . "\n";
                }

                continue;
            }

            $daysGhosting = $waktuCS
                ->copy()
                ->startOfDay()
                ->diffInDays($now->copy()->startOfDay());

            $sentCount = (int) ($lead->follow_up_sent_count ?? 0);

            $msg = "   Lama Ghosting: {$daysGhosting} Hari | Sent Count: {$sentCount}";
            $this->info($msg);
            $xrayText .= $msg . "\n";

            if ($daysGhosting >= 14) {
                $lead->pipeline_status = 'batal';
                $lead->perlu_follow_up = false;
                $lead->alasan_follow_up = null;
                $lead->is_eligible_for_hana = false;
                $lead->conversation_outcome = 'closed';
                $lead->ringkasan = ($lead->ringkasan ?? '') . "\n[SISTEM]: Auto-batal karena ghosting 14 hari.";
                $lead->save();

                $batalOtomatis[] = $lead->client_number;

                $msg = '   [!] DIEKSEKUSI: Auto Batal H+14.';
                $this->info($msg);
                $xrayText .= $msg . "\n";

                continue;
            }

            if ($sentCount === 1) {
                if (empty($lead->last_follow_up_sent_at)) {
                    $msg = '   ⚠️ Sent Count = 1, tapi last_follow_up_sent_at kosong. FU kedua belum bisa dihitung.';
                    $this->info($msg);
                    $xrayText .= $msg . "\n";
                    continue;
                }

                $lastFollowUp = Carbon::parse($lead->last_follow_up_sent_at, 'Asia/Jakarta');

                $daysSinceLastFollowUp = $lastFollowUp
                    ->copy()
                    ->startOfDay()
                    ->diffInDays($now->copy()->startOfDay());

                $msg = "   Lama Sejak FU-1: {$daysSinceLastFollowUp} Hari";
                $this->info($msg);
                $xrayText .= $msg . "\n";

                if ($daysSinceLastFollowUp >= 3) {
                    $lead->perlu_follow_up = true;
                    $lead->alasan_follow_up = 'Follow up kedua (Sudah 3 hari sejak FU pertama dikirim)';
                    $lead->save();

                    $msg = '   [!] DIEKSEKUSI: Masuk Follow Up Ke-2.';
                    $this->info($msg);
                    $xrayText .= $msg . "\n";
                    continue;
                }
            }

            if ($sentCount === 0 && $daysGhosting >= 2) {
                $lead->perlu_follow_up = true;
                $lead->alasan_follow_up = 'Follow up pertama (Sudah masuk zona H+2)';
                $lead->save();

                $msg = '   [!] DIEKSEKUSI: Masuk Follow Up Ke-1.';
                $this->info($msg);
                $xrayText .= $msg . "\n";
                continue;
            }

            if ($sentCount >= 2) {
                $msg = '   ⚪ Sudah pernah FU minimal 2 kali. Menunggu H+14 atau respon pasien.';
                $this->info($msg);
                $xrayText .= $msg . "\n";
                continue;
            }

            $msg = '   ⚪ Tidak ada tindakan. Belum masuk zona follow-up.';
            $this->info($msg);
            $xrayText .= $msg . "\n";
        }

        $xrayText .= "=========================================\n";

        $this->info('=========================================');
        $msg = '🏁 PEMINDAIAN SELESAI. Menghitung Hutang...';
        $this->info($msg);
        $xrayText .= $msg . "\n";

        $pasienHutang = LeadSummary::whereIn('pipeline_status', $activePipelines)
            ->where('is_eligible_for_hana', true)
            ->where('perlu_follow_up', true)
            ->get();

        $totalHutang = $pasienHutang->count();

        $msg = "Total Hutang Ditemukan: {$totalHutang}";
        $this->info($msg);
        $xrayText .= $msg . "\n";

        $detailHutang = '';

        if ($totalHutang > 0) {
            foreach ($pasienHutang as $p) {
                $nomor = $p->client_number;
                $riwayat = $p->kendala_utama ?? 'Belum ada data detail.';
                $tugas = $p->alasan_follow_up ?? 'Butuh disapa kembali.';
                $topik = $p->topik_follow_up ?? null;

                $detailHutang .= "- Pasien {$nomor} | Riwayat: {$riwayat} | Status: {$tugas}";

                if (!empty($topik)) {
                    $detailHutang .= " | Arahan: {$topik}";
                }

                $detailHutang .= "\n";
            }
        }

        $this->info('Mengirim ke AI Gemini...');

        $prompt =
            "Anda adalah H.A.N.A, Kepala Patient Advisor di Klinik Kanker. Anda harus membuat DUA pesan yang dipisahkan SECARA KETAT dengan teks penanda: |||SPLIT|||\n\n" .
            "BAGIAN 1: Briefing Pagi\n" .
            "- Ringkas data operasional kemarin:\n" .
            "  • Total chat yang ditangani tim PA/CS: {$totalChatDitanggapi} percakapan aktif.\n" .
            "  • Chat unik baru masuk: {$totalChatUnikBaru} pasien.\n" .
            "  • Sumber chat baru: {$totalGoogle} dari Google Ads/G-ID, {$totalFacebook} dari Facebook/F-ID, dan {$totalOrganik} organik/tanpa G-ID atau F-ID.\n" .
            "  • Minat chat baru: {$tanyaKemo} Kemo, {$tanyaRT} Radioterapi/RT, {$tanyaKonsul} Konsul, dan {$tanyaAwam} Awam/Belum jelas.\n" .
            "- Sebutkan bahwa ada {$totalHutang} pasien yang wajib di-follow up hari ini.\n" .
            "- Tulis paragraf pembuka yang ramah dan memotivasi tim PA. JANGAN menuliskan daftar pasien di Bagian 1.\n\n" .
            "|||SPLIT|||\n\n" .
            "BAGIAN 2: Daftar Follow Up\n" .
            "- Berikut adalah data pasien yang butuh dihubungi:\n{$detailHutang}\n" .
            "- Buatkan list yang rapi. Tulis nomor WA, riwayat singkat, alasan follow-up, dan saran chat empatik.\n" .
            "- Jangan mengarang fasilitas seperti diskon, penjemputan, penginapan, atau layanan yang tidak disebutkan di data.\n" .
            "- Jangan menjanjikan kesembuhan.\n" .
            "- Jika data kosong, tulis: 'Semua aman! Tidak ada pasien yang menggantung hari ini.'\n\n" .
            "ATURAN FORMAT: Gunakan tag HTML <b>teks</b> untuk menebalkan teks.";

        $geminiApiKey = config('services.gemini.api_key');
        $geminiModel = config('services.gemini.model', 'gemini-3-flash-preview');

        $narasi = "Gagal memuat narasi dari AI.";

        try {
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiApiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $result = $response->json('candidates.0.content.parts.0.text');

                if (!empty($result)) {
                    $narasi = $result;
                    $xrayText .= "✅ AI Gemini merespons dengan sukses.\n";
                }
            } else {
                $this->error('Gemini Error: ' . $response->body());
                Log::error("Gemini API Error Status {$response->status()}: " . $response->body());
                $xrayText .= "❌ AI Gemini Error: " . $response->body() . "\n";
            }
        } catch (\Exception $e) {
            $this->error('Gemini Exception: ' . $e->getMessage());
            Log::error('Gemini API Exception: ' . $e->getMessage());
            $xrayText .= "❌ AI Gemini Exception: " . $e->getMessage() . "\n";
        }

        $pesanArray = explode('|||SPLIT|||', $narasi);

        $pesanBriefing = trim($pesanArray[0] ?? $narasi);
        $pesanFollowUp = trim($pesanArray[1] ?? '');

        $narasiDb = str_replace(
            '|||SPLIT|||',
            "\n\n=== DAFTAR TUGAS FOLLOW UP ===\n\n",
            $narasi
        );

        $briefing = DailyBriefing::updateOrCreate(
            [
                'tanggal_briefing' => $now->toDateString(),
            ],
            [
                'narasi_teks' => trim($narasiDb),
                'xray_log' => $xrayText,
                'data_mentah' => [
                    'total_chat_ditanggapi' => $totalChatDitanggapi,
                    'chat_unik_baru' => $totalChatUnikBaru,
                    'google_gid' => $totalGoogle,
                    'facebook_fid' => $totalFacebook,
                    'organik' => $totalOrganik,
                    'minat_kemo' => $tanyaKemo,
                    'minat_rt' => $tanyaRT,
                    'minat_konsul' => $tanyaKonsul,
                    'minat_awam' => $tanyaAwam,
                    'hutang' => $totalHutang,
                    'batal' => count($batalOtomatis),
                ],
                'is_wa_sent' => false,
            ]
        );

        $fonnteToken = config('services.fonnte.token');
        $targetWa = config('services.fonnte.target_wa');

        $pesanWa1 = str_replace(
            ['<b>', '</b>', '<strong>', '</strong>'],
            '*',
            $pesanBriefing
        );

        $teksFinal1 =
            "👩‍⚕️ *H.A.N.A MORNING BRIEFING*\n" .
            "🗓️ _{$now->translatedFormat('l, d F Y')}_\n" .
            "──────────────────\n\n" .
            strip_tags($pesanWa1);

        $this->info('Mengirim WA ke Fonnte...');

        try {
            $resp1 = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $fonnteToken,
                ])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $targetWa,
                    'message' => $teksFinal1,
                ]);

            if (!empty($pesanFollowUp) && $totalHutang > 0) {
                sleep(2);

                $pesanWa2 = str_replace(
                    ['<b>', '</b>', '<strong>', '</strong>'],
                    '*',
                    $pesanFollowUp
                );

                $teksFinal2 =
                    "🎯 *DAFTAR EKSEKUSI FOLLOW-UP HARI INI*\n" .
                    "──────────────────\n\n" .
                    strip_tags($pesanWa2) .
                    "\n\n──────────────────\n" .
                    "💡 _Ayo tim, selesaikan hari ini agar pasien merasa diperhatikan!_ 💪";

                $resp2 = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => $fonnteToken,
                    ])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target' => $targetWa,
                        'message' => $teksFinal2,
                    ]);

                if (!$resp2->successful()) {
                    Log::error('Pesan kedua gagal dikirim ke Fonnte: ' . $resp2->body());
                    $this->error('Pesan kedua gagal dikirim ke Fonnte.');
                }
            }

            if ($resp1->successful()) {
                $briefing->update([
                    'is_wa_sent' => true,
                ]);

                $this->info('✅ BINGO! Proses H.A.N.A Selesai.');
            } else {
                Log::error('Pesan pertama gagal dikirim ke Fonnte: ' . $resp1->body());
                $this->error('Pesan pertama gagal dikirim ke Fonnte.');
            }
        } catch (\Exception $e) {
            $this->error('Sistem error WA: ' . $e->getMessage());
            Log::error('Sistem error saat mengirim WA: ' . $e->getMessage());
        }

        return 0;
    }

    private function isIndonesianHoliday(Carbon $date): array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://libur.deno.dev/api', [
                    'year' => $date->year,
                    'month' => $date->month,
                    'day' => $date->day,
                ]);

            if (!$response->successful()) {
                return [
                    'is_holiday' => false,
                    'holiday_names' => [],
                    'source' => 'api_failed',
                ];
            }

            $data = $response->json();

            return [
                'is_holiday' => $data['is_holiday'] ?? false,
                'holiday_names' => $data['holiday_list'] ?? [],
                'source' => 'api',
            ];
        } catch (\Exception $e) {
            Log::warning('Gagal cek hari libur Indonesia: ' . $e->getMessage());

            return [
                'is_holiday' => false,
                'holiday_names' => [],
                'source' => 'exception',
            ];
        }
    }
}