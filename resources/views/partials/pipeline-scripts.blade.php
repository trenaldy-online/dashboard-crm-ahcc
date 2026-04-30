<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const columns = document.querySelectorAll('.sortable-list');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'shared',
            animation: 200, 
            ghostClass: 'opacity-40',
            dragClass: 'shadow-2xl',
            
            onEnd: function (evt) {
                const itemEl = evt.item;  
                const clientNumber = itemEl.getAttribute('data-id');
                const newStatus = evt.to.getAttribute('data-status'); 
                const oldStatus = evt.from.getAttribute('data-status'); 

                if (newStatus === oldStatus) return;

                fetch('{{ route('pipeline.updateStatus') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: JSON.stringify({
                        client_number: clientNumber,
                        new_status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('count-' + newStatus).innerText = evt.to.children.length;
                        document.getElementById('count-' + oldStatus).innerText = evt.from.children.length;
                        
                        const draftBadge = itemEl.querySelector('.bg-brand-purple\\/20');
                        if(draftBadge) {
                            draftBadge.style.transition = 'opacity 0.3s';
                            draftBadge.style.opacity = '0';
                            setTimeout(() => draftBadge.remove(), 300);
                        }
                    }
                })
                .catch(error => alert('Koneksi terputus. Gagal menyimpan status.'));
            },
        });
    });

    const btnAi = document.getElementById('btn-trigger-ai');
    const progressContainer = document.getElementById('ai-progress-container');
    const progressBar = document.getElementById('ai-progress-bar');
    const percentageText = document.getElementById('ai-percentage');
    const statusText = document.getElementById('ai-status-text');

    btnAi.addEventListener('click', function() {
        btnAi.disabled = true;
        btnAi.innerHTML = 'Memulai proses...';
        progressContainer.classList.remove('hidden');

        fetch('{{ route('pipeline.triggerAi') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken 
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.batch_id) pollProgress(data.batch_id);
            else {
                alert(data.message || 'Tidak ada data untuk diproses.');
                resetUI();
            }
        })
        .catch(error => {
            alert('Gagal terhubung ke server.');
            resetUI();
        });
    });

    function pollProgress(batchId) {
        const interval = setInterval(() => {
            fetch(`{{ route('pipeline.progress') }}?id=${batchId}`)
            .then(response => response.json())
            .then(data => {
                progressBar.style.width = data.progress + '%';
                percentageText.innerText = data.progress + '%';
                
                statusText.innerHTML = `<svg class="animate-spin h-4 w-4 text-brand-purple inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>` 
                                     + `Memproses ${data.processed} dari ${data.total_jobs} chat pasien...`;

                if (data.finished) {
                    clearInterval(interval);
                    statusText.innerHTML = '✅ Proses selesai! Mengambil laporan rekap...';
                    progressBar.classList.replace('bg-brand-purple', 'bg-green-500');
                    percentageText.classList.replace('text-brand-purple', 'text-green-500');
                    
                    fetch('{{ route('pipeline.recapResult') }}')
                    .then(res => res.json())
                    .then(resData => {
                        statusText.innerHTML = '✅ Sukses! Berikut hasil deteksi AI hari ini:';
                        renderRecapResults(resData.results);
                    });
                }
            });
        }, 2000);
    }

    function renderRecapResults(results) {
        const recapContainer = document.getElementById('ai-recap-results');
        const recapList = document.getElementById('recap-list');
        recapList.innerHTML = '';

        if (results.length === 0) {
            recapList.innerHTML = '<p class="text-xs text-gray-400">Tidak ada data obrolan baru hari ini.</p>';
        } else {
            const statusMap = {
                'leads_baru': '<span class="text-gray-400">📥 Leads Baru</span>',
                'edukasi': '<span class="text-blue-400">🗣️ Edukasi</span>',
                'konsultasi': '<span class="text-yellow-400">🏥 Konsultasi</span>',
                'deal': '<span class="text-green-400">✅ Deal</span>',
                'batal': '<span class="text-red-400">❌ Batal</span>'
            };

            results.forEach(item => {
                const statusHtml = statusMap[item.pipeline_status] || item.pipeline_status;
                const aiBadge = !item.is_human_validated 
                    ? `<span class="ml-2 text-[9px] bg-brand-purple/20 text-brand-purple px-1.5 py-0.5 rounded border border-brand-purple/30 uppercase font-semibold tracking-wider">AI Draft</span>` 
                    : '';

                const li = `
                <div class="flex justify-between items-center bg-dark-surface p-3 rounded-lg border border-dark-border/80 hover:border-gray-500 transition-colors">
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-white font-semibold flex items-center">
                            ${item.client_number} ${aiBadge}
                        </span>
                        <span class="text-xs bg-dark-bg px-2 py-1 rounded border border-dark-border">
                            Ditempatkan di: ${statusHtml}
                        </span>
                    </div>
                    <button type="button" class="btn-lihat-detail text-xs bg-dark-bg hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded transition-colors border border-dark-border flex items-center gap-1" data-id="${item.client_number}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Lihat Detail
                    </button>
                </div>`;
                recapList.insertAdjacentHTML('beforeend', li);
            });
        }

        recapContainer.classList.remove('hidden');

        document.getElementById('btn-refresh-board').addEventListener('click', () => {
            window.location.reload();
        });
    }

    function resetUI() {
        btnAi.disabled = false;
        btnAi.innerHTML = '🤖 Rekap Chat Hari Ini';
        progressContainer.classList.add('hidden');
    }

    const detailModal = document.getElementById('detail-modal');
    const modalContent = document.getElementById('modal-content');
    const closeModalBtn = document.getElementById('close-modal');
    
    document.addEventListener('dblclick', function(e) {
        const card = e.target.closest('.group'); 
        if (card) {
            const clientNumber = card.getAttribute('data-id');
            openDetailModal(clientNumber);
        }
    });

    document.addEventListener('click', function(e) {
        const detailBtn = e.target.closest('.btn-lihat-detail');
        if (detailBtn) {
            const clientNumber = detailBtn.getAttribute('data-id');
            openDetailModal(clientNumber);
        }
    });

    window.openDetailModal = function(clientNumber) {
        window.currentOpenClient = clientNumber;
        detailModal.classList.remove('hidden');
        setTimeout(() => {
            detailModal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);

        document.getElementById('modal-client-number').innerText = clientNumber;
        document.getElementById('modal-avatar').innerText = clientNumber.slice(-2);
        
        const cleanNumber = clientNumber.replace(/\D/g, ''); 
        document.getElementById('modal-wa-link').href = `https://wa.me/${cleanNumber}`;

        const chatContainer = document.getElementById('modal-chat-container');
        chatContainer.innerHTML = `<div class="flex justify-center items-center h-full"><span class="text-gray-400 bg-dark-bg/80 px-4 py-2 rounded-full text-sm flex items-center gap-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mengambil data...</span></div>`;

        fetch('{{ route('pipeline.detail') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken 
            },
            body: JSON.stringify({ client_number: clientNumber })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const lead = data.lead;
                document.getElementById('modal-kanker').innerText = lead.kategori_kanker || 'Belum Terdeteksi';
                document.getElementById('modal-ringkasan').innerText = lead.ringkasan || 'Tidak ada ringkasan.';
                
                const statusMap = { 'leads_baru': '📥 Leads Baru', 'edukasi': '🗣️ Sedang Edukasi', 'konsultasi': '🏥 Jadwal Konsultasi', 'deal': '✅ Pasien Deal', 'batal': '❌ Batal / Mundur' };
                document.getElementById('modal-status').innerText = statusMap[lead.pipeline_status] || lead.pipeline_status;
                
                const aiBadge = document.getElementById('modal-ai-badge');
                if (!lead.is_human_validated) { aiBadge.classList.remove('hidden'); } 
                else { aiBadge.classList.add('hidden'); }

                // LOGIKA DUA WARNA MERAH/HIJAU
                const followupContainer = document.getElementById('modal-followup-container');
                const followupTitle = document.getElementById('followup-title');
                const followupSaran = document.getElementById('followup-saran-container');
                const isNeedFollowUp = lead.perlu_follow_up == true || lead.perlu_follow_up == 1 || lead.perlu_follow_up === '1';

                if (isNeedFollowUp) {
                    followupContainer.className = 'bg-gradient-to-br from-red-900/40 to-dark-bg border border-red-500/30 rounded-lg p-4 shadow-inner mt-4';
                    followupTitle.className = 'text-xs font-bold text-red-400 flex items-center gap-2 mb-3 uppercase tracking-wider';
                    followupTitle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Butuh Follow-Up Segera';

                    document.getElementById('modal-followup-alasan').innerText = lead.alasan_follow_up || 'AI mendeteksi percakapan ini menggantung dan pasien belum merespons.';
                    document.getElementById('label-alasan').className = 'text-[10px] text-red-300 font-semibold uppercase';
                    
                    followupSaran.classList.remove('hidden');
                    document.getElementById('modal-followup-topik').innerHTML = lead.topik_follow_up || 'Gunakan sapaan standar untuk menanyakan kelanjutan.';
                } else {
                    followupContainer.className = 'bg-gradient-to-br from-green-900/10 to-dark-bg border border-green-500/20 rounded-lg p-4 shadow-inner mt-4';
                    followupTitle.className = 'text-xs font-bold text-green-400 flex items-center gap-2 mb-3 uppercase tracking-wider';
                    followupTitle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Belum Perlu Follow-Up';

                    document.getElementById('modal-followup-alasan').innerText = lead.alasan_follow_up || 'Konteks obrolan masih aktif, CS baru saja merespons, atau pasien sudah memberikan keputusan final.';
                    document.getElementById('label-alasan').className = 'text-[10px] text-green-500/70 font-semibold uppercase';
                    
                    followupSaran.classList.add('hidden'); 
                }
                
                // --- KODE BARU: INJEKSI DATA INTELIJEN H.A.N.A ---
                const score = lead.lead_score || 0;
                document.getElementById('modal-lead-score').innerText = score;
                document.getElementById('modal-score-bar').style.width = score + '%';
                
                // Ubah warna bar berdasarkan skor
                const scoreBar = document.getElementById('modal-score-bar');
                if(score > 70) scoreBar.className = 'h-full bg-gradient-to-r from-red-500 to-orange-500';
                else if(score > 30) scoreBar.className = 'h-full bg-gradient-to-r from-yellow-400 to-orange-400';
                else scoreBar.className = 'h-full bg-gradient-to-r from-gray-500 to-gray-400';

                document.getElementById('modal-minat').innerText = lead.minat_treatment || 'Belum Diketahui';
                document.getElementById('modal-bayar').innerText = lead.metode_bayar || 'Belum Diketahui';
                document.getElementById('modal-status-medis').innerText = lead.status_medis || 'Belum Diketahui';
                document.getElementById('modal-pengirim').innerText = lead.profil_pengirim || 'Belum Diketahui';
                document.getElementById('modal-kendala').innerText = lead.kendala_utama || 'Belum Ada';

                // Tampilkan Tracker Iklan jika ada
                const adsContainer = document.getElementById('modal-ads-container');
                const gclidEl = document.getElementById('modal-gclid');
                const fbclidEl = document.getElementById('modal-fbclid');
                
                if (lead.gclid || lead.fbclid) {
                    adsContainer.classList.remove('hidden');
                    gclidEl.innerText = lead.gclid ? 'GCLID: ' + lead.gclid : '';
                    gclidEl.style.display = lead.gclid ? 'block' : 'none';
                    fbclidEl.innerText = lead.fbclid ? 'FBCLID: ' + lead.fbclid : '';
                    fbclidEl.style.display = lead.fbclid ? 'block' : 'none';
                } else {
                    adsContainer.classList.add('hidden');
                }
                // ------------------------------------------------

                chatContainer.innerHTML = ''; 
                
                if (data.chats.length === 0) {
                    chatContainer.innerHTML = `<div class="text-center text-gray-500 mt-10">Riwayat chat tidak ditemukan.</div>`;
                    return;
                }

                data.chats.forEach(chat => {
                    const isMe = chat.is_me == 1;
                    const bubbleAlign = isMe ? 'items-end self-end ml-auto' : 'items-start';
                    const bubbleBg = isMe ? 'bg-[#005c4b] text-[#e9edef]' : 'bg-[#202c33] text-[#e9edef]'; 
                    const radius = isMe ? 'rounded-tr-none' : 'rounded-tl-none';
                    
                    const bubbleHTML = `
                        <div class="flex flex-col ${bubbleAlign} max-w-[80%] mb-1">
                            <div class="px-3 py-2 text-sm leading-relaxed shadow-sm rounded-lg ${bubbleBg} ${radius}">
                                ${chat.message}
                            </div>
                            <span class="text-[10px] mt-1 text-gray-500 ${isMe ? 'text-right' : 'text-left'}">
                                ${chat.time}
                            </span>
                        </div>
                    `;
                    chatContainer.insertAdjacentHTML('beforeend', bubbleHTML);
                });

                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        })
        .catch(err => {
            chatContainer.innerHTML = `<div class="text-center text-red-500 mt-10">Gagal mengambil riwayat obrolan. (Error Koneksi)</div>`;
            console.error(err);
        });
    }

    function closeDetailModal() {
        detailModal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            detailModal.classList.add('hidden');
        }, 300); 
    }

    closeModalBtn.addEventListener('click', closeDetailModal);
    
    detailModal.addEventListener('click', function(e) {
        if (e.target === detailModal) closeDetailModal();
    });

    // ==============================================================
    // KODE AUTO-OPEN MODAL (KIRIMAN DARI DASHBOARD H.A.N.A)
    // ==============================================================
    const urlParams = new URLSearchParams(window.location.search);
    const targetClientNumber = urlParams.get('open');

    if (targetClientNumber) {
        // Beri jeda 500ms agar Kanban selesai me-render tampilannya dulu
        setTimeout(() => {
            // Panggil fungsi pembuka modal
            if(typeof window.openDetailModal === 'function') {
                window.openDetailModal(targetClientNumber);
            }
            
            // Trik Elegan: Hapus ?open= dari URL agar jika SPV menekan F5/Refresh, 
            // modal tidak otomatis terbuka terus-menerus.
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 500);
    }
    });

    // ==============================================================
    // FUNGSI GLOBAL: PANEL KENDALI MANUAL PA (BYPASS AI)
    // ==============================================================
    window.updateManualAction = function(clientNumber, actionType, actionValue) {
        if(!clientNumber) {
            alert('Nomor klien tidak ditemukan!');
            return;
        }

        // Tampilkan konfirmasi untuk menghindari klik tak sengaja
        let pesanKonfirmasi = actionType === 'perlu_follow_up' 
            ? 'Tandai nomor ini sudah selesai di-follow up?' 
            : `Pindahkan pasien ini ke kolom ${actionValue.toUpperCase()}?`;

        if(!confirm(pesanKonfirmasi)) return;

        document.body.style.cursor = 'wait';

        let payload = {
            client_number: clientNumber
        };
        payload[actionType] = actionValue;

        // Ambil CSRF token dari tag Meta
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ url('/pipeline/update-manual') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            document.body.style.cursor = 'default';
            if(data.success) {
                // Refresh halaman agar kartu otomatis pindah/hilang dari UI
                window.location.reload(); 
            } else {
                alert('Gagal mengupdate data. Silakan coba lagi.');
            }
        })
        .catch(error => {
            document.body.style.cursor = 'default';
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi sistem.');
        });
};
</script>