@extends('layouts.app')

@section('title', 'Telemetry & Morning Briefing - AHCC')

@php($headerTitle = 'Dashboard & Telemetry')

@section('content')

    <div class="mb-6 bg-gradient-to-r from-dark-bg to-dark-surface border border-brand-purple/30 rounded-xl p-5 shadow-lg relative overflow-hidden">

        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-purple/5 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>

        <div class="flex items-center justify-between mb-5 relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-brand-purple/20 rounded-lg flex items-center justify-center text-brand-purple border border-brand-purple/30 shadow-inner">
                    <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white tracking-wide">H.A.N.A Morning Briefing</h2>
                    <p class="text-sm text-brand-purple uppercase font-semibold tracking-wider">Misi Prioritas Anda Hari Ini</p>
                    @if(isset($briefingPagi))
                    <button id="btn-open-briefing" class="bg-brand-purple/20 border border-brand-purple/50 text-brand-purple hover:bg-brand-purple hover:text-white transition-colors px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2">
                        👩‍⚕️ Baca Surat Instruksi H.A.N.A
                    </button>
                    @endif
                </div>
            </div>
            <div class="text-sm text-gray-500 font-mono bg-dark-bg px-3 py-1.5 rounded-lg border border-dark-border">
                Update terakhir: {{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->format('H:i') }} WIB
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-10">
            
            <div class="bg-dark-bg/60 border border-red-500/20 rounded-lg p-4 hover:border-red-500/40 transition-colors">
                <h3 class="text-xs text-red-400 font-bold uppercase flex items-center gap-2 mb-3 border-b border-red-500/20 pb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    🚨 Ghosting & Wajib Follow-Up ({{ count($briefing['follow_up']) }})
                </h3>
                
                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($briefing['follow_up'] as $lead)
                    <a href="{{ url('/pipeline') }}?open={{ urlencode($lead->client_number) }}" class="block bg-dark-surface hover:bg-gray-800 p-3 rounded border border-dark-border transition-colors group cursor-pointer">
                        
                        <div class="flex justify-between items-center mb-2 border-b border-gray-700/50 pb-2">
                            <div>
                                <p class="text-sm text-white font-bold tracking-wide">{{ $lead->client_number }}</p>
                                <p class="text-[11px] text-red-400 font-medium mt-0.5">
                                    Kasus: <span class="text-gray-300">{{ $lead->kategori_kanker ?? 'Belum Terdeteksi' }}</span>
                                </p>
                            </div>
                            <span class="text-[10px] bg-red-900/50 text-red-300 px-2 py-1 rounded font-bold border border-red-700/50 whitespace-nowrap">
                                🚨 WAJIB FU
                            </span>
                        </div>

                        <div class="mb-2">
                            <span class="text-[9px] text-gray-500 uppercase tracking-wider font-bold block mb-1">Konteks Pasien:</span>
                            <p class="text-[11px] text-gray-300 leading-relaxed bg-gray-900/50 p-2 rounded border border-gray-700/30">
                                {{ $lead->alasan_follow_up ?? 'Belum ada catatan konteks.' }}
                            </p>
                        </div>
                        
                        <div class="bg-yellow-900/10 border border-yellow-700/30 rounded p-2 text-[10.5px] text-yellow-200/90 leading-relaxed">
                            <span class="font-bold text-yellow-500 flex items-center gap-1 mb-1">
                                💡 Saran Aksi:
                            </span>
                            @if($lead->follow_up_count == 1)
                                Sapa hangat & tanyakan bagian edukasi yang membingungkan. Fokus empati, hindari kesan hard-selling.
                            @elseif($lead->follow_up_count >= 2)
                                Kirimkan artikel/testimoni terkait {{ $lead->kategori_kanker ?? 'kanker' }}. Tawarkan bantuan atur jadwal konsul.
                            @else
                                Sapa kembali pasien dan tanyakan kabar terbaru terkait kondisinya hari ini.
                            @endif
                        </div>

                    </a>
                    @empty
                    <div class="flex flex-col items-center justify-center p-4 text-center">
                        <span class="text-2xl mb-1">✅</span>
                        <p class="text-xs text-gray-500 italic">Aman. Tidak ada pasien menggantung.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-dark-bg/60 border border-orange-500/20 rounded-lg p-4 hover:border-orange-500/40 transition-colors">
                <h3 class="text-xs text-orange-400 font-bold uppercase flex items-center gap-2 mb-3 border-b border-orange-500/20 pb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path></svg>
                    🔥 Prospek Panas (Suhu > 60)
                </h3>
                <div class="space-y-2">
                    @forelse($briefing['hot_leads'] as $lead)
                    <a href="{{ url('/pipeline') }}?open={{ urlencode($lead->client_number) }}" class="block bg-dark-surface hover:bg-gray-800 p-2.5 rounded border border-dark-border transition-colors flex justify-between items-center group cursor-pointer">
                        <div>
                            <p class="text-sm text-white font-semibold">{{ $lead->client_number }}</p>
                            <p class="text-[11px] text-gray-400 line-clamp-1 mt-0.5">{{ $lead->minat_treatment }}</p>
                        </div>
                        <span class="text-[10px] bg-orange-900/50 text-orange-300 px-2 py-1 rounded font-bold border border-orange-700/50">Skor: {{ $lead->lead_score }}</span>
                    </a>
                    @empty
                    <div class="flex flex-col items-center justify-center p-4 text-center">
                        <span class="text-2xl mb-1">🧊</span>
                        <p class="text-xs text-gray-500 italic">Belum ada prospek berpotensi tinggi.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-dark-bg/60 border border-brand-blue/20 rounded-lg p-4 hover:border-brand-blue/40 transition-colors">
                <h3 class="text-xs text-brand-blue font-bold uppercase flex items-center gap-2 mb-3 border-b border-brand-blue/20 pb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    📥 Pasien Baru Masuk Hari Ini
                </h3>
                <div class="space-y-2">
                    @forelse($briefing['baru_hari_ini'] as $lead)
                    <a href="{{ url('/pipeline') }}?open={{ urlencode($lead->client_number) }}" class="block bg-dark-surface hover:bg-gray-800 p-2.5 rounded border border-dark-border transition-colors flex justify-between items-center group cursor-pointer">
                        <div class="flex-1">
                            <p class="text-sm text-white font-semibold">{{ $lead->client_number }}</p>
                            <p class="text-[11px] text-brand-blue line-clamp-1 mt-0.5 font-medium">{{ $lead->kategori_kanker }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                    @empty
                    <div class="flex flex-col items-center justify-center p-4 text-center">
                        <span class="text-2xl mb-1">📭</span>
                        <p class="text-xs text-gray-500 italic">Belum ada pasien baru hari ini.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-dark-surface border border-dark-border rounded-xl p-6">
            <h2 class="text-base font-medium text-white mb-6">Traffic Chart</h2>
            <div class="h-72 w-full relative">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <div class="bg-dark-surface border border-dark-border rounded-xl p-6 overflow-hidden flex flex-col">
            <h2 class="text-base font-medium text-white mb-6">Ringkasan Chat Terbaru</h2>
            
            <div class="space-y-4 overflow-y-auto flex-1 pr-2 custom-scrollbar" style="max-height: 280px;">
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

    @if(isset($briefingPagi))
    <div id="briefing-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300 p-4 md:p-6">
        <div id="briefing-modal-content" class="bg-dark-surface border border-brand-purple/40 rounded-2xl w-11/12 max-w-4xl shadow-2xl transform scale-95 transition-transform duration-300 relative flex flex-col max-h-[90vh] overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-1.5 bg-brand-purple z-10"></div>
            
            <div class="p-6 md:p-8 flex flex-col h-full overflow-hidden">
                
                <div class="flex justify-between items-start mb-4 md:mb-6 shrink-0">
                    <div class="flex items-center gap-4 md:gap-5">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-dark-bg border-2 border-brand-purple flex items-center justify-center text-2xl md:text-3xl shadow-inner shrink-0">
                            👩‍⚕️
                        </div>
                        <div>
                            <h3 class="text-brand-purple font-bold text-xl md:text-2xl uppercase tracking-wide">Instruksi Pagi Kepala PA</h3>
                            <p class="text-xs md:text-sm text-gray-400 mt-1">🗓️ {{ \Carbon\Carbon::parse($briefingPagi->tanggal_briefing)->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                    <button id="btn-close-briefing" class="text-gray-400 hover:text-white bg-dark-bg p-2 rounded-lg transition-colors shrink-0">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="text-gray-100 text-sm md:text-base leading-relaxed md:leading-loose whitespace-pre-wrap bg-dark-bg p-5 md:p-6 rounded-xl border border-dark-border italic flex-1 overflow-y-auto custom-scrollbar">"{!! nl2br($briefingPagi->narasi_teks) !!}"</div>
                
                <div class="mt-6 md:mt-8 flex justify-between items-center shrink-0">
                    <span class="text-[10px] md:text-xs text-gray-500 uppercase font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Siap diteruskan ke WA
                    </span>
                    <button id="btn-mengerti-briefing" class="bg-brand-purple hover:bg-brand-purple/80 text-white px-6 md:px-8 py-2.5 md:py-3 rounded-lg text-sm md:text-base font-bold transition-colors shadow-lg shadow-brand-purple/20">
                        Siap, Laksanakan!
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartLabels = {!! json_encode($labels) !!};
        const dataChatMasuk = {!! json_encode($totalChatMasuk) !!};
        const dataDisummary = {!! json_encode($totalChatDisummary) !!};

        const ctx = document.getElementById('trafficChart').getContext('2d');

        // ==============================================================
        // LOGIKA AUTO-OPEN MORNING BRIEFING
        // ==============================================================
        @if(isset($briefingPagi))
            const briefingModal = document.getElementById('briefing-modal');
            const briefingContent = document.getElementById('briefing-modal-content');
            const btnOpen = document.getElementById('btn-open-briefing');
            const btnClose = document.getElementById('btn-close-briefing');
            const btnMengerti = document.getElementById('btn-mengerti-briefing');
            
            // Buat kunci memori unik berdasarkan tanggal surat hari ini
            const briefingDate = "{{ $briefingPagi->tanggal_briefing }}";
            const storageKey = 'hana_briefing_seen_' + briefingDate;

            function openBriefing() {
                briefingModal.classList.remove('hidden');
                
                // Cari elemen teks
                const dateEl = briefingContent.querySelector('p.text-gray-400');
                const textEl = briefingContent.querySelector('div.whitespace-pre-wrap');
                
                if(dateEl) dateEl.innerText = '🗓️ {{ \Carbon\Carbon::parse($briefingPagi->tanggal_briefing)->translatedFormat("l, d F Y") }}';
                
                // UBAH: Gunakan innerHTML dan <br> agar huruf tebal & enter terbaca
                if(textEl) textEl.innerHTML = '"{!! preg_replace("/\r|\n/", "<br>", addslashes($briefingPagi->narasi_teks)) !!}"';

                setTimeout(() => {
                    briefingModal.classList.remove('opacity-0');
                    briefingContent.classList.remove('scale-95');
                }, 10);
            }

            function closeBriefing() {
                briefingModal.classList.add('opacity-0');
                briefingContent.classList.add('scale-95');
                setTimeout(() => {
                    briefingModal.classList.add('hidden');
                }, 300);
                
                // Saat ditutup, tandai di memori browser bahwa PA sudah membacanya hari ini
                localStorage.setItem(storageKey, 'true');
            }

            // CEK APAKAH SUDAH DIBACA HARI INI?
            if (!localStorage.getItem(storageKey)) {
                // Jika belum, buka otomatis setelah loading 0.5 detik
                setTimeout(openBriefing, 500); 
            }

            // Pasang event ke tombol-tombol
            if(btnOpen) btnOpen.addEventListener('click', openBriefing);
            if(btnClose) btnClose.addEventListener('click', closeBriefing);
            if(btnMengerti) btnMengerti.addEventListener('click', closeBriefing);
        @endif
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels, 
                datasets: [
                    {
                        type: 'line',
                        label: 'Chat Disummary (Terselesaikan)',
                        data: dataDisummary, 
                        borderColor: '#8B5CF6', 
                        borderWidth: 2,
                        borderDash: [5, 5], 
                        pointBackgroundColor: '#111111',
                        pointBorderColor: '#8B5CF6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        yAxisID: 'y1' 
                    },
                    {
                        type: 'bar',
                        label: 'Total Chat Masuk',
                        data: dataChatMasuk, 
                        backgroundColor: 'rgba(59, 130, 246, 0.2)', 
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        borderWidth: 1,
                        borderRadius: 2,
                        barPercentage: 0.6,
                        yAxisID: 'y' 
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
                        display: true, 
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
                        beginAtZero: true 
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