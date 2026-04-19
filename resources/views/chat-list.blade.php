@extends('layouts.app')

@section('title', 'Daftar Chat - AHCC')

@php
    $headerTitle = 'Pemantauan Chat Terpusat';
@endphp

@section('content')
    <div class="flex h-[calc(100vh-180px)] bg-dark-surface border border-dark-border rounded-xl overflow-hidden shadow-lg">
        
        <div class="w-1/3 border-r border-dark-border flex flex-col bg-dark-bg/50 flex-shrink-0">
            <div class="p-4 border-b border-dark-border flex-shrink-0">
                <h2 class="text-white font-medium text-sm">Riwayat Nomor WA</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                @foreach($clients as $client)
                    <a href="{{ route('chat.list', ['client' => $client->client_number]) }}" 
                       class="block p-4 border-b border-dark-border/50 hover:bg-dark-surface transition-colors {{ $activeClient == $client->client_number ? 'bg-dark-surface border-l-4 border-l-brand-purple' : '' }}">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-brand-blue/20 flex items-center justify-center text-brand-blue font-bold mr-3 flex-shrink-0">
                                {{ substr($client->client_number, -2) }}
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="text-white text-sm font-medium truncate">{{ $client->client_number }}</h3>
                                <p class="text-dark-muted text-xs truncate mt-0.5">Klik untuk melihat chat...</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="w-2/3 flex flex-col relative" style="background-color: #e5ddd5;">
            @if($activeClient)
                <div class="p-4 flex items-center justify-between flex-shrink-0 shadow-sm z-10" style="background-color: #075e54; color: white;">
                    <div>
                        <h3 class="font-bold text-base m-0">Pasien: {{ $activeClient }}</h3>
                        <span class="text-xs opacity-90">Total Pesan: {{ $activeChats->count() }}</span>
                    </div>
                    <div class="text-right text-xs opacity-90 leading-relaxed">
                        Di-handle oleh:<br>
                        <strong>CS AHCC</strong>
                    </div>
                </div>

                <div id="chat-container" class="flex-1 overflow-y-auto p-5 space-y-2" style="background-color: #e5ddd5; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');">
                    
                    @php 
                        $lastDate = ''; 
                        \Carbon\Carbon::setLocale('id'); // Paksa Carbon menggunakan bahasa Indonesia
                    @endphp

                    @forelse($activeChats as $chat)
                        @php 
                            // Konversi waktu chat ke zona WIB (Jakarta)
                            $chatDate = \Carbon\Carbon::parse($chat->chat_time)->timezone('Asia/Jakarta');
                            $currentDate = $chatDate->format('Y-m-d'); 
                            $now = \Carbon\Carbon::now('Asia/Jakarta');
                        @endphp

                        @if($lastDate !== $currentDate)
                            @php
                                if ($chatDate->isToday()) {
                                    $dateLabel = 'Hari Ini';
                                } elseif ($chatDate->isYesterday()) {
                                    $dateLabel = 'Kemarin';
                                } elseif ($chatDate->diffInDays($now) < 7) {
                                    // Tampilkan nama hari (Senin, Selasa, dll)
                                    $dateLabel = $chatDate->translatedFormat('l'); 
                                } else {
                                    // Tampilkan Tanggal Bulan Tahun
                                    $dateLabel = $chatDate->translatedFormat('d F Y'); 
                                }
                            @endphp

                            <div class="flex justify-center my-4">
                                <span class="bg-white text-[#555] px-4 py-1.5 rounded-[10px] text-xs font-bold shadow-sm border border-[#e0e0e0]">
                                    {{ $dateLabel }}
                                </span>
                            </div>
                            
                            @php 
                                $lastDate = $currentDate; 
                            @endphp
                        @endif

                        @if($chat->is_me)
                            <div class="flex flex-col items-end self-end ml-auto max-w-[80%] mb-3">
                                <div class="px-3 py-2 text-sm leading-relaxed shadow-sm" style="background-color: #dcf8c6; color: #333; border-radius: 8px; border-top-right-radius: 0;">
                                    {{ $chat->message }}
                                </div>
                                <span class="text-[10px] mt-1 text-right" style="color: gray;">
                                    {{ $chatDate->format('H:i') }}
                                </span>
                            </div>
                        @else
                            <div class="flex flex-col items-start max-w-[80%] mb-3">
                                <div class="px-3 py-2 text-sm leading-relaxed shadow-sm" style="background-color: white; color: #333; border-radius: 8px; border-top-left-radius: 0;">
                                    {{ $chat->message }}
                                </div>
                                <span class="text-[10px] mt-1 text-right" style="color: gray;">
                                    {{ $chatDate->format('H:i') }}
                                </span>
                            </div>
                        @endif
                        
                    @empty
                        <div class="flex items-center justify-center h-full">
                            <span class="bg-white/70 px-4 py-2 rounded-lg text-sm text-gray-600 shadow-sm">
                                Tidak ada riwayat pesan untuk nomor ini.
                            </span>
                        </div>
                    @endforelse
                    
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-500" style="background-color: #e5ddd5; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');">
                    <span class="bg-white/80 px-6 py-3 rounded-full text-sm font-medium shadow-sm">
                        Pilih kontak di sebelah kiri untuk melihat percakapan
                    </span>
                </div>
            @endif
        </div>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var chatContainer = document.getElementById('chat-container');
            // Jika container chat ditemukan (ada nomor yang diklik)
            if (chatContainer) {
                // Posisikan scrollbar langsung ke titik paling bawah (scrollHeight)
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
    
@endsection