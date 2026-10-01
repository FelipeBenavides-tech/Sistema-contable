@php
    $links = [
        ['route' => 'pos',        'label' => 'Caja',       'largo' => 'Caja del día',     'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ['route' => 'ventas',     'label' => 'Ventas',     'largo' => 'Historial ventas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['route' => 'inventario', 'label' => 'Inventario', 'largo' => 'Inventario',       'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['route' => 'gastos',     'label' => 'Gastos',     'largo' => 'Gastos',           'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'reportes',   'label' => 'Reportes',   'largo' => 'Reportes',         'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['route' => 'barberos',   'label' => 'Barberos',   'largo' => 'Barberos',         'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['route' => 'servicios',  'label' => 'Servicios',  'largo' => 'Servicios',        'icon' => 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z'],
    ];
    // En el teléfono, la barra de abajo muestra las 4 pantallas más usadas + "Más"
    $barraInferior = ['pos', 'ventas', 'inventario', 'gastos'];
    $barberia = auth()->user()->barberia;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
    <title>{{ $title ?? 'Panel' }} · Kaixa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen" x-data="{ menu: false }" @keydown.escape.window="menu = false">

    {{-- Fondo oscuro detrás del menú en el teléfono --}}
    <div x-cloak x-show="menu" x-transition.opacity @click="menu = false"
         class="fixed inset-0 z-40 bg-ink/50 lg:hidden"></div>

    {{-- Menú lateral (fijo en computador, deslizable en teléfono) --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col bg-brand-500 text-white transition-transform duration-200 lg:w-60 lg:translate-x-0"
           :class="menu && 'translate-x-0'">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-5 pt-[max(1.25rem,env(safe-area-inset-top))]">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-500 text-ink">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $links[6]['icon'] }}"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold">Kaixa</p>
                    <p class="truncate text-xs text-white/60">{{ $barberia?->nombre ?? 'Mi barbería' }}</p>
                </div>
            </div>
            <button type="button" @click="menu = false" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 lg:hidden" aria-label="Cerrar menú">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-3">
            @foreach($links as $link)
                @php $activo = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}"
                   class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm transition {{ $activo ? 'bg-accent-500 font-semibold text-ink' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                    {{ $link['largo'] }}
                </a>
            @endforeach
        </nav>

        @if($barberia?->fecha_vencimiento && $barberia->dias_restantes <= 7)
            <div class="mx-3 mb-3 rounded-xl bg-accent-500/20 p-3 text-xs text-white">
                Tu plan vence en {{ $barberia->dias_restantes }} {{ $barberia->dias_restantes === 1 ? 'día' : 'días' }}.
            </div>
        @endif

        <div class="flex items-center justify-between gap-2 border-t border-white/10 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
            <div class="min-w-0">
                <p class="truncate text-xs font-medium">{{ auth()->user()->name }}</p>
                <p id="reloj" class="text-[11px] text-white/50"></p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-white/20 px-3 py-1.5 text-xs text-white/80 hover:bg-white/10">Salir</button>
            </form>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col lg:pl-60">
        {{-- Barra superior --}}
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-white/95 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur sm:px-6">
            <button type="button" @click="menu = true" class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 lg:hidden" aria-label="Abrir menú">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-bold text-ink">{{ $title ?? 'Panel' }}</h1>
                <p class="truncate text-xs text-muted lg:hidden">{{ $barberia?->nombre }}</p>
            </div>
            <p class="hidden text-xs text-muted sm:block">{{ now()->translatedFormat('l j \d\e F') }}</p>
        </header>

        {{-- Contenido (con espacio abajo para la barra del teléfono) --}}
        <main class="mx-auto w-full max-w-7xl flex-1 p-4 pb-28 sm:p-6 lg:pb-6">
            {{ $slot }}
        </main>
    </div>

    {{-- Barra inferior solo en teléfono --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
        @foreach(collect($links)->whereIn('route', $barraInferior) as $link)
            @php $activo = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}"
               class="flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ $activo ? 'text-brand-500' : 'text-muted' }}">
                <span class="flex h-7 w-12 items-center justify-center rounded-full {{ $activo ? 'bg-brand-50' : '' }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                </span>
                {{ $link['label'] }}
            </a>
        @endforeach
        <button type="button" @click="menu = true" class="flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium text-muted">
            <span class="flex h-7 w-12 items-center justify-center rounded-full">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h.01M12 12h.01M19 12h.01"/></svg>
            </span>
            Más
        </button>
    </nav>

    @livewireScripts
    <script>
        (function () {
            const formato = new Intl.DateTimeFormat('es-CO', {
                timeZone: 'America/Bogota', hour: '2-digit', minute: '2-digit', hour12: true,
                day: '2-digit', month: '2-digit',
            });
            function reloj() {
                const el = document.getElementById('reloj');
                if (el) el.textContent = formato.format(new Date());
            }
            if (!window.__relojKaixa) {
                window.__relojKaixa = setInterval(reloj, 30000);
            }
            document.addEventListener('livewire:navigated', reloj);
            reloj();

            // En campos de dinero: borrar el 0 al tocarlos para escribir directo
            document.addEventListener('focusin', (e) => {
                const el = e.target;
                if (el.matches && el.matches('input[type=number]') && (el.value === '0' || el.value === '0.00')) {
                    el.value = '';
                }
            });
        })();
    </script>
</body>
</html>
