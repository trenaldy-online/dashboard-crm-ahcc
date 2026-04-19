<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard AHCC WA Tools</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; background-color: #f0f2f5; color: #333; }
        .header-title { margin-bottom: 30px; }
        
        /* Grid untuk menampilkan kartu-kartu percakapan */
        .chat-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        
        .chat-card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden; }
        
        /* Bagian Kepala Kartu (Informasi Nomor) */
        .card-header { background-color: #075e54; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;}
        .client-info h3 { margin: 0; font-size: 16px; }
        .advisor-info { font-size: 12px; text-align: right; opacity: 0.8; }

        /* Bagian Isi Percakapan */
        .card-body { padding: 20px; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-color: #e5ddd5; max-height: 400px; overflow-y: auto; }
        
        /* Desain Gelembung Chat */
        .message { margin-bottom: 15px; display: flex; flex-direction: column; max-width: 80%; }
        .message-box { padding: 8px 12px; border-radius: 8px; font-size: 14px; line-height: 1.4; position: relative; }
        .message-time { font-size: 10px; color: gray; margin-top: 4px; text-align: right; }

        /* Pesan dari Klien (Kiri) */
        .msg-klien { align-items: flex-start; }
        .msg-klien .message-box { background-color: white; border-top-left-radius: 0; }

        /* Pesan dari CS / Anda (Kanan) */
        .msg-saya { align-items: flex-end; align-self: flex-end; margin-left: auto; }
        .msg-saya .message-box { background-color: #dcf8c6; border-top-right-radius: 0; }

        /* CSS Pembatas Tanggal ala WhatsApp/Instagram */
        .date-divider {
            display: flex;
            justify-content: center;
            margin: 15px 0;
        }
        .date-divider span {
            background-color: white;
            color: #555;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>

    <div class="header-title">
        <h2>📊 Dashboard Pemantauan CS AHCC</h2>
        <p>Riwayat percakapan dikelompokkan berdasarkan nomor klien.</p>
    </div>

    <div class="chat-container">
        @foreach($groupedChats as $clientNumber => $chats)
        
            <div class="chat-card">
                
                <div class="card-header">
                    <div class="client-info">
                        <h3>Pasien: {{ $clientNumber }}</h3>
                        <span style="font-size: 12px;">Total Pesan: {{ $chats->count() }}</span>
                    </div>
                    <div class="advisor-info">
                        Di-handle oleh:<br>
                        <strong>{{ $chats->first()->owner }}</strong><br>
                        ({{ $chats->first()->advisor_number }})
                    </div>
                </div>

                <div class="card-body">
                    @php $lastDate = ''; @endphp

                    @foreach($chats as $chat)
                        @php $currentDate = $chat->chat_time->format('Y-m-d'); @endphp

                        @if($lastDate !== $currentDate)
                            <div class="date-divider">
                                <span>{{ $chat->tanggal_pembatas }}</span>
                            </div>
                            @php $lastDate = $currentDate; @endphp
                        @endif

                        <div class="message {{ $chat->is_me ? 'msg-saya' : 'msg-klien' }}">
                            <div class="message-box">
                                {{ $chat->message }}
                            </div>
                            <span class="message-time">
                                {{ $chat->chat_time->format('H:i') }} 
                                </span>
                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>