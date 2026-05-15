<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadSummary;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $baseQuery = LeadSummary::whereBetween('created_at', [$startDate, $endDate]);

        $totalLead = (clone $baseQuery)->count();

        $eligibleLead = (clone $baseQuery)
            ->where('is_eligible_for_hana', true)
            ->count();

        $junkLead = (clone $baseQuery)
            ->where('conversation_outcome', 'junk_lead')
            ->count();

        $redirectedLead = (clone $baseQuery)
            ->where('conversation_outcome', 'redirected')
            ->count();

        $resolvedLead = (clone $baseQuery)
            ->where('conversation_outcome', 'resolved')
            ->count();

        $dealLead = (clone $baseQuery)
            ->where('pipeline_status', 'deal')
            ->count();

        $conversionRate = $totalLead > 0
            ? round(($dealLead / $totalLead) * 100, 1)
            : 0;

        $eligibleRate = $totalLead > 0
            ? round(($eligibleLead / $totalLead) * 100, 1)
            : 0;

        $junkRate = $totalLead > 0
            ? round(($junkLead / $totalLead) * 100, 1)
            : 0;

        // 1. Lead Quality
        $kualitasLead = (clone $baseQuery)
            ->select('conversation_outcome', DB::raw('count(*) as total'))
            ->groupBy('conversation_outcome')
            ->pluck('total', 'conversation_outcome');

        // 2. Pain Points
        $kendalaUtama = (clone $baseQuery)
            ->where('is_eligible_for_hana', true)
            ->whereNotNull('kendala_utama')
            ->where('kendala_utama', '!=', 'Belum Ada')
            ->select('kendala_utama', DB::raw('count(*) as total'))
            ->groupBy('kendala_utama')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'kendala_utama');

        // 3. Treatment Interest
        $minatTreatment = (clone $baseQuery)
            ->whereNotNull('minat_treatment')
            ->select('minat_treatment', DB::raw('count(*) as total'))
            ->groupBy('minat_treatment')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'minat_treatment');

        // 4. Kategori Kanker
        $kategoriKanker = (clone $baseQuery)
            ->whereNotNull('kategori_kanker')
            ->where('kategori_kanker', '!=', 'Belum Terdeteksi')
            ->where('kategori_kanker', '!=', 'Belum Diketahui')
            ->select('kategori_kanker', DB::raw('count(*) as total'))
            ->groupBy('kategori_kanker')
            ->orderByDesc('total')
            ->pluck('total', 'kategori_kanker');

        // 5. Pipeline Funnel
        $pipelineFunnel = (clone $baseQuery)
            ->whereNotNull('pipeline_status')
            ->select('pipeline_status', DB::raw('count(*) as total'))
            ->groupBy('pipeline_status')
            ->pluck('total', 'pipeline_status');
            
        // 6. Channel Source
        $leads = (clone $baseQuery)->get();

        $channelSummary = [
            'Google Ads' => [
                'total' => 0,
                'eligible' => 0,
                'junk' => 0,
                'deal' => 0,
            ],
            'Facebook Ads' => [
                'total' => 0,
                'eligible' => 0,
                'junk' => 0,
                'deal' => 0,
            ],
            'Organik' => [
                'total' => 0,
                'eligible' => 0,
                'junk' => 0,
                'deal' => 0,
            ],
        ];

        foreach ($leads as $lead) {
            $source = $this->detectSource($lead);

            $channelSummary[$source]['total']++;

            if ($lead->is_eligible_for_hana) {
                $channelSummary[$source]['eligible']++;
            }

            if ($lead->conversation_outcome === 'junk_lead') {
                $channelSummary[$source]['junk']++;
            }

            if ($lead->pipeline_status === 'deal') {
                $channelSummary[$source]['deal']++;
            }
        }

        foreach ($channelSummary as $source => $data) {
            $channelSummary[$source]['eligible_rate'] = $data['total'] > 0
                ? round(($data['eligible'] / $data['total']) * 100, 1)
                : 0;

            $channelSummary[$source]['junk_rate'] = $data['total'] > 0
                ? round(($data['junk'] / $data['total']) * 100, 1)
                : 0;

            $channelSummary[$source]['conversion_rate'] = $data['total'] > 0
                ? round(($data['deal'] / $data['total']) * 100, 1)
                : 0;
        }

        // 7. Average Lead Score by Source
        $leadScoreBySource = [
            'Google Ads' => [],
            'Facebook Ads' => [],
            'Organik' => [],
        ];

        foreach ($leads as $lead) {
            $source = $this->detectSource($lead);
            $leadScoreBySource[$source][] = (int) ($lead->lead_score ?? 0);
        }

        $avgLeadScoreBySource = [];

        foreach ($leadScoreBySource as $source => $scores) {
            $avgLeadScoreBySource[$source] = count($scores) > 0
                ? round(array_sum($scores) / count($scores), 1)
                : 0;
        }

        // 8. Insight sederhana otomatis
        $insights = $this->generateMarketingInsights(
            $totalLead,
            $eligibleRate,
            $junkRate,
            $conversionRate,
            $channelSummary,
            $kendalaUtama
        );

        return view('laporan.index', compact(
            'startDate',
            'endDate',
            'totalLead',
            'eligibleLead',
            'junkLead',
            'redirectedLead',
            'resolvedLead',
            'dealLead',
            'conversionRate',
            'eligibleRate',
            'junkRate',
            'kualitasLead',
            'kendalaUtama',
            'minatTreatment',
            'pipelineFunnel',
            'channelSummary',
            'avgLeadScoreBySource',
            'insights',
            'kategoriKanker',
        ));
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

    private function generateMarketingInsights(
        int $totalLead,
        float $eligibleRate,
        float $junkRate,
        float $conversionRate,
        array $channelSummary,
        $kendalaUtama
    ): array {
        $insights = [];

        if ($totalLead === 0) {
            return ['Belum ada data lead pada periode ini.'];
        }

        if ($junkRate >= 40) {
            $insights[] = 'Rasio junk lead cukup tinggi. Evaluasi targeting iklan, keyword, placement, dan kualitas landing page perlu diprioritaskan.';
        }

        if ($eligibleRate >= 60) {
            $insights[] = 'Mayoritas lead tergolong eligible. Traffic relatif sehat dan bisa diperkuat dengan follow-up serta retargeting.';
        } elseif ($eligibleRate < 40) {
            $insights[] = 'Eligible lead masih rendah. Perlu perbaikan pesan iklan agar menarik pasien yang lebih relevan dan siap berkonsultasi.';
        }

        if ($conversionRate < 5) {
            $insights[] = 'Conversion rate masih rendah. Periksa bottleneck dari edukasi ke konsultasi, termasuk biaya, trust, jadwal dokter, dan kualitas follow-up.';
        }

        $worstJunkSource = null;
        $highestJunkRate = 0;

        foreach ($channelSummary as $source => $data) {
            if ($data['junk_rate'] > $highestJunkRate) {
                $highestJunkRate = $data['junk_rate'];
                $worstJunkSource = $source;
            }
        }

        if ($worstJunkSource && $highestJunkRate >= 40) {
            $insights[] = "{$worstJunkSource} memiliki rasio junk lead tertinggi ({$highestJunkRate}%). Evaluasi materi iklan dan audience dari channel ini.";
        }

        if ($kendalaUtama->isNotEmpty()) {
            $topKendala = $kendalaUtama->keys()->first();
            $insights[] = "Pain point terbesar pasien adalah '{$topKendala}'. Gunakan insight ini untuk angle iklan, landing page, dan script follow-up.";
        }

        return $insights;
    }
}