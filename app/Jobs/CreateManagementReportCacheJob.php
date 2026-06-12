<?php

namespace App\Jobs;

use App\Models\ManagementLeadSummary;
use App\Models\ManagementReportCache;
use App\Models\WaChat;
use App\Services\GeminiClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CreateManagementReportCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 240;

    public function __construct(
        protected string $periodKey,
        protected string $periodStart,
        protected string $periodEnd,
        protected int $ttlSeconds = 1800
    ) {}

    public function handle(GeminiClient $gemini): void
    {
        $start = Carbon::parse($this->periodStart, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($this->periodEnd, 'Asia/Jakarta')->endOfDay();

        $contextText = $this->buildContext($start, $end);
        $payloadHash = hash('sha256', $contextText);
        $estimatedTokens = (int) ceil(mb_strlen($contextText) / 4);

        $existing = ManagementReportCache::where('period_key', $this->periodKey)
            ->where('status', 'active')
            ->where('source_payload_hash', $payloadHash)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existing) {
            return;
        }

        $record = ManagementReportCache::create([
            'period_key' => $this->periodKey,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'model' => env('VERTEX_AI_MODEL', 'gemini-2.5-flash'),
            'ttl_seconds' => $this->ttlSeconds,
            'expires_at' => now()->addSeconds($this->ttlSeconds),
            'status' => 'creating',
            'source_payload_hash' => $payloadHash,
            'source_payload' => $contextText,
            'cached_token_count' => $estimatedTokens,
        ]);

        if ($estimatedTokens < 4096) {
            $record->update([
                'status' => 'skipped_small_context',
                'last_error' => "Context cache tidak dibuat karena konteks raw chat terlalu kecil. Estimasi token: {$estimatedTokens}. Tanya Data tetap bisa berjalan tanpa cache.",
            ]);

            return;
        }

        $response = $gemini->createContextCache(
            displayName: "AHCC Raw Chat Management Context {$this->periodKey}",
            contextText: $contextText,
            ttlSeconds: $this->ttlSeconds,
            options: [
                'timeout' => 180,
                'retries' => 1,
            ]
        );

        if (!$response->successful()) {
            $record->update([
                'status' => 'failed',
                'last_error' => $response->body(),
            ]);

            return;
        }

        $body = $response->json();

        $record->update([
            'cache_name' => $body['name'] ?? null,
            'model' => $body['model'] ?? env('VERTEX_AI_MODEL', 'gemini-2.5-flash'),
            'expires_at' => !empty($body['expireTime'])
                ? Carbon::parse($body['expireTime'])->timezone(config('app.timezone', 'Asia/Jakarta'))->timezone(config('app.timezone', 'Asia/Jakarta'))
                : now()->addSeconds($this->ttlSeconds),
            'status' => 'active',
            'cached_token_count' => data_get($body, 'usageMetadata.totalTokenCount', $estimatedTokens),
            'last_error' => null,
        ]);
    }

    private function buildContext(Carbon $start, Carbon $end): string
    {
        $summaries = ManagementLeadSummary::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->orderBy('period_start')
            ->orderBy('period_end')
            ->orderByDesc('management_score')
            ->get();

        if ($summaries->isEmpty()) {
            $summaries = ManagementLeadSummary::where('period_key', $this->periodKey)
                ->orderByDesc('management_score')
                ->get();
        }

        $chats = WaChat::whereBetween('chat_time', [$start, $end])
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->orderBy('client_number')
            ->orderBy('chat_time')
            ->get();

        $summaryByClient = $summaries->keyBy('client_number');

        $aggregate = [
            'period_key' => $this->periodKey,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_chat_clients' => $chats->pluck('client_number')->unique()->count(),
            'total_raw_chats' => $chats->count(),
            'total_patient_chats' => $chats->where('is_me', false)->count(),
            'total_cs_chats' => $chats->where('is_me', true)->count(),
            'total_management_summaries' => $summaries->count(),
            'average_management_score' => round((float) ($summaries->avg('management_score') ?? 0), 1),
            'lead_quality_segment_counts' => $this->simpleCountByField($summaries, 'lead_quality_segment'),
            'source_channel_counts' => $this->simpleCountByField($summaries, 'source_channel'),
            'kategori_kanker_counts' => $this->simpleCountByField($summaries, 'kategori_kanker_norm'),
            'minat_treatment_counts' => $this->simpleCountByField($summaries, 'minat_treatment_norm'),
            'question_theme_counts' => $this->simpleCountByField($summaries, 'question_theme'),
            'kendala_utama_counts' => $this->simpleCountByField($summaries, 'kendala_utama_norm'),
            'profil_pengirim_counts' => $this->simpleCountByField($summaries, 'profil_pengirim_norm'),
        ];

        $lines = [];
        $lines[] = "KONTEKS CACHE RAW CHAT LAPORAN MANAGEMENT AHCC";
        $lines[] = "Jawaban harus berdasarkan konteks ini saja.";
        $lines[] = "";
        $lines[] = "AGREGAT DATABASE DETERMINISTIK:";
        $lines[] = json_encode($aggregate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lines[] = "";
        $lines[] = "RAW CHAT WHATSAPP PER CLIENT + METADATA SUMMARY:";
        $lines[] = "";

        foreach ($chats->groupBy('client_number') as $clientNumber => $clientChats) {
            $summary = $summaryByClient->get($clientNumber);

            $lines[] = "CLIENT: {$clientNumber}";
            $lines[] = "METADATA SUMMARY:";
            $lines[] = "  source_channel: " . ($summary?->source_channel ?: 'Belum Ada Summary');
            $lines[] = "  lead_quality_segment: " . ($summary?->lead_quality_segment ?: 'Belum Ada Summary');
            $lines[] = "  management_score: " . ($summary?->management_score ?? 'Belum Ada Summary');
            $lines[] = "  kategori_kanker_norm: " . ($summary?->kategori_kanker_norm ?: 'Belum Ada Summary');
            $lines[] = "  minat_treatment_norm: " . ($summary?->minat_treatment_norm ?: 'Belum Ada Summary');
            $lines[] = "  question_theme: " . ($summary?->question_theme ?: 'Belum Ada Summary');
            $lines[] = "  kendala_utama_norm: " . ($summary?->kendala_utama_norm ?: 'Belum Ada Summary');
            $lines[] = "  profil_pengirim_norm: " . ($summary?->profil_pengirim_norm ?: 'Belum Ada Summary');
            $lines[] = "RAW CHAT:";

            foreach ($clientChats as $chat) {
                $sender = $chat->is_me ? 'CS AHCC' : 'Pasien';
                $time = $chat->chat_time ? Carbon::parse($chat->chat_time)->format('Y-m-d H:i') : '-';
                $message = trim((string) $chat->message);

                if ($message === '') {
                    continue;
                }

                $lines[] = "  [{$time}] {$sender}: " . Str::limit($message, 700);
            }

            $lines[] = "";
        }

        $context = implode("\n", $lines);

        if (mb_strlen($context) > 180000) {
            $context = mb_substr($context, 0, 180000)
                . "\n\n[CATATAN SISTEM: konteks cache dipotong karena terlalu panjang. Gunakan AGREGAT DATABASE DETERMINISTIK sebagai sumber utama untuk pertanyaan statistik.]";
        }

        return $context;
    }

    private function simpleCountByField($collection, string $field): array
    {
        return $collection
            ->map(fn ($row) => trim((string) ($row->{$field} ?? '')))
            ->filter(fn ($value) => $value !== '')
            ->groupBy(fn ($value) => $value)
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->toArray();
    }
}
