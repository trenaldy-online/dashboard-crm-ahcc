@extends('layouts.app')

@section('title', 'Telemetry - API Monitor')

@php($headerTitle = 'Telemetry')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-medium text-white mb-6">Traffic Chart</h2>
            <div class="h-72 w-full relative">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-6 overflow-hidden flex flex-col">
            <h2 class="text-base font-medium text-white mb-6">Ringkasan Chat Terbaru</h2>
            
            <div class="space-y-4 overflow-y-auto flex-1 pr-2" style="max-height: 280px;">
                @forelse($recentSummaries as $summary)
                    <div class="border-b border-dark-border/50 pb-3 last:border-0">
                        <div class="flex justify-between items-start mb-1.5">
                            <span class="text-sm font-mono text-white flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                {{ $summary->client_number }}
                            </span>
                            
                            <div class="flex gap-1 text-right">
                                <span class="px-2 py-0.5 text-[10px] font-medium text-brand-purple bg-brand-purple/10 border border-brand-purple/20 rounded">
                                    {{ $summary->kategori_kanker }}
                                </span>
                            </div>
                        </div>
                        
                        <p class="text-xs text-dark-muted leading-relaxed line-clamp-2" title="{{ $summary->ringkasan }}">
                            {{ $summary->ringkasan }}
                        </p>
                    </div>
                @empty
                    <div class="text-center text-sm text-dark-muted py-4">
                        Belum ada ringkasan chat terbaru.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-dark-surface border border-dark-border rounded-xl p-6 mt-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-base font-medium text-white">Riwayat Chat Terselesaikan (Live Feed)</h2>
            <span class="flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
        </div>
        
        <div class="space-y-4">
            @forelse($recentSummaries as $summary)
                <div class="group bg-dark-bg/50 border border-dark-border rounded-lg p-5 hover:border-brand-purple/50 transition-colors">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-mono text-white group-hover:text-brand-purple transition-colors">
                            📞 {{ $summary->client_number }}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-purple bg-brand-purple/10 border border-brand-purple/20 px-3 py-1 rounded">
                            => {{ $summary->kategori_kanker }}
                        </span>
                    </div>
                    
                    <div class="pl-4 border-l-2 border-dark-border group-hover:border-brand-purple/50 transition-colors">
                        <p class="text-sm text-dark-muted leading-relaxed">
                            {{ $summary->ringkasan }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <svg class="w-10 h-10 text-dark-border mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <p class="text-sm text-dark-muted italic">Belum ada riwayat chat terbaru yang ditarik dari ekstensi.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menerima data asli dari Controller Laravel
        const chartLabels = {!! json_encode($labels) !!};
        const dataChatMasuk = {!! json_encode($totalChatMasuk) !!};
        const dataDisummary = {!! json_encode($totalChatDisummary) !!};

        const ctx = document.getElementById('trafficChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels, // Label 7 hari terakhir (cth: 04 Apr, 05 Apr)
                datasets: [
                    {
                        type: 'line',
                        label: 'Chat Disummary (Terselesaikan)',
                        data: dataDisummary, // Data Line Chart
                        borderColor: '#8B5CF6', 
                        borderWidth: 2,
                        borderDash: [5, 5], 
                        pointBackgroundColor: '#111111',
                        pointBorderColor: '#8B5CF6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        yAxisID: 'y1' // Menggunakan skala Y kanan
                    },
                    {
                        type: 'bar',
                        label: 'Total Chat Masuk',
                        data: dataChatMasuk, // Data Bar Chart
                        backgroundColor: 'rgba(59, 130, 246, 0.2)', 
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        borderWidth: 1,
                        borderRadius: 2,
                        barPercentage: 0.6,
                        yAxisID: 'y' // Menggunakan skala Y kiri
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { 
                        display: true, // Kita nyalakan legend agar manajemen tahu arti warnanya
                        labels: { color: '#9CA3AF' }
                    },
                    tooltip: {
                        backgroundColor: '#111111',
                        titleColor: '#E5E7EB',
                        bodyColor: '#E5E7EB',
                        borderColor: '#2A2A2A',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: '#9CA3AF', font: { size: 11 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { color: '#2A2A2A', drawBorder: false },
                        ticks: { color: '#9CA3AF', font: { size: 11 } },
                        beginAtZero: true // Memaksa grafik mulai dari angka 0
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { display: false },
                        ticks: { color: '#9CA3AF', font: { size: 11 } },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endpush