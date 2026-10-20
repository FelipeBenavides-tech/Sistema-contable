@php
    $links = [
        ['route' => 'pos',        'label' => 'Caja',       'largo' => 'Caja del día',     'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ['route' => 'ventas',     'label' => 'Ventas',     'largo' => 'Historial ventas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['route' => 'membresias', 'label' => 'Membresías', 'largo' => 'Membresías',       'icon' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z'],
        ['route' => 'inventario', 'label' => 'Inventario', 'largo' => 'Inventario',       'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['route' => 'gastos',     'label' => 'Gastos',     'largo' => 'Gastos',           'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'reportes',   'label' => 'Reportes',   'largo' => 'Reportes',         'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['route' => 'barberos',   'label' => 'Barberos',   'largo' => 'Barberos',         'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['route' => 'servicios',  'label' => 'Servicios',  'largo' => 'Servicios',        'icon' => 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z'],
        ['route' => 'mi-plan',    'label' => 'Mi plan',    'largo' => 'Mi plan Kaixa',    'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
    ];
    // En el teléfono, la barra de abajo muestra las 4 pantallas más usadas + "Más"
    $barraInferior = ['pos', 'ventas', 'inventario', 'gastos'];
    $barberia = auth()->user()->barberia;

    // Estado del pago de Kaixa: siempre visible arriba y en el menú
    $estadoPago = $barberia?->estado_pago;
    $dineroPlan = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $chipPlan = match (true) {
        !$barberia                  => null,
        $barberia->por_uso          => ['texto' => 'Saldo ' . $dineroPlan($barberia->saldo), 'corto' => $dineroPlan($barberia->saldo)],
        !$barberia->fecha_vencimiento => null,
        $estadoPago === 'en_gracia' => ['texto' => 'Vencido · se bloquea el ' . $barberia->fecha_limite->format('d/m'), 'corto' => 'Vencido ' . $barberia->fecha_vencimiento->format('d/m')],
        default                     => ['texto' => 'Vence el ' . $barberia->fecha_vencimiento->format('d/m/Y'), 'corto' => 'Vence ' . $barberia->fecha_vencimiento->format('d/m')],
    };
    $claseChip = match ($estadoPago) {
        'en_gracia'  => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'por_vencer' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        default      => 'bg-slate-50 text-slate-600 ring-slate-500/20',
    };

    // Aviso de no pago: sale una vez cada vez que se inicia sesión y se puede cerrar
    $avisoPago = $barberia && ($estadoPago === 'en_gracia' || ($estadoPago === 'por_vencer' && $barberia->por_uso))
        && !session()->has('aviso_pago_visto');
    if ($avisoPago) {
        session()->put('aviso_pago_visto', true);
    }
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
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[2px] lg:hidden"></div>

    {{-- Menú lateral (fijo en computador, deslizable en teléfono) --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-white/5 bg-ink text-white transition-transform duration-200 lg:w-64 lg:translate-x-0"
           :class="menu && 'translate-x-0'">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-5 pt-[max(1.25rem,env(safe-area-inset-top))]">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-900 text-accent-500 ring-1 ring-white/10">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ collect($links)->firstWhere('route', 'servicios')['icon'] }}"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold tracking-tight">Kaixa</p>
                    <p class="truncate text-xs text-slate-400">{{ $barberia?->nombre ?? 'Mi barbería' }}</p>
                </div>
            </div>
            <button type="button" @click="menu = false" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Menú</p>
            @foreach($links as $link)
                @php $activo = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}"
                   class="relative flex min-h-[42px] items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors {{ $activo ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    @if($activo)<span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-accent-500"></span>@endif
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                    {{ $link['largo'] }}
                </a>
            @endforeach
        </nav>

        @if($barberia)
            <a href="{{ route('mi-plan') }}" class="mx-3 mb-3 block rounded-lg bg-white/5 p-3 text-xs text-slate-300 ring-1 ring-inset {{ $estadoPago === 'en_gracia' ? 'ring-rose-400/40' : ($estadoPago === 'por_vencer' ? 'ring-amber-400/40' : 'ring-white/10') }} hover:bg-white/10">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-white">Plan {{ $barberia->nombre_plan }}</span>
                    @if($estadoPago === 'en_gracia')
                        <span class="rounded bg-rose-500/20 px-1.5 py-0.5 text-[11px] font-medium text-rose-200">Vencido</span>
                    @elseif($estadoPago === 'por_vencer')
                        <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[11px] font-medium text-amber-200">{{ $barberia->por_uso ? 'Saldo bajo' : 'Por vencer' }}</span>
                    @else
                        <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[11px] font-medium text-emerald-200">Al día</span>
                    @endif
                </span>
                <span class="mt-1 block">
                    @if($barberia->por_uso)
                        Saldo: {{ $dineroPlan($barberia->saldo) }}
                    @elseif($estadoPago === 'en_gracia')
                        Venció el {{ $barberia->fecha_vencimiento->format('d/m/Y') }}. Se bloquea el {{ $barberia->fecha_limite->format('d/m/Y') }}.
                    @elseif($barberia->fecha_vencimiento)
                        Vence el {{ $barberia->fecha_vencimiento->format('d/m/Y') }}
                    @endif
                </span>
            </a>
        @endif

        <div class="flex items-center justify-between gap-2 border-t border-white/10 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p id="reloj" class="text-xs text-slate-500"></p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-300 ring-1 ring-inset ring-white/15 hover:bg-white/10 hover:text-white">Salir</button>
            </form>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col lg:pl-64">
        {{-- Barra superior --}}
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-white/90 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur sm:px-8">
            <button type="button" @click="menu = true" class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 lg:hidden" aria-label="Abrir menú">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-semibold text-ink sm:text-lg">{{ $title ?? 'Panel' }}</h1>
                <p class="truncate text-xs text-muted lg:hidden">{{ $barberia?->nombre }}</p>
            </div>
            <p class="hidden text-sm text-muted xl:block">{{ ucfirst(now()->translatedFormat('l j \d\e F')) }}</p>
            @if($chipPlan)
                <a href="{{ route('mi-plan') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $claseChip }}" title="Estado de tu plan Kaixa">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="sm:hidden">{{ $chipPlan['corto'] }}</span>
                    <span class="hidden sm:inline">{{ $chipPlan['texto'] }}</span>
                </a>
            @endif
        </header>

        {{-- Contenido (con espacio abajo para la barra del teléfono) --}}
        <main class="mx-auto w-full max-w-7xl flex-1 p-4 pb-28 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>

    {{-- Barra inferior solo en teléfono --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
        @foreach(collect($links)->whereIn('route', $barraInferior) as $link)
            @php $activo = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}"
               class="relative flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ $activo ? 'text-brand-700' : 'text-slate-500' }}">
                @if($activo)<span class="absolute inset-x-5 top-0 h-0.5 rounded-full bg-brand-700"></span>@endif
                <span class="flex h-7 w-12 items-center justify-center">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                </span>
                {{ $link['label'] }}
            </a>
        @endforeach
        <button type="button" @click="menu = true" class="flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium text-slate-500">
            <span class="flex h-7 w-12 items-center justify-center">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h.01M12 12h.01M19 12h.01"/></svg>
            </span>
            Más
        </button>
    </nav>

    @if($avisoPago)
        <div x-data="{ abierto: true }" x-show="abierto" x-cloak class="modal-fondo" @click.self="abierto = false" @keydown.escape.window="abierto = false">
            <div class="modal-panel" role="alertdialog" aria-labelledby="aviso-pago-titulo">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $estadoPago === 'en_gracia' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-700' }}">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </span>
                    <div>
                        @if($barberia->por_uso)
                            <h3 id="aviso-pago-titulo" class="modal-titulo">Tu saldo de Kaixa está bajo</h3>
                            <p class="mt-1 text-sm text-muted">Te quedan <span class="font-semibold text-ink">{{ $dineroPlan($barberia->saldo) }}</span>. Cuando se acabe no podrás registrar ventas hasta recargar.</p>
                        @else
                            <h3 id="aviso-pago-titulo" class="modal-titulo">Tienes un pago pendiente</h3>
                            <p class="mt-1 text-sm text-muted">
                                Tu plan {{ $barberia->nombre_plan }} venció el <span class="font-semibold text-ink">{{ $barberia->fecha_vencimiento->format('d/m/Y') }}</span>.
                                Puedes seguir usando Kaixa hasta el <span class="font-semibold text-ink">{{ $barberia->fecha_limite->format('d/m/Y') }}</span>
                                ({{ $barberia->dias_gracia_restantes === 0 ? 'hoy es el último día' : 'quedan ' . $barberia->dias_gracia_restantes . ($barberia->dias_gracia_restantes === 1 ? ' día' : ' días') }}).
                                Después el sistema se deshabilita hasta que se registre el pago.
                            </p>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" @click="abierto = false" class="btn btn-light">Cerrar aviso</button>
                    <a href="{{ route('mi-plan') }}" class="btn btn-primary">Ver cómo pagar</a>
                </div>
            </div>
        </div>
    @endif

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
