<?php

namespace App\Jobs;


use App\Services\GeminiClient;
use App\Models\LeadSummary;
use App\Models\ManagementLeadSummary;
use App\Models\WaChat;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessManagementLeadSummaryJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        protected string $clientNumber,
        protected string $periodKey,
        protected string $periodStart,
        protected string $periodEnd,
        protected bool $force = false
    ) {}

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        if (!$this->force) {
            $exists = ManagementLeadSummary::where('client_number', $this->clientNumber)
                ->where('period_key', $this->periodKey)
                ->exists();

            if ($exists) {
                return;
            }
        }

        $start = Carbon::parse($this->periodStart, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($this->periodEnd, 'Asia/Jakarta')->endOfDay();

        $chats = WaChat::where('client_number', $this->clientNumber)
            ->whereBetween('chat_time', [$start, $end])
            ->orderBy('chat_time', 'asc')
            ->get();

        if ($chats->isEmpty()) {
            return;
        }

        $operationalSummary = LeadSummary::where('client_number', $this->clientNumber)->first();

        $patientMessageCount = $chats->where('is_me', false)->count();
        $csMessageCount = $chats->where('is_me', true)->count();
        $firstChatAt = optional($chats->first())->chat_time;
        $lastChatAt = optional($chats->last())->chat_time;

        $sourceChannel = $this->detectSourceChannel($operationalSummary, $chats);

        $transcript = $this->buildTranscript($chats);
        $representativeFallback = $this->pickRepresentativeQuestion($chats);
$model = env('GEMINI_MODEL', 'gemini-3-flash-preview');

        $prompt = $this->buildManagementPrompt(
            transcript: $transcript,
            sourceChannel: $sourceChannel,
            representativeFallback: $representativeFallback,
            operationalSummary: $operationalSummary,
            patientMessageCount: $patientMessageCount,
            csMessageCount: $csMessageCount
        );
        $response = app(GeminiClient::class)->generateJson($prompt, [
            'temperature' => 0.2,
            'timeout' => 60,
            'retries' => 2,
        ]);

        
        if (!$response->successful()) {
            Log::error("Gagal memproses management summary untuk {$this->clientNumber}. Response: " . $response->body());
            return;
        }

        $rawAiResponse = (string) $response->json('candidates.0.content.parts.0.text', '');
        $result = $this->decodeGeminiJson($rawAiResponse);

        if (!$result) {
            Log::error("Gagal decode JSON management summary untuk {$this->clientNumber}. Raw: " . $rawAiResponse);
            return;
        }

        $managementScore = (int) ($result['management_score'] ?? 0);
        $managementScore = max(0, min(100, $managementScore));

        ManagementLeadSummary::updateOrCreate(
            [
                'client_number' => $this->clientNumber,
                'period_key' => $this->periodKey,
            ],
            [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),

                'source_channel' => $this->normalizeSourceChannel($result['source_channel'] ?? $sourceChannel),

                'representative_question' => $this->cleanText($result['representative_question'] ?? $representativeFallback),
                'patient_intent' => $this->cleanText($result['patient_intent'] ?? null),
                'question_theme' => $this->normalizeQuestionTheme($result['question_theme'] ?? null),
                'management_summary' => $this->cleanText($result['management_summary'] ?? null),

                'kategori_kanker_norm' => $this->cleanLabel($result['kategori_kanker_norm'] ?? $operationalSummary?->kategori_kanker),
                'minat_treatment_norm' => $this->cleanLabel($result['minat_treatment_norm'] ?? $operationalSummary?->minat_treatment),
                'metode_bayar_norm' => $this->cleanLabel($result['metode_bayar_norm'] ?? $operationalSummary?->metode_bayar),
                'profil_pengirim_norm' => $this->cleanLabel($result['profil_pengirim_norm'] ?? $operationalSummary?->profil_pengirim),
                'status_medis_norm' => $this->cleanLabel($result['status_medis_norm'] ?? $operationalSummary?->status_medis),
                'kendala_utama_norm' => $this->cleanLabel($result['kendala_utama_norm'] ?? $operationalSummary?->kendala_utama),

                'lead_quality_segment' => $this->normalizeLeadQuality($result['lead_quality_segment'] ?? null),
                'management_score' => $managementScore,
                'score_reason' => $this->cleanText($result['score_reason'] ?? null),

                'recommended_action' => $this->cleanText($result['recommended_action'] ?? null),
                'content_angle' => $this->cleanText($result['content_angle'] ?? null),
                'data_quality_note' => $this->cleanText($result['data_quality_note'] ?? null),

                'patient_message_count' => $patientMessageCount,
                'cs_message_count' => $csMessageCount,
                'first_chat_at' => $firstChatAt,
                'last_chat_at' => $lastChatAt,

                'ai_raw_response' => $rawAiResponse,
            ]
        );
    }

    private function buildTranscript($chats): string
    {
        $lines = [];

        foreach ($chats as $chat) {
            $sender = $chat->is_me ? 'CS AHCC' : 'Pasien';

            $time = $chat->chat_time
                ? Carbon::parse($chat->chat_time)->timezone('Asia/Jakarta')->format('d M Y H:i')
                : '-';

            $message = trim((string) $chat->message);

            if ($message === '') {
                continue;
            }

            $lines[] = "[{$time}] {$sender}: {$message}";
        }

        $text = implode("\n", $lines);

        if (mb_strlen($text) > 24000) {
            $text = mb_substr($text, -24000);
        }

        return $text;
    }

    private function pickRepresentativeQuestion($chats): ?string
    {
        $noisePatterns = [
            '/^\s*halo+\s*$/i',
            '/^\s*hai+\s*$/i',
            '/^\s*hi+\s*$/i',
            '/^\s*p+\s*$/i',
            '/^\s*min+\s*$/i',
            '/^\s*admin+\s*$/i',
            '/^\s*terima\s*kasih\s*$/i',
            '/^\s*thanks?\s*$/i',
            '/^\s*\[?g-id\s*:/i',
            '/gclid/i',
            '/fbclid/i',
        ];

        $intentKeywords = [
            'biaya',
            'harga',
            'jadwal',
            'dokter',
            'konsultasi',
            'radioterapi',
            'kemoterapi',
            'kemo',
            'kanker',
            'tumor',
            'bpjs',
            'asuransi',
            'daftar',
            'pendaftaran',
            'rujukan',
            'second opinion',
            'terapi',
            'pengobatan',
            'berapa',
            'bisa',
            'mau tanya',
        ];

        $patientChats = $chats->where('is_me', false);

        foreach ($patientChats as $chat) {
            $message = trim((string) $chat->message);

            if ($message === '' || mb_strlen($message) < 8) {
                continue;
            }

            $isNoise = false;

            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    $isNoise = true;
                    break;
                }
            }

            if ($isNoise) {
                continue;
            }

            $lower = mb_strtolower($message);

            foreach ($intentKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    return mb_strlen($message) > 300 ? mb_substr($message, 0, 300) . '...' : $message;
                }
            }
        }

        foreach ($patientChats as $chat) {
            $message = trim((string) $chat->message);

            if ($message !== '' && mb_strlen($message) >= 8) {
                return mb_strlen($message) > 300 ? mb_substr($message, 0, 300) . '...' : $message;
            }
        }

        return null;
    }

    private function detectSourceChannel($operationalSummary, $chats): string
    {
        if (!empty($operationalSummary?->gclid)) {
            return 'Google Ads';
        }

        if (!empty($operationalSummary?->fbclid)) {
            return 'Facebook Ads';
        }

        $allText = mb_strtolower($chats->pluck('message')->filter()->implode("\n"));

        if (str_contains($allText, 'gclid') || str_contains($allText, 'g-id:') || str_contains($allText, '[g-id')) {
            return 'Google Ads';
        }

        if (str_contains($allText, 'fbclid') || str_contains($allText, 'facebook')) {
            return 'Facebook Ads';
        }

        return 'Organik';
    }

    private function buildManagementPrompt(
        string $transcript,
        string $sourceChannel,
        ?string $representativeFallback,
        $operationalSummary,
        int $patientMessageCount,
        int $csMessageCount
    ): string {
        $existingContext = [
            'kategori_kanker' => $operationalSummary?->kategori_kanker,
            'minat_treatment' => $operationalSummary?->minat_treatment,
            'metode_bayar' => $operationalSummary?->metode_bayar,
            'profil_pengirim' => $operationalSummary?->profil_pengirim,
            'status_medis' => $operationalSummary?->status_medis,
            'kendala_utama' => $operationalSummary?->kendala_utama,
            'lead_score_operasional' => $operationalSummary?->lead_score,
            'conversation_outcome_operasional' => $operationalSummary?->conversation_outcome,
            'pipeline_status_operasional' => $operationalSummary?->pipeline_status,
        ];

        $existingJson = json_encode($existingContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Anda adalah analis CRM dan marketing untuk laporan management bulanan AHCC.

Tugas Anda adalah menganalisis percakapan WhatsApp pasien untuk kebutuhan presentasi management, bukan untuk follow-up operasional harian.

Fokus analisis:
1. kebutuhan utama pasien,
2. pertanyaan representatif yang layak ditampilkan di laporan,
3. kategori kanker yang ditanyakan,
4. treatment yang diminati,
5. kendala utama pasien,
6. profil pengirim chat,
7. kualitas lead dari sudut pandang management/marketing,
8. rekomendasi action untuk CS atau marketing,
9. angle konten, iklan, atau FAQ yang bisa dibuat.

Aturan penting:
- Jangan menebak diagnosis atau kondisi medis jika tidak eksplisit.
- Jika data tidak jelas, isi "Belum Diketahui".
- Gunakan label yang rapi dan konsisten.
- Jangan output markdown.
- Jangan output penjelasan di luar JSON.
- Output harus satu object JSON valid.
- representative_question harus berisi pertanyaan atau kebutuhan pasien yang paling bermakna. Jangan pilih G-ID, gclid, fbclid, "halo", atau pesan pembuka kosong.
- management_summary maksimal 2 kalimat.
- recommended_action harus praktis untuk CS/marketing.
- content_angle harus berupa ide konten/iklan/FAQ jika relevan.
- management_score adalah skor 0-100:
  0-20 = junk / salah klik / tidak relevan
  21-40 = low intent
  41-60 = moderate intent
  61-80 = strong intent
  81-100 = high intent / urgent / sangat potensial
- lead_quality_segment harus salah satu dari: Hot, Warm, Cold, Junk.
- source_channel harus salah satu dari: Google Ads, Facebook Ads, Organik.
- question_theme harus salah satu dari: Biaya, Jadwal, Treatment, Dokter, Administrasi, Rujukan, Lokasi, Lainnya, Belum Diketahui.

Label normalisasi yang disarankan:
- kategori_kanker_norm: Kanker Payudara, Kanker Serviks, Kanker Paru, Kanker Kolorektal, Kanker Nasofaring, Kanker Prostat, Tumor, Belum Diketahui, Lainnya.
- minat_treatment_norm: Radioterapi, Kemoterapi, Konsultasi Onkologi, Second Opinion, Pemeriksaan, Rawat Inap, Belum Diketahui, Lainnya.
- metode_bayar_norm: Pribadi, BPJS, Asuransi, Perusahaan, Belum Diketahui, Lainnya.
- profil_pengirim_norm: Pasien Sendiri, Anak Pasien, Suami/Istri, Keluarga, Tenaga Medis, Belum Diketahui, Lainnya.
- status_medis_norm: Sudah Diagnosis, Belum Diagnosis, Pasca Operasi, Sedang Kemoterapi, Sedang Radioterapi, Kontrol, Belum Diketahui, Lainnya.
- kendala_utama_norm: Biaya, Jadwal Dokter, Trust, Jarak/Lokasi, Administrasi, Rujukan, Belum Ada, Belum Diketahui, Lainnya.

Channel awal terdeteksi: {$sourceChannel}
Representative fallback dari sistem: {$representativeFallback}
Jumlah pesan pasien: {$patientMessageCount}
Jumlah pesan CS: {$csMessageCount}

Konteks summary operasional yang sudah ada:
{$existingJson}

Transcript WhatsApp:
{$transcript}

Kembalikan JSON dengan format tepat seperti ini:
{
  "source_channel": "Google Ads / Facebook Ads / Organik",
  "representative_question": "...",
  "patient_intent": "...",
  "question_theme": "Biaya / Jadwal / Treatment / Dokter / Administrasi / Rujukan / Lokasi / Lainnya / Belum Diketahui",
  "management_summary": "...",
  "kategori_kanker_norm": "...",
  "minat_treatment_norm": "...",
  "metode_bayar_norm": "...",
  "profil_pengirim_norm": "...",
  "status_medis_norm": "...",
  "kendala_utama_norm": "...",
  "lead_quality_segment": "Hot / Warm / Cold / Junk",
  "management_score": 0,
  "score_reason": "...",
  "recommended_action": "...",
  "content_angle": "...",
  "data_quality_note": "..."
}
PROMPT;
    }

    private function decodeGeminiJson(?string $rawAiResponse): ?array
    {
        if (!$rawAiResponse) {
            return null;
        }

        $clean = trim($rawAiResponse);
        $clean = preg_replace('/^```json\s*/i', '', $clean);
        $clean = preg_replace('/^```\s*/', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

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

    private function normalizeSourceChannel(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value));

        if (str_contains($text, 'google')) {
            return 'Google Ads';
        }

        if (str_contains($text, 'facebook') || str_contains($text, 'meta')) {
            return 'Facebook Ads';
        }

        return 'Organik';
    }

    private function normalizeQuestionTheme(?string $value): string
    {
        $allowed = [
            'Biaya',
            'Jadwal',
            'Treatment',
            'Dokter',
            'Administrasi',
            'Rujukan',
            'Lokasi',
            'Lainnya',
            'Belum Diketahui',
        ];

        $text = trim((string) $value);

        foreach ($allowed as $item) {
            if (mb_strtolower($text) === mb_strtolower($item)) {
                return $item;
            }
        }

        return 'Belum Diketahui';
    }

    private function normalizeLeadQuality(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value));

        return match (true) {
            str_contains($text, 'hot') => 'Hot',
            str_contains($text, 'warm') => 'Warm',
            str_contains($text, 'cold') => 'Cold',
            str_contains($text, 'junk') => 'Junk',
            default => 'Cold',
        };
    }

    private function cleanText(?string $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function cleanLabel(?string $value): string
    {
        $text = trim((string) $value);

        return $text === '' ? 'Belum Diketahui' : $text;
    }
}
