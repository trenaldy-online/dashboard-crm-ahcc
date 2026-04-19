<aside class="w-64 bg-dark-bg border-r border-dark-border flex flex-col hidden md:flex shrink-0">
    <div class="h-16 flex items-center px-6 border-b border-dark-border">
        <svg class="w-6 h-6 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        <span class="text-lg font-semibold tracking-wide text-white">API Monitor</span>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-8">
        <div>
            <h3 class="px-3 text-xs font-semibold text-dark-muted uppercase tracking-wider mb-2">Overview</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('telemetry.index') }}" class="flex items-center px-3 py-2 bg-dark-surface text-white rounded-md group">
                        <svg class="w-5 h-5 mr-3 text-dark-muted group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('chat.list') }}" class="flex items-center px-3 py-2 text-dark-muted hover:bg-dark-surface hover:text-white rounded-md transition-colors group">
                        <svg class="w-5 h-5 mr-3 text-dark-muted group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        Daftar Chat
                    </a>
                </li>
                </ul>
        </div>
    </nav>
</aside>