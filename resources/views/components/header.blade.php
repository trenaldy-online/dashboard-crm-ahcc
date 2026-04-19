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
        <button class="p-2 text-dark-muted hover:text-white border border-dark-border rounded-md hover:bg-dark-surface transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </button>
    </div>
</header>