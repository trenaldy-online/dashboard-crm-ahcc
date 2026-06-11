@extends('layouts.app')

@section('title', 'Laporan Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="text-sm text-brand-purple font-semibold uppercase tracking-wider">Monthly Management Report</p>
            <h1 class="text-3xl font-bold text-white mt-1">Laporan Management</h1>
            <p class="text-gray-400 mt-2">
                Ringkasan performa marketing, kualitas lead, follow-up, SLA CS, dan kesehatan data.
            </p>
        </div>

        <form method="GET" action="{{ route('laporan.management') }}" class="bg-dark-surface border border-dark-border rounded-xl p-4 flex flex-col md:flex-row gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div class="flex items-end">
                <button class="bg-brand-purple hover:bg-brand-purple/90 text-white px-5 py-2 rounded-lg text-sm font-semibold">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider">Periode Laporan</p>
                <p class="text-white font-semibold">{{ $startDate->format('d M Y') }} sampai {{ $endDate->format('d M Y') }}</p>
            </div>
            <div class="text-sm text-gray-400">
                Default laporan memakai <span class="text-white font-semibold">tanggal chat pasien</span>, bukan tanggal summary dibuat.
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Pasien Aktif</p>
            <h3 class="text-3xl font-bold text-white mt-2">{{ $totalActivePatients }}</h3>
            <p class="text-xs text-gray-500 mt-1">nomor chat dalam periode</p>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Lead Baru</p>
            <h3 class="text-3xl font-bold text-blue-400 mt-2">{{ $totalNewLeads }}</h3>
            <p class="text-xs text-gray-500 mt-1">first chat dalam periode</p>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Eligible Rate</p>
            <h3 class="text-3xl font-bold text-green-400 mt-2">{{ $eligibleRate }}%</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $eligibleLead }} dari {{ $totalSummarizedLeads }} lead</p>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Junk Rate</p>
            <h3 class="text-3xl font-bold text-red-400 mt-2">{{ $junkRate }}%</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $junkLead }} lead junk</p>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Deal Rate</p>
            <h3 class="text-3xl font-bold text-brand-purple mt-2">{{ $conversionRate }}%</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $dealLead }} dari {{ $totalSummarizedLeads }} lead</p>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-5">
            <p class="text-xs text-gray-400 uppercase">Avg Score</p>
            <h3 class="text-3xl font-bold text-yellow-400 mt-2">{{ $avgLeadScore }}</h3>
            <p class="text-xs text-gray-500 mt-1">rata-rata lead score</p>
        </div>
    </div>

    
    <div id="management-kpi-notes" class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <div class="flex items-start gap-3 mb-5">
            <div class="w-9 h-9 rounded-lg bg-blue-500/15 flex items-center justify-center text-blue-400">i</div>
            <div>
                <h2 class="text-lg font-bold text-white">Catatan Interpretasi KPI</h2>
                <p class="text-sm text-gray-400 mt-1">
                    Angka eligible rate, junk rate, dan conversion tidak dijumlahkan menjadi 100% karena ketiganya berasal dari dimensi data yang berbeda.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-sm font-semibold text-white mb-2">Kenapa total rate tidak 100%?</p>
                <p class="text-sm text-gray-400 leading-relaxed">
                    <span class="text-green-400 font-semibold">Eligible Rate</span> menghitung lead yang layak ditangani,
                    <span class="text-red-400 font-semibold">Junk Rate</span> menghitung lead tidak relevan,
                    sedangkan <span class="text-brand-purple font-semibold">Conversion</span> menghitung lead yang sudah masuk status deal.
                    Conversion bisa menjadi bagian dari lead eligible, sehingga metrik ini bukan kategori yang saling terpisah.
                </p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-sm font-semibold text-white mb-2">Note Avg Score</p>
                <p class="text-sm text-gray-400 leading-relaxed">
                    <span class="text-yellow-400 font-semibold">Avg Score</span> adalah rata-rata <span class="text-white">lead_score</span>
                    dari hasil analisis AI. Interpretasi praktisnya:
                    <span class="text-gray-300">0-39 rendah</span>,
                    <span class="text-gray-300">40-69 sedang</span>,
                    dan <span class="text-gray-300">70-100 tinggi</span>.
                    Angka ini menunjukkan kualitas atau prioritas lead secara umum, bukan persentase conversion.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm">
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Pasien Aktif</p>
                <p class="text-gray-500 mt-1">Jumlah nomor unik di <span class="text-gray-300">wa_chats</span> yang memiliki chat pada periode laporan.</p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Lead Baru</p>
                <p class="text-gray-500 mt-1">Jumlah nomor yang first chat-nya terjadi pada periode laporan.</p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Eligible Rate</p>
                <p class="text-gray-500 mt-1">Persentase lead dengan <span class="text-gray-300">is_eligible_for_hana = true</span>.</p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Junk Rate</p>
                <p class="text-gray-500 mt-1">Persentase lead dengan <span class="text-gray-300">conversation_outcome = junk_lead</span>.</p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Conversion / Deal Rate</p>
                <p class="text-gray-500 mt-1">Persentase lead dengan <span class="text-gray-300">pipeline_status = deal</span>.</p>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-gray-300 font-semibold">Avg Score</p>
                <p class="text-gray-500 mt-1">Rata-rata <span class="text-gray-300">lead_score</span> dari lead yang sudah diringkas AI.</p>
            </div>
        </div>
    </div>


    <div class="bg-dark-surface border border-brand-purple/40 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-lg bg-brand-purple/20 flex items-center justify-center text-brand-purple">💡</div>
            <div>
                <h2 class="text-lg font-bold text-white">Executive Insight</h2>
                <p class="text-sm text-gray-400">Poin ringkas untuk bahan meeting management.</p>
            </div>
        </div>

        <div class="space-y-3">
            @foreach($managementInsights as $insight)
                <div class="bg-dark-bg border border-dark-border rounded-lg p-4 text-gray-300 text-sm leading-relaxed">
                    {{ $insight }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Funnel Pipeline Bulanan</h2>
            <div class="space-y-4">
                @php
                    $maxPipeline = max($pipelineFunnel->max() ?: 1, 1);
                @endphp

                @foreach($pipelineFunnel as $label => $total)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-300">{{ $label }}</span>
                            <span class="text-white font-semibold">{{ $total }}</span>
                        </div>
                        <div class="h-3 bg-dark-bg rounded-full overflow-hidden">
                            <div class="h-full bg-brand-purple rounded-full" style="width: {{ ($total / $maxPipeline) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Channel Quality</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-dark-border">
                            <th class="pb-3">Channel</th>
                            <th class="pb-3 text-right">Lead</th>
                            <th class="pb-3 text-right">Eligible</th>
                            <th class="pb-3 text-right">Junk</th>
                            <th class="pb-3 text-right">Deal</th>
                            <th class="pb-3 text-right">Score</th>
                            <th class="pb-3 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        @foreach($channelSummary as $channel => $data)
                            <tr>
                                <td class="py-3 text-white font-medium">{{ $channel }}</td>
                                <td class="py-3 text-right text-gray-300">{{ $data['total'] }}</td>
                                <td class="py-3 text-right text-green-400">{{ $data['eligible_rate'] }}%</td>
                                <td class="py-3 text-right text-red-400">{{ $data['junk_rate'] }}%</td>
                                <td class="py-3 text-right text-brand-purple">{{ $data['conversion_rate'] }}%</td>
                                <td class="py-3 text-right text-yellow-400">{{ $data['avg_score'] }}</td>
                                <td class="py-3 text-right">
                                    <button type="button"
                                        class="js-channel-detail-btn px-3 py-1.5 rounded-lg bg-brand-purple/15 hover:bg-brand-purple/25 text-brand-purple text-xs font-semibold transition"
                                        data-channel="{{ $channel }}">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- CHANNEL_DETAIL_MODAL_START -->
    <div id="channel-detail-modal" class="fixed inset-0 z-[9999] hidden">
        <div id="channel-detail-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-6xl max-h-[90vh] overflow-hidden bg-dark-surface border border-dark-border rounded-2xl shadow-2xl">
                <div class="flex items-start justify-between gap-4 p-6 border-b border-dark-border">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-brand-purple font-semibold">Channel Detail</p>
                        <h2 id="channel-detail-title" class="text-2xl font-bold text-white mt-1">Detail Channel</h2>
                        <p id="channel-detail-subtitle" class="text-sm text-gray-400 mt-1">Ringkasan pertanyaan dan karakteristik lead dari channel ini.</p>
                    </div>

                    <button id="channel-detail-close" type="button" class="w-9 h-9 rounded-lg bg-dark-bg hover:bg-dark-border text-gray-400 hover:text-white transition flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-110px)] space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 mb-3">Kategori kanker ditanyakan</p>
                            <div id="channel-detail-kanker" class="space-y-2"></div>
                        </div>

                        <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 mb-3">Minat treatment</p>
                            <div id="channel-detail-treatment" class="space-y-2"></div>
                        </div>

                        <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 mb-3">Profil pengirim chat</p>
                            <div id="channel-detail-profil" class="space-y-2"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 mb-3">Metode bayar</p>
                            <div id="channel-detail-bayar" class="space-y-2"></div>
                        </div>

                        <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 mb-3">Kendala utama</p>
                            <div id="channel-detail-kendala" class="space-y-2"></div>
                        </div>
                    </div>

                    <div class="bg-dark-bg border border-dark-border rounded-xl p-4">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <p class="text-xs uppercase text-gray-500">Pertanyaan / chat pertama yang masuk</p>
                                <p class="text-sm text-gray-400 mt-1">Menampilkan maksimal 30 chat pertama pasien terbaru dari channel ini.</p>
                            </div>
                        </div>

                        <div id="channel-detail-questions" class="space-y-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CHANNEL_DETAIL_MODAL_END -->


    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Demand Pasien</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                    <p class="text-xs uppercase text-gray-500 mb-1">Kategori kanker teratas</p>
                    <p class="text-lg font-bold text-white">{{ $kategoriKanker->keys()->first() ?? '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $kategoriKanker->first() ?? 0 }} lead</p>
                </div>
                <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                    <p class="text-xs uppercase text-gray-500 mb-1">Treatment teratas</p>
                    <p class="text-lg font-bold text-white">{{ $minatTreatment->keys()->first() ?? '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $minatTreatment->first() ?? 0 }} lead</p>
                </div>
                <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                    <p class="text-xs uppercase text-gray-500 mb-1">Metode bayar teratas</p>
                    <p class="text-lg font-bold text-white">{{ $metodeBayar->keys()->first() ?? '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $metodeBayar->first() ?? 0 }} lead</p>
                </div>
                <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                    <p class="text-xs uppercase text-gray-500 mb-1">Profil pengirim teratas</p>
                    <p class="text-lg font-bold text-white">{{ $profilPengirim->keys()->first() ?? '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $profilPengirim->first() ?? 0 }} lead</p>
                </div>
            </div>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Pain Point Utama</h2>
            <div style="height: 280px;">
                <canvas id="painPointChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Perlu Follow-up</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ $followUpOutstanding }}</h3>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Follow-up Hari Ini</p>
            <h3 class="text-2xl font-bold text-blue-400 mt-1">{{ $followUpToday }}</h3>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Overdue</p>
            <h3 class="text-2xl font-bold text-red-400 mt-1">{{ $overdueFollowUp }}</h3>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Belum Dibalas</p>
            <h3 class="text-2xl font-bold text-yellow-400 mt-1">{{ $unansweredLead }}</h3>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Belum Dibalas &gt;24J</p>
            <h3 class="text-2xl font-bold text-red-400 mt-1">{{ $unansweredMoreThan24Hours }}</h3>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Avg First Response</p>
            <h3 class="text-2xl font-bold text-green-400 mt-1">{{ $responseStats['average_label'] }}</h3>
            <p class="text-xs text-gray-500 mt-1">n={{ $responseStats['sample_size'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Volume Lead per Channel</h2>
            <div style="height: 300px;">
                <canvas id="channelChart"></canvas>
            </div>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-semibold text-white mb-5">Jam Ramai Chat Pasien</h2>
            <div style="height: 300px;">
                <canvas id="hourChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-white">Data Health</h2>
            <span class="text-xs text-gray-500">Audit kelengkapan data sebelum meeting</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-7 gap-4">
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Raw Chat</p>
                <p class="text-xl font-bold text-white">{{ $dataHealth['total_raw_chat'] }}</p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Nomor Chat</p>
                <p class="text-xl font-bold text-white">{{ $dataHealth['total_chat_clients'] }}</p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Summary AI</p>
                <p class="text-xl font-bold text-white">{{ $dataHealth['total_lead_summaries'] }}</p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Belum Summary</p>
                <p class="text-xl font-bold {{ $dataHealth['unsummarized_clients'] > 0 ? 'text-red-400' : 'text-green-400' }}">
                    {{ $dataHealth['unsummarized_clients'] }}
                </p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Periode Belum Summary</p>
                <p class="text-xl font-bold {{ $dataHealth['period_unsummarized_clients'] > 0 ? 'text-red-400' : 'text-green-400' }}">
                    {{ $dataHealth['period_unsummarized_clients'] }}
                </p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Export Hari Ini</p>
                <p class="text-xl font-bold text-blue-400">{{ $dataHealth['export_today_clients'] }}</p>
            </div>
            <div class="bg-dark-bg border border-dark-border rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Chat Hari Ini</p>
                <p class="text-xl font-bold text-brand-purple">{{ $dataHealth['chat_today_clients'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.color = '#9CA3AF';
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";

    const channelSummary = @json($channelSummary);
    const painPoints = @json($painPoints);
    const chatVolumeByHour = @json($chatVolumeByHour);
    const channelDetails = @json($channelDetails ?? []);

    function emptySafeLabels(objectData) {
        const keys = Object.keys(objectData || {});
        return keys.length ? keys : ['Tidak ada data'];
    }

    function emptySafeValues(objectData) {
        const values = Object.values(objectData || {});
        return values.length ? values : [0];
    }

    
    /* CHANNEL_DETAIL_SCRIPT_START */
    const channelDetailModal = document.getElementById('channel-detail-modal');
    const channelDetailBackdrop = document.getElementById('channel-detail-backdrop');
    const channelDetailClose = document.getElementById('channel-detail-close');
    const channelDetailTitle = document.getElementById('channel-detail-title');
    const channelDetailSubtitle = document.getElementById('channel-detail-subtitle');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderCountList(containerId, data) {
        const container = document.getElementById(containerId);
        const entries = Object.entries(data || {});

        if (!entries.length) {
            container.innerHTML = '<p class="text-sm text-gray-500">Tidak ada data.</p>';
            return;
        }

        const max = Math.max(...entries.map(([, total]) => Number(total) || 0), 1);

        container.innerHTML = entries.map(([label, total]) => {
            const width = ((Number(total) || 0) / max) * 100;

            return `
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm mb-1">
                        <span class="text-gray-300 truncate">${escapeHtml(label)}</span>
                        <span class="text-white font-semibold">${escapeHtml(total)}</span>
                    </div>
                    <div class="h-2 bg-dark-surface rounded-full overflow-hidden">
                        <div class="h-full bg-brand-purple rounded-full" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderQuestions(channel, questions) {
        const container = document.getElementById('channel-detail-questions');

        if (!questions || !questions.length) {
            container.innerHTML = '<p class="text-sm text-gray-500">Tidak ada chat pasien yang terbaca untuk channel ini.</p>';
            return;
        }

        container.innerHTML = questions.map((item) => `
            <div class="border border-dark-border rounded-lg p-4 bg-dark-surface/60">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-brand-purple/15 text-brand-purple font-semibold">${escapeHtml(channel)}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-dark-bg text-gray-400">${escapeHtml(item.client_number)}</span>
                        <span class="text-xs px-2 py-1 rounded-full bg-dark-bg text-gray-400">Score: ${escapeHtml(item.lead_score)}</span>
                    </div>
                    <span class="text-xs text-gray-500">${escapeHtml(item.chat_time)}</span>
                </div>

                <p class="text-sm text-gray-200 leading-relaxed mb-3">${escapeHtml(item.message)}</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                    <div class="text-gray-500">Kanker: <span class="text-gray-300">${escapeHtml(item.kategori_kanker)}</span></div>
                    <div class="text-gray-500">Treatment: <span class="text-gray-300">${escapeHtml(item.minat_treatment)}</span></div>
                    <div class="text-gray-500">Pengirim: <span class="text-gray-300">${escapeHtml(item.profil_pengirim)}</span></div>
                </div>
            </div>
        `).join('');
    }

    function openChannelDetail(channel) {
        const data = channelDetails[channel] || {};

        channelDetailTitle.textContent = `Detail ${channel}`;
        channelDetailSubtitle.textContent = `${data.total || 0} lead dari channel ${channel}.`;

        renderCountList('channel-detail-kanker', data.kategori_kanker);
        renderCountList('channel-detail-treatment', data.minat_treatment);
        renderCountList('channel-detail-profil', data.profil_pengirim);
        renderCountList('channel-detail-bayar', data.metode_bayar);
        renderCountList('channel-detail-kendala', data.kendala_utama);
        renderQuestions(channel, data.questions);

        channelDetailModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeChannelDetail() {
        channelDetailModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('.js-channel-detail-btn').forEach((button) => {
        button.addEventListener('click', function () {
            openChannelDetail(this.dataset.channel);
        });
    });

    channelDetailClose?.addEventListener('click', closeChannelDetail);
    channelDetailBackdrop?.addEventListener('click', closeChannelDetail);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !channelDetailModal.classList.contains('hidden')) {
            closeChannelDetail();
        }
    });
    /* CHANNEL_DETAIL_SCRIPT_END */


    new Chart(document.getElementById('channelChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(channelSummary),
            datasets: [{
                label: 'Total Lead',
                data: Object.values(channelSummary).map(item => item.total),
                backgroundColor: 'rgba(139, 92, 246, 0.65)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#2A2A2A' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('painPointChart'), {
        type: 'bar',
        data: {
            labels: emptySafeLabels(painPoints),
            datasets: [{
                label: 'Jumlah Lead',
                data: emptySafeValues(painPoints),
                backgroundColor: 'rgba(245, 158, 11, 0.65)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#2A2A2A' } },
                y: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('hourChart'), {
        type: 'line',
        data: {
            labels: emptySafeLabels(chatVolumeByHour),
            datasets: [{
                label: 'Chat Pasien',
                data: emptySafeValues(chatVolumeByHour),
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                borderWidth: 2,
                tension: 0.35,
                fill: true,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#2A2A2A' } },
                x: { grid: { color: '#2A2A2A' } }
            }
        }
    });
});
</script>
@endpush
