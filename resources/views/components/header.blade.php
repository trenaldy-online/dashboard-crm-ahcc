@php
    // Tarik data riwayat langsung dari sini agar lonceng berfungsi di SEMUA halaman aplikasi
    $briefingHistory = \App\Models\DailyBriefing::orderBy('tanggal_briefing', 'desc')->take(7)->get();
@endphp

<header class="h-16 flex items-center justify-between px-8 border-b border-dark-border shrink-0 bg-dark-bg">
    <div class="text-sm text-dark-muted">
        <span class="hover:text-white cursor-pointer transition-colors">Dashboard</span>
        <span class="mx-2">/</span>
        <span class="text-white font-medium">{{ $title ?? 'Telemetry' }}</span>
    </div>

    <div class="flex items-center space-x-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" placeholder="Search endpoints..." class="bg-dark-surface border border-dark-border text-sm rounded-md focus:ring-1 focus:ring-brand-purple focus:border-brand-purple block w-64 pl-10 p-2 text-white placeholder-dark-muted transition-shadow">
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-xs text-dark-muted bg-dark-bg px-1.5 py-0.5 rounded border border-dark-border">/</span>
            </div>
        </div>
        
        <div class="relative" id="notification-wrapper">
            <button id="btn-notification" class="relative p-2 text-dark-muted hover:text-white border border-dark-border rounded-md hover:bg-dark-surface transition-colors focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                
                @if(isset($briefingHistory) && count($briefingHistory) > 0)
                <span class="absolute top-1 right-1.5 w-2.5 h-2.5 bg-brand-purple rounded-full border-[1.5px] border-dark-bg"></span>
                @endif
            </button>

            <div id="notification-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-dark-surface border border-dark-border rounded-xl shadow-2xl z-50 overflow-hidden transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                <div class="px-4 py-3 border-b border-dark-border bg-dark-bg/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-white">Riwayat Briefing</h3>
                    <span class="text-[10px] bg-brand-purple/20 text-brand-purple px-2 py-0.5 rounded font-bold uppercase tracking-wider">H.A.N.A</span>
                </div>
                
                <div class="max-h-[300px] overflow-y-auto custom-scrollbar">
                    @if(isset($briefingHistory) && count($briefingHistory) > 0)
                        @foreach($briefingHistory as $history)
                        @php
                            $tglFormat = \Carbon\Carbon::parse($history->tanggal_briefing)->translatedFormat('l, d F Y');
                        @endphp
                        
                        <button type="button" onclick='bukaRiwayatModal(@json($tglFormat), @json($history->narasi_teks))' class="w-full text-left px-4 py-3 border-b border-dark-border/50 hover:bg-dark-bg transition-colors flex gap-3 items-start group">
                            
                            <div class="text-lg mt-0.5 opacity-80 group-hover:opacity-100 transition-opacity">👩‍⚕️</div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <p class="text-xs font-bold text-gray-200 group-hover:text-brand-purple transition-colors">{{ \Carbon\Carbon::parse($history->tanggal_briefing)->translatedFormat('d M Y') }}</p>
                                    @if(\Carbon\Carbon::parse($history->tanggal_briefing)->isToday())
                                        <span class="text-[8px] bg-green-500/20 text-green-400 px-1.5 rounded uppercase font-bold">Baru</span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-gray-500 line-clamp-2 mt-1 leading-relaxed">{{ $history->narasi_teks }}</p>
                            </div>
                        </button>
                        @endforeach
                    @else
                        <div class="px-4 py-8 text-center flex flex-col items-center">
                            <svg class="w-8 h-8 text-dark-muted opacity-50 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <span class="text-xs text-dark-muted italic">Belum ada riwayat tersimpan.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnNotif = document.getElementById('btn-notification');
    const dropNotif = document.getElementById('notification-dropdown');

    if(btnNotif && dropNotif) {
        // 1. Logika Klik Tombol Lonceng
        btnNotif.addEventListener('click', function(e) {
            e.stopPropagation(); // Mencegah klik bocor
            if(dropNotif.classList.contains('hidden')) {
                // Buka Dropdown
                dropNotif.classList.remove('hidden');
                setTimeout(() => {
                    dropNotif.classList.remove('opacity-0', 'scale-95');
                }, 10);
            } else {
                tutupDropdown();
            }
        });

        // 2. Logika Tutup Saat Klik di Luar Kotak
        document.addEventListener('click', function(e) {
            if(!dropNotif.contains(e.target) && !btnNotif.contains(e.target)) {
                tutupDropdown();
            }
        });

        function tutupDropdown() {
            dropNotif.classList.add('opacity-0', 'scale-95');
            setTimeout(() => { dropNotif.classList.add('hidden'); }, 200);
        }
    }

    // 3. Fungsi Buka Modal Riwayat
    window.bukaRiwayatModal = function(tanggal, teks) {
        const briefingModal = document.getElementById('briefing-modal');
        const briefingContent = document.getElementById('briefing-modal-content');
        
        if(briefingModal && briefingContent) {
            // Cari elemen teks di dalam modal
            const dateEl = briefingContent.querySelector('p'); // Tag P untuk tanggal
            const textEl = briefingContent.querySelector('div.whitespace-pre-wrap'); // Tag Div untuk narasi
            
            if(dateEl) dateEl.innerText = '🗓️ ' + tanggal;
            if(textEl) textEl.innerHTML = '"' + teks.replace(/\n/g, '<br>') + '"';

            // Buka Modal
            briefingModal.classList.remove('hidden');
            setTimeout(() => {
                briefingModal.classList.remove('opacity-0');
                briefingContent.classList.remove('scale-95');
            }, 10);

            // Tutup dropdown lonceng
            if(dropNotif) tutupDropdown();
        } else {
            // Pengaman darurat: Jika modal tidak ada di halaman ini, munculkan pop-up standar browser
            alert("Instruksi H.A.N.A (" + tanggal + "):\n\n" + teks);
        }
    };
});
</script>