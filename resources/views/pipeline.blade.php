@extends('layouts.app')

@section('title', 'Pipeline Pasien - AHCC')

@php
    \Carbon\Carbon::setLocale('id');
@endphp

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="flex flex-col h-[calc(100vh-180px)] bg-dark-surface border border-dark-border rounded-xl overflow-hidden shadow-lg p-6">
    
    <div class="flex justify-between items-center mb-4 flex-shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-white">📊 Papan Corong Penjualan (Pipeline)</h1>
            <p class="text-sm text-dark-muted mt-1">Geser kartu pasien untuk memperbarui status mereka.</p>
        </div>
        <div>
            <button id="btn-trigger-ai" class="bg-brand-purple hover:bg-opacity-80 transition-opacity text-white font-medium py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                🤖 Rekap Chat Hari Ini
            </button>
        </div>
    </div>

    <div class="bg-dark-bg/50 border border-dark-border rounded-xl p-4 mb-5 flex-shrink-0">
        <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
            
            <div class="w-full md:w-3/5">
                <label class="block text-xs font-semibold text-dark-muted uppercase tracking-wider mb-1.5">Pencarian Kartu Pasien</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor WA, keluhan, jenis kanker..." class="w-full bg-dark-surface border border-dark-border text-white text-sm rounded-lg pl-9 pr-3 py-2.5 focus:ring-1 focus:ring-brand-purple focus:border-brand-purple transition-colors">
                </div>
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-xs font-semibold text-red-400/80 uppercase tracking-wider mb-1.5">Wajib Follow Up?</label>
                <select name="perlu_fu" class="w-full bg-dark-surface border border-dark-border text-white text-sm rounded-lg px-3 py-2.5 focus:ring-1 focus:ring-red-500 focus:border-red-500">
                    <option value="">Semua Pasien</option>
                    <option value="1" {{ request('perlu_fu') === '1' ? 'selected' : '' }}>🚨 Ya, Butuh FU</option>
                    <option value="0" {{ request('perlu_fu') === '0' ? 'selected' : '' }}>✅ Aman / Selesai</option>
                </select>
            </div>

            <div class="w-full md:w-1/5 flex gap-2">
                <button type="submit" class="flex-1 bg-brand-purple hover:bg-opacity-80 text-white font-semibold text-sm py-2.5 px-4 rounded-lg transition-colors shadow-md">
                    Terapkan
                </button>
                @if(request()->anyFilled(['search', 'perlu_fu']))
                    <a href="{{ url()->current() }}" class="bg-dark-surface hover:bg-dark-border border border-dark-border text-gray-300 flex items-center justify-center px-3 py-2.5 rounded-lg transition-colors" title="Reset Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </a>
                @endif
            </div>

        </form>
    </div>
    <div id="ai-progress-container" class="hidden mb-6 bg-dark-bg/50 border border-dark-border rounded-lg p-4 flex-shrink-0 shadow-inner">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-gray-300 font-medium flex items-center gap-2" id="ai-status-text">
                <svg class="animate-spin h-4 w-4 text-brand-purple" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Menyiapkan AI...
            </span>
            <span class="text-sm text-brand-purple font-bold" id="ai-percentage">0%</span>
        </div>
        <div class="w-full bg-dark-surface rounded-full h-2.5 border border-dark-border">
            <div id="ai-progress-bar" class="bg-brand-purple h-2.5 rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
        </div>

        <div id="ai-recap-results" class="hidden mt-5 border-t border-dark-border pt-4">
            <h4 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                📋 Rangkuman Aktivitas Hari Ini
            </h4>
            <div id="recap-list" class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
            </div>
            
            <button id="btn-refresh-board" class="mt-4 w-full bg-brand-purple hover:bg-opacity-80 transition-opacity text-white text-sm font-medium py-2 rounded-lg shadow-sm">
                Tutup & Segarkan Papan Kanban
            </button>
        </div>
    </div>

    <div class="flex flex-1 gap-5 overflow-x-auto pb-2">
        
        @php
            $columns = [
                'leads_baru' => ['title' => '📥 Leads Baru', 'accent' => 'border-t-gray-400'],
                'edukasi'    => ['title' => '🗣️ Sedang Edukasi', 'accent' => 'border-t-brand-blue'],
                'konsultasi' => ['title' => '🏥 Jadwal Konsultasi', 'accent' => 'border-t-yellow-500'],
                'deal'       => ['title' => '✅ Pasien Deal', 'accent' => 'border-t-green-500'],
                'batal'      => ['title' => '❌ Batal / Mundur', 'accent' => 'border-t-red-500'],
            ];
        @endphp

        @foreach($columns as $statusKey => $col)
        <div class="flex flex-col bg-dark-bg/50 border border-dark-border rounded-xl w-[320px] flex-shrink-0 {{ $col['accent'] }} border-t-4">
            
            <div class="p-3 border-b border-dark-border flex justify-between items-center bg-dark-surface/30 rounded-t-lg">
                <h3 class="font-semibold text-white text-sm">{{ $col['title'] }}</h3>
                <span class="bg-dark-surface border border-dark-border text-gray-300 text-xs font-bold px-2 py-1 rounded-md" id="count-{{ $statusKey }}">
                    {{ count($leads[$statusKey]) }}
                </span>
            </div>
            
            <div class="flex-1 p-3 overflow-y-auto sortable-list min-h-[150px] space-y-3" data-status="{{ $statusKey }}">
                
                @foreach($leads[$statusKey] as $lead)
                @php
                    $createdAt = \Carbon\Carbon::parse($lead->created_at);
                    $updatedAt = \Carbon\Carbon::parse($lead->updated_at);
                    
                    $isBrandNew = $createdAt->isToday(); 
                    $isUpdatedToday = !$isBrandNew && $updatedAt->isToday(); 

                    $displayDate = $updatedAt->translatedFormat('d M Y, H:i');
                @endphp

                <div class="bg-dark-surface p-4 rounded-lg border border-dark-border/80 cursor-grab hover:border-brand-purple hover:shadow-md transition-all group" data-id="{{ $lead->client_number }}">
                    
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="w-6 h-6 rounded-full bg-brand-blue/20 flex items-center justify-center text-[10px] text-brand-blue font-bold">
                                {{ substr($lead->client_number, -2) }}
                            </div>
                            <span class="text-sm font-semibold text-white">{{ $lead->client_number }}</span>
                            
                            @if($isBrandNew)
                                <span class="text-[9px] bg-green-500/20 text-green-400 border border-green-500/30 px-1.5 py-0.5 rounded uppercase tracking-widest font-bold" title="Pasien baru masuk hari ini">
                                    BARU
                                </span>
                            @elseif($isUpdatedToday)
                                <span class="text-[9px] bg-blue-500/20 text-blue-400 border border-blue-500/30 px-1.5 py-0.5 rounded uppercase tracking-widest font-bold" title="Pasien lama yang melakukan chat lagi hari ini">
                                    UPDATE
                                </span>
                            @endif
                        </div>
                        
                        @if(!$lead->is_human_validated)
                            <span class="text-[9px] bg-brand-purple/20 text-brand-purple px-2 py-0.5 rounded border border-brand-purple/30 font-semibold tracking-wider uppercase ml-1">
                                AI Draft
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-xs mb-2 flex items-center gap-2">
                        <span class="text-dark-muted">Kanker:</span> 
                        <span class="text-gray-200 font-medium bg-dark-bg px-2 py-0.5 rounded">{{ $lead->kategori_kanker ?? 'Belum terdeteksi' }}</span>
                    </div>
                    
                    <p class="text-xs text-dark-muted line-clamp-2 leading-relaxed group-hover:text-gray-400 transition-colors">
                        {{ $lead->ringkasan ?? 'Belum ada ringkasan dari percakapan ini.' }}
                    </p>

                    @if($lead->perlu_follow_up)
                        <div class="mt-2 bg-red-500/10 border border-red-500/20 rounded-md p-1.5 flex items-start gap-1.5" title="AI mendeteksi percakapan ini butuh tindakan lanjutan">
                            <svg class="w-3.5 h-3.5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-[10px] text-red-400 font-medium leading-tight">Butuh Follow-up</span>
                        </div>
                    @endif

                    <div class="mt-3 pt-3 border-t border-dark-border/50 flex justify-between items-center text-[10px] text-gray-500">
                        <span class="flex items-center gap-1.5" title="Tanggal direkap oleh AI">
                            <svg class="w-3 h-3 text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Direkap: {{ $displayDate }} WIB
                        </span>
                    </div>
                </div>
                @endforeach

            </div>
        </div>
        @endforeach

    </div>
</div>

@include('partials.modal-detail')

@endsection

@push('scripts')
    @include('partials.pipeline-scripts')
@endpush