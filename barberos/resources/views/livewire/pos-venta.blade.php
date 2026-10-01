@php
    $cantidadItems = collect($carrito)->sum('cantidad');
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
@endphp
<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:gap-6">

    {{-- ─────────── Catálogo ─────────── --}}
    <section class="flex min-w-0 flex-1 flex-col gap-4">

        {{-- Resumen del día --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="kpi">
                <p class="kpi-label">Total hoy</p>
                <p class="kpi-value text-brand-500">{{ $dinero($resumenDia['total'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label">Efectivo</p>
                <p class="kpi-value text-green-600">{{ $dinero($resumenDia['total_efectivo'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label">Nequi / transf.</p>
                <p class="kpi-value text-blue-600">{{ $dinero($resumenDia['total_nequi'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label">Ventas</p>
                <p class="kpi-value text-amber-600">{{ $resumenDia['cantidad_ventas'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
            @foreach(['todos' => 'Todos', 'servicio' => 'Servicios', 'producto' => 'Productos'] as $valor => $texto)
                <button type="button" wire:click="$set('categoria', '{{ $valor }}')"
                        class="chip {{ $categoria === $valor ? 'chip-activo' : '' }}">
                    {{ $texto }}
                </button>
            @endforeach
        </div>

        {{-- Servicios y productos --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach($servicios as $servicio)
                @php $enCarrito = $carrito['srv_' . $servicio->id]['cantidad'] ?? 0; @endphp
                <button type="button" wire:click="agregarServicio({{ $servicio->id }})"
                        class="relative flex flex-col items-start rounded-2xl border-2 bg-white p-3 text-left shadow-card transition active:scale-[0.97] sm:p-4 {{ $enCarrito ? 'border-brand-500 bg-brand-50' : 'border-transparent hover:border-brand-200' }}">
                    @if($enCarrito)
                        <span class="absolute right-2 top-2 flex h-6 min-w-[24px] items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-bold text-white">{{ $enCarrito }}</span>
                    @endif
                    <span class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    </span>
                    <span class="line-clamp-2 text-sm font-semibold leading-tight text-ink">{{ $servicio->nombre }}</span>
                    <span class="mt-1 text-base font-bold text-brand-500">{{ $dinero($servicio->precio) }}</span>
                </button>
            @endforeach

            @foreach($productosInventario as $producto)
                @php
                    $enCarrito = $carrito['inv_' . $producto->id]['cantidad'] ?? 0;
                    $agotado = $producto->stock_actual <= 0;
                @endphp
                <button type="button" wire:click="agregarProductoInventario({{ $producto->id }})" @disabled($agotado)
                        class="relative flex flex-col items-start rounded-2xl border-2 bg-white p-3 text-left shadow-card transition active:scale-[0.97] disabled:opacity-50 sm:p-4 {{ $enCarrito ? 'border-amber-500 bg-amber-50' : 'border-transparent hover:border-amber-200' }}">
                    @if($enCarrito)
                        <span class="absolute right-2 top-2 flex h-6 min-w-[24px] items-center justify-center rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white">{{ $enCarrito }}</span>
                    @endif
                    <span class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <span class="line-clamp-2 text-sm font-semibold leading-tight text-ink">{{ $producto->nombre }}</span>
                    <span class="mt-1 text-base font-bold text-amber-600">{{ $dinero($producto->precio_venta) }}</span>
                    <span class="mt-0.5 text-xs {{ $producto->stock_bajo ? 'text-red-600' : 'text-muted' }}">
                        {{ $agotado ? 'Agotado' : 'Quedan ' . $producto->stock_actual }}
                    </span>
                </button>
            @endforeach

            @if($servicios->isEmpty() && $productosInventario->isEmpty())
                <div class="col-span-full rounded-2xl border-2 border-dashed border-line bg-white px-4 py-10 text-center">
                    <p class="text-sm font-medium text-muted">Todavía no hay nada para vender aquí.</p>
                    <a href="{{ route('servicios') }}" class="mt-2 inline-block text-sm font-semibold text-brand-500">Agregar servicios →</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ─────────── Cobro ─────────── --}}
    <section id="cobro" class="flex scroll-mt-20 flex-col gap-3 lg:sticky lg:top-20 lg:w-[360px] lg:shrink-0">

        @if($ventaExitosa)
            <div class="alert-success flex items-center gap-2">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ¡Venta registrada!
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between bg-brand-500 px-4 py-3 text-white">
                <p class="font-bold">Cobro</p>
                @if($cantidadItems)
                    <button type="button" wire:click="vaciarCarrito" class="text-xs text-white/80 underline-offset-2 hover:underline">Vaciar</button>
                @endif
            </div>

            {{-- 1. Lo que se va a cobrar --}}
            <div class="border-b border-line p-4">
                <p class="card-title mb-2">1 · Lo que se cobra</p>
                @forelse($carrito as $key => $item)
                    <div wire:key="item-{{ $key }}" class="flex items-center gap-2 py-1.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $item['nombre_servicio'] }}</p>
                            <p class="text-xs text-muted">{{ $dinero($item['precio']) }} c/u</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="restarItem('{{ $key }}')" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 font-bold text-muted" aria-label="Quitar uno">−</button>
                            <span class="w-6 text-center text-sm font-bold">{{ $item['cantidad'] }}</span>
                            <button type="button"
                                    wire:click="{{ $item['inventario_id'] ? 'agregarProductoInventario(' . $item['inventario_id'] . ')' : 'agregarServicio(' . $item['servicio_id'] . ')' }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 font-bold text-brand-500" aria-label="Agregar uno">+</button>
                        </div>
                        <p class="w-20 text-right text-sm font-bold text-brand-500">{{ $dinero($item['subtotal']) }}</p>
                    </div>
                @empty
                    <p class="py-3 text-center text-sm text-muted">Toca un servicio o producto para agregarlo.</p>
                @endforelse
            </div>

            {{-- 2. Barbero --}}
            <div class="border-b border-line p-4">
                <label for="barbero" class="card-title mb-2 block">2 · Barbero</label>
                <select id="barbero" wire:model.live="barberoId" class="input">
                    <option value="0">— Seleccionar —</option>
                    @foreach($barberos as $b)
                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
                @if($barberos->isEmpty())
                    <a href="{{ route('barberos') }}" class="mt-2 inline-block text-xs font-semibold text-brand-500">Agregar barberos →</a>
                @endif
            </div>

            {{-- 3. Método de pago --}}
            <div class="p-4">
                <p class="card-title mb-2">3 · Método de pago</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach([
                        'efectivo'  => ['Efectivo', 'border-green-600 bg-green-50 text-green-700'],
                        'nequi'     => ['Nequi', 'border-blue-600 bg-blue-50 text-blue-700'],
                        'combinado' => ['Combinado', 'border-amber-500 bg-amber-50 text-amber-700'],
                    ] as $valor => [$texto, $estiloActivo])
                        <button type="button" wire:click="$set('metodoPago', '{{ $valor }}')"
                                class="min-h-[44px] rounded-xl border-2 text-sm font-semibold transition {{ $metodoPago === $valor ? $estiloActivo : 'border-line bg-white text-muted' }}">
                            {{ $texto }}
                        </button>
                    @endforeach
                </div>

                @if($metodoPago === 'combinado')
                    @php $suma = (float) $montoEfectivo + (float) $montoNequi; @endphp
                    <div class="mt-3 grid grid-cols-2 gap-2 rounded-xl bg-amber-50 p-3">
                        <div>
                            <label class="label !text-amber-800" for="montoEfectivo">Efectivo</label>
                            <input id="montoEfectivo" wire:model.live.debounce.400ms="montoEfectivo" type="number" inputmode="numeric" min="0" step="1000" class="input bg-white">
                        </div>
                        <div>
                            <label class="label !text-amber-800" for="montoNequi">Nequi</label>
                            <input id="montoNequi" wire:model.live.debounce.400ms="montoNequi" type="number" inputmode="numeric" min="0" step="1000" class="input bg-white">
                        </div>
                        @if($suma > 0)
                            <p class="col-span-2 text-xs font-semibold {{ abs($suma - $this->totalCarrito) <= 1 ? 'text-green-700' : 'text-red-600' }}">
                                Suma {{ $dinero($suma) }}
                                @if(abs($suma - $this->totalCarrito) <= 1)
                                    ✓ cuadra con el total
                                @elseif($suma < $this->totalCarrito)
                                    · faltan {{ $dinero($this->totalCarrito - $suma) }}
                                @else
                                    · sobran {{ $dinero($suma - $this->totalCarrito) }}
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Total y botón --}}
            <div class="border-t border-line bg-slate-50 p-4">
                <div class="mb-3 flex items-end justify-between">
                    <span class="text-sm font-semibold text-muted">Total</span>
                    <span class="text-3xl font-extrabold text-brand-500">{{ $dinero($this->totalCarrito) }}</span>
                </div>
                <button type="button" wire:click="cobrar" wire:loading.attr="disabled" @disabled(!$cantidadItems)
                        class="btn btn-primary w-full !min-h-[52px] text-base">
                    <span wire:loading.remove wire:target="cobrar">Registrar cobro</span>
                    <span wire:loading wire:target="cobrar">Guardando…</span>
                </button>
            </div>
        </div>

        {{-- Ventas de hoy --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line bg-slate-50 px-4 py-3">
                <p class="card-title">Últimas ventas de hoy</p>
                <a href="{{ route('ventas') }}" class="text-xs font-semibold text-brand-500">Ver todas</a>
            </div>
            @forelse($ventasHoy as $v)
                <div class="border-b border-line px-4 py-3 text-sm last:border-b-0">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-semibold">{{ $v->barbero?->nombre ?? 'Venta directa' }}</span>
                        <span class="font-bold text-brand-500">{{ $dinero($v->total) }}</span>
                    </div>
                    <div class="mt-0.5 flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-muted">{{ $v->items->pluck('nombre_servicio')->implode(', ') }}</span>
                        <span class="badge {{ $v->metodo_pago === 'efectivo' ? 'badge-green' : ($v->metodo_pago === 'combinado' ? 'badge-amber' : 'badge-blue') }}">{{ ucfirst($v->metodo_pago) }}</span>
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-muted">Sin ventas registradas hoy.</p>
            @endforelse
        </div>
    </section>

    {{-- Barra flotante en el teléfono para ir al cobro --}}
    @if($cantidadItems)
        <a href="#cobro"
           class="fixed inset-x-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 flex items-center justify-between rounded-2xl bg-ink px-4 py-3 text-white shadow-lg lg:hidden">
            <span class="text-sm">{{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} · <strong>{{ $dinero($this->totalCarrito) }}</strong></span>
            <span class="rounded-xl bg-accent-500 px-3 py-1.5 text-sm font-bold text-ink">Cobrar ↓</span>
        </a>
    @endif
</div>
