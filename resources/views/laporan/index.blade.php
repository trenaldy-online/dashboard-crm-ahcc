@extends('layouts.app')

@section('title', 'Laporan Analitik H.A.N.A - AHCC')

@php($headerTitle = 'Dashboard Analitik Marketing')

@section('content')

<div class="mb-6 bg-gradient-to-r from-dark-bg to-dark-surface border border-brand-purple/30 rounded-xl p-5 shadow-lg relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-brand-purple/5 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-brand-purple/20 rounded-lg flex items-center justify-center text-brand-purple border border-brand-purple/30 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-wide">Business Intelligence Marketing</h2>
                <p class="text-sm text-brand-purple uppercase font-semibold tracking-wider">
                    Lead Quality, Channel Performance & Patient Pain Points
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('laporan.index') }}" class="flex flex-col sm:flex-row gap-2">
            <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                class="bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-gray-300">
            <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                class="bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-gray-300">
            <button type="submit" class="bg-brand-purple text-white rounded-lg px-4 py-2 text-sm font-semibold">
                Filter
            </button>
        </form>
    </div>
</div>

{{-- KPI CARDS --}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Total Lead</p>
        <h3 class="text-2xl font-bold text-white mt-1">{{ $totalLead }}</h3>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Eligible</p>
        <h3 class="text-2xl font-bold text-green-400 mt-1">{{ $eligibleLead }}</h3>
        <p class="text-xs text-gray-500 mt-1">{{ $eligibleRate }}%</p>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Junk Lead</p>
        <h3 class="text-2xl font-bold text-red-400 mt-1">{{ $junkLead }}</h3>
        <p class="text-xs text-gray-500 mt-1">{{ $junkRate }}%</p>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Redirected</p>
        <h3 class="text-2xl font-bold text-yellow-400 mt-1">{{ $redirectedLead }}</h3>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Resolved</p>
        <h3 class="text-2xl font-bold text-blue-400 mt-1">{{ $resolvedLead }}</h3>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-4">
        <p class="text-xs text-gray-400 uppercase">Deal</p>
        <h3 class="text-2xl font-bold text-brand-purple mt-1">{{ $dealLead }}</h3>
        <p class="text-xs text-gray-500 mt-1">CVR {{ $conversionRate }}%</p>
    </div>
</div>

{{-- CHANNEL PERFORMANCE --}}
<div class="bg-dark-surface border border-dark-border rounded-xl p-6 mb-6">
    <h2 class="text-base font-medium text-white mb-4">Channel Performance</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-400 uppercase border-b border-dark-border">
                <tr>
                    <th class="py-3">Channel</th>
                    <th class="py-3">Total Lead</th>
                    <th class="py-3">Eligible</th>
                    <th class="py-3">Junk</th>
                    <th class="py-3">Deal</th>
                    <th class="py-3">Eligible Rate</th>
                    <th class="py-3">Junk Rate</th>
                    <th class="py-3">Conversion</th>
                    <th class="py-3">Avg Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-dark-border">
                @foreach($channelSummary as $source => $data)
                    <tr class="text-gray-300">
                        <td class="py-3 font-semibold text-white">{{ $source }}</td>
                        <td class="py-3">{{ $data['total'] }}</td>
                        <td class="py-3 text-green-400">{{ $data['eligible'] }}</td>
                        <td class="py-3 text-red-400">{{ $data['junk'] }}</td>
                        <td class="py-3 text-brand-purple">{{ $data['deal'] }}</td>
                        <td class="py-3">{{ $data['eligible_rate'] }}%</td>
                        <td class="py-3">{{ $data['junk_rate'] }}%</td>
                        <td class="py-3">{{ $data['conversion_rate'] }}%</td>
                        <td class="py-3">{{ $avgLeadScoreBySource[$source] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- CHARTS --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <h2 class="text-base font-medium text-white mb-6">Kualitas Lead</h2>
        <div style="height: 320px;">
            <canvas id="kualitasLeadChart"></canvas>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <h2 class="text-base font-medium text-white mb-6">Pipeline Funnel</h2>
        <div style="height: 320px;">
            <canvas id="pipelineChart"></canvas>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <h2 class="text-base font-medium text-white mb-6">Top Kendala Utama Pasien</h2>
        <div style="height: 320px;">
            <canvas id="kendalaUtamaChart"></canvas>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
        <h2 class="text-base font-medium text-white mb-6">Minat Treatment</h2>
        <div style="height: 320px;">
            <canvas id="minatTreatmentChart"></canvas>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
            <h2 class="text-base font-medium text-white">Kategori Kanker Terbanyak</h2>

            <div class="flex items-center gap-2">
                <button id="kategoriPrev"
                    class="px-3 py-1 text-xs rounded bg-dark-bg border border-dark-border text-gray-300 hover:text-white">
                    Prev
                </button>

                <span id="kategoriPageInfo" class="text-xs text-gray-400"></span>

                <button id="kategoriNext"
                    class="px-3 py-1 text-xs rounded bg-dark-bg border border-dark-border text-gray-300 hover:text-white">
                    Next
                </button>
            </div>
        </div>

        <div style="height: 320px;">
            <canvas id="kategoriKankerChart"></canvas>
        </div>
    </div>
</div>

{{-- INSIGHT --}}
<div class="bg-dark-surface border border-brand-purple/30 rounded-xl p-6">
    <h2 class="text-base font-medium text-white mb-4">Insight Strategi Marketing</h2>

    <ul class="space-y-3">
        @foreach($insights as $insight)
            <li class="text-sm text-gray-300 bg-dark-bg border border-dark-border rounded-lg p-3">
                💡 {{ $insight }}
            </li>
        @endforeach
    </ul>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataKualitas = {!! json_encode($kualitasLead) !!};
    const dataKendala = {!! json_encode($kendalaUtama) !!};
    const dataMinat = {!! json_encode($minatTreatment) !!};
    const dataPipeline = {!! json_encode($pipelineFunnel) !!};

    Chart.defaults.color = '#9CA3AF';
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";

    function prettyLabel(label) {
        if (!label) return 'Belum Diketahui';
        return label.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    new Chart(document.getElementById('kualitasLeadChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(dataKualitas).map(prettyLabel),
            datasets: [{
                data: Object.values(dataKualitas),
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderColor: '#1e1e1e',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('pipelineChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(dataPipeline).map(prettyLabel),
            datasets: [{
                label: 'Jumlah Lead',
                data: Object.values(dataPipeline),
                backgroundColor: 'rgba(139, 92, 246, 0.65)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#2A2A2A' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    new Chart(document.getElementById('kendalaUtamaChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(dataKendala),
            datasets: [{
                label: 'Jumlah Pasien',
                data: Object.values(dataKendala),
                backgroundColor: 'rgba(245, 158, 11, 0.65)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#2A2A2A' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });

    new Chart(document.getElementById('minatTreatmentChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(dataMinat),
            datasets: [{
                label: 'Jumlah Lead',
                data: Object.values(dataMinat),
                backgroundColor: 'rgba(59, 130, 246, 0.65)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#2A2A2A' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });

    const dataKategoriKanker = {!! json_encode($kategoriKanker) !!};

    const kategoriLabelsAll = Object.keys(dataKategoriKanker);
    const kategoriValuesAll = Object.values(dataKategoriKanker);

    let kategoriCurrentPage = 1;
    const kategoriPerPage = 10;
    const kategoriTotalPages = Math.ceil(kategoriLabelsAll.length / kategoriPerPage);

    const kategoriCtx = document.getElementById('kategoriKankerChart');

    let kategoriChart = new Chart(kategoriCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Jumlah Lead',
                data: [],
                backgroundColor: 'rgba(16, 185, 129, 0.65)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#2A2A2A' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });

    function renderKategoriPage(page) {
        const start = (page - 1) * kategoriPerPage;
        const end = start + kategoriPerPage;

        const labels = kategoriLabelsAll.slice(start, end);
        const values = kategoriValuesAll.slice(start, end);

        kategoriChart.data.labels = labels;
        kategoriChart.data.datasets[0].data = values;
        kategoriChart.update();

        document.getElementById('kategoriPageInfo').textContent =
            `Page ${kategoriCurrentPage} / ${kategoriTotalPages || 1}`;

        document.getElementById('kategoriPrev').disabled = kategoriCurrentPage <= 1;
        document.getElementById('kategoriNext').disabled = kategoriCurrentPage >= kategoriTotalPages;

        document.getElementById('kategoriPrev').classList.toggle('opacity-40', kategoriCurrentPage <= 1);
        document.getElementById('kategoriNext').classList.toggle('opacity-40', kategoriCurrentPage >= kategoriTotalPages);
    }

    document.getElementById('kategoriPrev').addEventListener('click', function () {
        if (kategoriCurrentPage > 1) {
            kategoriCurrentPage--;
            renderKategoriPage(kategoriCurrentPage);
        }
    });

    document.getElementById('kategoriNext').addEventListener('click', function () {
        if (kategoriCurrentPage < kategoriTotalPages) {
            kategoriCurrentPage++;
            renderKategoriPage(kategoriCurrentPage);
        }
    });

    renderKategoriPage(kategoriCurrentPage);
    });
</script>
@endpush