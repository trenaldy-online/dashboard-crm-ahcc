@extends('layouts.app')

@section('title', 'Laporan Management')

@section('content')
@php
    $pageStart = $pageStart ?? request('start_date') ?? now()->startOfMonth()->toDateString();
    $pageEnd = $pageEnd ?? request('end_date') ?? now()->toDateString();
@endphp


@php
    $activeCache = $activeCache ?? null;
    $batchInfo = $batchInfo ?? null;

    if (!isset($qaHistory) || !$qaHistory || (method_exists($qaHistory, 'isEmpty') && $qaHistory->isEmpty())) {
        $qaHistory = \App\Models\ManagementReportQuestion::whereDate('period_start', '>=', $pageStart)
            ->whereDate('period_end', '<=', $pageEnd)
            ->latest()
            ->limit(12)
            ->get();
    }
@endphp


@php
    $pageStart = $start->format('Y-m-d');
    $pageEnd = $end->format('Y-m-d');

    $segmentRows = collect([
        ['label' => 'Hot Lead', 'count' => $hotCount, 'class' => 'hot'],
        ['label' => 'Warm Lead', 'count' => $warmCount, 'class' => 'warm'],
        ['label' => 'Cold Lead', 'count' => $coldCount, 'class' => 'cold'],
        ['label' => 'Junk Lead', 'count' => $junkCount, 'class' => 'junk'],
    ]);

    $funnelRows = collect([
        ['label' => 'Pasien Aktif', 'count' => $periodPatientClients, 'class' => 'funnel-1'],
        ['label' => 'Management Summary', 'count' => $processedCount, 'class' => 'funnel-2'],
        ['label' => 'Hot + Warm', 'count' => $hotWarmCount, 'class' => 'funnel-3'],
        ['label' => 'Hot Lead', 'count' => $hotCount, 'class' => 'funnel-4'],
    ]);

    $bestChannel = collect($channelStats)->sortByDesc('avg_score')->first();
    $bestChannelName = $bestChannel['channel'] ?? '-';
    $bestChannelScore = $bestChannel['avg_score'] ?? 0;

    $topCancer = $topCancers->first();
    $topTreatment = $topTreatments->first();
    $topTheme = $topQuestionThemes->first();
    $topPain = $topPainPoints->first();

    $qualityLabels = $segmentRows->pluck('label')->values();
    $qualityValues = $segmentRows->pluck('count')->values();

    $funnelLabels = $funnelRows->pluck('label')->values();
    $funnelValues = $funnelRows->pluck('count')->values();

    $channelLabels = collect($channelStats)->pluck('channel')->values();
    $channelTotals = collect($channelStats)->pluck('count')->values();
    $channelAvgScores = collect($channelStats)->pluck('avg_score')->values();

    $themeLabels = $topQuestionThemes->pluck('label')->values();
    $themeValues = $topQuestionThemes->pluck('count')->values();

    $treatmentLabels = $topTreatments->pluck('label')->values();
    $treatmentValues = $topTreatments->pluck('count')->values();
@endphp

<style>
    .mgmt-page {
        color: #f5f5f5;
    }

    .mgmt-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        padding: 22px 24px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(20, 20, 20, 0.98), rgba(24, 24, 24, 0.98));
        border: 1px solid rgba(139, 92, 246, 0.35);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        margin-bottom: 24px;
    }

    .mgmt-hero-left {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .mgmt-hero-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(139, 92, 246, 0.16);
        border: 1px solid rgba(139, 92, 246, 0.28);
        flex-shrink: 0;
    }

    .mgmt-hero-icon svg {
        width: 28px;
        height: 28px;
        color: #a855f7;
    }

    .mgmt-hero-title {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
    }

    .mgmt-hero-subtitle {
        margin-top: 4px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #a855f7;
        text-transform: uppercase;
    }

    .mgmt-hero-note {
        margin-top: 8px;
        color: #9ca3af;
        font-size: 13px;
    }

    .mgmt-filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .mgmt-filter-input {
        width: 160px;
        border-radius: 12px;
        background: #111111;
        border: 1px solid #2f2f2f;
        color: #f5f5f5;
        padding: 11px 12px;
        outline: none;
        font-size: 14px;
    }

    .mgmt-btn {
        border: none;
        border-radius: 12px;
        background: #8b5cf6;
        color: #ffffff;
        font-weight: 700;
        padding: 11px 18px;
        cursor: pointer;
        transition: 0.15s ease;
    }

    .mgmt-btn:hover {
        background: #7c3aed;
        transform: translateY(-1px);
    }

    .mgmt-executive {
        background: rgba(59, 130, 246, 0.10);
        border: 1px solid rgba(59, 130, 246, 0.22);
        border-radius: 16px;
        padding: 16px 18px;
        color: #cbd5e1;
        line-height: 1.65;
        margin-bottom: 24px;
    }

    .mgmt-executive strong {
        color: #ffffff;
    }

    .mgmt-grid-kpi {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .mgmt-card,
    .mgmt-section-card {
        background: #171717;
        border: 1px solid #2a2a2a;
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .mgmt-card {
        padding: 18px;
    }

    .mgmt-section-card {
        padding: 22px;
        margin-bottom: 24px;
    }

    .mgmt-kpi-label {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .mgmt-kpi-value {
        font-size: 24px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 8px;
    }

    .mgmt-kpi-note {
        font-size: 12px;
        color: #8b95a7;
        line-height: 1.45;
    }

    .text-green { color: #4ade80 !important; }
    .text-red { color: #f87171 !important; }
    .text-yellow { color: #facc15 !important; }
    .text-blue { color: #60a5fa !important; }
    .text-purple { color: #a855f7 !important; }

    .mgmt-section-title {
        margin: 0 0 18px;
        font-size: 16px;
        font-weight: 800;
        color: #ffffff;
    }

    .mgmt-table-wrap {
        overflow-x: auto;
    }

    .mgmt-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 980px;
    }

    .mgmt-table th {
        text-align: left;
        font-size: 12px;
        color: #9ca3af;
        font-weight: 700;
        padding: 0 10px 14px;
        border-bottom: 1px solid #2c2c2c;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .mgmt-table td {
        padding: 14px 10px;
        border-bottom: 1px solid #242424;
        color: #e5e7eb;
        font-size: 14px;
        vertical-align: middle;
    }

    .mgmt-table tr:last-child td {
        border-bottom: none;
    }

    .mgmt-table .row-title {
        font-weight: 700;
        color: #ffffff;
    }

    .mgmt-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .mgmt-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .chart-card {
        min-height: 360px;
        display: flex;
        flex-direction: column;
    }

    .chart-wrap {
        position: relative;
        flex: 1;
        min-height: 285px;
    }

    .chart-wrap.small {
        min-height: 245px;
    }

    .chart-center-kpi {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        flex-direction: column;
        margin-top: 34px;
    }

    .chart-center-value {
        font-size: 28px;
        font-weight: 850;
        color: #ffffff;
        line-height: 1;
    }

    .chart-center-label {
        margin-top: 6px;
        font-size: 12px;
        color: #9ca3af;
    }

    .mgmt-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .mgmt-list-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 1px solid #242424;
    }

    .mgmt-list-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .mgmt-list-main {
        color: #f5f5f5;
        font-size: 14px;
        font-weight: 600;
    }

    .mgmt-list-meta {
        color: #9ca3af;
        font-size: 12px;
        white-space: nowrap;
    }

    .mgmt-grid-bottom {
        display: grid;
        grid-template-columns: 1.15fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .mgmt-qa-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .mgmt-qa-item {
        padding-bottom: 14px;
        border-bottom: 1px solid #242424;
    }

    .mgmt-qa-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .mgmt-qa-text {
        color: #f5f5f5;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.55;
        margin-bottom: 6px;
    }

    .mgmt-qa-meta {
        color: #9ca3af;
        font-size: 12px;
        line-height: 1.45;
    }

    .mgmt-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .pill-hot { background: rgba(16, 185, 129, 0.14); color: #6ee7b7; }
    .pill-warm { background: rgba(245, 158, 11, 0.14); color: #fcd34d; }
    .pill-cold { background: rgba(59, 130, 246, 0.14); color: #93c5fd; }
    .pill-junk { background: rgba(239, 68, 68, 0.14); color: #fca5a5; }

    .mgmt-small-note {
        font-size: 12px;
        color: #8b95a7;
        margin-top: 10px;
        line-height: 1.5;
    }

    @media (max-width: 1400px) {
        .mgmt-grid-kpi {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 1100px) {
        .mgmt-grid-2,
        .mgmt-grid-3,
        .mgmt-grid-bottom {
            grid-template-columns: 1fr;
        }

        .mgmt-hero {
            flex-direction: column;
            align-items: stretch;
        }

        .mgmt-filter-form {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .mgmt-grid-kpi {
            grid-template-columns: 1fr;
        }

        .mgmt-filter-input {
            width: 100%;
        }

        .mgmt-filter-form {
            flex-direction: column;
            align-items: stretch;
        }
    }

    .mgmt-alert {
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 18px;
        font-size: 14px;
        line-height: 1.55;
    }

    .mgmt-alert-success {
        background: rgba(34, 197, 94, 0.10);
        border: 1px solid rgba(34, 197, 94, 0.24);
        color: #bbf7d0;
    }

    .mgmt-alert-warning {
        background: rgba(245, 158, 11, 0.10);
        border: 1px solid rgba(245, 158, 11, 0.24);
        color: #fde68a;
    }

    .mgmt-process-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }

    .mgmt-field label {
        display: block;
        color: #9ca3af;
        font-size: 12px;
        margin-bottom: 6px;
        font-weight: 700;
    }

    .mgmt-input {
        width: 100%;
        border-radius: 12px;
        background: #111111;
        border: 1px solid #2f2f2f;
        color: #f5f5f5;
        padding: 11px 12px;
        outline: none;
        font-size: 14px;
    }

    .mgmt-input:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
    }

    .mgmt-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #d1d5db;
        font-size: 13px;
        margin-top: 12px;
    }

    .mgmt-checkbox input {
        accent-color: #8b5cf6;
    }

    .mgmt-process-note {
        margin-top: 12px;
        color: #9ca3af;
        font-size: 12px;
        line-height: 1.55;
    }

    @media (max-width: 1200px) {
        .mgmt-process-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .mgmt-process-grid {
            grid-template-columns: 1fr;
        }
    }


    .mgmt-batch-card {
        background: #171717;
        border: 1px solid #2a2a2a;
        border-radius: 18px;
        padding: 18px;
        margin-bottom: 24px;
    }

    .mgmt-batch-top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .mgmt-batch-title {
        color: #ffffff;
        font-weight: 800;
        font-size: 16px;
        margin-bottom: 4px;
    }

    .mgmt-batch-meta {
        color: #9ca3af;
        font-size: 12px;
        line-height: 1.5;
    }

    .mgmt-progress-track {
        height: 12px;
        background: #262626;
        border-radius: 999px;
        overflow: hidden;
        margin: 14px 0;
    }

    .mgmt-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #8b5cf6, #22c55e);
        border-radius: 999px;
        transition: width 0.25s ease;
    }

    .mgmt-batch-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .mgmt-batch-mini {
        background: #111111;
        border: 1px solid #242424;
        border-radius: 14px;
        padding: 12px;
    }

    .mgmt-batch-mini-label {
        color: #9ca3af;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }

    .mgmt-batch-mini-value {
        color: #ffffff;
        font-size: 18px;
        font-weight: 800;
    }

    .mgmt-status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .mgmt-status-running {
        background: rgba(59, 130, 246, 0.16);
        color: #93c5fd;
    }

    .mgmt-status-finished {
        background: rgba(34, 197, 94, 0.16);
        color: #86efac;
    }

    .mgmt-status-failed {
        background: rgba(239, 68, 68, 0.16);
        color: #fca5a5;
    }

    @media (max-width: 900px) {
        .mgmt-batch-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mgmt-batch-top {
            flex-direction: column;
        }
    }


    .mgmt-ai-grid {
        display: grid;
        grid-template-columns: 0.95fr 1.05fr;
        gap: 18px;
        margin-bottom: 24px;
    }

    .mgmt-cache-status {
        padding: 12px 14px;
        border-radius: 14px;
        background: #111111;
        border: 1px solid #242424;
        color: #d1d5db;
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 14px;
    }

    .mgmt-textarea {
        width: 100%;
        min-height: 92px;
        border-radius: 12px;
        background: #111111;
        border: 1px solid #2f2f2f;
        color: #f5f5f5;
        padding: 12px;
        outline: none;
        font-size: 14px;
        resize: vertical;
    }

    .mgmt-select {
        width: 100%;
        border-radius: 12px;
        background: #111111;
        border: 1px solid #2f2f2f;
        color: #f5f5f5;
        padding: 11px 12px;
        outline: none;
        font-size: 14px;
    }

    .mgmt-answer-card {
        background: #111111;
        border: 1px solid #242424;
        border-radius: 14px;
        padding: 14px;
        margin-top: 12px;
    }

    .mgmt-answer-q {
        color: #ffffff;
        font-weight: 800;
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .mgmt-answer-a {
        color: #d1d5db;
        white-space: pre-line;
        line-height: 1.65;
        font-size: 14px;
    }

    @media (max-width: 1100px) {
        .mgmt-ai-grid {
            grid-template-columns: 1fr;
        }
    }


    .mgmt-process-options {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 18px;
        align-items: center;
    }

    .mgmt-clean-checks {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .mgmt-cache-options {
        display: none;
        margin-top: 10px;
        padding: 14px;
        border-radius: 14px;
        background: #111111;
        border: 1px solid #242424;
    }

    .mgmt-cache-options.show {
        display: block;
    }

    .mgmt-cache-row {
        display: grid;
        grid-template-columns: 220px 220px;
        gap: 12px;
        align-items: end;
    }

    .mgmt-status-box {
        padding: 14px;
        border-radius: 14px;
        background: #111111;
        border: 1px solid #242424;
        color: #d1d5db;
        line-height: 1.6;
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .mgmt-process-options {
            grid-template-columns: 1fr;
        }

        .mgmt-cache-row {
            grid-template-columns: 1fr;
        }
    }


    .mgmt-latest-answer {
        margin-top: 18px;
        border-radius: 16px;
        border: 1px solid rgba(139, 92, 246, 0.28);
        background: rgba(139, 92, 246, 0.08);
        padding: 16px;
    }

    .mgmt-latest-answer-title {
        color: #ffffff;
        font-weight: 800;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .mgmt-latest-question {
        color: #c4b5fd;
        font-weight: 700;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .mgmt-latest-answer-body {
        color: #e5e7eb;
        line-height: 1.75;
        font-size: 14px;
        white-space: pre-line;
    }

    .mgmt-answer-card {
        background: #111111;
        border: 1px solid #242424;
        border-radius: 14px;
        padding: 14px;
        margin-top: 12px;
    }

    .mgmt-answer-q {
        color: #ffffff;
        font-weight: 800;
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .mgmt-answer-a {
        color: #d1d5db;
        white-space: pre-line;
        line-height: 1.65;
        font-size: 14px;
    }


    .mgmt-answer-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .mgmt-answer-toggle {
        border: 1px solid rgba(139, 92, 246, 0.35);
        background: rgba(139, 92, 246, 0.12);
        color: #c4b5fd;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-answer-toggle:hover {
        background: rgba(139, 92, 246, 0.22);
        color: #ffffff;
    }

    .mgmt-answer-collapsed .mgmt-latest-answer-body,
    .mgmt-answer-collapsed .mgmt-answer-a,
    .mgmt-answer-collapsed .mgmt-small-note {
        display: none !important;
    }

    .mgmt-answer-preview {
        display: none;
        color: #9ca3af;
        font-size: 13px;
        line-height: 1.55;
        margin-top: 8px;
    }

    .mgmt-answer-collapsed .mgmt-answer-preview {
        display: block;
    }


    .mgmt-history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .mgmt-delete-btn {
        border: 1px solid rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.10);
        color: #fca5a5;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-delete-btn:hover {
        background: rgba(239, 68, 68, 0.18);
        color: #ffffff;
    }

    .mgmt-clear-btn {
        border: 1px solid rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.10);
        color: #fca5a5;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-clear-btn:hover {
        background: rgba(239, 68, 68, 0.18);
        color: #ffffff;
    }

    .mgmt-answer-actions {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
    }


    .mgmt-filter-period-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        margin: 8px 0 10px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(34, 197, 94, 0.28);
        background: rgba(34, 197, 94, 0.10);
        color: #bbf7d0;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.4;
    }

    .mgmt-active-filter-note {
        margin-top: 8px;
        margin-bottom: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.22);
        background: rgba(59, 130, 246, 0.08);
        color: #bfdbfe;
        font-size: 13px;
        font-weight: 700;
    }


    .mgmt-answer-rich {
        color: #e5e7eb;
        line-height: 1.75;
        font-size: 14px;
    }

    .mgmt-answer-rich h1,
    .mgmt-answer-rich h2,
    .mgmt-answer-rich h3 {
        margin: 18px 0 10px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(139, 92, 246, 0.22);
        background: rgba(139, 92, 246, 0.10);
        color: #ddd6fe;
        font-size: 15px;
        font-weight: 900;
    }

    .mgmt-answer-rich h1:first-child,
    .mgmt-answer-rich h2:first-child,
    .mgmt-answer-rich h3:first-child {
        margin-top: 0;
    }

    .mgmt-answer-rich p {
        margin: 8px 0;
        color: #d1d5db;
    }

    .mgmt-answer-rich ul,
    .mgmt-answer-rich ol {
        margin: 10px 0 12px;
        padding-left: 0;
        list-style: none;
    }

    .mgmt-answer-rich li {
        position: relative;
        margin: 8px 0;
        padding: 10px 12px 10px 36px;
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.62);
        border: 1px solid rgba(148, 163, 184, 0.14);
        color: #d1d5db;
    }

    .mgmt-answer-rich li::before {
        content: "•";
        position: absolute;
        left: 15px;
        top: 9px;
        color: #a78bfa;
        font-size: 18px;
        font-weight: 900;
    }

    .mgmt-answer-rich strong {
        color: #ffffff;
        font-weight: 900;
    }

    .mgmt-answer-rich em {
        color: #c4b5fd;
        font-style: normal;
        font-weight: 800;
    }

    .mgmt-answer-rich code {
        padding: 2px 6px;
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.9);
        color: #93c5fd;
        font-size: 12px;
    }

    .mgmt-answer-rich blockquote {
        margin: 12px 0;
        padding: 12px 14px;
        border-left: 4px solid rgba(139, 92, 246, 0.7);
        background: rgba(139, 92, 246, 0.08);
        border-radius: 10px;
        color: #d1d5db;
    }

    .mgmt-answer-rich hr {
        border: none;
        border-top: 1px solid rgba(148, 163, 184, 0.18);
        margin: 16px 0;
    }

    .mgmt-answer-rich table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0;
        overflow: hidden;
        border-radius: 12px;
    }

    .mgmt-answer-rich th,
    .mgmt-answer-rich td {
        border: 1px solid rgba(148, 163, 184, 0.16);
        padding: 10px 12px;
        text-align: left;
    }

    .mgmt-answer-rich th {
        background: rgba(139, 92, 246, 0.14);
        color: #ddd6fe;
    }

    .mgmt-answer-rich td {
        background: rgba(15, 23, 42, 0.48);
        color: #d1d5db;
    }


    /* Compact spacing for AI answer */
    .mgmt-answer-rich {
        line-height: 1.48 !important;
        font-size: 13.5px !important;
    }

    .mgmt-answer-rich h1,
    .mgmt-answer-rich h2,
    .mgmt-answer-rich h3 {
        margin: 10px 0 7px !important;
        padding: 8px 10px !important;
        border-radius: 10px !important;
        font-size: 14px !important;
    }

    .mgmt-answer-rich p {
        margin: 5px 0 !important;
    }

    .mgmt-answer-rich ul,
    .mgmt-answer-rich ol {
        margin: 6px 0 8px !important;
        padding-left: 0 !important;
    }

    .mgmt-answer-rich li {
        margin: 5px 0 !important;
        padding: 7px 10px 7px 30px !important;
        border-radius: 10px !important;
        min-height: unset !important;
    }

    .mgmt-answer-rich li p {
        margin: 0 !important;
    }

    .mgmt-answer-rich li::before {
        left: 13px !important;
        top: 6px !important;
        font-size: 15px !important;
    }

    .mgmt-answer-rich code {
        padding: 1px 5px !important;
        font-size: 12px !important;
    }

    .mgmt-answer-rich blockquote {
        margin: 8px 0 !important;
        padding: 9px 11px !important;
    }

    .mgmt-latest-answer-body,
    .mgmt-answer-a {
        padding-top: 2px !important;
    }


    .mgmt-coverage-card {
        margin-top: 14px;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.40);
    }

    .mgmt-coverage-card.partial {
        border-color: rgba(245, 158, 11, 0.35);
        background: rgba(245, 158, 11, 0.08);
    }

    .mgmt-coverage-card.complete {
        border-color: rgba(34, 197, 94, 0.32);
        background: rgba(34, 197, 94, 0.08);
    }

    .mgmt-coverage-card.none {
        border-color: rgba(239, 68, 68, 0.32);
        background: rgba(239, 68, 68, 0.08);
    }

    .mgmt-coverage-title {
        font-size: 14px;
        font-weight: 900;
        color: #ffffff;
        margin-bottom: 6px;
    }

    .mgmt-coverage-desc {
        color: #cbd5e1;
        font-size: 13px;
        line-height: 1.55;
        margin-bottom: 10px;
    }

    .mgmt-coverage-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .mgmt-coverage-box {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(2, 6, 23, 0.28);
        border-radius: 12px;
        padding: 10px 12px;
    }

    .mgmt-coverage-box strong {
        display: block;
        color: #e5e7eb;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .mgmt-coverage-box span {
        display: inline-block;
        margin: 3px 5px 3px 0;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        color: #dbeafe;
        background: rgba(59, 130, 246, 0.14);
        border: 1px solid rgba(59, 130, 246, 0.20);
    }

    .mgmt-coverage-box .missing {
        color: #fed7aa;
        background: rgba(245, 158, 11, 0.14);
        border-color: rgba(245, 158, 11, 0.25);
    }

    @media (max-width: 900px) {
        .mgmt-coverage-grid {
            grid-template-columns: 1fr;
        }
    }


    .mgmt-clarity-note {
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.22);
        background: rgba(59, 130, 246, 0.08);
        color: #bfdbfe;
        font-size: 13px;
        line-height: 1.55;
    }

    .mgmt-clarity-note strong {
        color: #ffffff;
        font-weight: 900;
    }

    .mgmt-kpi-helper {
        margin-top: 6px;
        color: #94a3b8;
        font-size: 11.5px;
        line-height: 1.45;
    }

    .mgmt-batch-help {
        margin-top: 10px;
        margin-bottom: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(245, 158, 11, 0.26);
        background: rgba(245, 158, 11, 0.08);
        color: #fde68a;
        font-size: 13px;
        line-height: 1.55;
    }

    .mgmt-batch-help strong {
        color: #ffffff;
        font-weight: 900;
    }


    /* Compact KPI cards */
    .mgmt-kpi-card,
    .mgmt-stat-card,
    .mgmt-summary-card {
        min-height: unset !important;
    }

    .mgmt-kpi-card h3,
    .mgmt-stat-card h3,
    .mgmt-summary-card h3 {
        line-height: 1.25 !important;
        min-height: unset !important;
        margin-bottom: 8px !important;
        letter-spacing: 0.08em !important;
    }

    .mgmt-kpi-card .mgmt-kpi-value,
    .mgmt-stat-card .mgmt-kpi-value,
    .mgmt-summary-card .mgmt-kpi-value {
        line-height: 1.05 !important;
        margin-bottom: 8px !important;
    }

    .mgmt-kpi-card p,
    .mgmt-stat-card p,
    .mgmt-summary-card p {
        line-height: 1.42 !important;
        margin-top: 6px !important;
        margin-bottom: 0 !important;
    }

    .mgmt-kpi-helper {
        margin-top: 5px !important;
        font-size: 11px !important;
        line-height: 1.35 !important;
    }


    /* Collapsible management cards */
    .mgmt-collapsible-card {
        transition: 0.18s ease;
    }

    .mgmt-collapsible-card.mgmt-collapsed {
        padding-top: 16px !important;
        padding-bottom: 16px !important;
        margin-bottom: 16px !important;
    }

    .mgmt-collapsible-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 0;
    }

    .mgmt-collapsible-header .mgmt-section-title {
        margin: 0 !important;
    }

    .mgmt-collapsible-body {
        margin-top: 18px;
    }

    .mgmt-collapsible-card.mgmt-collapsed .mgmt-collapsible-body {
        display: none !important;
    }

    .mgmt-collapse-btn {
        border: 1px solid rgba(139, 92, 246, 0.35);
        background: rgba(139, 92, 246, 0.12);
        color: #c4b5fd;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-collapse-btn:hover {
        background: rgba(139, 92, 246, 0.22);
        color: #ffffff;
    }

    .mgmt-collapsible-subtitle {
        margin-top: 7px;
        color: #8b95a7;
        font-size: 12px;
        line-height: 1.45;
    }

    .mgmt-collapsible-card:not(.mgmt-collapsed) .mgmt-collapsible-subtitle {
        display: none;
    }


    .mgmt-detail-btn {
        border: 1px solid rgba(139, 92, 246, 0.35);
        background: rgba(139, 92, 246, 0.12);
        color: #c4b5fd;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-detail-btn:hover {
        background: rgba(139, 92, 246, 0.22);
        color: #ffffff;
    }

    .mgmt-channel-detail-row {
        display: none;
    }

    .mgmt-channel-detail-row.show {
        display: table-row;
    }

    .mgmt-channel-detail-cell {
        padding: 0 10px 18px !important;
        border-bottom: 1px solid #242424;
    }

    .mgmt-channel-detail-panel {
        margin-top: 4px;
        border-radius: 16px;
        border: 1px solid rgba(139, 92, 246, 0.20);
        background: rgba(139, 92, 246, 0.06);
        padding: 16px;
    }

    .mgmt-channel-detail-title {
        color: #ffffff;
        font-weight: 900;
        font-size: 14px;
        margin-bottom: 12px;
    }

    .mgmt-channel-detail-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .mgmt-channel-detail-box {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.45);
        border-radius: 14px;
        padding: 12px;
    }

    .mgmt-channel-detail-label {
        color: #9ca3af;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .mgmt-channel-detail-value {
        color: #ffffff;
        font-size: 15px;
        font-weight: 850;
        line-height: 1.35;
    }

    .mgmt-channel-detail-note {
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.20);
        background: rgba(59, 130, 246, 0.08);
        color: #bfdbfe;
        font-size: 13px;
        line-height: 1.55;
        padding: 11px 12px;
    }

    .mgmt-channel-mini-breakdown {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .mgmt-channel-mini-pill {
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.28);
    }

    @media (max-width: 1100px) {
        .mgmt-channel-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .mgmt-channel-detail-grid {
            grid-template-columns: 1fr;
        }
    }


    .mgmt-detail-btn {
        border: 1px solid rgba(139, 92, 246, 0.35);
        background: rgba(139, 92, 246, 0.12);
        color: #c4b5fd;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-detail-btn:hover {
        background: rgba(139, 92, 246, 0.22);
        color: #ffffff;
    }

    .mgmt-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(0, 0, 0, 0.72);
        backdrop-filter: blur(6px);
    }

    .mgmt-modal-overlay.show {
        display: flex;
    }

    .mgmt-modal {
        width: min(980px, 96vw);
        max-height: 88vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        border: 1px solid rgba(139, 92, 246, 0.32);
        background: #171717;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
    }

    .mgmt-modal-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        padding: 18px 20px;
        border-bottom: 1px solid #2a2a2a;
        background: rgba(139, 92, 246, 0.08);
    }

    .mgmt-modal-title {
        color: #ffffff;
        font-size: 18px;
        font-weight: 900;
        margin: 0;
    }

    .mgmt-modal-subtitle {
        color: #9ca3af;
        font-size: 13px;
        margin-top: 5px;
        line-height: 1.45;
    }

    .mgmt-modal-close {
        border: 1px solid rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.10);
        color: #fca5a5;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .mgmt-modal-body {
        padding: 18px 20px 22px;
        overflow-y: auto;
    }

    .mgmt-modal-kpis {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }

    .mgmt-modal-kpi {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.42);
        border-radius: 14px;
        padding: 12px;
    }

    .mgmt-modal-kpi-label {
        color: #9ca3af;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .mgmt-modal-kpi-value {
        color: #ffffff;
        font-size: 18px;
        font-weight: 900;
    }

    .mgmt-modal-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .mgmt-modal-box {
        border: 1px solid #2a2a2a;
        background: #111111;
        border-radius: 16px;
        padding: 14px;
        min-height: 180px;
    }

    .mgmt-modal-box-title {
        color: #ffffff;
        font-size: 14px;
        font-weight: 900;
        margin-bottom: 12px;
    }

    .mgmt-detail-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .mgmt-detail-list-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 9px 10px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(15, 23, 42, 0.44);
    }

    .mgmt-detail-list-label {
        color: #e5e7eb;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.35;
    }

    .mgmt-detail-list-meta {
        color: #a78bfa;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .mgmt-question-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .mgmt-question-item {
        padding: 11px 12px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.48);
    }

    .mgmt-question-text {
        color: #e5e7eb;
        font-size: 13px;
        line-height: 1.55;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .mgmt-question-meta {
        color: #9ca3af;
        font-size: 11.5px;
        line-height: 1.45;
    }

    @media (max-width: 1000px) {
        .mgmt-modal-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mgmt-modal-grid {
            grid-template-columns: 1fr;
        }
    }


    /* Channel popup field-first correction */
    .mgmt-detail-btn {
        border: 1px solid rgba(139, 92, 246, 0.35);
        background: rgba(139, 92, 246, 0.12);
        color: #c4b5fd;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .mgmt-detail-btn:hover {
        background: rgba(139, 92, 246, 0.22);
        color: #ffffff;
    }

    .mgmt-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(0, 0, 0, 0.72);
        backdrop-filter: blur(6px);
    }

    .mgmt-modal-overlay.show {
        display: flex;
    }

    .mgmt-modal {
        width: min(1160px, 96vw);
        max-height: 88vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        border: 1px solid rgba(139, 92, 246, 0.32);
        background: #171717;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
    }

    .mgmt-modal-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        padding: 18px 20px;
        border-bottom: 1px solid #2a2a2a;
        background: rgba(139, 92, 246, 0.08);
    }

    .mgmt-modal-title {
        color: #ffffff;
        font-size: 18px;
        font-weight: 900;
        margin: 0;
    }

    .mgmt-modal-subtitle {
        color: #9ca3af;
        font-size: 13px;
        margin-top: 5px;
        line-height: 1.45;
    }

    .mgmt-modal-close {
        border: 1px solid rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.10);
        color: #fca5a5;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .mgmt-modal-body {
        padding: 18px 20px 22px;
        overflow-y: auto;
    }

    .mgmt-modal-kpis {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .mgmt-modal-kpi,
    .mgmt-breakdown-panel,
    .mgmt-correction-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.42);
        border-radius: 14px;
        padding: 12px;
    }

    .mgmt-modal-kpi-label {
        color: #9ca3af;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .mgmt-modal-kpi-value {
        color: #ffffff;
        font-size: 18px;
        font-weight: 900;
    }

    .mgmt-field-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .mgmt-field-tab {
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.55);
        color: #cbd5e1;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .mgmt-field-tab.active {
        border-color: rgba(139, 92, 246, 0.45);
        background: rgba(139, 92, 246, 0.20);
        color: #ffffff;
    }

    .mgmt-breakdown-grid {
        display: grid;
        grid-template-columns: 0.9fr 1.4fr;
        gap: 14px;
    }

    .mgmt-value-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .mgmt-value-btn {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(2, 6, 23, 0.30);
        color: #e5e7eb;
        border-radius: 12px;
        padding: 10px 12px;
        cursor: pointer;
        text-align: left;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
    }

    .mgmt-value-btn:hover,
    .mgmt-value-btn.active {
        border-color: rgba(139, 92, 246, 0.42);
        background: rgba(139, 92, 246, 0.12);
    }

    .mgmt-value-name {
        font-weight: 900;
        font-size: 13px;
    }

    .mgmt-value-meta {
        color: #a78bfa;
        font-weight: 900;
        font-size: 12px;
        white-space: nowrap;
    }

    .mgmt-ambiguous-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 7px;
        border-radius: 999px;
        background: rgba(245, 158, 11, 0.14);
        color: #fcd34d;
        font-size: 10px;
        font-weight: 900;
    }

    .mgmt-correction-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 520px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .mgmt-correction-title {
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .mgmt-correction-meta {
        color: #9ca3af;
        font-size: 12px;
        line-height: 1.55;
        margin-bottom: 10px;
    }

    .mgmt-correction-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        align-items: start;
    }

    .mgmt-correction-form .full {
        grid-column: 1 / -1;
    }

    .mgmt-correction-form textarea {
        min-height: 74px;
    }

    @media (max-width: 1000px) {
        .mgmt-modal-kpis,
        .mgmt-breakdown-grid,
        .mgmt-correction-form {
            grid-template-columns: 1fr;
        }
    }


    /* Channel summary-first modal */
    .mgmt-channel-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .mgmt-channel-summary-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.42);
        border-radius: 14px;
        padding: 14px;
        min-height: 180px;
    }

    .mgmt-channel-summary-title {
        color: #ffffff;
        font-size: 14px;
        font-weight: 900;
        margin-bottom: 12px;
    }

    .mgmt-summary-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .mgmt-summary-list-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        padding: 9px 10px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(2, 6, 23, 0.30);
    }

    .mgmt-summary-list-label {
        color: #e5e7eb;
        font-size: 13px;
        font-weight: 850;
        line-height: 1.35;
    }

    .mgmt-summary-list-meta {
        color: #a78bfa;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .mgmt-summary-ambiguous-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 7px;
        border-radius: 999px;
        background: rgba(245, 158, 11, 0.14);
        color: #fcd34d;
        font-size: 10px;
        font-weight: 900;
        vertical-align: middle;
    }

    .mgmt-correction-accordion {
        margin-top: 16px;
        border: 1px solid rgba(245, 158, 11, 0.24);
        background: rgba(245, 158, 11, 0.06);
        border-radius: 16px;
        overflow: hidden;
    }

    .mgmt-correction-accordion-head {
        width: 100%;
        border: none;
        background: transparent;
        color: #ffffff;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        text-align: left;
    }

    .mgmt-correction-accordion-title {
        font-size: 14px;
        font-weight: 950;
        display: block;
    }

    .mgmt-correction-accordion-subtitle {
        display: block;
        margin-top: 4px;
        color: #fcd34d;
        font-size: 12px;
        line-height: 1.45;
        font-weight: 700;
    }

    .mgmt-correction-toggle-pill {
        border: 1px solid rgba(245, 158, 11, 0.32);
        background: rgba(245, 158, 11, 0.12);
        color: #fde68a;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .mgmt-correction-accordion-body {
        display: block;
        padding: 0 16px 16px;
    }

    .mgmt-correction-accordion.collapsed .mgmt-correction-accordion-body {
        display: none !important;
    }

    .mgmt-correction-accordion.collapsed {
        background: rgba(245, 158, 11, 0.035);
    }

    @media (max-width: 1100px) {
        .mgmt-channel-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .mgmt-channel-summary-grid {
            grid-template-columns: 1fr;
        }
    }


    /* Sales pipeline funnel from Kanban */
    .mgmt-sales-funnel-wrap {
        min-height: 320px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0;
        padding: 10px 4px 4px;
    }

    .mgmt-sales-funnel {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        width: 100%;
        margin: 4px auto 18px;
    }

    .mgmt-funnel-segment {
        height: 58px;
        min-width: 210px;
        max-width: 96%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 900;
        position: relative;
        margin-top: -1px;
        clip-path: polygon(8% 0%, 92% 0%, 84% 100%, 16% 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: inset 0 -12px 24px rgba(0, 0, 0, 0.12);
    }

    .mgmt-funnel-segment:first-child {
        border-radius: 10px 10px 0 0;
    }

    .mgmt-funnel-segment:last-child {
        border-radius: 0 0 10px 10px;
    }

    .mgmt-funnel-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 0 42px;
        text-align: center;
        white-space: nowrap;
    }

    .mgmt-funnel-label {
        font-size: 13px;
        font-weight: 900;
    }

    .mgmt-funnel-value {
        font-size: 18px;
        font-weight: 950;
    }

    .mgmt-funnel-new {
        background: linear-gradient(90deg, #38bdf8, #0ea5e9);
    }

    .mgmt-funnel-education {
        background: linear-gradient(90deg, #2563eb, #1d4ed8);
    }

    .mgmt-funnel-consultation {
        background: linear-gradient(90deg, #4338ca, #312e81);
    }

    .mgmt-funnel-deal {
        background: linear-gradient(90deg, #16a34a, #15803d);
    }

    .mgmt-funnel-cancel {
        background: linear-gradient(90deg, #ef4444, #991b1b);
    }

    .mgmt-funnel-legend {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .mgmt-funnel-legend-item {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.45);
        border-radius: 12px;
        padding: 9px 10px;
    }

    .mgmt-funnel-legend-label {
        color: #9ca3af;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .mgmt-funnel-legend-value {
        color: #ffffff;
        font-size: 16px;
        font-weight: 900;
    }

    @media (max-width: 900px) {
        .mgmt-funnel-legend {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mgmt-funnel-content {
            padding: 0 24px;
        }
    }

    @media (max-width: 600px) {
        .mgmt-funnel-legend {
            grid-template-columns: 1fr;
        }

        .mgmt-funnel-label {
            font-size: 12px;
        }

        .mgmt-funnel-value {
            font-size: 16px;
        }
    }


    /* Exact-style Sales Pipeline Funnel */
    .mgmt-funnel-art {
        position: relative;
        height: 430px;
        width: 100%;
        overflow: hidden;
        padding: 10px 0;
    }

    .mgmt-funnel-main-shape {
        position: absolute;
        left: 0;
        top: 22px;
        width: 76%;
        height: 380px;
        z-index: 2;
    }

    .mgmt-funnel-layer {
        position: relative;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: -2px;
        height: 82px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        color: #ffffff;
        font-weight: 950;
        text-align: center;
        text-shadow: 0 2px 8px rgba(0,0,0,0.35);
        box-shadow:
            inset 0 14px 26px rgba(255,255,255,0.10),
            inset 0 -16px 26px rgba(0,0,0,0.18),
            0 14px 28px rgba(0,0,0,0.22);
        clip-path: polygon(0 0, 100% 0, 90% 100%, 10% 100%);
    }

    .mgmt-funnel-layer span {
        font-size: 20px;
        line-height: 1.1;
        white-space: nowrap;
    }

    .mgmt-funnel-layer strong {
        font-size: 34px;
        line-height: 1;
        letter-spacing: 0.02em;
    }

    .mgmt-funnel-layer-new {
        width: 100%;
        background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 50%, #0284c7 100%);
    }

    .mgmt-funnel-layer-education {
        width: 88%;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
    }

    .mgmt-funnel-layer-consultation {
        width: 74%;
        background: linear-gradient(135deg, #3730a3 0%, #312e81 55%, #1e1b4b 100%);
    }

    .mgmt-funnel-layer-deal {
        width: 55%;
        height: 150px;
        margin-top: -1px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 45%, #15803d 100%);
        clip-path: polygon(0 0, 100% 0, 56% 76%, 56% 100%, 44% 100%, 44% 76%);
        align-items: flex-start;
        padding-top: 38px;
    }

    .mgmt-funnel-dropoff-svg {
        position: absolute;
        right: 9%;
        top: 35px;
        width: 27%;
        height: 310px;
        z-index: 1;
        overflow: visible;
        opacity: 0.92;
        pointer-events: none;
    }

    .mgmt-funnel-drop-path {
        fill: none;
        stroke-linecap: round;
        filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.32));
    }

    .mgmt-funnel-drop-path.path-1 {
        stroke: rgba(248, 113, 113, 0.72);
        stroke-width: 34;
    }

    .mgmt-funnel-drop-path.path-2 {
        stroke: rgba(239, 68, 68, 0.66);
        stroke-width: 30;
    }

    .mgmt-funnel-drop-path.path-3 {
        stroke: rgba(220, 38, 38, 0.62);
        stroke-width: 26;
    }

    .mgmt-funnel-drop-path.path-4 {
        stroke: rgba(185, 28, 28, 0.58);
        stroke-width: 22;
    }

    .mgmt-funnel-drop-path-highlight {
        fill: none;
        stroke: rgba(254, 202, 202, 0.32);
        stroke-width: 2;
        stroke-dasharray: 2 12;
        stroke-linecap: round;
    }

    .mgmt-funnel-cancel-box {
        position: absolute;
        right: 0;
        bottom: 44px;
        width: 170px;
        min-height: 118px;
        z-index: 3;
        border-radius: 22px;
        padding: 22px 24px;
        background: linear-gradient(145deg, #ef4444 0%, #b91c1c 52%, #7f1d1d 100%);
        border: 2px solid rgba(248, 113, 113, 0.85);
        box-shadow:
            inset 0 14px 22px rgba(255,255,255,0.10),
            inset 0 -16px 26px rgba(0,0,0,0.22),
            0 16px 34px rgba(127, 29, 29, 0.35);
        color: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
    }

    .mgmt-funnel-cancel-box span {
        font-size: 22px;
        font-weight: 950;
        line-height: 1;
    }

    .mgmt-funnel-cancel-box strong {
        font-size: 38px;
        font-weight: 950;
        line-height: 1;
    }

    .mgmt-funnel-note {
        margin-top: 8px;
    }

    @media (max-width: 1200px) {
        .mgmt-funnel-art {
            height: 390px;
        }

        .mgmt-funnel-main-shape {
            width: 74%;
        }

        .mgmt-funnel-layer {
            height: 74px;
        }

        .mgmt-funnel-layer-deal {
            height: 132px;
            padding-top: 32px;
        }

        .mgmt-funnel-layer span {
            font-size: 17px;
        }

        .mgmt-funnel-layer strong {
            font-size: 28px;
        }

        .mgmt-funnel-cancel-box {
            width: 142px;
            min-height: 102px;
        }
    }

    @media (max-width: 900px) {
        .mgmt-funnel-art {
            height: auto;
            min-height: unset;
            overflow: visible;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .mgmt-funnel-main-shape,
        .mgmt-funnel-cancel-box,
        .mgmt-funnel-dropoff-svg {
            position: relative;
            left: auto;
            right: auto;
            top: auto;
            bottom: auto;
        }

        .mgmt-funnel-main-shape {
            width: 100%;
            height: auto;
        }

        .mgmt-funnel-dropoff-svg {
            display: none;
        }

        .mgmt-funnel-cancel-box {
            width: 100%;
            min-height: 86px;
            flex-direction: row;
            justify-content: center;
            align-items: center;
        }
    }


    /* Refined Sales Pipeline layout */
    .mgmt-grid-quality-pipeline {
        grid-template-columns: minmax(420px, 0.88fr) minmax(560px, 1.12fr);
        align-items: stretch;
    }

    .mgmt-grid-quality-pipeline .chart-card {
        min-height: 560px;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-art {
        height: 405px !important;
        max-width: 720px;
        margin: 0 auto;
        overflow: visible !important;
        padding-top: 4px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-main-shape {
        left: 0 !important;
        top: 34px !important;
        width: 76% !important;
        height: 340px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer {
        height: 70px !important;
        margin-bottom: -1px !important;
        gap: 16px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer span {
        font-size: 18px !important;
        line-height: 1.1 !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer strong {
        font-size: 32px !important;
        line-height: 1 !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer-new {
        width: 98% !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer-education {
        width: 86% !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer-consultation {
        width: 72% !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-layer-deal {
        width: 51% !important;
        height: 128px !important;
        padding-top: 31px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-dropoff-svg {
        right: 8.5% !important;
        top: 72px !important;
        width: 25% !important;
        height: 270px !important;
        opacity: 0.78 !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-drop-path.path-1 {
        stroke-width: 26 !important;
        stroke: rgba(248, 113, 113, 0.58) !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-drop-path.path-2 {
        stroke-width: 23 !important;
        stroke: rgba(239, 68, 68, 0.50) !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-drop-path.path-3 {
        stroke-width: 20 !important;
        stroke: rgba(220, 38, 38, 0.46) !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-drop-path.path-4 {
        stroke-width: 17 !important;
        stroke: rgba(185, 28, 28, 0.40) !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-cancel-box {
        right: 0 !important;
        bottom: 70px !important;
        width: 135px !important;
        min-height: 98px !important;
        padding: 18px 20px !important;
        border-radius: 18px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-cancel-box span {
        font-size: 20px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-cancel-box strong {
        font-size: 34px !important;
    }

    .mgmt-grid-quality-pipeline .mgmt-funnel-note {
        margin-top: 0 !important;
        font-size: 12px !important;
        line-height: 1.45 !important;
    }

    @media (max-width: 1300px) {
        .mgmt-grid-quality-pipeline {
            grid-template-columns: 1fr;
        }

        .mgmt-grid-quality-pipeline .mgmt-funnel-art {
            max-width: 760px;
        }
    }

</style>

<div class="mgmt-page">
    <div class="mgmt-hero">
        <div class="mgmt-hero-left">
            <div class="mgmt-hero-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19V9"></path>
                    <path d="M10 19V5"></path>
                    <path d="M16 19v-8"></path>
                    <path d="M22 19V3"></path>
                    <path d="M2 21h20"></path>
                </svg>
            </div>
            <div>
                <h1 class="mgmt-hero-title">Business Intelligence Management</h1>
                <div class="mgmt-hero-subtitle">Lead Quality, Channel Performance & Patient Insights</div>
                <div class="mgmt-hero-note">
                    Periode {{ $periodLabel }} · Sumber data utama: <strong>management_lead_summaries</strong>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('laporan.management') }}" class="mgmt-filter-form">
            <input type="date" name="start_date" value="{{ request('start_date', $pageStart) }}" class="mgmt-filter-input">
            <input type="date" name="end_date" value="{{ request('end_date', $pageEnd) }}" class="mgmt-filter-input">
            <button type="submit" class="mgmt-btn">Filter</button>
        </form>
    </div>


    @if(session('success'))
        <div class="mgmt-alert mgmt-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mgmt-alert mgmt-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mgmt-executive">
        <strong>Executive Insight</strong><br>
        {{ $executiveInsight }}
    </div>


        <div class="mgmt-section-card">
        <h2 class="mgmt-section-title">Proses Laporan AI</h2>

        <form method="POST" action="{{ route('laporan.management.process') }}">
            @csrf

            <input type="hidden" name="start_date" value="{{ $pageStart }}">
            <input type="hidden" name="end_date" value="{{ $pageEnd }}">

            <div class="mgmt-process-options">
                <div class="mgmt-clean-checks">
                    <label class="mgmt-checkbox">
                        <input type="checkbox" name="enable_qa" value="1" id="enableQaCheckbox">
                        Aktifkan Tanya Data setelah rekap selesai
                    </label>

                    <div class="mgmt-cache-options" id="autoCacheOptions">
                        <div class="mgmt-cache-row">
                            <div class="mgmt-field">
                                <label>Durasi Cache</label>
                                <select name="cache_ttl" id="autoCacheTtlSelect" class="mgmt-select">
                                    <option value="900">15 Menit</option>
                                    <option value="1800" selected>30 Menit</option>
                                    <option value="3600">1 Jam</option>
                                    <option value="custom">Custom Detik</option>
                                </select>
                            </div>

                            <div class="mgmt-field" id="autoCustomTtlWrap" style="display:none;">
                                <label>Custom TTL Detik</label>
                                <input type="number" name="cache_ttl_custom" class="mgmt-input" value="1800" min="300">
                            </div>
                        </div>

                        <div class="mgmt-process-note">
                            Cache hanya dibuat setelah rekap AI selesai. Jika data terlalu kecil untuk cache,
                            Tanya Data tetap berjalan otomatis tanpa cache.
                        </div>
                    </div>

                    <div class="mgmt-process-note">
                        Tanggal proses mengikuti filter di atas: <strong>{{ $pageStart }}</strong> s/d <strong>{{ $pageEnd }}</strong>.
                        Jika periode ini sudah pernah diproses, halaman hanya membaca hasil yang tersimpan dan tidak memproses ulang data lama.
                    </div>


                @if(isset($processingCoverage))
                    <div class="mgmt-coverage-card {{ $processingCoverage['status'] ?? '' }}">
                        <div class="mgmt-coverage-title">Status Kelengkapan Data</div>

                        @if(($processingCoverage['status'] ?? null) === 'complete')
                            <div class="mgmt-coverage-desc">
                                Semua tanggal pada filter ini yang memiliki chat pasien sudah diproses.
                                Data laporan sudah lengkap untuk filter aktif.
                            </div>
                        @elseif(($processingCoverage['status'] ?? null) === 'partial')
                            <div class="mgmt-coverage-desc">
                                Data laporan yang tampil saat ini masih sebagian.
                                Ada tanggal yang sudah diproses dan ada tanggal yang belum diproses.
                                Jika tombol proses ditekan, sistem hanya memproses lead/client pada tanggal gap tersebut.
                            </div>
                        @elseif(($processingCoverage['status'] ?? null) === 'none')
                            <div class="mgmt-coverage-desc">
                                Filter ini memiliki chat pasien, tetapi belum ada data laporan management yang diproses.
                                Tekan tombol proses untuk mulai membuat laporan.
                            </div>
                        @else
                            <div class="mgmt-coverage-desc">
                                Tidak ditemukan chat pasien pada filter tanggal ini.
                            </div>
                        @endif

                        
                        <div class="mgmt-clarity-note">
                            <strong>Bedanya dengan Total Job AI:</strong>
                            panel ini menunjukkan tanggal yang sudah atau belum masuk proses summary. Total Job AI menunjukkan jumlah lead/client yang sedang dikirim ke AI.
                        </div>


<div class="mgmt-coverage-grid">
                            <div class="mgmt-coverage-box">
                                <strong>Tanggal Sudah Diproses</strong>
                                @forelse(($processingCoverage['processed_ranges'] ?? []) as $range)
                                    <span>{{ $range['label'] }}</span>
                                @empty
                                    <span>-</span>
                                @endforelse
                            </div>

                            <div class="mgmt-coverage-box">
                                <strong>Tanggal Belum Diproses</strong>
                                @forelse(($processingCoverage['missing_ranges'] ?? []) as $range)
                                    <span class="missing">{{ $range['label'] }}</span>
                                @empty
                                    <span>-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                </div>

                <div>
                    <button type="submit" class="mgmt-btn">{{ (($processingCoverage['missing_dates_count'] ?? 0) > 0) ? 'Proses Data Tanggal Belum Diproses' : 'Mulai Proses Laporan' }}</button>
                </div>
            </div>
        </form>
    </div>






    @if($batchInfo)
        <div class="mgmt-batch-card">
            <div class="mgmt-batch-top">
                <div>
                    <div class="mgmt-batch-title">Status Proses AI Management Summary</div>
                    <div class="mgmt-batch-meta">
                        Batch ID: {{ $batchInfo['id'] }}<br>
                        Dibuat: {{ $batchInfo['created_at'] ?: '-' }}
                        @if($batchInfo['finished_at'])

                <div class="mgmt-batch-help">
                    <strong>Catatan:</strong>
                    Total job AI adalah jumlah lead/client pada tanggal gap yang sedang dilengkapi.
                    Angka ini bisa berbeda dari KPI <strong>Tanpa Summary</strong>, karena satu pasien bisa sudah punya summary di periode lama,
                    tetapi tetap perlu diproses lagi jika ada chat baru pada tanggal yang belum masuk summary.
                </div>

                            · Selesai: {{ $batchInfo['finished_at'] }}
                        @endif
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    @if($batchInfo['failed_jobs'] > 0)
                        <span class="mgmt-status-pill mgmt-status-failed">Ada Failed Job</span>
                    @elseif($batchInfo['finished'])
                        <span class="mgmt-status-pill mgmt-status-finished">Selesai</span>
                    @else
                        <span class="mgmt-status-pill mgmt-status-running">Berjalan</span>
                    @endif

                    <a class="mgmt-btn" href="{{ request()->fullUrl() }}" style="text-decoration:none;">Refresh</a>
                </div>
            </div>

            <div class="mgmt-progress-track">
                <div class="mgmt-progress-fill" style="width: {{ $batchInfo['progress'] }}%;"></div>
            </div>

            <div class="mgmt-batch-grid">
                <div class="mgmt-batch-mini">
                    <div class="mgmt-batch-mini-label">Progress</div>
                    <div class="mgmt-batch-mini-value">{{ $batchInfo['progress'] }}%</div>
                </div>

                <div class="mgmt-batch-mini">
                    <div class="mgmt-batch-mini-label">Total Job</div>
                    <div class="mgmt-batch-mini-value">{{ $batchInfo['total_jobs'] }}</div>
                </div>

                <div class="mgmt-batch-mini">
                    <div class="mgmt-batch-mini-label">Processed</div>
                    <div class="mgmt-batch-mini-value text-green">{{ $batchInfo['processed_jobs'] }}</div>
                </div>

                <div class="mgmt-batch-mini">
                    <div class="mgmt-batch-mini-label">Pending</div>
                    <div class="mgmt-batch-mini-value text-blue">{{ $batchInfo['pending_jobs'] }}</div>
                </div>

                <div class="mgmt-batch-mini">
                    <div class="mgmt-batch-mini-label">Failed</div>
                    <div class="mgmt-batch-mini-value text-red">{{ $batchInfo['failed_jobs'] }}</div>
                </div>
            </div>

            <div class="mgmt-small-note">
                Jalankan <code>php artisan queue:work --tries=1 --timeout=300</code> di terminal terpisah.
                Jika pending tidak berkurang, berarti worker belum berjalan.
            </div>
        </div>
    @endif

    
        <div class="mgmt-ai-grid">
        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Status Tanya Data</h2>

            <div class="mgmt-status-box">
                @if($activeCache)
                    <strong style="color:#86efac;">Mode cache aktif</strong><br>
                    Cache valid sampai: {{ optional($activeCache->expires_at)->format('Y-m-d H:i:s') ?: '-' }}<br>
                    Model: {{ $activeCache->model ?: '-' }}<br>
                    Tanya Data akan memakai cache konteks periode ini.
                @else
                    <strong style="color:#fde68a;">Mode direct context</strong><br>
                    Belum ada cache aktif untuk periode ini. Tanya Data tetap bisa dipakai,
                    tetapi konteks laporan akan dikirim langsung ke AI saat bertanya.
                @endif
            </div>

            <div class="mgmt-process-note">
                Cache bukan syarat wajib. Cache hanya optimasi biaya/token untuk sesi tanya-jawab panjang setelah rekap selesai.
            </div>
        </div>

        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Tanya Data Management</h2>

            <form method="POST" action="{{ route('laporan.management.ask') }}">
                @csrf
                <input type="hidden" name="start_date" value="{{ $pageStart }}">
                <input type="hidden" name="end_date" value="{{ $pageEnd }}">

                <div class="mgmt-field" style="margin-bottom:12px;">
                    <label>Pertanyaan</label>
                    <textarea name="question" class="mgmt-textarea" placeholder="Contoh: Channel mana yang paling bagus untuk periode ini dan kenapa?"></textarea>
                </div>

                <button type="submit" class="mgmt-btn">Tanyakan</button>
            </form>

            @if(($qaHistory ?? collect())->isNotEmpty())
                @php
                    $latestQa = $qaHistory->first();
                @endphp

                <div class="mgmt-latest-answer">
                    <div class="mgmt-latest-answer-title">Jawaban Terakhir</div>
                    <div class="mgmt-latest-question">
                        Q: {{ $latestQa->question }}
                    </div>
                    <div class="mgmt-latest-answer-body mgmt-answer-rich">{!! \Illuminate\Support\Str::markdown($latestQa->answer ?: 'Belum ada jawaban / terjadi error.') !!}</div>
                    <div class="mgmt-small-note" style="margin-top:12px;">
                        Periode jawaban: {{ optional($latestQa->period_start)->format('d/m/Y') }} s/d {{ optional($latestQa->period_end)->format('d/m/Y') }}<br>
                        {{ $latestQa->created_at }} · {{ $latestQa->cache_name ? 'pakai cache' : 'tanpa cache' }}
                        @if($latestQa->cached_tokens)
                            · cached tokens: {{ $latestQa->cached_tokens }}
                        @endif
                        @if($latestQa->total_tokens)
                            · total tokens: {{ $latestQa->total_tokens }}
                        @endif
                    </div>

                    <div class="mgmt-answer-actions">
                        <form method="POST" action="{{ route('laporan.management.questions.destroy', $latestQa->id) }}" onsubmit="return confirm('Hapus jawaban terakhir ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="start_date" value="{{ $pageStart }}">
                            <input type="hidden" name="end_date" value="{{ $pageEnd }}">
                            <button type="submit" class="mgmt-delete-btn">Hapus</button>
                        </form>
                    </div>
                </div>
            @endif

            <div style="margin-top:18px;">
                <div class="mgmt-history-header">
                    <h3 class="mgmt-section-title" style="font-size:14px; margin:0;">Riwayat Tanya Data</h3>

                    @if(($qaHistory ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ route('laporan.management.questions.clear') }}" onsubmit="return confirm('Hapus semua riwayat tanya data pada periode ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="start_date" value="{{ $pageStart }}">
                            <input type="hidden" name="end_date" value="{{ $pageEnd }}">
                            <button type="submit" class="mgmt-clear-btn">Hapus Semua</button>
                        </form>
                    @endif
                </div>

                @forelse($qaHistory as $qa)
                    <div class="mgmt-answer-card">
                        <div class="mgmt-answer-q">{{ $qa->question }}</div>

                        <div class="mgmt-filter-period-badge">
                            Filter pertanyaan:
                            {{ $qa->period_start ? \Carbon\Carbon::parse($qa->period_start)->format('d/m/Y') : '-' }}
                            s/d
                            {{ $qa->period_end ? \Carbon\Carbon::parse($qa->period_end)->format('d/m/Y') : '-' }}
                        </div>
                        <div class="mgmt-answer-a mgmt-answer-rich">{!! \Illuminate\Support\Str::markdown($qa->answer ?: 'Belum ada jawaban / terjadi error.') !!}</div>
                        <div class="mgmt-small-note">
                            Periode jawaban: {{ optional($qa->period_start)->format('d/m/Y') }} s/d {{ optional($qa->period_end)->format('d/m/Y') }}<br>
                            {{ $qa->created_at }} · {{ $qa->cache_name ? 'pakai cache' : 'tanpa cache' }}
                            @if($qa->cached_tokens)
                                · cached tokens: {{ $qa->cached_tokens }}
                            @endif
                            @if($qa->total_tokens)
                                · total tokens: {{ $qa->total_tokens }}
                            @endif
                        </div>

                        <div class="mgmt-answer-actions">
                            <form method="POST" action="{{ route('laporan.management.questions.destroy', $qa->id) }}" onsubmit="return confirm('Hapus riwayat pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="start_date" value="{{ $pageStart }}">
                                <input type="hidden" name="end_date" value="{{ $pageEnd }}">
                                <button type="submit" class="mgmt-delete-btn">Hapus</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mgmt-small-note">Belum ada riwayat pertanyaan.</div>
                @endforelse
            </div>
        </div>
    </div>





    <div class="mgmt-grid-kpi">
        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Total Lead</div>
            <div class="mgmt-kpi-value">{{ number_format($processedCount) }}</div>
            <div class="mgmt-kpi-note">Lead yang sudah masuk ke management summary pada periode ini.</div>
        </div>

        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Hot + Warm</div>
            <div class="mgmt-kpi-value text-green">{{ number_format($hotWarmCount) }}</div>
            <div class="mgmt-kpi-note">
                Hot: {{ number_format($hotCount) }} · Warm: {{ number_format($warmCount) }}
            </div>
        </div>

        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Cold Lead</div>
            <div class="mgmt-kpi-value text-blue">{{ number_format($coldCount ?? 0) }}</div>
            <div class="mgmt-kpi-note">
                Lead kategori Cold pada periode ini.
                @if(($unclassifiedLeadCount ?? 0) > 0)
                    <div class="mgmt-kpi-helper">Belum terklasifikasi: {{ number_format($unclassifiedLeadCount) }}</div>
                @endif
            </div>
        </div>

        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Junk Lead</div>
            <div class="mgmt-kpi-value text-red">{{ number_format($junkCount) }}</div>
            <div class="mgmt-kpi-note">
                {{ $processedCount > 0 ? round(($junkCount / max($processedCount, 1)) * 100, 1) : 0 }}% dari total summary.
            </div>
        </div>

        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Tanpa Summary</div>
            <div class="mgmt-kpi-value text-yellow">{{ number_format($unsummarizedPatientClients) }}</div>
            <div class="mgmt-kpi-note">
                Pasien aktif yang belum punya summary.
                <div class="mgmt-kpi-helper">Bukan jumlah job AI.</div>
            </div>
        </div>

        <div class="mgmt-card">
            <div class="mgmt-kpi-label">Raw Chat</div>
            <div class="mgmt-kpi-value text-purple">{{ number_format($rawChatCount) }}</div>
            <div class="mgmt-kpi-note">Total pesan WA pada range periode saat ini.</div>
        </div>
    </div>

    <div class="mgmt-section-card">
        <h2 class="mgmt-section-title">Channel Performance</h2>

        <script>
            window.mgmtChannelDetails = @json($channelDetails ?? []);
            window.mgmtCorrectionUrl = "{{ route('laporan.management.corrections.store') }}";
            window.mgmtCsrfToken = "{{ csrf_token() }}";
            window.mgmtPageStart = "{{ $pageStart }}";
            window.mgmtPageEnd = "{{ $pageEnd }}";
        </script>

        <div class="mgmt-table-wrap">
            <table class="mgmt-table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Total Lead</th>
                        <th>Hot</th>
                        <th>Warm</th>
                        <th>Cold</th>
                        <th>Junk</th>
                        <th>Hot + Warm Rate</th>
                        <th>Junk Rate</th>
                        <th>Avg Score</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($channelStats as $channel)
                        @php
                            $hotWarm = $channel['hot'] + $channel['warm'];
                            $total = max($channel['count'], 1);
                            $hotWarmRate = round(($hotWarm / $total) * 100, 1);
                            $junkRate = round(($channel['junk'] / $total) * 100, 1);
                        @endphp
                        <tr>
                            <td class="row-title">{{ $channel['channel'] }}</td>
                            <td>{{ $channel['count'] }}</td>
                            <td class="text-green">{{ $channel['hot'] }}</td>
                            <td class="text-yellow">{{ $channel['warm'] }}</td>
                            <td class="text-blue">{{ $channel['cold'] }}</td>
                            <td class="text-red">{{ $channel['junk'] }}</td>
                            <td>{{ $hotWarmRate }}%</td>
                            <td>{{ $junkRate }}%</td>
                            <td>{{ $channel['avg_score'] }}</td>
                            <td>
                                <button type="button" class="mgmt-detail-btn js-open-channel-modal" data-channel="{{ e($channel['channel']) }}">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="color:#9ca3af;">Belum ada data channel performance pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mgmt-modal-overlay" id="channelDetailModal" aria-hidden="true">
        <div class="mgmt-modal" role="dialog" aria-modal="true">
            <div class="mgmt-modal-header">
                <div>
                    <h3 class="mgmt-modal-title" id="channelModalTitle">Detail Channel</h3>
                    <div class="mgmt-modal-subtitle" id="channelModalSubtitle">Pilih jenis data, lalu pilih nilai yang ingin dibreakdown.</div>
                </div>
                <button type="button" class="mgmt-modal-close" id="channelModalClose">Tutup</button>
            </div>

            <div class="mgmt-modal-body">
                <div class="mgmt-modal-kpis" id="channelModalKpis"></div>

                <div class="mgmt-channel-summary-grid">
                    <div class="mgmt-channel-summary-card">
                        <div class="mgmt-channel-summary-title">Kanker yang Ditanyakan</div>
                        <div class="mgmt-summary-list" id="channelSummaryCancer"></div>
                    </div>

                    <div class="mgmt-channel-summary-card">
                        <div class="mgmt-channel-summary-title">Treatment yang Diminati</div>
                        <div class="mgmt-summary-list" id="channelSummaryTreatment"></div>
                    </div>

                    <div class="mgmt-channel-summary-card">
                        <div class="mgmt-channel-summary-title">Tema Pertanyaan</div>
                        <div class="mgmt-summary-list" id="channelSummaryTheme"></div>
                    </div>

                    <div class="mgmt-channel-summary-card">
                        <div class="mgmt-channel-summary-title">Profil Pengirim</div>
                        <div class="mgmt-summary-list" id="channelSummaryProfile"></div>
                    </div>

                    <div class="mgmt-channel-summary-card">
                        <div class="mgmt-channel-summary-title">Kendala Utama</div>
                        <div class="mgmt-summary-list" id="channelSummaryObstacle"></div>
                    </div>
                </div>

                <div class="mgmt-correction-accordion collapsed" id="channelCorrectionAccordion">
                    <button type="button" class="mgmt-correction-accordion-head" id="channelCorrectionToggle">
                        <span>
                            <span class="mgmt-correction-accordion-title">Review Kategori Ambigu</span>
                            <span class="mgmt-correction-accordion-subtitle">
                                Opsional. Buka hanya jika ingin membetulkan hasil klasifikasi AI dan menyimpan pembelajaran sistem.
                            </span>
                        </span>
                        <span class="mgmt-correction-toggle-pill" id="channelCorrectionToggleText">Tampilkan</span>
                    </button>

                    <div class="mgmt-correction-accordion-body">
                        <div class="mgmt-field-tabs" id="channelFieldTabs"></div>

                        <div class="mgmt-breakdown-grid">
                            <div class="mgmt-breakdown-panel">
                                <div class="mgmt-modal-box-title" id="channelValueTitle">Pilih Nilai</div>
                                <div class="mgmt-value-list" id="channelValueList"></div>
                            </div>

                            <div class="mgmt-breakdown-panel">
                                <div class="mgmt-modal-box-title" id="channelCorrectionTitle">Data untuk Koreksi</div>
                                <div class="mgmt-small-note" style="margin-bottom:12px;">
                                    Pilih jenis data, lalu pilih nilai yang ingin dibreakdown. Setelah itu, koreksi lead yang memang perlu diperbaiki.
                                </div>
                                <div class="mgmt-correction-list" id="channelCorrectionList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mgmt-grid-2 mgmt-grid-quality-pipeline">
        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Kualitas Lead</h2>
            <div class="chart-wrap">
                <canvas id="qualityDonutChart"></canvas>
                <div class="chart-center-kpi">
                    <div class="chart-center-value">{{ number_format($processedCount) }}</div>
                    <div class="chart-center-label">Total Lead</div>
                </div>
            </div>
            <div class="mgmt-small-note">
                Segmentasi berdasarkan kolom <strong>lead_quality_segment</strong>.
            </div>
        </div>

        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Sales Pipeline</h2>

            @php
                $pipelineRows = collect($kanbanFunnelRows ?? []);
                $getPipelineCount = function ($key) use ($pipelineRows) {
                    $row = $pipelineRows->firstWhere('key', $key);
                    return (int) ($row['count'] ?? 0);
                };

                $leadsBaruCount = $getPipelineCount('leads_baru');
                $edukasiCount = $getPipelineCount('edukasi');
                $konsultasiCount = $getPipelineCount('konsultasi');
                $dealCount = $getPipelineCount('deal');
                $batalCount = $getPipelineCount('batal');
            @endphp

            <div class="mgmt-funnel-art">
                <div class="mgmt-funnel-main-shape">
                    <div class="mgmt-funnel-layer mgmt-funnel-layer-new">
                        <span>Leads Baru</span>
                        <strong>{{ number_format($leadsBaruCount) }}</strong>
                    </div>

                    <div class="mgmt-funnel-layer mgmt-funnel-layer-education">
                        <span>Sedang Edukasi</span>
                        <strong>{{ number_format($edukasiCount) }}</strong>
                    </div>

                    <div class="mgmt-funnel-layer mgmt-funnel-layer-consultation">
                        <span>Konsultasi</span>
                        <strong>{{ number_format($konsultasiCount) }}</strong>
                    </div>

                    <div class="mgmt-funnel-layer mgmt-funnel-layer-deal">
                        <span>Deal</span>
                        <strong>{{ number_format($dealCount) }}</strong>
                    </div>
                </div>

                <svg class="mgmt-funnel-dropoff-svg" viewBox="0 0 260 330" preserveAspectRatio="none" aria-hidden="true">
                    <path class="mgmt-funnel-drop-path path-1" d="M6 28 C120 44, 116 236, 224 276" />
                    <path class="mgmt-funnel-drop-path path-2" d="M8 98 C118 112, 118 246, 224 280" />
                    <path class="mgmt-funnel-drop-path path-3" d="M10 166 C116 178, 122 254, 224 284" />
                    <path class="mgmt-funnel-drop-path path-4" d="M12 230 C112 236, 128 266, 224 288" />

                    <path class="mgmt-funnel-drop-path-highlight" d="M6 28 C120 44, 116 236, 224 276" />
                    <path class="mgmt-funnel-drop-path-highlight" d="M8 98 C118 112, 118 246, 224 280" />
                    <path class="mgmt-funnel-drop-path-highlight" d="M10 166 C116 178, 122 254, 224 284" />
                    <path class="mgmt-funnel-drop-path-highlight" d="M12 230 C112 236, 128 266, 224 288" />
                </svg>

                <div class="mgmt-funnel-cancel-box">
                    <span>Batal</span>
                    <strong>{{ number_format($batalCount) }}</strong>
                </div>
            </div>

            <div class="mgmt-small-note mgmt-funnel-note">
                Data diambil dari Papan Kanban berdasarkan status pipeline lead pada periode filter aktif.
            </div>
        </div>
    </div>

    <div class="mgmt-grid-2">
        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Channel Volume</h2>
            <div class="chart-wrap small">
                <canvas id="channelVolumeChart"></canvas>
            </div>
            <div class="mgmt-small-note">
                Membandingkan jumlah lead per channel.
            </div>
        </div>

        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Channel Avg Score</h2>
            <div class="chart-wrap small">
                <canvas id="channelScoreChart"></canvas>
            </div>
            <div class="mgmt-small-note">
                Membandingkan kualitas rata-rata lead per channel.
            </div>
        </div>
    </div>

    <div class="mgmt-grid-2">
        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Tema Pertanyaan</h2>
            <div class="chart-wrap small">
                <canvas id="questionThemeChart"></canvas>
            </div>
        </div>

        <div class="mgmt-section-card chart-card">
            <h2 class="mgmt-section-title">Minat Treatment</h2>
            <div class="chart-wrap small">
                <canvas id="treatmentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="mgmt-grid-3">
        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Kanker yang Ditanyakan</h2>
            <div class="mgmt-list">
                @forelse($topCancers as $item)
                    <div class="mgmt-list-item">
                        <div class="mgmt-list-main">{{ $item['label'] }}</div>
                        <div class="mgmt-list-meta">{{ $item['count'] }} · {{ $item['percentage'] }}%</div>
                    </div>
                @empty
                    <div class="mgmt-list-main" style="color:#9ca3af;">Belum ada data.</div>
                @endforelse
            </div>
        </div>

        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Pain Points Pasien</h2>
            <div class="mgmt-list">
                @forelse($topPainPoints as $item)
                    <div class="mgmt-list-item">
                        <div class="mgmt-list-main">{{ $item['label'] }}</div>
                        <div class="mgmt-list-meta">{{ $item['count'] }} · {{ $item['percentage'] }}%</div>
                    </div>
                @empty
                    <div class="mgmt-list-main" style="color:#9ca3af;">Belum ada data.</div>
                @endforelse
            </div>
        </div>

        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Snapshot Singkat</h2>
            <div class="mgmt-list">
                <div class="mgmt-list-item">
                    <div class="mgmt-list-main">Best Channel</div>
                    <div class="mgmt-list-meta">{{ $bestChannelName }} · {{ $bestChannelScore }}</div>
                </div>
                <div class="mgmt-list-item">
                    <div class="mgmt-list-main">Kanker Teratas</div>
                    <div class="mgmt-list-meta">{{ $topCancer['label'] ?? '-' }}</div>
                </div>
                <div class="mgmt-list-item">
                    <div class="mgmt-list-main">Treatment Teratas</div>
                    <div class="mgmt-list-meta">{{ $topTreatment['label'] ?? '-' }}</div>
                </div>
                <div class="mgmt-list-item">
                    <div class="mgmt-list-main">Tema Teratas</div>
                    <div class="mgmt-list-meta">{{ $topTheme['label'] ?? '-' }}</div>
                </div>
                <div class="mgmt-list-item">
                    <div class="mgmt-list-main">Pain Point Teratas</div>
                    <div class="mgmt-list-meta">{{ $topPain['label'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mgmt-grid-bottom">
        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Pertanyaan Representatif</h2>
            <div class="mgmt-qa-list">
                @forelse($representativeQuestions->take(8) as $row)
                    <div class="mgmt-qa-item">
                        <div class="mgmt-qa-text">“{{ $row->representative_question }}”</div>
                        <div class="mgmt-qa-meta">
                            {{ $row->client_number }} · {{ $row->source_channel }} ·
                            {{ $row->kategori_kanker_norm }} · {{ $row->minat_treatment_norm }} ·
                            <span class="mgmt-pill pill-{{ strtolower($row->lead_quality_segment) }}">
                                {{ $row->lead_quality_segment }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="mgmt-qa-meta">Belum ada pertanyaan representatif.</div>
                @endforelse
            </div>
        </div>

        <div class="mgmt-section-card">
            <h2 class="mgmt-section-title">Rekomendasi & Content Angle</h2>
            <div class="mgmt-qa-list">
                @forelse($contentAngles->take(8) as $row)
                    <div class="mgmt-qa-item">
                        <div class="mgmt-qa-text">{{ $row->content_angle }}</div>
                        <div class="mgmt-qa-meta">
                            {{ $row->source_channel }} · {{ $row->question_theme }} · Score {{ $row->management_score }}
                        </div>
                    </div>
                @empty
                    <div class="mgmt-qa-meta">Belum ada ide konten dari data.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qualityLabels = @json($qualityLabels);
    const qualityValues = @json($qualityValues);
    const funnelLabels = @json($funnelLabels);
    const funnelValues = @json($funnelValues);
    const channelLabels = @json($channelLabels);
    const channelTotals = @json($channelTotals);
    const channelAvgScores = @json($channelAvgScores);
    const themeLabels = @json($themeLabels);
    const themeValues = @json($themeValues);
    const treatmentLabels = @json($treatmentLabels);
    const treatmentValues = @json($treatmentValues);

    const textColor = '#d1d5db';
    const mutedColor = '#6b7280';
    const gridColor = 'rgba(255,255,255,0.08)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

    const commonPlugins = {
        legend: {
            labels: {
                color: textColor,
                usePointStyle: true,
                boxWidth: 8,
                boxHeight: 8,
                padding: 18
            }
        },
        tooltip: {
            backgroundColor: '#0f0f0f',
            titleColor: '#ffffff',
            bodyColor: '#d1d5db',
            borderColor: '#2a2a2a',
            borderWidth: 1,
            padding: 12
        }
    };

    function hasData(values) {
        return Array.isArray(values) && values.some(v => Number(v) > 0);
    }

    function safeLabels(labels, fallback) {
        return Array.isArray(labels) && labels.length ? labels : fallback;
    }

    function safeValues(values, fallback) {
        return hasData(values) ? values : fallback;
    }

    const qualityCanvas = document.getElementById('qualityDonutChart');
    if (qualityCanvas) {
        new Chart(qualityCanvas, {
            type: 'doughnut',
            data: {
                labels: safeLabels(qualityLabels, ['Belum Ada Data']),
                datasets: [{
                    data: safeValues(qualityValues, [1]),
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
                    borderColor: '#171717',
                    borderWidth: 5,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: commonPlugins
            }
        });
    }

    const funnelCanvas = document.getElementById('pipelineFunnelChart');
    if (funnelCanvas) {
        new Chart(funnelCanvas, {
            type: 'bar',
            data: {
                labels: safeLabels(funnelLabels, ['Belum Ada Data']),
                datasets: [{
                    label: 'Jumlah',
                    data: safeValues(funnelValues, [0]),
                    backgroundColor: ['#10b981', '#8b5cf6', '#3b82f6', '#f59e0b'],
                    borderRadius: 10,
                    barThickness: 34
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonPlugins.tooltip
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: mutedColor, precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    const channelVolumeCanvas = document.getElementById('channelVolumeChart');
    if (channelVolumeCanvas) {
        new Chart(channelVolumeCanvas, {
            type: 'bar',
            data: {
                labels: safeLabels(channelLabels, ['Belum Ada Data']),
                datasets: [{
                    label: 'Total Lead',
                    data: safeValues(channelTotals, [0]),
                    backgroundColor: '#8b5cf6',
                    borderRadius: 10,
                    barThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonPlugins.tooltip
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: mutedColor, precision: 0 }
                    }
                }
            }
        });
    }

    const channelScoreCanvas = document.getElementById('channelScoreChart');
    if (channelScoreCanvas) {
        new Chart(channelScoreCanvas, {
            type: 'bar',
            data: {
                labels: safeLabels(channelLabels, ['Belum Ada Data']),
                datasets: [{
                    label: 'Avg Score',
                    data: safeValues(channelAvgScores, [0]),
                    backgroundColor: '#22c55e',
                    borderRadius: 10,
                    barThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonPlugins.tooltip
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: gridColor },
                        ticks: { color: mutedColor }
                    }
                }
            }
        });
    }

    const themeCanvas = document.getElementById('questionThemeChart');
    if (themeCanvas) {
        new Chart(themeCanvas, {
            type: 'bar',
            data: {
                labels: safeLabels(themeLabels, ['Belum Ada Data']),
                datasets: [{
                    label: 'Jumlah',
                    data: safeValues(themeValues, [0]),
                    backgroundColor: '#3b82f6',
                    borderRadius: 9,
                    barThickness: 22
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonPlugins.tooltip
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: mutedColor, precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    const treatmentCanvas = document.getElementById('treatmentChart');
    if (treatmentCanvas) {
        new Chart(treatmentCanvas, {
            type: 'bar',
            data: {
                labels: safeLabels(treatmentLabels, ['Belum Ada Data']),
                datasets: [{
                    label: 'Jumlah',
                    data: safeValues(treatmentValues, [0]),
                    backgroundColor: '#f59e0b',
                    borderRadius: 9,
                    barThickness: 22
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: commonPlugins.tooltip
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: mutedColor, precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ttlSelect = document.getElementById('cacheTtlSelect');
    const customWrap = document.getElementById('customTtlWrap');

    if (ttlSelect && customWrap) {
        const toggleCustom = () => {
            customWrap.style.display = ttlSelect.value === 'custom' ? 'block' : 'none';
        };

        ttlSelect.addEventListener('change', toggleCustom);
        toggleCustom();
    }
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const enableQa = document.getElementById('enableQaCheckbox');
    const cacheOptions = document.getElementById('autoCacheOptions');
    const ttlSelect = document.getElementById('autoCacheTtlSelect');
    const customTtlWrap = document.getElementById('autoCustomTtlWrap');

    function syncQaOptions() {
        if (enableQa && cacheOptions) {
            cacheOptions.classList.toggle('show', enableQa.checked);
        }

        if (ttlSelect && customTtlWrap) {
            customTtlWrap.style.display = ttlSelect.value === 'custom' ? 'block' : 'none';
        }
    }

    if (enableQa) {
        enableQa.addEventListener('change', syncQaOptions);
    }

    if (ttlSelect) {
        ttlSelect.addEventListener('change', syncQaOptions);
    }

    syncQaOptions();
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    function prepareAnswerToggle(card, bodySelector, defaultCollapsed = false) {
        if (!card || card.dataset.answerToggleReady === '1') {
            return;
        }

        const body = card.querySelector(bodySelector);

        if (!body) {
            return;
        }

        card.dataset.answerToggleReady = '1';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mgmt-answer-toggle';

        const preview = document.createElement('div');
        preview.className = 'mgmt-answer-preview';

        const bodyText = (body.innerText || body.textContent || '').trim();
        preview.textContent = bodyText.length > 180
            ? bodyText.substring(0, 180) + '...'
            : bodyText;

        body.insertAdjacentElement('afterend', preview);

        function setCollapsed(isCollapsed) {
            card.classList.toggle('mgmt-answer-collapsed', isCollapsed);
            button.textContent = isCollapsed ? 'Tampilkan' : 'Minimize';
        }

        button.addEventListener('click', function () {
            setCollapsed(!card.classList.contains('mgmt-answer-collapsed'));
        });

        const latestTitle = card.querySelector('.mgmt-latest-answer-title');

        if (latestTitle) {
            const header = document.createElement('div');
            header.className = 'mgmt-answer-header-row';

            latestTitle.parentNode.insertBefore(header, latestTitle);
            header.appendChild(latestTitle);
            header.appendChild(button);
        } else {
            const question = card.querySelector('.mgmt-answer-q');

            if (question) {
                const header = document.createElement('div');
                header.className = 'mgmt-answer-header-row';

                question.parentNode.insertBefore(header, question);
                header.appendChild(question);
                header.appendChild(button);
            } else {
                card.insertBefore(button, card.firstChild);
            }
        }

        setCollapsed(defaultCollapsed);
    }

    document.querySelectorAll('.mgmt-latest-answer').forEach(function (card) {
        prepareAnswerToggle(card, '.mgmt-latest-answer-body', false);
    });

    document.querySelectorAll('.mgmt-answer-card').forEach(function (card) {
        prepareAnswerToggle(card, '.mgmt-answer-a', true);
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const targets = {
        'Proses Laporan AI': 'Pengaturan proses rekap AI dan context cache.',
        'Status Tanya Data': 'Status mode tanya data, cache, dan direct context.',
        'Tanya Data Management': 'Form tanya jawab berbasis data management report.'
    };

    function slugify(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    document.querySelectorAll('.mgmt-section-card').forEach(function (card) {
        const title = card.querySelector(':scope > .mgmt-section-title');

        if (!title) {
            return;
        }

        const titleText = title.textContent.trim();

        if (!Object.prototype.hasOwnProperty.call(targets, titleText)) {
            return;
        }

        if (card.dataset.collapsibleReady === '1') {
            return;
        }

        card.dataset.collapsibleReady = '1';
        card.classList.add('mgmt-collapsible-card');

        const storageKey = 'mgmt-card-collapsed-' + slugify(titleText);

        const header = document.createElement('div');
        header.className = 'mgmt-collapsible-header';

        const right = document.createElement('div');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mgmt-collapse-btn';

        const subtitle = document.createElement('div');
        subtitle.className = 'mgmt-collapsible-subtitle';
        subtitle.textContent = targets[titleText];

        const body = document.createElement('div');
        body.className = 'mgmt-collapsible-body';

        title.parentNode.insertBefore(header, title);
        header.appendChild(title);
        right.appendChild(button);
        header.appendChild(right);
        header.insertAdjacentElement('afterend', subtitle);

        let node = subtitle.nextSibling;
        const nodesToMove = [];

        while (node) {
            const next = node.nextSibling;
            nodesToMove.push(node);
            node = next;
        }

        nodesToMove.forEach(function (item) {
            body.appendChild(item);
        });

        card.appendChild(body);

        function setCollapsed(isCollapsed) {
            card.classList.toggle('mgmt-collapsed', isCollapsed);
            button.textContent = isCollapsed ? 'Tampilkan' : 'Minimize';
            localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
        }

        button.addEventListener('click', function () {
            setCollapsed(!card.classList.contains('mgmt-collapsed'));
        });

        const saved = localStorage.getItem(storageKey);

        // Default: collapsed agar halaman management report lebih bersih.
        setCollapsed(saved === null ? true : saved === '1');
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-channel-detail-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const mainRow = button.closest('tr');
            const detailRow = mainRow ? mainRow.nextElementSibling : null;

            if (!detailRow || !detailRow.classList.contains('mgmt-channel-detail-row')) {
                return;
            }

            const isOpen = detailRow.classList.toggle('show');
            button.textContent = isOpen ? 'Tutup' : 'Detail';
        });
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('channelDetailModal');
    const closeBtn = document.getElementById('channelModalClose');
    const accordion = document.getElementById('channelCorrectionAccordion');
    const accordionToggle = document.getElementById('channelCorrectionToggle');
    const accordionToggleText = document.getElementById('channelCorrectionToggleText');

    if (!modal) return;

    const details = Array.isArray(window.mgmtChannelDetails) ? window.mgmtChannelDetails : [];
    const byChannel = {};

    details.forEach(function (item) {
        byChannel[item.channel] = item;
    });

    let activeChannel = null;
    let activeField = null;
    let activeValue = null;

    const fieldConfig = [
        {
            field: 'kategori_kanker_norm',
            container: 'channelSummaryCancer'
        },
        {
            field: 'minat_treatment_norm',
            container: 'channelSummaryTreatment'
        },
        {
            field: 'question_theme',
            container: 'channelSummaryTheme'
        },
        {
            field: 'profil_pengirim_norm',
            container: 'channelSummaryProfile'
        },
        {
            field: 'kendala_utama_norm',
            container: 'channelSummaryObstacle'
        }
    ];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setAccordionCollapsed(isCollapsed) {
        if (!accordion) return;

        accordion.classList.toggle('collapsed', isCollapsed);

        if (accordionToggleText) {
            accordionToggleText.textContent = isCollapsed ? 'Tampilkan' : 'Minimize';
        }
    }

    if (accordionToggle) {
        accordionToggle.addEventListener('click', function () {
            setAccordionCollapsed(!accordion.classList.contains('collapsed'));
        });
    }

    function renderKpis(data) {
        document.getElementById('channelModalKpis').innerHTML = `
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Total Lead</div>
                <div class="mgmt-modal-kpi-value">${escapeHtml(data.total)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Hot</div>
                <div class="mgmt-modal-kpi-value text-green">${escapeHtml(data.hot)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Warm</div>
                <div class="mgmt-modal-kpi-value text-yellow">${escapeHtml(data.warm)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Cold</div>
                <div class="mgmt-modal-kpi-value text-blue">${escapeHtml(data.cold)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Junk</div>
                <div class="mgmt-modal-kpi-value text-red">${escapeHtml(data.junk)}</div>
            </div>
        `;
    }

    function renderSummaryLists(data) {
        fieldConfig.forEach(function (config) {
            const container = document.getElementById(config.container);
            const breakdown = data.breakdowns && data.breakdowns[config.field];

            if (!container) return;

            if (!breakdown || !breakdown.values || !breakdown.values.length) {
                container.innerHTML = '<div class="mgmt-small-note">Belum ada data.</div>';
                return;
            }

            container.innerHTML = breakdown.values.slice(0, 8).map(function (row) {
                return `
                    <div class="mgmt-summary-list-item">
                        <div class="mgmt-summary-list-label">
                            ${escapeHtml(row.value)}
                            ${row.is_ambiguous ? '<span class="mgmt-summary-ambiguous-badge">Perlu cek</span>' : ''}
                        </div>
                        <div class="mgmt-summary-list-meta">${escapeHtml(row.count)} · ${escapeHtml(row.percentage)}%</div>
                    </div>
                `;
            }).join('');
        });
    }

    function renderFieldTabs(data) {
        const tabs = document.getElementById('channelFieldTabs');
        const breakdowns = data.breakdowns || {};
        const fields = Object.keys(breakdowns);

        tabs.innerHTML = fields.map(function (fieldName, index) {
            const item = breakdowns[fieldName];

            return `
                <button type="button" class="mgmt-field-tab ${index === 0 ? 'active' : ''}" data-field="${escapeHtml(fieldName)}">
                    ${escapeHtml(item.field_label)}
                </button>
            `;
        }).join('');

        tabs.querySelectorAll('.mgmt-field-tab').forEach(function (button) {
            button.addEventListener('click', function () {
                tabs.querySelectorAll('.mgmt-field-tab').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectField(button.dataset.field);
            });
        });

        if (fields.length) {
            selectField(fields[0]);
        }
    }

    function selectField(fieldName) {
        activeField = fieldName;
        activeValue = null;

        const data = activeChannel;
        const breakdown = data.breakdowns[fieldName];

        document.getElementById('channelValueTitle').textContent = breakdown.field_label;
        document.getElementById('channelCorrectionTitle').textContent = 'Data untuk Koreksi';

        renderValues(breakdown);
        document.getElementById('channelCorrectionList').innerHTML =
            '<div class="mgmt-small-note">Pilih salah satu nilai di kiri untuk melihat data lead yang masuk kategori tersebut.</div>';
    }

    function renderValues(breakdown) {
        const list = document.getElementById('channelValueList');
        const values = breakdown.values || [];

        if (!values.length) {
            list.innerHTML = '<div class="mgmt-small-note">Belum ada data.</div>';
            return;
        }

        list.innerHTML = values.map(function (row) {
            return `
                <button type="button" class="mgmt-value-btn" data-value="${escapeHtml(row.value)}">
                    <span>
                        <span class="mgmt-value-name">${escapeHtml(row.value)}</span>
                        ${row.is_ambiguous ? '<span class="mgmt-ambiguous-badge">Perlu cek</span>' : ''}
                    </span>
                    <span class="mgmt-value-meta">${escapeHtml(row.count)} · ${escapeHtml(row.percentage)}%</span>
                </button>
            `;
        }).join('');

        list.querySelectorAll('.mgmt-value-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                list.querySelectorAll('.mgmt-value-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectValue(button.dataset.value);
            });
        });
    }

    function selectValue(value) {
        activeValue = value;

        const breakdown = activeChannel.breakdowns[activeField];
        const rows = (breakdown.rows_by_value && breakdown.rows_by_value[value]) ? breakdown.rows_by_value[value] : [];

        document.getElementById('channelCorrectionTitle').textContent =
            breakdown.field_label + ' → ' + value;

        renderCorrectionRows(breakdown, value, rows);
    }

    function renderCorrectionRows(breakdown, value, rows) {
        const container = document.getElementById('channelCorrectionList');

        if (!rows.length) {
            container.innerHTML = '<div class="mgmt-small-note">Tidak ada data lead untuk nilai ini.</div>';
            return;
        }

        container.innerHTML = rows.map(function (row) {
            return `
                <div class="mgmt-correction-card">
                    <div class="mgmt-correction-title">
                        ${escapeHtml(breakdown.field_label)} saat ini: ${escapeHtml(value)}
                    </div>

                    <div class="mgmt-correction-meta">
                        <strong>Client:</strong> ${escapeHtml(row.client_number || '-')} ·
                        <strong>Segment:</strong> ${escapeHtml(row.lead_quality_segment || '-')} ·
                        <strong>Score:</strong> ${escapeHtml(row.management_score ?? '-')}<br>
                        <strong>Profil:</strong> ${escapeHtml(row.profil_pengirim_norm || '-')} ·
                        <strong>Kendala:</strong> ${escapeHtml(row.kendala_utama_norm || '-')}<br>
                        <strong>Kanker:</strong> ${escapeHtml(row.kategori_kanker_norm || '-')} ·
                        <strong>Treatment:</strong> ${escapeHtml(row.minat_treatment_norm || '-')} ·
                        <strong>Tema:</strong> ${escapeHtml(row.question_theme || '-')}<br>
                        <strong>Intent:</strong> ${escapeHtml(row.patient_intent || '-')}<br>
                        <strong>Ringkasan:</strong> ${escapeHtml(row.management_summary || '-')}
                    </div>

                    <form method="POST" action="${escapeHtml(window.mgmtCorrectionUrl)}" class="mgmt-correction-form">
                        <input type="hidden" name="_token" value="${escapeHtml(window.mgmtCsrfToken)}">
                        <input type="hidden" name="summary_id" value="${escapeHtml(row.summary_id)}">
                        <input type="hidden" name="field_name" value="${escapeHtml(breakdown.field_name)}">
                        <input type="hidden" name="start_date" value="${escapeHtml(window.mgmtPageStart)}">
                        <input type="hidden" name="end_date" value="${escapeHtml(window.mgmtPageEnd)}">

                        <div class="mgmt-field">
                            <label>Nilai Sekarang</label>
                            <input type="text" class="mgmt-input" value="${escapeHtml(value)}" readonly>
                        </div>

                        <div class="mgmt-field">
                            <label>Ubah Menjadi</label>
                            <input type="text" name="new_value" class="mgmt-input" placeholder="Masukkan kategori yang benar" required>
                        </div>

                        <div class="mgmt-field full">
                            <label>Keyword Pembelajaran Sistem</label>
                            <input type="text" name="learning_keywords" class="mgmt-input" placeholder="Contoh: tumor otak, radioterapi, biaya, keluarga pasien">
                        </div>

                        <div class="mgmt-field full">
                            <label>Alasan Koreksi / Kenapa Masuk Kategori Ini</label>
                            <textarea name="correction_reason" class="mgmt-textarea" placeholder="Jelaskan alasan agar sistem bisa memakai rule ini pada proses berikutnya." required></textarea>
                        </div>

                        <div class="full" style="display:flex; justify-content:flex-end;">
                            <button type="submit" class="mgmt-btn">Simpan Koreksi + Rule</button>
                        </div>
                    </form>
                </div>
            `;
        }).join('');
    }

    function openModal(channelName) {
        const data = byChannel[channelName];
        if (!data) return;

        activeChannel = data;
        activeField = null;
        activeValue = null;

        document.getElementById('channelModalTitle').textContent = 'Detail Channel: ' + channelName;
        document.getElementById('channelModalSubtitle').textContent =
            'Total ' + data.total + ' lead · Hot+Warm ' + data.hot_warm_rate + '% · Junk ' + data.junk_rate + '% · Avg Score ' + data.avg_score;

        renderKpis(data);
        renderSummaryLists(data);
        renderFieldTabs(data);
        setAccordionCollapsed(true);

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-open-channel-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.dataset.channel);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('channelDetailModal');
    const closeBtn = document.getElementById('channelModalClose');

    if (!modal) return;

    const details = Array.isArray(window.mgmtChannelDetails) ? window.mgmtChannelDetails : [];
    const byChannel = {};

    details.forEach(function (item) {
        byChannel[item.channel] = item;
    });

    let activeChannel = null;
    let activeField = null;
    let activeValue = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderKpis(data) {
        document.getElementById('channelModalKpis').innerHTML = `
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Total Lead</div>
                <div class="mgmt-modal-kpi-value">${escapeHtml(data.total)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Hot</div>
                <div class="mgmt-modal-kpi-value text-green">${escapeHtml(data.hot)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Warm</div>
                <div class="mgmt-modal-kpi-value text-yellow">${escapeHtml(data.warm)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Cold</div>
                <div class="mgmt-modal-kpi-value text-blue">${escapeHtml(data.cold)}</div>
            </div>
            <div class="mgmt-modal-kpi">
                <div class="mgmt-modal-kpi-label">Junk</div>
                <div class="mgmt-modal-kpi-value text-red">${escapeHtml(data.junk)}</div>
            </div>
        `;
    }

    function renderFieldTabs(data) {
        const tabs = document.getElementById('channelFieldTabs');
        const breakdowns = data.breakdowns || {};
        const fields = Object.keys(breakdowns);

        tabs.innerHTML = fields.map(function (fieldName, index) {
            const item = breakdowns[fieldName];

            return `
                <button type="button" class="mgmt-field-tab ${index === 0 ? 'active' : ''}" data-field="${escapeHtml(fieldName)}">
                    ${escapeHtml(item.field_label)}
                </button>
            `;
        }).join('');

        tabs.querySelectorAll('.mgmt-field-tab').forEach(function (button) {
            button.addEventListener('click', function () {
                tabs.querySelectorAll('.mgmt-field-tab').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectField(button.dataset.field);
            });
        });

        if (fields.length) {
            selectField(fields[0]);
        }
    }

    function selectField(fieldName) {
        activeField = fieldName;
        activeValue = null;

        const data = activeChannel;
        const breakdown = data.breakdowns[fieldName];

        document.getElementById('channelValueTitle').textContent = breakdown.field_label;
        document.getElementById('channelCorrectionTitle').textContent = 'Data untuk Koreksi';

        renderValues(breakdown);
        document.getElementById('channelCorrectionList').innerHTML =
            '<div class="mgmt-small-note">Pilih salah satu nilai di kiri untuk melihat data lead yang masuk kategori tersebut.</div>';
    }

    function renderValues(breakdown) {
        const list = document.getElementById('channelValueList');
        const values = breakdown.values || [];

        if (!values.length) {
            list.innerHTML = '<div class="mgmt-small-note">Belum ada data.</div>';
            return;
        }

        list.innerHTML = values.map(function (row) {
            return `
                <button type="button" class="mgmt-value-btn" data-value="${escapeHtml(row.value)}">
                    <span>
                        <span class="mgmt-value-name">${escapeHtml(row.value)}</span>
                        ${row.is_ambiguous ? '<span class="mgmt-ambiguous-badge">Perlu cek</span>' : ''}
                    </span>
                    <span class="mgmt-value-meta">${escapeHtml(row.count)} · ${escapeHtml(row.percentage)}%</span>
                </button>
            `;
        }).join('');

        list.querySelectorAll('.mgmt-value-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                list.querySelectorAll('.mgmt-value-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectValue(button.dataset.value);
            });
        });
    }

    function selectValue(value) {
        activeValue = value;

        const breakdown = activeChannel.breakdowns[activeField];
        const rows = (breakdown.rows_by_value && breakdown.rows_by_value[value]) ? breakdown.rows_by_value[value] : [];

        document.getElementById('channelCorrectionTitle').textContent =
            breakdown.field_label + ' → ' + value;

        renderCorrectionRows(breakdown, value, rows);
    }

    function renderCorrectionRows(breakdown, value, rows) {
        const container = document.getElementById('channelCorrectionList');

        if (!rows.length) {
            container.innerHTML = '<div class="mgmt-small-note">Tidak ada data lead untuk nilai ini.</div>';
            return;
        }

        container.innerHTML = rows.map(function (row) {
            return `
                <div class="mgmt-correction-card">
                    <div class="mgmt-correction-title">
                        ${escapeHtml(breakdown.field_label)} saat ini: ${escapeHtml(value)}
                    </div>

                    <div class="mgmt-correction-meta">
                        <strong>Client:</strong> ${escapeHtml(row.client_number || '-')} ·
                        <strong>Segment:</strong> ${escapeHtml(row.lead_quality_segment || '-')} ·
                        <strong>Score:</strong> ${escapeHtml(row.management_score ?? '-')}<br>
                        <strong>Profil:</strong> ${escapeHtml(row.profil_pengirim_norm || '-')} ·
                        <strong>Kendala:</strong> ${escapeHtml(row.kendala_utama_norm || '-')}<br>
                        <strong>Kanker:</strong> ${escapeHtml(row.kategori_kanker_norm || '-')} ·
                        <strong>Treatment:</strong> ${escapeHtml(row.minat_treatment_norm || '-')} ·
                        <strong>Tema:</strong> ${escapeHtml(row.question_theme || '-')}<br>
                        <strong>Intent:</strong> ${escapeHtml(row.patient_intent || '-')}<br>
                        <strong>Ringkasan:</strong> ${escapeHtml(row.management_summary || '-')}
                    </div>

                    <form method="POST" action="${escapeHtml(window.mgmtCorrectionUrl)}" class="mgmt-correction-form">
                        <input type="hidden" name="_token" value="${escapeHtml(window.mgmtCsrfToken)}">
                        <input type="hidden" name="summary_id" value="${escapeHtml(row.summary_id)}">
                        <input type="hidden" name="field_name" value="${escapeHtml(breakdown.field_name)}">
                        <input type="hidden" name="start_date" value="${escapeHtml(window.mgmtPageStart)}">
                        <input type="hidden" name="end_date" value="${escapeHtml(window.mgmtPageEnd)}">

                        <div class="mgmt-field">
                            <label>Nilai Sekarang</label>
                            <input type="text" class="mgmt-input" value="${escapeHtml(value)}" readonly>
                        </div>

                        <div class="mgmt-field">
                            <label>Ubah Menjadi</label>
                            <input type="text" name="new_value" class="mgmt-input" placeholder="Masukkan kategori yang benar" required>
                        </div>

                        <div class="mgmt-field full">
                            <label>Keyword Pembelajaran Sistem</label>
                            <input type="text" name="learning_keywords" class="mgmt-input" placeholder="Contoh: tumor otak, radioterapi, biaya, keluarga pasien">
                        </div>

                        <div class="mgmt-field full">
                            <label>Alasan Koreksi / Kenapa Masuk Kategori Ini</label>
                            <textarea name="correction_reason" class="mgmt-textarea" placeholder="Jelaskan alasan agar sistem bisa memakai rule ini pada proses berikutnya." required></textarea>
                        </div>

                        <div class="full" style="display:flex; justify-content:flex-end;">
                            <button type="submit" class="mgmt-btn">Simpan Koreksi + Rule</button>
                        </div>
                    </form>
                </div>
            `;
        }).join('');
    }

    function openModal(channelName) {
        const data = byChannel[channelName];
        if (!data) return;

        activeChannel = data;
        activeField = null;
        activeValue = null;

        document.getElementById('channelModalTitle').textContent = 'Detail Channel: ' + channelName;
        document.getElementById('channelModalSubtitle').textContent =
            'Total ' + data.total + ' lead · Hot+Warm ' + data.hot_warm_rate + '% · Junk ' + data.junk_rate + '% · Avg Score ' + data.avg_score;

        renderKpis(data);
        renderFieldTabs(data);

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-open-channel-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.dataset.channel);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('submit', async function (event) {
        const form = event.target;

        if (!form.classList || !form.classList.contains('mgmt-correction-form')) {
            return;
        }

        event.preventDefault();

        const card = form.closest('.mgmt-correction-card');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
            submitBtn.style.opacity = '0.75';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            let payload = null;

            try {
                payload = await response.json();
            } catch (jsonError) {
                payload = null;
            }

            if (!response.ok || !payload || payload.ok !== true) {
                throw new Error((payload && payload.message) ? payload.message : 'Gagal menyimpan koreksi.');
            }

            if (card) {
                card.style.borderColor = 'rgba(34, 197, 94, 0.45)';
                card.style.background = 'rgba(34, 197, 94, 0.08)';

                const doneNotice = document.createElement('div');
                doneNotice.className = 'mgmt-alert mgmt-alert-success';
                doneNotice.style.marginTop = '10px';
                doneNotice.style.marginBottom = '0';
                doneNotice.textContent = payload.message || 'Koreksi berhasil disimpan.';

                card.appendChild(doneNotice);

                setTimeout(function () {
                    const list = card.closest('.mgmt-correction-list');

                    card.remove();

                    if (list && !list.querySelector('.mgmt-correction-card')) {
                        list.innerHTML = '<div class="mgmt-small-note">Semua data pada nilai ini sudah dikoreksi. Pilih nilai lain di kiri jika masih ada yang perlu dicek.</div>';
                    }
                }, 650);
            }

        } catch (error) {
            alert(error.message || 'Gagal menyimpan koreksi.');

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                submitBtn.style.opacity = '';
            }
        }
    });
});
</script>

@endsection
