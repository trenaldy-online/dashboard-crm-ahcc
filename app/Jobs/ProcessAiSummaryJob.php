<?php

namespace App\Jobs;


use App\Services\GeminiClient;
use App\Models\WaChat;
use App\Models\LeadSummary;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $chats = WaChat::where('client_number', $this->clientNumber)
            ->orderBy('chat_time', 'asc')
            ->get();

        if ($chats->isEmpty()) {
            return;
        }

        $existingLead = LeadSummary::where('client_number', $this->clientNumber)->first();

        $patientMessageCount = 0;
        $csMessageCount = 0;
        $lastPatientMessage = '';
        $lastCsMessage = '';
        $allPatientText = '';
        $allCsText = '';
        $transcript = '';

        foreach ($chats as $chat) {
            $sender = $chat->is_me ? 'CS AHCC' : 'Pasien';
            $waktu = Carbon::parse($chat->chat_time)
                ->timezone('Asia/Jakarta')
                ->format('d M Y, H:i');

            $message = trim($chat->message ?? '');

            $transcript .= "[{$waktu}] {$sender}: {$message}\n";

            if ($chat->is_me) {
                $csMessageCount++;
                $lastCsMessage = strtolower($message);
                $allCsText .= ' ' . strtolower($message);
            } else {
                $patientMessageCount++;
                $lastPatientMessage = strtolower($message);
                $allPatientText .= ' ' . strtolower($message);
            }
        }

        $statusLama = $existingLead ? $existingLead->pipeline_status : 'Belum Terdaftar (Pasien Baru)';
        $hariIni = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');

        $conversationOutcome = 'pending';
        $isEligibleForHana = false;
        $skipGemini = false;

        $highIntentKeywords = [
            'biaya',
            'harga',
            'tarif',
            'jadwal',
            'dokter',
            'konsultasi',
            'daftar',
            'booking',
            'reservasi',
            'radioterapi',
            'radio terapi',
            'radiasi',
            'kemoterapi',
            'kemo',
            'kanker',
            'tumor',
            'hasil lab',
            'hasil biopsi',
            'hasil patologi',
            'operasi',
            'linac',
            'terapi',
            'pengobatan',
            'second opinion',
        ];

        $isHighIntent = false;

        foreach ($highIntentKeywords as $keyword) {
            if (str_contains($allPatientText, $keyword)) {
                $isHighIntent = true;
                break;
            }
        }

        $veryLowIntentMessages = [
            'p',
            'halo',
            'hallo',
            'hi',
            'hai',
            'tes',
            'test',
            'min',
            'admin',
            'assalamualaikum',
            'selamat pagi',
            'selamat siang',
            'selamat sore',
            'selamat malam',
        ];

        $cleanLastPatient = trim(strtolower($lastPatientMessage));
        $isVeryLowIntent = in_array($cleanLastPatient, $veryLowIntentMessages, true);

        if ($patientMessageCount <= 1 && !$isHighIntent) {
            $conversationOutcome = 'junk_lead';
            $isEligibleForHana = false;
            $skipGemini = true;
        }

        if ($conversationOutcome === 'pending' && $patientMessageCount <= 1 && $isVeryLowIntent) {
            $conversationOutcome = 'junk_lead';
            $isEligibleForHana = false;
            $skipGemini = true;
        }

        $redirectKeywords = [
            'silakan hubungi',
            'silahkan hubungi',
            'dapat menghubungi',
            'bisa menghubungi',
            'hubungi nomor',
            'nomor rekanan',
            'nomor tersebut',
            'kami arahkan ke',
            'kami arahkan',
            'rekanan',
            'divisi terkait',
            'bagian terkait',
            'admin terkait',
            'cs umum',
            'mri',
            'ct scan',
            'laboratorium',
            'rawat inap',
            'tidak tersedia di ahcc',
            'di luar layanan kami',
        ];

        $isRedirectedByCs = false;

        foreach ($redirectKeywords as $keyword) {
            if (str_contains($allCsText, $keyword)) {
                $isRedirectedByCs = true;
                break;
            }
        }

        $closingKeywords = [
            'terima kasih',
            'terimakasih',
            'makasih',
            'thanks',
            'thank you',
            'ok',
            'oke',
            'baik',
            'siap',
            'sudah jelas',
            'nanti saya kabari',
        ];

        $continuingIntentKeywords = [
            'mau daftar',
            'mau booking',
            'mau konsultasi',
            'jadwalnya',
            'berapa biaya',
            'bisa hari ini',
            'lanjut',
            'bagaimana caranya',
            'alamat',
            'dokternya',
        ];

        $hasClosingKeyword = false;
        foreach ($closingKeywords as $keyword) {
            if (str_contains($lastPatientMessage, $keyword)) {
                $hasClosingKeyword = true;
                break;
            }
        }

        $hasContinuingIntent = false;
        foreach ($continuingIntentKeywords as $keyword) {
            if (str_contains($lastPatientMessage, $keyword)) {
                $hasContinuingIntent = true;
                break;
            }
        }

        if ($conversationOutcome === 'pending' && $isRedirectedByCs && $hasClosingKeyword && !$hasContinuingIntent) {
            $conversationOutcome = 'redirected';
            $isEligibleForHana = false;
            $skipGemini = true;
        }

        if ($conversationOutcome === 'pending' && $hasClosingKeyword && !$hasContinuingIntent && !$isHighIntent) {
            $conversationOutcome = 'resolved';
            $isEligibleForHana = false;
            $skipGemini = true;
        }

        if ($skipGemini) {
            $isHumanValidated = $existingLead?->is_human_validated ?? false;

            LeadSummary::updateOrCreate(
                ['client_number' => $this->clientNumber],
                [
                    'patient_message_count' => $patientMessageCount,
                    'conversation_outcome' => $conversationOutcome,
                    'is_eligible_for_hana' => false,

                    'is_human_validated' => $isHumanValidated,
                    'pipeline_status' => $isHumanValidated ? $existingLead->pipeline_status : ($existingLead->pipeline_status ?? 'leads_baru'),

                    'perlu_follow_up' => $existingLead?->perlu_follow_up ?? false,
                    'alasan_follow_up' => $existingLead?->alasan_follow_up,

                    'kategori_kanker' => $existingLead->kategori_kanker ?? 'Belum Terdeteksi',
                    'ringkasan' => $existingLead->ringkasan ?? $this->fallbackRingkasan($conversationOutcome),
                    'topik_follow_up' => null,

                    'lead_score' => $existingLead->lead_score ?? 0,
                    'minat_treatment' => $existingLead->minat_treatment ?? 'Belum Diketahui',
                    'metode_bayar' => $existingLead->metode_bayar ?? 'Belum Diketahui',
                    'profil_pengirim' => $existingLead->profil_pengirim ?? 'Belum Diketahui',
                    'status_medis' => $existingLead->status_medis ?? 'Belum Diketahui',
                    'sentimen_emosi' => $existingLead->sentimen_emosi ?? 'Biasa',
                    'kendala_utama' => $existingLead->kendala_utama ?? 'Belum Ada',
                    'gclid' => $existingLead->gclid ?? null,
                    'fbclid' => $existingLead->fbclid ?? null,
                    'tunda_sampai_tanggal' => $existingLead->tunda_sampai_tanggal ?? null,
                ]
            );

            return;
        }

        $prompt = "Anda adalah H.A.N.A, Direktur CRM, Ahli Data Medis, sekaligus Senior Patient Advisor di RS Kanker AHCC.
Tugas Anda adalah membaca transkrip obrolan WA pasien ini dan mengekstrak datanya ke dalam format JSON yang valid.

TRANSKRIP CHAT:
\"\"\"
{$transcript}
\"\"\"

INFO PENTING:
- Status pasien sebelumnya adalah '{$statusLama}'.
- Hari ini adalah: {$hariIni}.
- Jumlah pesan pasien: {$patientMessageCount}.
- Jumlah pesan CS: {$csMessageCount}.
- Hasil heuristic sementara: {$conversationOutcome}.

ATURAN MUTLAK:
- JANGAN MENEBAK.
- Jika informasi tidak disebutkan eksplisit di chat, isi dengan 'Belum Diketahui' atau null.
- Jangan menganggap semua pasien yang diam sebagai layak follow-up.
- Tentukan apakah percakapan ini memang layak dikejar ulang oleh PA.

EVALUASI CONVERSATION OUTCOME:
Pilih salah satu:
1. 'junk_lead' -> Jika pasien hanya basa-basi, salah klik, sangat minim konteks, atau hanya 1 pesan pendek tanpa intent kuat.
2. 'resolved' -> Jika kebutuhan pasien sudah terjawab dan pasien menutup percakapan secara natural.
3. 'redirected' -> Jika CS mengarahkan pasien ke nomor/divisi/rekanan lain, terutama layanan di luar scope AHCC.
4. 'engaged_followup' -> Jika pasien menunjukkan minat kuat terkait layanan kanker AHCC, tetapi belum membuat jadwal, belum deal, atau percakapan terputus.

CATATAN PENTING:
- Satu pesan pasien BISA tetap engaged_followup jika berisi intent kuat seperti biaya, jadwal, dokter, konsultasi, radioterapi, kemoterapi, kanker, terapi, atau pendaftaran.
- Jika CS sudah redirect ke nomor lain dan pasien menutup dengan 'terima kasih/baik/ok', pilih redirected/resolved, bukan engaged_followup.
- Jika pasien hanya tanya layanan yang bukan AHCC dan sudah diarahkan, jangan jadikan follow-up.

KEMBALIKAN HANYA OBJEK JSON VALID TANPA MARKDOWN:
{
    \"kategori_kanker\": \"Jenis kanker atau 'Belum Terdeteksi'\",
    \"ringkasan\": \"Rangkuman obrolan maksimal 2 kalimat\",
    \"pipeline_status\": \"Pilih salah satu: leads_baru, edukasi, konsultasi, deal. Pilih batal hanya jika pasien menolak keras/meninggal.\",
    \"analisa_logika\": \"Alasan memilih conversation_outcome\",
    \"conversation_outcome\": \"junk_lead/resolved/redirected/engaged_followup\",
    \"topik_follow_up\": \"Jika engaged_followup, buat 1 kalimat saran follow-up. Jika tidak, isi null.\",
    \"lead_score\": 0,
    \"minat_treatment\": \"Radioterapi, Kemo, dll atau 'Belum Diketahui'\",
    \"metode_bayar\": \"Pribadi, BPJS, Asuransi, dll atau 'Belum Diketahui'\",
    \"profil_pengirim\": \"Pasien Sendiri, Anak, Keluarga, dll atau 'Belum Diketahui'\",
    \"status_medis\": \"Sedang Kemo, Pasca Operasi, dll atau 'Belum Diketahui'\",
    \"sentimen_emosi\": \"Panik, Cemas, Biasa, dll\",
    \"kendala_utama\": \"Biaya/Jarak/Efek Samping/Ragu/Belum Ada\",
    \"gclid\": null,
    \"fbclid\": null,
    \"tunda_sampai_tanggal\": null
}";
try {
        $response = app(GeminiClient::class)->generateJson($prompt, [
            'temperature' => 0.2,
            'timeout' => 60,
            'retries' => 2,
        ]);

        
            if (!$response->successful()) {
                Log::error("API Gemini Error (Pasien {$this->clientNumber}): " . $response->body());
                return;
            }

            $rawAiResponse = (string) $response->json('candidates.0.content.parts.0.text', '');

            $result = $this->decodeGeminiJson($rawAiResponse);

            if (!$result) {
                Log::error("Gagal decode JSON Gemini untuk pasien {$this->clientNumber}. Raw: " . $rawAiResponse);
                return;
            }

            if ($conversationOutcome === 'pending') {
                $conversationOutcome = $result['conversation_outcome'] ?? 'junk_lead';
            }

            $allowedOutcomes = ['junk_lead', 'resolved', 'redirected', 'engaged_followup'];

            if (!in_array($conversationOutcome, $allowedOutcomes, true)) {
                $conversationOutcome = 'junk_lead';
            }

            $isEligibleForHana = ($conversationOutcome === 'engaged_followup');
            $isHumanValidated = $existingLead?->is_human_validated ?? false;

            LeadSummary::updateOrCreate(
                ['client_number' => $this->clientNumber],
                [
                    'patient_message_count' => $patientMessageCount,
                    'conversation_outcome' => $conversationOutcome,
                    'is_eligible_for_hana' => $isEligibleForHana,

                    'is_human_validated' => $isHumanValidated,
                    'pipeline_status' => $isHumanValidated
                        ? $existingLead->pipeline_status
                        : ($result['pipeline_status'] ?? 'leads_baru'),

                    'perlu_follow_up' => $existingLead?->perlu_follow_up ?? false,
                    'alasan_follow_up' => $existingLead?->alasan_follow_up,

                    'kategori_kanker' => $result['kategori_kanker'] ?? 'Belum Terdeteksi',
                    'ringkasan' => $result['ringkasan'] ?? 'Tidak ada ringkasan',
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
                    'tunda_sampai_tanggal' => $result['tunda_sampai_tanggal'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            Log::error("ProcessAiSummaryJob Error untuk pasien {$this->clientNumber}: " . $e->getMessage());
        }
    }

    private function decodeGeminiJson(?string $rawAiResponse): ?array
{
    if (!$rawAiResponse) {
        return null;
    }

    $clean = trim($rawAiResponse);

    // Bersihkan jika Gemini tetap membungkus dengan markdown.
    $clean = preg_replace('/^```json\s*/i', '', $clean);
    $clean = preg_replace('/^```\s*/', '', $clean);
    $clean = preg_replace('/\s*```$/', '', $clean);
    $clean = trim($clean);

    // Coba decode normal dulu.
    $decoded = json_decode($clean, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    // Kalau gagal, ambil objek JSON pertama yang balanced.
    // Ini menangani kasus Gemini memberi output:
    // { ... }
    // }
    $jsonObject = $this->extractFirstBalancedJsonObject($clean);

    if (!$jsonObject) {
        return null;
    }

    $decoded = json_decode($jsonObject, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    return null;
}

    private function extractFirstBalancedJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function fallbackRingkasan(string $conversationOutcome): string
    {
        return match ($conversationOutcome) {
            'junk_lead' => 'Lead tidak menunjukkan engagement yang cukup untuk follow-up H.A.N.A.',
            'redirected' => 'Percakapan diarahkan ke nomor atau divisi lain dan tidak perlu follow-up H.A.N.A.',
            'resolved' => 'Percakapan sudah selesai secara natural dan tidak perlu follow-up H.A.N.A.',
            default => 'Belum ada ringkasan.',
        };
    }
}