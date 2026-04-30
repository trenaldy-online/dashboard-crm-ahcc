<div id="detail-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <div class="bg-dark-surface border border-dark-border rounded-xl w-11/12 max-w-6xl h-[85vh] flex flex-col shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        <div class="flex justify-between items-center p-4 border-b border-dark-border bg-dark-bg/50 rounded-t-xl flex-shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-brand-blue/20 flex items-center justify-center text-brand-blue font-bold text-lg">
                    <span id="modal-avatar">WA</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white" id="modal-client-number">+628...</h2>
                    <p class="text-xs text-dark-muted" id="modal-timestamp">Memuat data...</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="#" id="modal-wa-link" target="_blank" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    Buka di WhatsApp
                </a>
                <button id="close-modal" class="text-gray-400 hover:text-white bg-dark-bg p-2 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <div class="flex flex-1 overflow-hidden">
            <div class="w-1/3 border-r border-dark-border bg-dark-bg/30 p-6 flex flex-col gap-5 overflow-y-auto custom-scrollbar">
                
                <div class="flex-shrink-0">
                    <p class="text-xs text-dark-muted mb-2 font-semibold uppercase tracking-wider">Status Pipeline Saat Ini</p>
                    <div class="bg-dark-surface border border-dark-border px-4 py-3 rounded-lg flex justify-between items-center">
                        <span class="text-brand-purple font-bold" id="modal-status">Memuat...</span>
                        <span id="modal-ai-badge" class="hidden text-[10px] bg-brand-purple/20 text-brand-purple px-2 py-1 rounded border border-brand-purple/30 font-semibold uppercase">H.A.N.A Draft</span>
                    </div>
                </div>

                <div class="flex-shrink-0 mt-1 mb-2 bg-dark-surface border border-dark-border rounded-lg p-3">
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold mb-2 flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-brand-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        Tindakan Manual (Bypass)
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="updateManualAction(currentOpenClient, 'perlu_follow_up', false)" class="flex-1 bg-dark-bg border border-green-500/30 hover:bg-green-500/20 text-green-400 px-2 py-1.5 rounded-md text-[11px] font-bold transition-colors flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Selesai Follow-up
                        </button>

                        <div class="w-full flex gap-2 mt-1">
                            <button type="button" onclick="updateManualAction(currentOpenClient, 'pipeline_status', 'edukasi')" class="flex-1 bg-dark-bg border border-blue-500/30 hover:bg-blue-900/30 text-blue-400 px-2 py-1 rounded-md text-[10px] font-semibold transition-colors">
                                Pindah ke Edukasi
                            </button>
                            <button type="button" onclick="updateManualAction(currentOpenClient, 'pipeline_status', 'konsultasi')" class="flex-1 bg-dark-bg border border-purple-500/30 hover:bg-purple-900/30 text-purple-400 px-2 py-1 rounded-md text-[10px] font-semibold transition-colors">
                                Pindah ke Konsul
                            </button>
                        </div>
                        
                        <div class="w-full flex gap-2">
                            <button type="button" onclick="updateManualAction(currentOpenClient, 'pipeline_status', 'batal')" class="w-1/3 bg-dark-bg border border-red-500/30 hover:bg-red-900/40 text-red-400 px-2 py-1 rounded-md text-[10px] font-semibold transition-colors">
                                ❌ Batal
                            </button>
                            <button type="button" onclick="updateManualAction(currentOpenClient, 'pipeline_status', 'deal')" class="w-2/3 bg-dark-bg border border-green-500/50 hover:bg-green-600 hover:text-white text-green-500 px-2 py-1 rounded-md text-[10px] font-bold transition-colors">
                                🎉 Tandai Deal
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-dark-surface border border-dark-border rounded-lg p-4 relative overflow-hidden flex-shrink-0">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                    <p class="text-xs text-blue-400 mb-1 font-semibold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Ekstraksi H.A.N.A
                    </p>
                    <h3 class="text-white font-bold text-lg mt-2 mb-1" id="modal-kanker">Memuat...</h3>
                    <p class="text-sm text-gray-300 leading-relaxed" id="modal-ringkasan">Memuat ringkasan medis...</p>
                </div>

                <div class="bg-dark-surface border border-dark-border rounded-lg p-4 relative overflow-hidden flex-shrink-0">
                    <p class="text-xs text-brand-blue mb-3 font-semibold flex items-center gap-1 uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Profil Intelijen
                    </p>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2 flex items-center justify-between bg-dark-bg p-2 rounded border border-dark-border mb-1">
                            <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">🔥 Suhu Prospek</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-dark-surface rounded-full overflow-hidden flex-shrink-0">
                                    <div id="modal-score-bar" class="h-full bg-gradient-to-r from-red-500 to-yellow-400 transition-all duration-500" style="width: 0%"></div>
                                </div>
                                <span class="text-sm font-bold text-white w-5 text-right" id="modal-lead-score">0</span>
                            </div>
                        </div>

                        <div class="bg-dark-bg p-2 rounded border border-dark-border flex flex-col justify-center">
                            <span class="text-[9px] text-gray-500 block mb-0.5 uppercase font-bold tracking-wide">Minat Tindakan</span>
                            <span class="text-xs font-semibold text-gray-200 line-clamp-1" id="modal-minat">-</span>
                        </div>
                        <div class="bg-dark-bg p-2 rounded border border-dark-border flex flex-col justify-center">
                            <span class="text-[9px] text-gray-500 block mb-0.5 uppercase font-bold tracking-wide">Metode Bayar</span>
                            <span class="text-xs font-semibold text-green-400 line-clamp-1" id="modal-bayar">-</span>
                        </div>
                        <div class="bg-dark-bg p-2 rounded border border-dark-border flex flex-col justify-center">
                            <span class="text-[9px] text-gray-500 block mb-0.5 uppercase font-bold tracking-wide">Status Medis</span>
                            <span class="text-xs font-semibold text-gray-200 line-clamp-1" id="modal-status-medis">-</span>
                        </div>
                        <div class="bg-dark-bg p-2 rounded border border-dark-border flex flex-col justify-center">
                            <span class="text-[9px] text-gray-500 block mb-0.5 uppercase font-bold tracking-wide">Pengirim Chat</span>
                            <span class="text-xs font-semibold text-gray-200 line-clamp-1" id="modal-pengirim">-</span>
                        </div>
                        <div class="col-span-2 bg-dark-bg p-2 rounded border border-red-900/30">
                            <span class="text-[9px] text-red-400 block mb-0.5 uppercase font-bold tracking-wide">Kendala / Keberatan Utama</span>
                            <span class="text-xs font-semibold text-red-200 leading-tight" id="modal-kendala">-</span>
                        </div>

                        <div class="col-span-2 bg-blue-900/10 p-2 mt-1 rounded border border-blue-900/30 flex flex-col gap-1 hidden" id="modal-ads-container">
                            <span class="text-[9px] text-brand-blue block uppercase font-bold tracking-wide flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                Sumber Iklan (Ads)
                            </span>
                            <span class="text-[10px] font-mono text-gray-300 truncate" id="modal-gclid"></span>
                            <span class="text-[10px] font-mono text-gray-300 truncate" id="modal-fbclid"></span>
                        </div>
                    </div>
                </div>

                <div id="modal-followup-container" class="border rounded-lg p-4 shadow-inner transition-colors flex-shrink-0">
                    <h4 id="followup-title" class="text-xs font-bold flex items-center gap-2 mb-3 uppercase tracking-wider">
                        </h4>
                    <div class="space-y-3">
                        <div>
                            <span class="text-[10px] font-semibold uppercase opacity-80" id="label-alasan">Alasan / Konteks</span>
                            <p class="text-sm mt-0.5 leading-relaxed" id="modal-followup-alasan">-</p>
                        </div>
                        <div id="followup-saran-container">
                            <span class="text-[10px] text-red-300 font-semibold uppercase">Saran Kalimat CS</span>
                            <p class="text-sm text-red-200 mt-0.5 leading-relaxed font-medium italic" id="modal-followup-topik">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 flex flex-col min-h-[150px]">
                    <p class="text-xs text-dark-muted mb-2 font-semibold uppercase tracking-wider">Catatan Internal SPV/CS</p>
                    <textarea class="w-full flex-1 bg-dark-surface border border-dark-border rounded-lg p-3 text-sm text-white focus:ring-2 focus:ring-brand-purple focus:border-transparent resize-none" placeholder="Ketik catatan tambahan untuk pasien ini di sini..."></textarea>
                    <button class="w-full bg-dark-surface hover:bg-dark-border border border-dark-border text-white text-sm font-medium py-2 mt-3 rounded-lg transition-colors">Simpan Catatan</button>
                </div>

            </div>

            <div class="w-2/3 flex flex-col bg-[#0b141a]"> 
                <div class="bg-[#202c33] px-4 py-2 flex-shrink-0 border-b border-dark-border shadow-sm">
                    <h3 class="text-gray-200 text-sm font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        Transkrip Bukti Percakapan Asli
                    </h3>
                </div>
                
                <div id="modal-chat-container" class="flex-1 overflow-y-auto p-6 space-y-4" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.9;">
                    <div class="flex justify-center items-center h-full">
                        <span class="text-gray-400 bg-dark-bg/80 px-4 py-2 rounded-full text-sm">Mengambil riwayat percakapan...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>