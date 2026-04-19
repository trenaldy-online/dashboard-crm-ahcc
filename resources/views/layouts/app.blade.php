<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'API Monitor Dashboard')</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { bg: '#111111', surface: '#1A1A1A', border: '#2A2A2A', text: '#E5E7EB', muted: '#9CA3AF' },
                        brand: { purple: '#8B5CF6', blue: '#3B82F6', yellow: '#F59E0B' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dark-bg text-dark-text font-sans antialiased h-screen flex overflow-hidden">

    <x-sidebar />

    <main class="flex-1 flex flex-col overflow-hidden bg-dark-bg">
        
        <x-header :title="$headerTitle ?? 'Dashboard'" />

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                @yield('content')
            </div>
        </div>
    </main>

    @stack('scripts')

</body>
</html>