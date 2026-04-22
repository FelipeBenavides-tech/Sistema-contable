<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberOS — {{ $title ?? 'Panel' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0047FF;
            --accent:  #FFB800;
            --bg:      #F0F4FF;
            --surface: #FFFFFF;
            --border:  #DDE3FF;
            --text:    #0A1628;
            --muted:   #5A6A8A;
            --success: #16A34A;
            --danger:  #DC2626;
            --info:    #0047FF;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); }

        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 240px;
            height: 100vh;
            background: var(--primary);
            z-index: 50;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
        }

        #overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 40;
        }

        #main-content {
            margin-left: 240px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        #hamburger { display: none; }
        #close-sidebar { display: none; }

        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #overlay.open { display: block; }
            #main-content { margin-left: 0; }
            #hamburger { display: flex; }
            #close-sidebar { display: flex; }
        }

        /* Scroll horizontal en tablas móvil */
.tabla-scroll {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid var(--border);
}
.tabla-scroll > div {
    min-width: 600px;
}
    </style>
</head>
<body style="display:flex;min-height:100vh">

    {{-- Overlay --}}
    <div id="overlay" onclick="closeSidebar()"></div>

    {{-- Sidebar --}}
    <aside id="sidebar">
        <div class="px-5 py-5 border-b flex items-center justify-between"
             style="border-color:rgba(255,255,255,0.1)">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background:var(--accent)">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">Kaixa</p>
                    <p class="text-xs" style="color:rgba(255,255,255,0.5)">
    {{ auth()->user()->barberia?->nombre ?? 'BarberOS' }}
</p>
                </div>
            </div>
            <button id="close-sidebar"
                    onclick="closeSidebar()"
                    class="w-7 h-7 rounded-lg items-center justify-center"
                    style="background:rgba(255,255,255,0.15);color:white;border:none;cursor:pointer;font-size:14px">
                ✕
            </button>
        </div>

        <nav style="flex:1;padding:12px;display:flex;flex-direction:column;gap:4px">
            @php
            $links = [
                ['route' => 'pos',        'label' => 'Caja del día',     'icon' => 'M3 3h18v4H3zM3 10h18v4H3zM3 17h10v4H3z'],
                ['route' => 'ventas',     'label' => 'Historial ventas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['route' => 'inventario', 'label' => 'Inventario',       'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['route' => 'gastos',     'label' => 'Gastos',           'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'reportes',   'label' => 'Reportes',         'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['route' => 'barberos',   'label' => 'Barberos',         'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
                ['route' => 'servicios',  'label' => 'Servicios',        'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
            ];
            @endphp

            @foreach($links as $link)
            @php $active = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}"
               onclick="closeSidebar()"
               style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;font-size:13px;text-decoration:none;transition:all 0.15s;
                      {{ $active
                          ? 'background:var(--accent);color:#1A1A1A;font-weight:600;'
                          : 'color:rgba(255,255,255,0.65);' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"
                     viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="{{ $link['icon'] }}"/>
                </svg>
                {{ $link['label'] }}
            </a>
            @endforeach
        </nav>

        <div style="padding:16px;border-top:1px solid rgba(255,255,255,0.1)">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <p style="font-size:12px;font-weight:500;color:white">{{ auth()->user()->name }}</p>
                    <p id="reloj" style="font-size:11px;color:var(--muted)"></p>
                </div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   style="font-size:11px;padding:4px 10px;border-radius:8px;color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.15);text-decoration:none">
                    Salir
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                    @csrf
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <main id="main-content">

        {{-- Topbar --}}
        <header style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0">
            <div style="display:flex;align-items:center;gap:12px">
                <button id="hamburger"
                        onclick="openSidebar()"
                        style="width:36px;height:36px;border-radius:10px;background:#F1F5F9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--primary)">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 style="font-size:14px;font-weight:600;color:var(--text)">{{ $title ?? 'Panel' }}</h1>

                </div>
            </div>
        </header>

        {{-- Content --}}
        <div style="flex:1;overflow:auto;padding:20px">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('overlay').classList.add('open');
    }

    function closeSidebar() {
        if (window.innerWidth < 1024) {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('open');
        }
    }

    function actualizarReloj() {
        const ahora = new Date();
        const opciones = {
            timeZone: 'America/Bogota',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        const formato = new Intl.DateTimeFormat('es-CO', opciones).format(ahora);
        const reloj = document.getElementById('reloj');
        if (reloj) reloj.textContent = formato;
    }

    function initInputs() {
    document.querySelectorAll('input[type=number]').forEach(input => {
        if (input.dataset.init) return;
        input.dataset.init = '1';
        input.addEventListener('focus', function() {
            if (this.value === '0' || this.value === '0.00') this.value = '';
        });
        input.addEventListener('blur', function() {
            if (this.value === '') this.value = '0';
        });
        input.addEventListener('keypress', function(e) {
            const allowed = ['0','1','2','3','4','5','6','7','8','9','.'];
            if (!allowed.includes(e.key)) e.preventDefault();
            if (e.key === '.' && this.value.includes('.')) e.preventDefault();
        });
        input.addEventListener('paste', function(e) {
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d*\.?\d*$/.test(paste)) e.preventDefault();
        });
    });
}

document.addEventListener('DOMContentLoaded', initInputs);
document.addEventListener('livewire:navigated', initInputs);
document.addEventListener('livewire:update', initInputs);

// Observer para detectar nuevos inputs dinámicos
const observer = new MutationObserver(() => initInputs());
document.addEventListener('DOMContentLoaded', () => {
    observer.observe(document.body, { childList: true, subtree: true });
});
    document.addEventListener('DOMContentLoaded', function() {
        initInputs();
        actualizarReloj();
        setInterval(actualizarReloj, 1000);
    });

    document.addEventListener('livewire:navigated', initInputs);
</script>
</body>
</html>
