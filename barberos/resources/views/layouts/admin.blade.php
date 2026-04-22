<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin SaaS — {{ $title ?? 'Panel' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1A3A5C;
            --accent:  #C9A84C;
            --bg:      #F4F6F9;
            --surface: #FFFFFF;
            --border:  #E2E8F0;
            --text:    #1E293B;
            --muted:   #64748B;
            --success: #16A34A;
            --danger:  #DC2626;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); }
    </style>
</head>
<body class="h-full flex">

    {{-- Sidebar Admin --}}
    <aside class="w-60 shrink-0 flex flex-col" style="background:var(--primary);min-height:100vh">
        <div class="px-6 py-5 border-b" style="border-color:rgba(255,255,255,0.1)">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                     style="background:var(--accent)">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">Kaixa Admin</p>
                    <p class="text-xs" style="color:rgba(255,255,255,0.5)">Panel SaaS</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            <a href="{{ route('admin.panel') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm"
               style="background:var(--accent);color:#1A1A1A;font-weight:600;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Barberías
            </a>
        </nav>

        <div class="px-4 py-4 border-t" style="border-color:rgba(255,255,255,0.1)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs" style="color:rgba(255,255,255,0.4)">Administrador</p>
                </div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-admin').submit();"
                   class="text-xs px-2 py-1 rounded-lg"
                   style="color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.15)">
                    Salir
                </a>
                <form id="logout-admin" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <main class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <header class="flex items-center justify-between px-6 py-4 border-b shrink-0"
                style="background:var(--surface);border-color:var(--border)">
            <div>
                <h1 class="text-base font-semibold">{{ $title ?? 'Panel' }}</h1>
                <p class="text-xs mt-0.5" style="color:var(--muted)">
                    {{ now()->format('d/m/Y') }}
                </p>
            </div>
        </header>

        <div class="flex-1 overflow-auto p-6">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
