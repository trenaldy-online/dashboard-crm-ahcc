<aside class="w-64 border-r border-dark-border bg-[#0b141a] flex flex-col shrink-0 h-screen">
    
    <div class="h-16 flex items-center px-6 border-b border-dark-border bg-dark-bg cursor-default">
        <div class="w-8 h-8 rounded bg-brand-purple/20 border border-brand-purple/50 flex items-center justify-center text-brand-purple font-bold text-lg mr-3 shadow-[0_0_10px_rgba(139,92,246,0.2)]">
            H
        </div>
        <div class="flex flex-col">
            <span class="text-white font-bold tracking-wider leading-none">H.A.N.A <span class="text-brand-purple">CRM</span></span>
            <span class="text-[9px] text-gray-500 uppercase tracking-widest mt-1">Patient Advisor</span>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-8 custom-scrollbar">
        
        <div>
            <p class="px-3 text-[11px] font-bold tracking-wider text-dark-muted uppercase mb-3">Workspace</p>
            <nav class="space-y-1.5">
                
                <a href="{{ url('/api-telemetry') }}" class="{{ request()->is('api-telemetry*') ? 'bg-dark-surface text-white border-l-2 border-brand-purple' : 'text-gray-400 hover:bg-dark-surface/50 hover:text-white border-l-2 border-transparent' }} flex items-center gap-3 px-3 py-2.5 rounded-r-lg transition-all group">
                    <svg class="w-5 h-5 {{ request()->is('api-telemetry*') ? 'text-brand-purple' : 'text-gray-500 group-hover:text-brand-purple' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span class="text-sm font-medium">Dashboard Pagi</span>
                </a>

                <a href="{{ url('/pipeline') }}" class="{{ request()->is('pipeline*') ? 'bg-dark-surface text-white border-l-2 border-brand-purple' : 'text-gray-400 hover:bg-dark-surface/50 hover:text-white border-l-2 border-transparent' }} flex items-center gap-3 px-3 py-2.5 rounded-r-lg transition-all group">
                    <svg class="w-5 h-5 {{ request()->is('pipeline*') ? 'text-brand-purple' : 'text-gray-500 group-hover:text-brand-purple' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    <span class="text-sm font-medium">Papan Kanban</span>
                    
                    @php
                        $fuCount = \App\Models\LeadSummary::where('perlu_follow_up', true)->whereNotIn('pipeline_status', ['deal', 'batal'])->count();
                    @endphp
                    @if($fuCount > 0)
                        <span class="ml-auto bg-red-500/20 text-red-400 py-0.5 px-2 rounded-full text-[10px] font-bold">{{ $fuCount }}</span>
                    @endif
                </a>

                <a href="{{ url('/daftar-chat') }}" class="{{ request()->is('daftar-chat*') ? 'bg-dark-surface text-white border-l-2 border-brand-purple' : 'text-gray-400 hover:bg-dark-surface/50 hover:text-white border-l-2 border-transparent' }} flex items-center gap-3 px-3 py-2.5 rounded-r-lg transition-all group">
                    <svg class="w-5 h-5 {{ request()->is('daftar-chat*') ? 'text-brand-purple' : 'text-gray-500 group-hover:text-brand-purple' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span class="text-sm font-medium">Log Chat Masuk</span>
                </a>
            </nav>
        </div>

        <div>
            <p class="px-3 text-[11px] font-bold tracking-wider text-dark-muted uppercase mb-3">Sistem</p>
            <nav class="space-y-1.5">
                <a href="#" class="text-gray-400 hover:bg-dark-surface/50 hover:text-white border-l-2 border-transparent flex items-center gap-3 px-3 py-2.5 rounded-r-lg transition-all group cursor-not-allowed opacity-50" title="Akan datang">
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="text-sm font-medium">Pengaturan AI</span>
                </a>
            </nav>
        </div>

    </div>
</aside>