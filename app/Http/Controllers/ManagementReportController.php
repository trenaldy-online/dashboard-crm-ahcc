<?php

namespace App\Http\Controllers;












use App\Models\ManagementClassificationRule;use App\Models\ManagementSummaryCorrection;use Illuminate\Bus\Batch;use App\Jobs\CreateManagementReportCacheJob;use Illuminate\Support\Str;use App\Services\GeminiClient;use App\Models\ManagementReportQuestion;use App\Models\ManagementReportCache;use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\Bus;use App\Jobs\ProcessManagementLeadSummaryJob;use App\Models\ManagementLeadSummary;
use App\Models\WaChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ManagementReportController extends Controller
{
    public function index(Request $request)
    {
        [$start, $end, $periodKey, $periodLabel] = $this->resolvePeriod($request);

        $summaries = $this->getManagementSummaries($periodKey, $start, $end);

        $periodChatClients = WaChat::whereBetween('chat_time', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->distinct()
            ->count('client_number');

        $periodPatientClients = WaChat::whereBetween('chat_time', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->where('is_me', false)
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->distinct()
            ->count('client_number');

        $rawChatCount = WaChat::whereBetween('chat_time', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->count();

        $processedCount = $summaries->count();
        $avgScore = round((float) ($summaries->avg('management_score') ?? 0), 1);

        $hotCount = $summaries->where('lead_quality_segment', 'Hot')->count();
        $warmCount = $summaries->where('lead_quality_segment', 'Warm')->count();
        $coldCount = $summaries->where('lead_quality_segment', 'Cold')->count();
        $junkCount = $summaries->where('lead_quality_segment', 'Junk')->count();

        $hotWarmCount = $hotCount + $warmCount;
        $unsummarizedPatientClients = max($periodPatientClients - $processedCount, 0);

        $channelStats = $this->buildChannelStats($summaries);

        $topCancers = $this->countByField($summaries, 'kategori_kanker_norm', 10);
        $topTreatments = $this->countByField($summaries, 'minat_treatment_norm', 10);
        $topProfiles = $this->countByField($summaries, 'profil_pengirim_norm', 10);
        $topPayments = $this->countByField($summaries, 'metode_bayar_norm', 10);
        $topPainPoints = $this->countByField($summaries, 'kendala_utama_norm', 10);
        $topQuestionThemes = $this->countByField($summaries, 'question_theme', 10);
        $qualitySegments = $this->countByField($summaries, 'lead_quality_segment', 10, false);

        $representativeQuestions = $summaries
            ->filter(fn ($row) => $this->isUsefulText($row->representative_question))
            ->sortByDesc('management_score')
            ->values()
            ->take(20);

        $recommendedActions = $summaries
            ->filter(fn ($row) => $this->isUsefulText($row->recommended_action))
            ->sortByDesc('management_score')
            ->values()
            ->take(12);

        $contentAngles = $summaries
            ->filter(fn ($row) => $this->isUsefulText($row->content_angle))
            ->sortByDesc('management_score')
            ->values()
            ->take(12);

        $lastProcessedAt = $summaries->max('updated_at');

        $executiveInsight = $this->buildExecutiveInsight(
            summaries: $summaries,
            channelStats: $channelStats,
            topTreatments: $topTreatments,
            topPainPoints: $topPainPoints,
            periodLabel: $periodLabel
        );

        $artisanCommand = $this->buildArtisanCommand($periodKey, $start, $end);

        $activeCache = ManagementReportCache::where('period_key', $periodKey)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        $qaHistory = $qaHistory ?? ManagementReportQuestion::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->latest()
            ->limit(12)
            ->get();


        $batchId = $request->query('batch_id') ?: session('last_management_batch_id');
        $batchInfo = null;

        if ($batchId) {
            $batch = Bus::findBatch($batchId);

            if ($batch) {
                $batchInfo = [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'failed_jobs' => $batch->failedJobs,
                    'progress' => $batch->progress(),
                    'finished' => $batch->finished(),
                    'cancelled' => $batch->cancelled(),
                    'created_at' => $this->formatBatchDate($batch->createdAt),
                    'finished_at' => $this->formatBatchDate($batch->finishedAt),
                ];
            }
        }


        $headerTitle = 'Laporan Management';


        $activeCache = $activeCache ?? ManagementReportCache::where('period_key', $periodKey)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        $qaHistory = $qaHistory ?? ManagementReportQuestion::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->latest()
            ->limit(12)
            ->get();

        $coverageStart = $start;
        $coverageEnd = $end;
        $processingCoverage = $this->buildProcessingCoverage($coverageStart, $coverageEnd);


        $pageStart = $pageStart ?? ($start ?? null)?->toDateString() ?? ($startDate ?? null)?->toDateString() ?? request('start_date') ?? now()->startOfMonth()->toDateString();
        $pageEnd = $pageEnd ?? ($end ?? null)?->toDateString() ?? ($endDate ?? null)?->toDateString() ?? request('end_date') ?? now()->toDateString();


        $kpiSummaryRows = $summaries ?? $managementSummaries ?? $summaryRows ?? collect();

        if (! $kpiSummaryRows instanceof \Illuminate\Support\Collection) {
            $kpiSummaryRows = collect($kpiSummaryRows);
        }

        $hotCount = $hotCount ?? $kpiSummaryRows->where('lead_quality_segment', 'Hot')->count();
        $warmCount = $warmCount ?? $kpiSummaryRows->where('lead_quality_segment', 'Warm')->count();
        $coldCount = $coldCount ?? $kpiSummaryRows->where('lead_quality_segment', 'Cold')->count();
        $junkCount = $junkCount ?? $kpiSummaryRows->where('lead_quality_segment', 'Junk')->count();
        $hotWarmCount = $hotWarmCount ?? ($hotCount + $warmCount);
        $unclassifiedLeadCount = $unclassifiedLeadCount ?? max(0, $kpiSummaryRows->count() - $hotCount - $warmCount - $coldCount - $junkCount);


        $channelDetailRows = ManagementLeadSummary::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->get();

        $makeChannelCounts = function ($items, string $field): array {
            $total = max($items->count(), 1);

            return $items
                ->map(fn ($row) => trim((string) ($row->{$field} ?? '')))
                ->map(fn ($value) => $value !== '' ? $value : 'Belum Diketahui')
                ->groupBy(fn ($value) => $value)
                ->map(function ($group, $label) use ($total) {
                    return [
                        'label' => $label,
                        'count' => $group->count(),
                        'percentage' => round(($group->count() / $total) * 100, 1),
                    ];
                })
                ->sortByDesc('count')
                ->values()
                ->all();
        };

        
        $ambiguousValues = [
            '',
            '-',
            'Belum Diketahui',
            'Lainnya',
            'Treatment',
            'Unknown',
            'Tidak Diketahui',
            'N/A',
            'Belum Ada',
        ];


        $ambiguousValues = $ambiguousValues ?? [
            '',
            '-',
            'Belum Diketahui',
            'Lainnya',
            'Treatment',
            'Unknown',
            'Tidak Diketahui',
            'N/A',
            'Belum Ada',
        ];

        $correctionFields = $correctionFields ?? [
            'kategori_kanker_norm' => 'Kanker yang Ditanyakan',
            'minat_treatment_norm' => 'Treatment yang Diminati',
            'question_theme' => 'Tema Pertanyaan',
            'profil_pengirim_norm' => 'Profil Pengirim',
            'kendala_utama_norm' => 'Kendala Utama',
        ];

        $detailFields = $detailFields ?? $correctionFields;

$channelDetails = $channelDetailRows
            ->groupBy(fn ($row) => trim((string) ($row->source_channel ?: 'Belum Diketahui')))
            ->map(function ($items, $channel) use ($makeChannelCounts, $ambiguousValues, $correctionFields, $detailFields) {
                $hot = $items->where('lead_quality_segment', 'Hot')->count();
                $warm = $items->where('lead_quality_segment', 'Warm')->count();
                $cold = $items->where('lead_quality_segment', 'Cold')->count();
                $junk = $items->where('lead_quality_segment', 'Junk')->count();
                $total = max($items->count(), 1);

                return [
                    'channel' => $channel,
                    'total' => $items->count(),
                    'hot' => $hot,
                    'warm' => $warm,
                    'cold' => $cold,
                    'junk' => $junk,
                    'hot_warm_rate' => round((($hot + $warm) / $total) * 100, 1),
                    'junk_rate' => round(($junk / $total) * 100, 1),
                    'avg_score' => round((float) ($items->avg('management_score') ?? 0), 1),
                    'cancers' => $makeChannelCounts($items, 'kategori_kanker_norm'),
                    'treatments' => $makeChannelCounts($items, 'minat_treatment_norm'),
                    'question_themes' => $makeChannelCounts($items, 'question_theme'),
                    'questions' => $items
                        ->map(function ($row) {
                            return [
                                'client_number' => $row->client_number,
                                'question' => $row->representative_question ?: $row->patient_intent ?: $row->management_summary ?: '-',
                                'segment' => $row->lead_quality_segment ?: '-',
                                'cancer' => $row->kategori_kanker_norm ?: '-',
                                'treatment' => $row->minat_treatment_norm ?: '-',
                                'score' => $row->management_score,
                            ];
                        })
                        ->filter(fn ($row) => trim((string) $row['question']) !== '-')
                        ->take(12)
                        ->values()
                        ->all(),
                ];
            })
            ->values();
$channelDetails = $channelDetailRows
            ->groupBy(fn ($row) => trim((string) ($row->source_channel ?: 'Belum Diketahui')))
            ->map(function ($items, $channel) use ($makeChannelCounts, $ambiguousValues, $correctionFields) {
                $hot = $items->where('lead_quality_segment', 'Hot')->count();
                $warm = $items->where('lead_quality_segment', 'Warm')->count();
                $cold = $items->where('lead_quality_segment', 'Cold')->count();
                $junk = $items->where('lead_quality_segment', 'Junk')->count();
                $total = max($items->count(), 1);

                $ambiguousRows = [];

                foreach ($items as $row) {
                    foreach ($correctionFields as $fieldName => $fieldLabel) {
                        $currentValue = trim((string) ($row->{$fieldName} ?? ''));

                        if (in_array($currentValue, $ambiguousValues, true)) {
                            $ambiguousRows[] = [
                                'summary_id' => $row->id,
                                'client_number' => $row->client_number,
                                'field_name' => $fieldName,
                                'field_label' => $fieldLabel,
                                'current_value' => $currentValue !== '' ? $currentValue : 'Belum Diketahui',
                                'representative_question' => $row->representative_question ?: '-',
                                'patient_intent' => $row->patient_intent ?: '-',
                                'management_summary' => $row->management_summary ?: '-',
                                'kategori_kanker_norm' => $row->kategori_kanker_norm ?: '-',
                                'minat_treatment_norm' => $row->minat_treatment_norm ?: '-',
                                'question_theme' => $row->question_theme ?: '-',
                                'profil_pengirim_norm' => $row->profil_pengirim_norm ?: '-',
                                'kendala_utama_norm' => $row->kendala_utama_norm ?: '-',
                            ];
                        }
                    }
                }

                return [
                    'channel' => $channel,
                    'total' => $items->count(),
                    'hot' => $hot,
                    'warm' => $warm,
                    'cold' => $cold,
                    'junk' => $junk,
                    'hot_warm_rate' => round((($hot + $warm) / $total) * 100, 1),
                    'junk_rate' => round(($junk / $total) * 100, 1),
                    'avg_score' => round((float) ($items->avg('management_score') ?? 0), 1),
                    'cancers' => $makeChannelCounts($items, 'kategori_kanker_norm'),
                    'treatments' => $makeChannelCounts($items, 'minat_treatment_norm'),
                    'question_themes' => $makeChannelCounts($items, 'question_theme'),
                    'sender_profiles' => $makeChannelCounts($items, 'profil_pengirim_norm'),
                    'main_obstacles' => $makeChannelCounts($items, 'kendala_utama_norm'),
                    'ambiguous_rows' => array_slice($ambiguousRows, 0, 80),
                ];
            })
            ->values();


        // AHCC CHANNEL DETAILS POPUP V3 FIELD-FIRST
        $channelDetailRows = ManagementLeadSummary::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->get();

        $detailFields = [
            'kategori_kanker_norm' => 'Kanker yang Ditanyakan',
            'minat_treatment_norm' => 'Treatment yang Diminati',
            'question_theme' => 'Tema Pertanyaan',
            'profil_pengirim_norm' => 'Profil Pengirim',
            'kendala_utama_norm' => 'Kendala Utama',
        ];

        $ambiguousValues = [
            '',
            '-',
            'Belum Diketahui',
            'Lainnya',
            'Treatment',
            'Unknown',
            'Tidak Diketahui',
            'N/A',
            'Belum Ada',
        ];

        $channelDetails = $channelDetailRows
            ->groupBy(fn ($row) => trim((string) ($row->source_channel ?: 'Belum Diketahui')))
            ->map(function ($items, $channel) use ($detailFields, $ambiguousValues, $correctionFields) {
                $hot = $items->where('lead_quality_segment', 'Hot')->count();
                $warm = $items->where('lead_quality_segment', 'Warm')->count();
                $cold = $items->where('lead_quality_segment', 'Cold')->count();
                $junk = $items->where('lead_quality_segment', 'Junk')->count();
                $total = max($items->count(), 1);

                $breakdowns = [];

                foreach ($detailFields as $fieldName => $fieldLabel) {
                    $valueGroups = $items->groupBy(function ($row) use ($fieldName) {
                        $value = trim((string) ($row->{$fieldName} ?? ''));

                        return $value !== '' ? $value : 'Belum Diketahui';
                    });

                    $values = $valueGroups
                        ->map(function ($group, $value) use ($total, $ambiguousValues) {
                            return [
                                'value' => $value,
                                'count' => $group->count(),
                                'percentage' => round(($group->count() / $total) * 100, 1),
                                'is_ambiguous' => in_array($value, $ambiguousValues, true),
                            ];
                        })
                        ->sortByDesc('count')
                        ->values()
                        ->all();

                    $rowsByValue = [];

                    foreach ($valueGroups as $value => $group) {
                        $rowsByValue[$value] = $group
                            ->map(function ($row) use ($fieldName, $value) {
                                return [
                                    'summary_id' => $row->id,
                                    'client_number' => $row->client_number,
                                    'field_name' => $fieldName,
                                    'current_value' => $value,
                                    'representative_question' => $row->representative_question ?: '-',
                                    'patient_intent' => $row->patient_intent ?: '-',
                                    'management_summary' => $row->management_summary ?: '-',
                                    'kategori_kanker_norm' => $row->kategori_kanker_norm ?: '-',
                                    'minat_treatment_norm' => $row->minat_treatment_norm ?: '-',
                                    'question_theme' => $row->question_theme ?: '-',
                                    'profil_pengirim_norm' => $row->profil_pengirim_norm ?: '-',
                                    'kendala_utama_norm' => $row->kendala_utama_norm ?: '-',
                                    'lead_quality_segment' => $row->lead_quality_segment ?: '-',
                                    'management_score' => $row->management_score,
                                ];
                            })
                            ->values()
                            ->all();
                    }

                    $breakdowns[$fieldName] = [
                        'field_name' => $fieldName,
                        'field_label' => $fieldLabel,
                        'values' => $values,
                        'rows_by_value' => $rowsByValue,
                    ];
                }

                return [
                    'channel' => $channel,
                    'total' => $items->count(),
                    'hot' => $hot,
                    'warm' => $warm,
                    'cold' => $cold,
                    'junk' => $junk,
                    'hot_warm_rate' => round((($hot + $warm) / $total) * 100, 1),
                    'junk_rate' => round(($junk / $total) * 100, 1),
                    'avg_score' => round((float) ($items->avg('management_score') ?? 0), 1),
                    'breakdowns' => $breakdowns,
                ];
            })
            ->values();


        // AHCC KANBAN SALES PIPELINE FUNNEL
        $kanbanStart = $start;
        $kanbanEnd = $end;

        $kanbanClientNumbers = WaChat::whereBetween('chat_time', [
                $kanbanStart->copy()->startOfDay(),
                $kanbanEnd->copy()->endOfDay(),
            ])
            ->where('is_me', false)
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->pluck('client_number')
            ->filter()
            ->unique()
            ->values();

        $kanbanStatusRows = DB::table('lead_summaries')
            ->when(
                $kanbanClientNumbers->isNotEmpty(),
                fn ($query) => $query->whereIn('client_number', $kanbanClientNumbers),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->select('pipeline_status', DB::raw('COUNT(DISTINCT client_number) as total'))
            ->groupBy('pipeline_status')
            ->get();

        $normalizePipelineStatus = function ($status) {
            $key = strtolower(trim((string) $status));
            $key = str_replace([' ', '-'], '_', $key);

            return match ($key) {
                'lead_baru', 'leads_baru', 'new', 'new_lead', 'new_leads', 'baru' => 'leads_baru',
                'edukasi', 'sedang_edukasi', 'education', 'educating' => 'edukasi',
                'konsultasi', 'consultation', 'consulting', 'jadwal_konsultasi' => 'konsultasi',
                'deal', 'closed_won', 'closing', 'converted' => 'deal',
                'batal', 'cancel', 'cancelled', 'canceled', 'closed_lost', 'lost' => 'batal',
                default => $key ?: 'leads_baru',
            };
        };

        $kanbanStatusCounts = collect([
            'leads_baru' => 0,
            'edukasi' => 0,
            'konsultasi' => 0,
            'deal' => 0,
            'batal' => 0,
        ]);

        foreach ($kanbanStatusRows as $row) {
            $normalized = $normalizePipelineStatus($row->pipeline_status);
            $kanbanStatusCounts[$normalized] = ($kanbanStatusCounts[$normalized] ?? 0) + (int) $row->total;
        }

        $kanbanFunnelRows = collect([
            [
                'key' => 'leads_baru',
                'label' => 'Leads Baru',
                'count' => $kanbanStatusCounts['leads_baru'] ?? 0,
                'class' => 'new',
                'width' => 96,
            ],
            [
                'key' => 'edukasi',
                'label' => 'Sedang Edukasi',
                'count' => $kanbanStatusCounts['edukasi'] ?? 0,
                'class' => 'education',
                'width' => 84,
            ],
            [
                'key' => 'konsultasi',
                'label' => 'Konsultasi',
                'count' => $kanbanStatusCounts['konsultasi'] ?? 0,
                'class' => 'consultation',
                'width' => 72,
            ],
            [
                'key' => 'deal',
                'label' => 'Deal',
                'count' => $kanbanStatusCounts['deal'] ?? 0,
                'class' => 'deal',
                'width' => 60,
            ],
            [
                'key' => 'batal',
                'label' => 'Batal',
                'count' => $kanbanStatusCounts['batal'] ?? 0,
                'class' => 'cancel',
                'width' => 48,
            ],
        ]);

        return view('laporan.management', compact(
            'start',
            'end',
            'periodKey',
            'periodLabel',
            'summaries',
            'periodChatClients',
            'periodPatientClients',
            'rawChatCount',
            'processedCount',
            'avgScore',
            'hotCount',
            'warmCount',
            'coldCount',
            'junkCount',
            'hotWarmCount',
            'unsummarizedPatientClients',
            'channelStats',
            'topCancers',
            'topTreatments',
            'topProfiles',
            'topPayments',
            'topPainPoints',
            'topQuestionThemes',
            'qualitySegments',
            'representativeQuestions',
            'recommendedActions',
            'contentAngles',
            'lastProcessedAt',
            'executiveInsight',
            'artisanCommand',
            'headerTitle',
            'batchInfo',
            'activeCache',
            'qaHistory',
            'processingCoverage',
            'pageStart',
            'pageEnd',
            'unclassifiedLeadCount',
            'channelDetails',
            'kanbanFunnelRows'
        ));
    }



    public function createCache(Request $request, GeminiClient $gemini)
    {
        [$start, $end, $periodKey] = $this->resolvePeriod($request);

        $ttlSeconds = $this->resolveCacheTtl($request);
        $contextText = $this->buildManagementReportContext($periodKey, $start, $end);

        $estimatedTokens = (int) ceil(mb_strlen($contextText) / 4);

        if ($estimatedTokens < 4096) {
            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('warning', "Context cache belum dibuat karena konteks periode ini masih terlalu kecil. Estimasi token: {$estimatedTokens}. Untuk Gemini 3.x, target minimal sekitar 4.096 token. Fitur Tanya Data tetap bisa dipakai tanpa cache.");
        }

        $payloadHash = hash('sha256', $contextText);

        $existing = ManagementReportCache::where('period_key', $periodKey)
            ->where('status', 'active')
            ->where('source_payload_hash', $payloadHash)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existing) {
            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('success', 'Cache tanya data masih aktif dan cocok dengan data periode ini.');
        }

        $cacheRecord = ManagementReportCache::create([
            'period_key' => $periodKey,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'model' => env('VERTEX_AI_MODEL', 'gemini-2.5-flash'),
            'ttl_seconds' => $ttlSeconds,
            'expires_at' => now()->addSeconds($ttlSeconds),
            'status' => 'creating',
            'source_payload_hash' => $payloadHash,
            'source_payload' => $contextText,
        ]);

        $response = $gemini->createContextCache(
            displayName: "AHCC Management Report {$periodKey}",
            contextText: $contextText,
            ttlSeconds: $ttlSeconds,
            options: [
                'timeout' => 120,
                'retries' => 1,
            ]
        );

        if (!$response->successful()) {
            $cacheRecord->update([
                'status' => 'failed',
                'last_error' => $response->body(),
            ]);

            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('warning', 'Gagal membuat context cache. Detail error tersimpan di management_report_caches.');
        }

        $body = $response->json();

        $cacheRecord->update([
            'cache_name' => $body['name'] ?? null,
            'model' => $body['model'] ?? env('VERTEX_AI_MODEL', 'gemini-2.5-flash'),
            'expires_at' => !empty($body['expireTime']) ? Carbon::parse($body['expireTime'])->timezone(config('app.timezone', 'Asia/Jakarta'))->timezone(config('app.timezone', 'Asia/Jakarta')) : now()->addSeconds($ttlSeconds),
            'status' => 'active',
            'cached_token_count' => data_get($body, 'usageMetadata.totalTokenCount'),
            'last_error' => null,
        ]);

        return redirect()
            ->route('laporan.management', [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ])
            ->with('success', 'Context cache tanya data berhasil dibuat.');
    }


    public function destroyQuestion(Request $request, int $id)
    {
        $question = ManagementReportQuestion::findOrFail($id);

        $startDate = optional($question->period_start)->format('Y-m-d');
        $endDate = optional($question->period_end)->format('Y-m-d');

        $question->delete();

        return redirect()
            ->route('laporan.management', [
                'start_date' => $request->input('start_date', $startDate),
                'end_date' => $request->input('end_date', $endDate),
            ])
            ->with('success', 'Riwayat tanya data berhasil dihapus.');
    }

    public function clearQuestions(Request $request)
    {
        [$start, $end, $periodKey] = $this->resolvePeriod($request);

        $deleted = ManagementReportQuestion::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->delete();

        return redirect()
            ->route('laporan.management', [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ])
            ->with('success', "Riwayat tanya data periode ini berhasil dihapus. Total terhapus: {$deleted}.");
    }



    public function correctSummaryField(Request $request)
    {
        $allowedFields = [
            'kategori_kanker_norm',
            'minat_treatment_norm',
            'question_theme',
            'profil_pengirim_norm',
            'kendala_utama_norm',
        ];

        $validated = $request->validate([
            'summary_id' => ['required', 'integer', 'exists:management_lead_summaries,id'],
            'field_name' => ['required', 'string', 'in:' . implode(',', $allowedFields)],
            'new_value' => ['required', 'string', 'max:255'],
            'correction_reason' => ['required', 'string', 'max:3000'],
            'learning_keywords' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $summary = ManagementLeadSummary::findOrFail($validated['summary_id']);
        $fieldName = $validated['field_name'];
        $oldValue = $summary->{$fieldName};

        $summary->{$fieldName} = trim($validated['new_value']);
        $summary->save();

        ManagementSummaryCorrection::create([
            'management_lead_summary_id' => $summary->id,
            'client_number' => $summary->client_number,
            'period_key' => $summary->period_key,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => trim($validated['new_value']),
            'correction_reason' => trim($validated['correction_reason']),
            'learning_keywords' => trim((string) ($validated['learning_keywords'] ?? '')),
            'source' => 'human',
            'created_by' => Auth::id(),
        ]);

        ManagementClassificationRule::create([
            'field_name' => $fieldName,
            'match_keywords' => trim((string) ($validated['learning_keywords'] ?? '')),
            'target_value' => trim($validated['new_value']),
            'reasoning' => trim($validated['correction_reason']),
            'example_summary_id' => $summary->id,
            'example_client_number' => $summary->client_number,
            'source' => 'human_correction',
            'is_active' => true,
        ]);


        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Koreksi berhasil disimpan dan menjadi rule pembelajaran sistem.',
                'summary_id' => $summary->id,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => trim($validated['new_value']),
            ]);
        }

        return redirect()
            ->route('laporan.management', [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ])
            ->with('success', 'Koreksi data berhasil disimpan dan menjadi rule pembelajaran sistem.');
    }


    public function askData(Request $request, GeminiClient $gemini)
    {
        [$start, $end, $periodKey] = $this->resolvePeriod($request);

        $question = trim((string) $request->input('question'));

        if ($question === '') {
            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('warning', 'Pertanyaan tidak boleh kosong.');
        }

        $activeCache = ManagementReportCache::where('period_key', $periodKey)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        $provider = env('AI_PROVIDER', 'developer');
        $model = env('VERTEX_AI_MODEL') ?: env('GEMINI_MODEL');

        $prompt = $this->buildQuestionPrompt($question);

        if ($activeCache && $activeCache->cache_name) {
            $response = $gemini->generateTextWithCache(
                cacheName: $activeCache->cache_name,
                prompt: $prompt,
                options: [
                    'maxOutputTokens' => 4096,
                    'temperature' => 0.2,
                    'timeout' => 120,
                ]
            );

            $cacheName = $activeCache->cache_name;
        } else {
            $contextText = $this->buildManagementReportContext($periodKey, $start, $end);

            $response = $gemini->generateText(
                prompt: $contextText . "\n\nPERTANYAAN USER:\n" . $prompt,
                options: [
                    'maxOutputTokens' => 4096,
                    'temperature' => 0.2,
                    'timeout' => 120,
                    'responseMimeType' => null,
                ]
            );

            $cacheName = null;
        }

        if (!$response->successful()) {
            ManagementReportQuestion::create([
                'period_key' => $periodKey,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'question' => $question,
                'answer' => null,
                'cache_name' => $cacheName,
                'model' => $model,
                'provider' => $provider,
                'raw_response' => $response->body(),
            ]);

            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('warning', 'Gagal menjawab pertanyaan. Detail error tersimpan di riwayat pertanyaan.');
        }

        $responseJson = $response->json();
        $answer = $this->extractGeminiAnswerText($responseJson);

        if (trim($answer) === '') {
            $finishReason = (string) data_get($responseJson, 'candidates.0.finishReason', 'UNKNOWN');

            $answer = "AI berhasil dipanggil, tetapi tidak mengembalikan teks jawaban. Finish reason: {$finishReason}. Silakan coba ulangi pertanyaan dengan kalimat yang lebih spesifik.";
        }

        $usage = $response->json('usageMetadata', []);

        ManagementReportQuestion::create([
            'period_key' => $periodKey,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'question' => $question,
            'answer' => $answer,
            'cache_name' => $cacheName,
            'model' => $model,
            'provider' => $provider,
            'prompt_tokens' => data_get($usage, 'promptTokenCount'),
            'cached_tokens' => data_get($usage, 'cachedContentTokenCount'),
            'total_tokens' => data_get($usage, 'totalTokenCount'),
            'raw_response' => $response->body(),
        ]);

        return redirect()
            ->route('laporan.management', [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ])
            ->with('success', $cacheName ? 'Pertanyaan berhasil dijawab memakai context cache.' : 'Pertanyaan berhasil dijawab tanpa context cache.');
    }



    public function process(Request $request)
    {
        [$start, $end, $periodKey] = $this->resolvePeriod($request);

        $enableQa = $request->boolean('enable_qa');
        $ttlSeconds = $this->resolveCacheTtl($request);

        $missingRanges = $this->resolveUnprocessedDateRanges($start, $end);

        if (empty($missingRanges)) {
            if ($enableQa) {
                CreateManagementReportCacheJob::dispatch(
                    $periodKey,
                    $start->toDateString(),
                    $end->toDateString(),
                    $ttlSeconds
                );
            }

            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('success', 'Semua tanggal yang memiliki chat pasien pada filter ini sudah diproses. Tidak ada proses AI tambahan yang dijalankan.');
        }

        $jobs = [];
        $rangeLabels = [];

        foreach ($missingRanges as $range) {
            $rangeStart = Carbon::parse($range['start'], 'Asia/Jakarta')->startOfDay();
            $rangeEnd = Carbon::parse($range['end'], 'Asia/Jakarta')->endOfDay();
            $rangePeriodKey = $rangeStart->format('Ymd') . '-' . $rangeEnd->format('Ymd');

            $rangeLabels[] = $range['label'];

            $clientNumbers = WaChat::whereBetween('chat_time', [$rangeStart, $rangeEnd])
                ->where('is_me', false)
                ->whereNotNull('client_number')
                ->where('client_number', '!=', '')
                ->select('client_number')
                ->distinct()
                ->pluck('client_number')
                ->filter()
                ->values();

            foreach ($clientNumbers as $clientNumber) {
                $jobs[] = new ProcessManagementLeadSummaryJob(
                    clientNumber: $clientNumber,
                    periodKey: $rangePeriodKey,
                    periodStart: $rangeStart->toDateString(),
                    periodEnd: $rangeEnd->toDateString(),
                    force: false
                );
            }
        }

        if (empty($jobs)) {
            return redirect()
                ->route('laporan.management', [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ])
                ->with('success', 'Tidak ada client dengan chat pasien pada tanggal yang belum diproses.');
        }

        $fullPeriodKey = $periodKey;
        $fullStart = $start->toDateString();
        $fullEnd = $end->toDateString();
        $shouldCreateCache = $enableQa;
        $cacheTtlSeconds = $ttlSeconds;

        $batch = Bus::batch($jobs)
            ->name('Management Report Incremental ' . $fullPeriodKey)
            ->finally(function (Batch $batch) use ($shouldCreateCache, $fullPeriodKey, $fullStart, $fullEnd, $cacheTtlSeconds) {
                if ($shouldCreateCache && ! $batch->cancelled()) {
                    CreateManagementReportCacheJob::dispatch(
                        $fullPeriodKey,
                        $fullStart,
                        $fullEnd,
                        $cacheTtlSeconds
                    );
                }
            })
            ->dispatch();

        return redirect()
            ->route('laporan.management', [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'batch_id' => $batch->id,
            ])
            ->with('success', 'Proses incremental dijalankan hanya untuk tanggal yang belum diproses: ' . implode(', ', $rangeLabels) . '. Total job: ' . count($jobs) . '.');
    }



    private function extractGeminiAnswerText(?array $responseJson): string
    {
        if (!$responseJson) {
            return '';
        }

        $texts = [];

        foreach ((array) data_get($responseJson, 'candidates', []) as $candidate) {
            foreach ((array) data_get($candidate, 'content.parts', []) as $part) {
                if (isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                    $texts[] = trim($part['text']);
                }
            }
        }

        return trim(implode("\n\n", $texts));
    }





    private function buildProcessingCoverage(Carbon $start, Carbon $end): array
    {
        $rawDates = WaChat::whereBetween('chat_time', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->where('is_me', false)
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->whereNotNull('message')
            ->where('message', '!=', '')
            ->selectRaw('DATE(chat_time) as chat_date')
            ->distinct()
            ->orderBy('chat_date')
            ->pluck('chat_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        $summaryIntervals = ManagementLeadSummary::whereDate('period_end', '>=', $start->toDateString())
            ->whereDate('period_start', '<=', $end->toDateString())
            ->select('period_start', 'period_end')
            ->distinct()
            ->get()
            ->map(function ($row) use ($start, $end) {
                $periodStart = Carbon::parse($row->period_start)->startOfDay();
                $periodEnd = Carbon::parse($row->period_end)->endOfDay();

                if ($periodStart->lt($start)) {
                    $periodStart = $start->copy()->startOfDay();
                }

                if ($periodEnd->gt($end)) {
                    $periodEnd = $end->copy()->endOfDay();
                }

                return [
                    'start' => $periodStart->toDateString(),
                    'end' => $periodEnd->toDateString(),
                ];
            })
            ->values();

        $processedDates = [];
        $missingDates = [];

        foreach ($rawDates as $date) {
            if ($this->isDateCoveredByRanges($date, $summaryIntervals)) {
                $processedDates[] = $date;
            } else {
                $missingDates[] = $date;
            }
        }

        $processedRanges = $this->mergeDateStringsToRanges($processedDates);
        $missingRanges = $this->mergeDateStringsToRanges($missingDates);

        $status = 'no_raw_data';

        if ($rawDates->count() > 0 && count($processedDates) === 0) {
            $status = 'none';
        }

        if ($rawDates->count() > 0 && count($processedDates) > 0 && count($missingDates) > 0) {
            $status = 'partial';
        }

        if ($rawDates->count() > 0 && count($missingDates) === 0) {
            $status = 'complete';
        }

        return [
            'status' => $status,
            'filter_start' => $start->toDateString(),
            'filter_end' => $end->toDateString(),
            'raw_dates_count' => $rawDates->count(),
            'processed_dates_count' => count($processedDates),
            'missing_dates_count' => count($missingDates),
            'processed_ranges' => $processedRanges,
            'missing_ranges' => $missingRanges,
        ];
    }

    private function resolveUnprocessedDateRanges(Carbon $start, Carbon $end): array
    {
        $coverage = $this->buildProcessingCoverage($start, $end);

        return $coverage['missing_ranges'] ?? [];
    }

    private function isDateCoveredByRanges(string $date, $ranges): bool
    {
        $target = Carbon::parse($date)->toDateString();

        foreach ($ranges as $range) {
            $rangeStart = Carbon::parse($range['start'])->toDateString();
            $rangeEnd = Carbon::parse($range['end'])->toDateString();

            if ($target >= $rangeStart && $target <= $rangeEnd) {
                return true;
            }
        }

        return false;
    }

    private function mergeDateStringsToRanges(array $dates): array
    {
        $dates = collect($dates)
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($dates)) {
            return [];
        }

        $ranges = [];
        $rangeStart = Carbon::parse($dates[0]);
        $previous = Carbon::parse($dates[0]);

        for ($i = 1; $i < count($dates); $i++) {
            $current = Carbon::parse($dates[$i]);

            if ($previous->copy()->addDay()->isSameDay($current)) {
                $previous = $current;
                continue;
            }

            $ranges[] = [
                'start' => $rangeStart->toDateString(),
                'end' => $previous->toDateString(),
                'label' => $this->formatDateRangeLabel($rangeStart, $previous),
            ];

            $rangeStart = $current;
            $previous = $current;
        }

        $ranges[] = [
            'start' => $rangeStart->toDateString(),
            'end' => $previous->toDateString(),
            'label' => $this->formatDateRangeLabel($rangeStart, $previous),
        ];

        return $ranges;
    }

    private function formatDateRangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('d/m/Y');
        }

        return $start->format('d/m/Y') . ' s/d ' . $end->format('d/m/Y');
    }


    private function buildQuestionPrompt(string $question): string
    {
        return <<<PROMPT
Anda adalah analis data management AHCC.

Jawab pertanyaan user hanya berdasarkan KONTEXT LAPORAN yang sudah diberikan, terutama:
1. AGREGAT DATABASE,
2. RAW CHAT WHATSAPP,
3. METADATA MANAGEMENT SUMMARY sebagai pendukung.

Aturan wajib:
- Jangan mengarang data.
- Jangan memakai pengetahuan umum di luar konteks.
- Jangan memberi rekomendasi medis, diagnosis, tindakan klinis, atau layanan spesifik jika tidak tertulis eksplisit di konteks.
- Untuk pertanyaan statistik/jumlah/terbanyak, jawab berdasarkan angka agregat.
- Jika ada nilai seri/tie, sebutkan semua yang seri.
- Jika kategori "Lainnya" atau "Belum Diketahui" muncul, sebutkan sebagai kategori non-spesifik. Jangan disembunyikan.
- Jika data tidak cukup, katakan data tidak cukup.
- Rekomendasi management hanya boleh berupa rekomendasi operasional/data/marketing yang langsung diturunkan dari angka atau chat.
- Jangan menulis pembuka panjang seperti "Berdasarkan konteks laporan management AHCC yang diberikan".
- Jangan terlalu panjang. Prioritaskan jawaban ringkas, tegas, dan mudah dibaca oleh management.

Format jawaban WAJIB menggunakan Markdown berikut:

### Ringkasan Eksekutif
Tulis 1–2 kalimat jawaban utama.

### Angka Kunci
- Tulis angka/data utama dalam bullet pendek.
- Jika ada tie/seri, tulis dengan jelas.
- Bedakan kategori spesifik dan non-spesifik.

### Catatan Management
- Tulis catatan operasional yang aman dan relevan.
- Jika tidak perlu rekomendasi, tulis "Tidak ada rekomendasi tambahan."

Pertanyaan user:
{$question}
PROMPT;
    }

    private function buildManagementReportContext(string $periodKey, Carbon $start, Carbon $end): string
    {
        $periodStart = $start->copy()->startOfDay();
        $periodEnd = $end->copy()->endOfDay();

        $summaries = $this->getManagementSummaries($periodKey, $start, $end);

        $chats = WaChat::whereBetween('chat_time', [$periodStart, $periodEnd])
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->orderBy('client_number')
            ->orderBy('chat_time')
            ->get();

        $summaryByClient = $summaries->keyBy('client_number');
        $chatClients = $chats->pluck('client_number')->filter()->unique()->values();

        $totalChats = $chats->count();
        $totalPatientChats = $chats->where('is_me', false)->count();
        $totalCsChats = $chats->where('is_me', true)->count();
        $totalChatClients = $chatClients->count();
        $totalSummaries = $summaries->count();
        $avgScore = round((float) ($summaries->avg('management_score') ?? 0), 1);

        $aggregate = [
            'period_key' => $periodKey,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_chat_clients' => $totalChatClients,
            'total_raw_chats' => $totalChats,
            'total_patient_chats' => $totalPatientChats,
            'total_cs_chats' => $totalCsChats,
            'total_management_summaries' => $totalSummaries,
            'average_management_score' => $avgScore,
            'lead_quality_segment_counts' => $this->simpleCountByField($summaries, 'lead_quality_segment', false),
            'source_channel_counts' => $this->simpleCountByField($summaries, 'source_channel', false),
            'kategori_kanker_counts' => $this->simpleCountByField($summaries, 'kategori_kanker_norm', false),
            'minat_treatment_counts' => $this->simpleCountByField($summaries, 'minat_treatment_norm', false),
            'question_theme_counts' => $this->simpleCountByField($summaries, 'question_theme', false),
            'kendala_utama_counts' => $this->simpleCountByField($summaries, 'kendala_utama_norm', false),
            'profil_pengirim_counts' => $this->simpleCountByField($summaries, 'profil_pengirim_norm', false),
        ];

        $channelQuality = $summaries
            ->groupBy('source_channel')
            ->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'avg_score' => round((float) ($items->avg('management_score') ?? 0), 1),
                    'hot' => $items->where('lead_quality_segment', 'Hot')->count(),
                    'warm' => $items->where('lead_quality_segment', 'Warm')->count(),
                    'cold' => $items->where('lead_quality_segment', 'Cold')->count(),
                    'junk' => $items->where('lead_quality_segment', 'Junk')->count(),
                ];
            })
            ->toArray();

        $lines = [];
        $lines[] = "KONTEKS LAPORAN MANAGEMENT AHCC";
        $lines[] = "Instruksi: jawaban user harus berdasarkan konteks ini saja.";
        $lines[] = "";
        $lines[] = "PERIODE:";
        $lines[] = "Period key: {$periodKey}";
        $lines[] = "Tanggal: {$start->toDateString()} sampai {$end->toDateString()}";
        $lines[] = "";
        $lines[] = "AGREGAT DATABASE DETERMINISTIK:";
        $lines[] = json_encode($aggregate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lines[] = "";
        $lines[] = "KUALITAS CHANNEL:";
        $lines[] = json_encode($channelQuality, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lines[] = "";
        $lines[] = "CATATAN PENTING:";
        $lines[] = "- kategori 'Lainnya' adalah kategori non-spesifik, tetapi tetap harus dihitung jika muncul di agregat.";
        $lines[] = "- Jika kategori non-spesifik dan kategori spesifik memiliki jumlah sama, sebutkan tie/seri.";
        $lines[] = "- Jangan membuat rekomendasi klinis yang tidak eksplisit tertulis pada RAW CHAT.";
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
            $lines[] = "  patient_intent: " . Str::limit((string) ($summary?->patient_intent ?: ''), 500);
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
                . "\n\n[CATATAN SISTEM: konteks dipotong karena terlalu panjang. Gunakan agregat database sebagai sumber utama untuk pertanyaan statistik.]";
        }

        return $context;
    }

    private function simpleCountByField($collection, string $field, bool $hideUnknown = false): array
    {
        return $collection
            ->map(fn ($row) => trim((string) ($row->{$field} ?? '')))
            ->filter(function ($value) use ($hideUnknown) {
                if ($value === '') {
                    return false;
                }

                if ($hideUnknown && in_array(mb_strtolower($value), [
                    'belum diketahui',
                    'unknown',
                    'null',
                    '-',
                    'n/a',
                    'tidak diketahui',
                ], true)) {
                    return false;
                }

                return true;
            })
            ->groupBy(fn ($value) => $value)
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->toArray();
    }


    private function resolveCacheTtl(Request $request): int
    {
        $ttl = (string) $request->input('cache_ttl', '1800');

        if ($ttl === 'custom') {
            return max((int) $request->input('cache_ttl_custom', 1800), 300);
        }

        return match ($ttl) {
            '900' => 900,
            '1800' => 1800,
            '3600' => 3600,
            default => max((int) $ttl, 300),
        };
    }

    private function formatBatchDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->timezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolvePeriod(Request $request): array
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->input('start_date'), 'Asia/Jakarta')->startOfDay();
            $end = Carbon::parse($request->input('end_date'), 'Asia/Jakarta')->endOfDay();

            if ($end->lt($start)) {
                $end = $start->copy()->endOfDay();
            }

            $periodKey = $start->format('Ymd') . '-' . $end->format('Ymd');
            $periodLabel = $start->format('d M Y') . ' - ' . $end->format('d M Y');

            return [$start, $end, $periodKey, $periodLabel];
        }

        if ($request->filled('month')) {
            $month = trim((string) $request->input('month'));

            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = now('Asia/Jakarta')->format('Y-m');
            }

            $start = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Jakarta')->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [$start, $end, $month, $start->translatedFormat('F Y')];
        }

        $start = now('Asia/Jakarta')->startOfMonth();
        $end = now('Asia/Jakarta')->endOfMonth();
        $periodKey = $start->format('Y-m');

        return [$start, $end, $periodKey, $start->translatedFormat('F Y')];
    }


    private function getManagementSummaries(string $periodKey, Carbon $start, Carbon $end)
    {
        return ManagementLeadSummary::whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_end', '<=', $end->toDateString())
            ->orderBy('period_start')
            ->orderBy('period_end')
            ->orderByDesc('management_score')
            ->get();
    }


    private function buildChannelStats(Collection $summaries): array
    {
        $channels = ['Google Ads', 'Facebook Ads', 'Organik'];
        $total = max($summaries->count(), 1);

        return collect($channels)->map(function ($channel) use ($summaries, $total) {
            $items = $summaries
                ->filter(fn ($row) => $this->normalizeChannel($row->source_channel) === $channel)
                ->values();

            $count = $items->count();

            return [
                'channel' => $channel,
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
                'avg_score' => round((float) ($items->avg('management_score') ?? 0), 1),
                'hot' => $items->where('lead_quality_segment', 'Hot')->count(),
                'warm' => $items->where('lead_quality_segment', 'Warm')->count(),
                'cold' => $items->where('lead_quality_segment', 'Cold')->count(),
                'junk' => $items->where('lead_quality_segment', 'Junk')->count(),
                'top_cancer' => optional($this->countByField($items, 'kategori_kanker_norm', 1)->first())['label'] ?? 'Belum Diketahui',
                'top_treatment' => optional($this->countByField($items, 'minat_treatment_norm', 1)->first())['label'] ?? 'Belum Diketahui',
                'top_profile' => optional($this->countByField($items, 'profil_pengirim_norm', 1)->first())['label'] ?? 'Belum Diketahui',
                'top_question_theme' => optional($this->countByField($items, 'question_theme', 1)->first())['label'] ?? 'Belum Diketahui',
            ];
        })->all();
    }

    private function countByField(Collection $collection, string $field, int $limit = 10, bool $hideUnknown = true): Collection
    {
        $total = max($collection->count(), 1);

        return $collection
            ->map(fn ($row) => trim((string) ($row->{$field} ?? '')))
            ->filter(function ($value) use ($hideUnknown) {
                if ($value === '') {
                    return false;
                }

                if ($hideUnknown && in_array(mb_strtolower($value), [
                    'belum diketahui',
                    'unknown',
                    'null',
                    '-',
                    'n/a',
                    'tidak diketahui',
                ], true)) {
                    return false;
                }

                return true;
            })
            ->groupBy(fn ($value) => $value)
            ->map(fn ($items, $label) => [
                'label' => $label,
                'count' => $items->count(),
                'percentage' => round(($items->count() / $total) * 100, 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->take($limit);
    }

    private function normalizeChannel(?string $value): string
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

    private function isUsefulText(?string $value): bool
    {
        $text = trim((string) $value);

        if ($text === '') {
            return false;
        }

        return !in_array(mb_strtolower($text), [
            'belum diketahui',
            'unknown',
            '-',
            'n/a',
            'tidak diketahui',
        ], true);
    }

    private function buildExecutiveInsight(
        Collection $summaries,
        array $channelStats,
        Collection $topTreatments,
        Collection $topPainPoints,
        string $periodLabel
    ): string {
        if ($summaries->isEmpty()) {
            return "Belum ada management summary untuk periode {$periodLabel}. Jalankan proses AI management summary terlebih dahulu agar insight dapat muncul.";
        }

        $bestChannel = collect($channelStats)
            ->filter(fn ($item) => $item['count'] > 0)
            ->sortByDesc('avg_score')
            ->first();

        $topTreatment = $topTreatments->first();
        $topPainPoint = $topPainPoints->first();

        $bestChannelText = $bestChannel
            ? "{$bestChannel['channel']} dengan rata-rata skor {$bestChannel['avg_score']}"
            : "belum terlihat jelas";

        $treatmentText = $topTreatment['label'] ?? 'belum terlihat jelas';
        $painPointText = $topPainPoint['label'] ?? 'belum terlihat jelas';

        return "Pada periode {$periodLabel}, sudah ada {$summaries->count()} lead yang diproses untuk laporan management. Channel dengan kualitas rata-rata terbaik adalah {$bestChannelText}. Minat treatment paling dominan adalah {$treatmentText}, sedangkan kendala utama yang paling sering muncul adalah {$painPointText}.";
    }

    private function buildArtisanCommand(string $periodKey, Carbon $start, Carbon $end): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
            return "php artisan hana:management-summary --month={$periodKey} --sync --min-patient-messages=1";
        }

        return "php artisan hana:management-summary --start={$start->toDateString()} --end={$end->toDateString()} --sync --min-patient-messages=1";
    }
}
