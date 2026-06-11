<?php

namespace App\Http\Controllers;

use App\Models\LeadSummary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date, 'Asia/Jakarta')->startOfDay()
            : Carbon::now('Asia/Jakarta')->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date, 'Asia/Jakarta')->endOfDay()
            : Carbon::now('Asia/Jakarta')->endOfDay();

        $activeClientNumbers = DB::table('wa_chats')
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->whereBetween('chat_time', [$startDate, $endDate])
            ->select('client_number')
            ->distinct()
            ->pluck('client_number')
            ->filter()
            ->values();

        $newLeadClientNumbers = DB::table('wa_chats')
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->select('client_number')
            ->groupBy('client_number')
            ->havingRaw('MIN(chat_time) BETWEEN ? AND ?', [
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
            ])
            ->pluck('client_number')
            ->filter()
            ->values();

        $leads = LeadSummary::query()
            ->whereIn('client_number', $activeClientNumbers)
            ->get();

        $totalActivePatients = $activeClientNumbers->count();
        $totalNewLeads = $newLeadClientNumbers->count();
        $totalSummarizedLeads = $leads->count();

        $eligibleLead = $leads->where('is_eligible_for_hana', true)->count();
        $junkLead = $leads->where('conversation_outcome', 'junk_lead')->count();
        $dealLead = $leads->where('pipeline_status', 'deal')->count();

        $eligibleRate = $totalSummarizedLeads > 0 ? round(($eligibleLead / $totalSummarizedLeads) * 100, 1) : 0;
        $junkRate = $totalSummarizedLeads > 0 ? round(($junkLead / $totalSummarizedLeads) * 100, 1) : 0;
        $conversionRate = $totalSummarizedLeads > 0 ? round(($dealLead / $totalSummarizedLeads) * 100, 1) : 0;
        $avgLeadScore = $totalSummarizedLeads > 0
            ? round((float) $leads->avg(fn ($lead) => (int) ($lead->lead_score ?? 0)), 1)
            : 0;

        $pipelineOrder = [
            'leads_baru' => 'Leads Baru',
            'edukasi' => 'Edukasi',
            'konsultasi' => 'Konsultasi',
            'deal' => 'Deal',
            'batal' => 'Batal',
        ];

        $pipelineFunnel = collect($pipelineOrder)->mapWithKeys(function ($label, $status) use ($leads) {
            return [$label => $leads->where('pipeline_status', $status)->count()];
        });

        $channelSummary = $this->buildChannelSummary($leads);

        $channelDetails = $this->buildChannelDetails($leads);

        $kategoriKanker = $this->valueCounts($leads, 'kategori_kanker', [
            'Belum Terdeteksi',
            'Belum Diketahui',
            '-',
        ], 8);

        $minatTreatment = $this->valueCounts($leads, 'minat_treatment', [
            'Belum Diketahui',
            '-',
        ], 8);

        $metodeBayar = $this->valueCounts($leads, 'metode_bayar', [
            'Belum Diketahui',
            '-',
        ], 8);

        $profilPengirim = $this->valueCounts($leads, 'profil_pengirim', [
            'Belum Diketahui',
            '-',
        ], 8);

        $painPoints = $this->valueCounts($leads, 'kendala_utama', [
            'Belum Ada',
            'Belum Diketahui',
            '-',
        ], 8);

        $baseLeadQuery = LeadSummary::query()
            ->whereIn('client_number', $activeClientNumbers);

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $followUpOutstanding = (clone $baseLeadQuery)
            ->where('perlu_follow_up', true)
            ->whereNotIn('pipeline_status', ['deal', 'batal'])
            ->count();

        $followUpToday = (clone $baseLeadQuery)
            ->where('perlu_follow_up', true)
            ->whereDate('tunda_sampai_tanggal', $today)
            ->whereNotIn('pipeline_status', ['deal', 'batal'])
            ->count();

        $overdueFollowUp = (clone $baseLeadQuery)
            ->where('perlu_follow_up', true)
            ->whereNotNull('tunda_sampai_tanggal')
            ->whereDate('tunda_sampai_tanggal', '<', $today)
            ->whereNotIn('pipeline_status', ['deal', 'batal'])
            ->count();

        $unansweredLead = (clone $baseLeadQuery)
            ->whereNotNull('last_patient_reply_at')
            ->where(function ($query) {
                $query->whereNull('last_cs_reply_at')
                    ->orWhereColumn('last_patient_reply_at', '>', 'last_cs_reply_at');
            })
            ->whereNotIn('pipeline_status', ['deal', 'batal'])
            ->count();

        $unansweredMoreThan24Hours = (clone $baseLeadQuery)
            ->whereNotNull('last_patient_reply_at')
            ->where('last_patient_reply_at', '<=', Carbon::now('Asia/Jakarta')->subHours(24))
            ->where(function ($query) {
                $query->whereNull('last_cs_reply_at')
                    ->orWhereColumn('last_patient_reply_at', '>', 'last_cs_reply_at');
            })
            ->whereNotIn('pipeline_status', ['deal', 'batal'])
            ->count();

        $responseStats = $this->calculateResponseStats($activeClientNumbers, $startDate, $endDate);

        $chatVolumeByHour = DB::table('wa_chats')
            ->whereBetween('chat_time', [$startDate, $endDate])
            ->where('is_me', false)
            ->selectRaw('HOUR(chat_time) as jam, COUNT(*) as total')
            ->groupBy('jam')
            ->orderBy('jam')
            ->pluck('total', 'jam')
            ->mapWithKeys(fn ($total, $jam) => [str_pad((string) $jam, 2, '0', STR_PAD_LEFT) . ':00' => $total]);

        $dataHealth = $this->buildDataHealth($startDate, $endDate);

        $managementInsights = $this->generateManagementInsights(
            $totalActivePatients,
            $eligibleRate,
            $junkRate,
            $conversionRate,
            $channelSummary,
            $painPoints,
            $followUpOutstanding,
            $overdueFollowUp,
            $unansweredMoreThan24Hours,
            $responseStats
        );

        $headerTitle = 'Laporan Management';

        return view('laporan.management', compact(
            'startDate',
            'endDate',
            'totalActivePatients',
            'totalNewLeads',
            'totalSummarizedLeads',
            'eligibleLead',
            'junkLead',
            'dealLead',
            'eligibleRate',
            'junkRate',
            'conversionRate',
            'avgLeadScore',
            'pipelineFunnel',
            'channelSummary',
            'channelDetails',
            'kategoriKanker',
            'minatTreatment',
            'metodeBayar',
            'profilPengirim',
            'painPoints',
            'followUpOutstanding',
            'followUpToday',
            'overdueFollowUp',
            'unansweredLead',
            'unansweredMoreThan24Hours',
            'responseStats',
            'chatVolumeByHour',
            'dataHealth',
            'managementInsights',
            'headerTitle'
        ));
    }

    private function buildChannelSummary($leads): array
    {
        $summary = [
            'Google Ads' => ['total' => 0, 'eligible' => 0, 'junk' => 0, 'deal' => 0, 'scores' => []],
            'Facebook Ads' => ['total' => 0, 'eligible' => 0, 'junk' => 0, 'deal' => 0, 'scores' => []],
            'Organik' => ['total' => 0, 'eligible' => 0, 'junk' => 0, 'deal' => 0, 'scores' => []],
        ];

        foreach ($leads as $lead) {
            $source = $this->detectSource($lead);

            $summary[$source]['total']++;
            $summary[$source]['scores'][] = (int) ($lead->lead_score ?? 0);

            if ($lead->is_eligible_for_hana) {
                $summary[$source]['eligible']++;
            }

            if ($lead->conversation_outcome === 'junk_lead') {
                $summary[$source]['junk']++;
            }

            if ($lead->pipeline_status === 'deal') {
                $summary[$source]['deal']++;
            }
        }

        foreach ($summary as $source => $data) {
            $total = (int) $data['total'];

            $summary[$source]['eligible_rate'] = $total > 0 ? round(($data['eligible'] / $total) * 100, 1) : 0;
            $summary[$source]['junk_rate'] = $total > 0 ? round(($data['junk'] / $total) * 100, 1) : 0;
            $summary[$source]['conversion_rate'] = $total > 0 ? round(($data['deal'] / $total) * 100, 1) : 0;
            $summary[$source]['avg_score'] = count($data['scores']) > 0
                ? round(array_sum($data['scores']) / count($data['scores']), 1)
                : 0;

            unset($summary[$source]['scores']);
        }

        return $summary;
    }

    private function detectSource($lead): string
    {
        if (!empty($lead->gclid)) {
            return 'Google Ads';
        }

        if (!empty($lead->fbclid)) {
            return 'Facebook Ads';
        }

        return 'Organik';
    }

    private function valueCounts($leads, string $field, array $exclude = [], int $limit = 8)
    {
        $exclude = collect($exclude)
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->all();

        return $leads
            ->pluck($field)
            ->map(fn ($value) => trim((string) $value))
            ->filter(function ($value) use ($exclude) {
                return $value !== '' && !in_array(mb_strtolower($value), $exclude, true);
            })
            ->countBy()
            ->sortDesc()
            ->take($limit);
    }

    private function calculateResponseStats($clientNumbers, Carbon $startDate, Carbon $endDate): array
    {
        if ($clientNumbers->isEmpty()) {
            return [
                'average_minutes' => null,
                'median_minutes' => null,
                'average_label' => '-',
                'median_label' => '-',
                'sample_size' => 0,
            ];
        }

        $chats = DB::table('wa_chats')
            ->whereIn('client_number', $clientNumbers)
            ->whereBetween('chat_time', [$startDate, $endDate])
            ->orderBy('client_number')
            ->orderBy('chat_time')
            ->get(['client_number', 'is_me', 'chat_time']);

        $minutes = [];

        foreach ($chats->groupBy('client_number') as $clientChats) {
            $firstPatientTime = null;

            foreach ($clientChats as $chat) {
                if (!$chat->is_me && $chat->chat_time) {
                    $firstPatientTime = Carbon::parse($chat->chat_time);
                    break;
                }
            }

            if (!$firstPatientTime) {
                continue;
            }

            foreach ($clientChats as $chat) {
                if ($chat->is_me && $chat->chat_time) {
                    $replyTime = Carbon::parse($chat->chat_time);

                    if ($replyTime->gt($firstPatientTime)) {
                        $minutes[] = max(0, $firstPatientTime->diffInMinutes($replyTime));
                        break;
                    }
                }
            }
        }

        sort($minutes);

        $count = count($minutes);
        $average = $count > 0 ? round(array_sum($minutes) / $count, 1) : null;
        $median = null;

        if ($count > 0) {
            $middle = intdiv($count, 2);
            $median = $count % 2
                ? $minutes[$middle]
                : round(($minutes[$middle - 1] + $minutes[$middle]) / 2, 1);
        }

        return [
            'average_minutes' => $average,
            'median_minutes' => $median,
            'average_label' => $this->formatMinutes($average),
            'median_label' => $this->formatMinutes($median),
            'sample_size' => $count,
        ];
    }

    private function formatMinutes($minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return round($minutes) . ' menit';
        }

        return round($minutes / 60, 1) . ' jam';
    }

    private function buildDataHealth(Carbon $startDate, Carbon $endDate): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $chatClients = DB::table('wa_chats')
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->select('client_number')
            ->distinct()
            ->pluck('client_number');

        $summarizedClients = DB::table('lead_summaries')
            ->whereIn('client_number', $chatClients)
            ->whereNotNull('ringkasan')
            ->whereRaw("TRIM(COALESCE(ringkasan, '')) <> ''")
            ->select('client_number')
            ->distinct()
            ->pluck('client_number');

        $periodChatClients = DB::table('wa_chats')
            ->whereBetween('chat_time', [$startDate, $endDate])
            ->whereNotNull('client_number')
            ->where('client_number', '!=', '')
            ->select('client_number')
            ->distinct()
            ->pluck('client_number');

        $periodSummarizedClients = DB::table('lead_summaries')
            ->whereIn('client_number', $periodChatClients)
            ->whereNotNull('ringkasan')
            ->whereRaw("TRIM(COALESCE(ringkasan, '')) <> ''")
            ->select('client_number')
            ->distinct()
            ->pluck('client_number');

        return [
            'total_raw_chat' => DB::table('wa_chats')->count(),
            'total_chat_clients' => $chatClients->count(),
            'total_lead_summaries' => DB::table('lead_summaries')->count(),
            'unsummarized_clients' => $chatClients->diff($summarizedClients)->count(),
            'period_unsummarized_clients' => $periodChatClients->diff($periodSummarizedClients)->count(),
            'export_today_clients' => DB::table('wa_chats')
                ->whereNotNull('client_number')
                ->where('client_number', '!=', '')
                ->whereDate('created_at', $today)
                ->distinct('client_number')
                ->count('client_number'),
            'chat_today_clients' => DB::table('wa_chats')
                ->whereNotNull('client_number')
                ->where('client_number', '!=', '')
                ->whereDate('chat_time', $today)
                ->distinct('client_number')
                ->count('client_number'),
        ];
    }


    private function buildChannelDetails($leads): array
    {
        $channels = ['Google Ads', 'Facebook Ads', 'Organik'];
        $details = [];

        foreach ($channels as $channel) {
            $channelLeads = $leads
                ->filter(fn ($lead) => $this->detectSource($lead) === $channel)
                ->values();

            $clientNumbers = $channelLeads
                ->pluck('client_number')
                ->filter()
                ->unique()
                ->values();

            $questions = [];

            if ($clientNumbers->isNotEmpty()) {
                $patientChats = DB::table('wa_chats')
                    ->whereIn('client_number', $clientNumbers)
                    ->where('is_me', false)
                    ->whereNotNull('message')
                    ->where('message', '!=', '')
                    ->orderBy('client_number')
                    ->orderBy('chat_time')
                    ->get([
                        'client_number',
                        'message',
                        'chat_time',
                    ]);

                $questions = $patientChats
                    ->groupBy('client_number')
                    ->map(function ($chats, $clientNumber) use ($channelLeads) {
                        $firstChat = $chats->first();
                        $lead = $channelLeads->firstWhere('client_number', $clientNumber);

                        $message = trim((string) $firstChat->message);

                        return [
                            'client_number' => $clientNumber,
                            'message' => mb_strlen($message) > 260 ? mb_substr($message, 0, 260) . '...' : $message,
                            'chat_time' => $firstChat->chat_time
                                ? Carbon::parse($firstChat->chat_time)->format('d M Y H:i')
                                : '-',
                            'chat_time_raw' => (string) $firstChat->chat_time,
                            'kategori_kanker' => $lead?->kategori_kanker ?: '-',
                            'minat_treatment' => $lead?->minat_treatment ?: '-',
                            'profil_pengirim' => $lead?->profil_pengirim ?: '-',
                            'lead_score' => $lead?->lead_score ?? '-',
                        ];
                    })
                    ->sortByDesc('chat_time_raw')
                    ->take(30)
                    ->values()
                    ->all();
            }

            $details[$channel] = [
                'total' => $channelLeads->count(),
                'kategori_kanker' => $this->valueCounts($channelLeads, 'kategori_kanker', [
                    'Belum Terdeteksi',
                    'Belum Diketahui',
                    '-',
                ], 10)->toArray(),
                'minat_treatment' => $this->valueCounts($channelLeads, 'minat_treatment', [
                    'Belum Diketahui',
                    '-',
                ], 10)->toArray(),
                'profil_pengirim' => $this->valueCounts($channelLeads, 'profil_pengirim', [
                    'Belum Diketahui',
                    '-',
                ], 10)->toArray(),
                'metode_bayar' => $this->valueCounts($channelLeads, 'metode_bayar', [
                    'Belum Diketahui',
                    '-',
                ], 10)->toArray(),
                'kendala_utama' => $this->valueCounts($channelLeads, 'kendala_utama', [
                    'Belum Ada',
                    'Belum Diketahui',
                    '-',
                ], 10)->toArray(),
                'questions' => $questions,
            ];
        }

        return $details;
    }

    private function generateManagementInsights(
        int $totalActivePatients,
        float $eligibleRate,
        float $junkRate,
        float $conversionRate,
        array $channelSummary,
        $painPoints,
        int $followUpOutstanding,
        int $overdueFollowUp,
        int $unansweredMoreThan24Hours,
        array $responseStats
    ): array {
        if ($totalActivePatients === 0) {
            return ['Belum ada aktivitas pasien pada periode yang dipilih.'];
        }

        $insights = [];

        if ($eligibleRate >= 60) {
            $insights[] = "Kualitas lead relatif sehat dengan eligible rate {$eligibleRate}%. Fokus berikutnya adalah mempercepat follow-up dan mendorong pasien menuju konsultasi.";
        } elseif ($eligibleRate > 0) {
            $insights[] = "Eligible rate masih {$eligibleRate}%. Perlu evaluasi targeting iklan, keyword, materi edukasi, dan kualitas percakapan awal.";
        }

        if ($junkRate >= 30) {
            $insights[] = "Junk lead cukup tinggi ({$junkRate}%). Periksa channel dan campaign yang paling banyak menghasilkan lead tidak relevan.";
        }

        if ($conversionRate < 5) {
            $insights[] = "Conversion rate masih rendah ({$conversionRate}%). Bottleneck kemungkinan terjadi antara edukasi, konsultasi, biaya, jadwal dokter, atau trust pasien.";
        }

        $topChannel = collect($channelSummary)->sortByDesc('total')->keys()->first();
        if ($topChannel) {
            $topChannelData = $channelSummary[$topChannel];
            $insights[] = "Channel dengan volume terbesar adalah {$topChannel} ({$topChannelData['total']} lead), dengan eligible rate {$topChannelData['eligible_rate']}% dan avg score {$topChannelData['avg_score']}.";
        }

        if ($painPoints->isNotEmpty()) {
            $topPainPoint = $painPoints->keys()->first();
            $insights[] = "Pain point paling sering muncul adalah '{$topPainPoint}'. Gunakan ini untuk bahan script CS, FAQ, landing page, dan konten edukasi.";
        }

        if ($overdueFollowUp > 0) {
            $insights[] = "Ada {$overdueFollowUp} follow-up overdue. Ini perlu diprioritaskan agar lead hangat tidak hilang.";
        } elseif ($followUpOutstanding > 0) {
            $insights[] = "Ada {$followUpOutstanding} pasien yang masih perlu follow-up. Pastikan daftar ini menjadi agenda kerja CS.";
        }

        if ($unansweredMoreThan24Hours > 0) {
            $insights[] = "Ada {$unansweredMoreThan24Hours} pasien yang terakhir chat dan belum dibalas lebih dari 24 jam. Ini berisiko menurunkan kepercayaan dan conversion.";
        }

        if (($responseStats['average_minutes'] ?? null) !== null) {
            $insights[] = "Rata-rata first response time adalah {$responseStats['average_label']} dari {$responseStats['sample_size']} percakapan yang memiliki balasan CS.";
        }

        return array_slice($insights, 0, 6);
    }
}
