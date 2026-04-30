@extends('layouts.app')

@section('title', 'Daftar Chat - AHCC')

@php
    $headerTitle = 'Pemantauan Chat Terpusat';
    \Carbon\Carbon::setLocale('id'); // Pindahkan ke atas agar berlaku global di view ini
@endphp

@section('content')
    
    <div class="bg-dark-surface border border-dark-border rounded-xl p-4 mb-4 shadow-sm">
        <form action="{{ route('chat.list') }}" method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
            
            <div class="w-full md:w-2/5">
                <label class="block text-xs font-semibold text-dark-muted uppercase tracking-wider mb-1.5">Pencarian Bebas</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor WA, keluhan, jenis kanker..." class="w-full bg-dark-bg border border-dark-border text-white text-sm rounded-lg pl-9 pr-3 py-2.5 focus:ring-1 focus:ring-brand-purple focus:border-brand-purple transition-colors">
                </div>
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-xs font-semibold text-dark-muted uppercase tracking-wider mb-1.5">Pipeline</label>
                <select name="pipeline_status" class="w-full bg-dark-bg border border-dark-border text-white text-sm rounded-lg px-3 py-2.5 focus:ring-1 focus:ring-brand-purple focus:border-brand-purple">
                    <option value="">Semua Status</option>
                    <option value="leads_baru" {{ request('pipeline_status') == 'leads_baru' ? 'selected' : '' }}>Leads Baru</option>
                    <option value="edukasi" {{ request('pipeline_status') == 'edukasi' ? 'selected' : '' }}>Edukasi</option>
                    <option value="konsultasi" {{ request('pipeline_status') == 'konsultasi' ? 'selected' : '' }}>Konsultasi Dokter</option>
                    <option value="deal" {{ request('pipeline_status') == 'deal' ? 'selected' : '' }}>Deal / Selesai</option>
                    <option value="batal" {{ request('pipeline_status') == 'batal' ? 'selected' : '' }}>Batal / Ghosting</option>
                </select>
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-xs font-semibold text-red-400/80 uppercase tracking-wider mb-1.5">Status Follow Up</label>
                <select name="perlu_fu" class="w-full bg-dark-bg border border-dark-border text-white text-sm rounded-lg px-3 py-2.5 focus:ring-1 focus:ring-red-500 focus:border-red-500">
                    <option value="">Semua (Bebas)</option>
                    <option value="1" {{ request('perlu_fu') === '1' ? 'selected' : '' }}>🚨 Wajib Follow Up</option>
                    <option value="0" {{ request('perlu_fu') === '0' ? 'selected' : '' }}>✅ Aman / Selesai</option>
                </select>
            </div>

            <div class="w-full md:w-1/5 flex gap-2">
                <button type="submit" class="flex-1 bg-brand-purple hover:bg-brand-purple/80 text-white font-semibold text-sm py-2.5 px-4 rounded-lg transition-colors shadow-lg shadow-brand-purple/20">
                    Terapkan
                </button>
                @if(request()->anyFilled(['search', 'pipeline_status', 'perlu_fu']))
                    <a href="{{ route('chat.list') }}" class="bg-dark-bg hover:bg-dark-border border border-dark-border text-gray-300 flex items-center justify-center px-3 py-2.5 rounded-lg transition-colors" title="Reset Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </a>
                @endif
            </div>

        </form>
    </div>
    <div class="flex h-[calc(100vh-180px)] bg-dark-surface border border-dark-border rounded-xl overflow-hidden shadow-lg">
        
        <div class="w-1/3 border-r border-dark-border flex flex-col bg-dark-bg/50 flex-shrink-0">
            <div class="p-4 border-b border-dark-border flex-shrink-0">
                <h2 class="text-white font-medium text-sm">Riwayat Nomor WA</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                @foreach($clients as $client)
                    @php
                        // Logika untuk menentukan teks timestamp
                        if ($client->last_activity) {
                            $lastChat = \Carbon\Carbon::parse($client->last_activity)->timezone('Asia/Jakarta');
                            $now = \Carbon\Carbon::now('Asia/Jakarta');

                            if ($lastChat->isToday()) {
                                $displayTime = $lastChat->format('H:i');
                            } elseif ($lastChat->isYesterday()) {
                                $displayTime = 'Kemarin';
                            } elseif ($lastChat->diffInDays($now) < 7) {
                                $displayTime = $lastChat->translatedFormat('l'); // Menampilkan nama hari
                            } else {
                                $displayTime = $lastChat->format('d/m/Y');
                            }
                        } else {
                            $displayTime = '-';
                        }
                    @endphp

                    <a href="{{ route('chat.list', array_merge(request()->all(), ['client' => $client->client_number])) }}" 
   class="block p-4 border-b border-dark-border/50 hover:bg-dark-surface transition-colors {{ $activeClient == $client->client_number ? 'bg-dark-surface border-l-4 border-l-brand-purple' : '' }}">
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center overflow-hidden">
                                <div class="w-10 h-10 rounded-full bg-brand-blue/20 flex items-center justify-center text-brand-blue font-bold mr-3 flex-shrink-0">
                                    {{ substr($client->client_number, -2) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h3 class="text-white text-sm font-medium truncate">{{ $client->client_number }}</h3>
                                    <p class="text-dark-muted text-xs truncate mt-0.5">
                                        @if($activeClient == $client->client_number)
                                            Sedang dibuka...
                                        @else
                                            Klik untuk melihat chat
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="text-xs text-dark-muted whitespace-nowrap ml-2">
                                {{ $displayTime }}
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
                    @endphp

                    @forelse($activeChats as $chat)
                        @php 
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
                                    $dateLabel = $chatDate->translatedFormat('l'); 
                                } else {
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
                                <div class="px-3 py-2 text-sm leading-relaxed shadow-sm whitespace-pre-wrap break-words" style="background-color: #dcf8c6; color: #333; border-radius: 8px; border-top-right-radius: 0;">{{ $chat->message }}</div>
                                <span class="text-[10px] mt-1 text-right" style="color: gray;">
                                    {{ $chatDate->format('H:i') }}
                                </span>
                            </div>
                        @else
                            <div class="flex flex-col items-start max-w-[80%] mb-3">
                                <div class="px-3 py-2 text-sm leading-relaxed shadow-sm whitespace-pre-wrap break-words" style="background-color: white; color: #333; border-radius: 8px; border-top-left-radius: 0;">{{ $chat->message }}</div>
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
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
@endsection