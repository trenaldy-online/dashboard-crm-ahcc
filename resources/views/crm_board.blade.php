<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AHCC CRM & AI Review Board</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; color: #333; }
        .header { margin-bottom: 30px; }
        .header h1 { margin: 0; color: #111827; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        
        /* Desain Kartu Pasien */
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #d1d5db; }
        .card.pending { border-left-color: #f59e0b; } /* Kuning untuk butuh review */
        .card.disetujui { border-left-color: #10b981; } /* Hijau untuk selesai */
        
        .client-number { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #1f2937; }
        
        /* Tombol Triger AI */
        .btn-ai { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; text-align: center; display: inline-block; text-decoration: none; }
        .btn-ai:hover { opacity: 0.9; }

        /* Panel Hasil AI */
        .ai-result { background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: bold; margin-bottom: 10px; margin-right: 5px; }
        .badge-kanker { background: #fee2e2; color: #991b1b; }
        .badge-layanan { background: #dbeafe; color: #1e40af; }
        .summary-text { font-size: 14px; line-height: 1.5; margin-bottom: 15px; color: #4b5563; }
        
        /* Aksi Review */
        .action-buttons { display: flex; gap: 10px; }
        .btn-approve { background: #10b981; color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; flex: 1; font-weight: bold; }
        .btn-reject { background: #ef4444; color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; flex: 1; font-weight: bold; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🏥 Meja Kerja CRM & Validasi AI</h1>
        <p>Monitor prospek pasien dan setujui ringkasan otomatis dari Gemini AI.</p>
    </div>

    @if(session('success')) <div class="alert alert-success">✅ {{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-error">❌ {{ session('error') }}</div> @endif

    <div class="grid-container">
        @foreach($clients as $client)
            @php 
                // Cek apakah klien ini sudah punya rekap AI di database
                $summary = $summaries->get($client->client_number); 
            @endphp

            <div class="card {{ $summary ? $summary->status_review : '' }}">
                <div class="client-number">📞 {{ $client->client_number }}</div>

                @if(!$summary)
                    <form action="{{ route('ai.summary', $client->client_number) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-ai">✨ Minta AI Buatkan Rekap</button>
                    </form>
                @else
                    <div class="ai-result">
                        <div>
                            <span class="badge badge-kanker">🩺 {{ $summary->kategori_kanker }}</span>
                            <span class="badge badge-layanan">🎯 {{ $summary->minat_layanan }}</span>
                        </div>
                        <div class="summary-text">
                            <strong>Ringkasan AI:</strong><br>
                            {{ $summary->ringkasan }}
                        </div>
                        
                        @if($summary->status_review === 'pending')
                            <div class="action-buttons">
                                <form action="{{ route('crm.approve', $client->client_number) }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn-approve" style="width: 100%;">✅ Setujui</button>
                                </form>

                                <form action="{{ route('crm.reject', $client->client_number) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Yakin ingin menolak dan menghapus rekap AI ini?');">
                                    @csrf
                                    <button type="submit" class="btn-reject" style="width: 100%;">🗑️ Tolak & Ulangi</button>
                                </form>
                            </div>
                        @else
                            <div style="text-align: center; font-weight: bold; color: #10b981; margin-bottom: 10px;">
                                ✔️ Data Telah Divalidasi
                            </div>
                            
                            <form action="{{ route('ai.followup', $client->client_number) }}" method="POST">
                                @csrf
                                <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold;">
                                    📝 Buatkan Draf Follow-Up
                                </button>
                            </form>

                            @if(session('followup_draft') && session('followup_client') == $client->client_number)
                                <div style="margin-top: 15px; padding: 15px; background: #eff6ff; border: 1px dashed #3b82f6; border-radius: 8px;">
                                    <strong style="color: #1d4ed8;">🤖 Draf AI:</strong>
                                    <p style="font-size: 14px; color: #333; margin: 10px 0;">{{ session('followup_draft') }}</p>
                                    
                                    <button onclick="navigator.clipboard.writeText('{{ session('followup_draft') }}'); alert('Draf disalin! Silakan tempel di WhatsApp.');" style="background: #1f2937; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                        📋 Copy Teks
                                    </button>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->client_number) }}" target="_blank" style="display: inline-block; background: #25D366; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; margin-left: 5px;">
                                        💬 Buka Chat WA
                                    </a>
                                </div>
                            @endif

                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

</body>
</html>