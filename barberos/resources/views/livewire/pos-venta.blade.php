@php
    $cantidadItems = collect($carrito)->sum('cantidad');
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $iconoServicio = 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z';
    $iconoProducto = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
@endphp
<div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:gap-8">

    {{-- ─────────── Catálogo ─────────── --}}
    <section class="flex min-w-0 flex-1 flex-col gap-5">

        {{-- Resumen del día --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="kpi">
                <p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Total hoy</p>
                <p class="kpi-value">{{ $dinero($resumenDia['total'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Efectivo</p>
                <p class="kpi-value">{{ $dinero($resumenDia['total_efectivo'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label"><span class="kpi-dot bg-sky-500"></span>Nequi / transf.</p>
                <p class="kpi-value">{{ $dinero($resumenDia['total_nequi'] ?? 0) }}</p>
            </div>
            <div class="kpi">
                <p class="kpi-label"><span class="kpi-dot bg-slate-400"></span>Ventas</p>
                <p class="kpi-value">{{ $resumenDia['cantidad_ventas'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="flex items-center justify-between gap-3">
            <h2 class="section-title hidden sm:block">Catálogo</h2>
            <div class="segmentado w-full sm:w-auto">
                @foreach(['todos' => 'Todos', 'servicio' => 'Servicios', 'producto' => 'Productos'] as $valor => $texto)
                    <button type="button" wire:click="$set('categoria', '{{ $valor }}')"
                            class="segmento {{ $categoria === $valor ? 'segmento-activo' : '' }}">
                        {{ $texto }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Servicios y productos --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach($servicios as $servicio)
                @php
                    $enCarrito = $carrito['srv_' . $servicio->id]['cantidad'] ?? 0;
                    $esProducto = $servicio->categoria === 'producto';
                @endphp
                <button type="button" wire:key="srv-{{ $servicio->id }}" wire:click="agregarServicio({{ $servicio->id }})"
                        class="group relative flex flex-col items-start rounded-xl border bg-white p-3.5 text-left shadow-card transition hover:border-slate-300 hover:shadow-elevada active:scale-[0.98] sm:p-4 {{ $enCarrito ? 'border-brand-500 ring-1 ring-brand-500' : 'border-line' }}">
                    @if($enCarrito)
                        <span class="absolute right-2.5 top-2.5 flex h-6 min-w-[24px] items-center justify-center rounded-full bg-brand-700 px-1.5 text-xs font-semibold text-white">{{ $enCarrito }}</span>
                    @endif
                    <span class="mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:text-brand-600">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $esProducto ? $iconoProducto : $iconoServicio }}"/></svg>
                    </span>
                    <span class="line-clamp-2 text-sm font-medium leading-snug text-ink">{{ $servicio->nombre }}</span>
                    <span class="mt-1 text-base font-semibold text-ink">{{ $dinero($servicio->precio) }}</span>
                    <span class="mt-0.5 text-xs text-muted">{{ $esProducto ? 'Producto' : 'Servicio' }}</span>
                </button>
            @endforeach

            @foreach($productosInventario as $producto)
                @php
                    $enCarrito = $carrito['inv_' . $producto->id]['cantidad'] ?? 0;
                    $agotado = $producto->stock_actual <= 0;
                @endphp
                <button type="button" wire:key="inv-{{ $producto->id }}" wire:click="agregarProductoInventario({{ $producto->id }})" @disabled($agotado)
                        class="group relative flex flex-col items-start rounded-xl border bg-white p-3.5 text-left shadow-card transition hover:border-slate-300 hover:shadow-elevada active:scale-[0.98] disabled:opacity-50 sm:p-4 {{ $enCarrito ? 'border-brand-500 ring-1 ring-brand-500' : 'border-line' }}">
                    @if($enCarrito)
                        <span class="absolute right-2.5 top-2.5 flex h-6 min-w-[24px] items-center justify-center rounded-full bg-brand-700 px-1.5 text-xs font-semibold text-white">{{ $enCarrito }}</span>
                    @endif
                    <span class="mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:text-brand-600">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconoProducto }}"/></svg>
                    </span>
                    <span class="line-clamp-2 text-sm font-medium leading-snug text-ink">{{ $producto->nombre }}</span>
                    <span class="mt-1 text-base font-semibold text-ink">{{ $dinero($producto->precio_venta) }}</span>
                    <span class="mt-0.5 text-xs {{ $producto->stock_bajo ? 'font-medium text-rose-600' : 'text-muted' }}">
                        {{ $agotado ? 'Agotado' : 'Producto · quedan ' . $producto->stock_actual }}
                    </span>
                </button>
            @endforeach

            @if($servicios->isEmpty() && $productosInventario->isEmpty())
                <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center">
                    <p class="text-sm text-muted">
                        @if($categoria === 'servicio') No hay servicios activos.
                        @elseif($categoria === 'producto') No hay productos para vender.
                        @else Todavía no hay nada para vender aquí.
                        @endif
                    </p>
                    <a href="{{ route($categoria === 'producto' ? 'inventario' : 'servicios') }}" class="mt-2 inline-block text-sm font-medium text-brand-600 hover:text-brand-800">
                        {{ $categoria === 'producto' ? 'Ir a Inventario' : 'Agregar servicios' }} →
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- ─────────── Cobro ─────────── --}}
    <section id="cobro" class="flex scroll-mt-20 flex-col gap-4 lg:sticky lg:top-24 lg:w-[360px] lg:shrink-0">

        @if($ventaExitosa)
            <div class="alert-success flex items-center gap-2">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Venta registrada.
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @if($citasPorCobrar->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-4 py-3 sm:px-5">
                    <h2 class="section-title">Citas de hoy por cobrar</h2>
                    <a href="{{ route('agenda') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">Agenda</a>
                </div>
                @foreach($citasPorCobrar as $c)
                    <button type="button" wire:key="cita-{{ $c->id }}" wire:click="cargarCita({{ $c->id }})"
                            class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5 text-left text-sm last:border-b-0 hover:bg-slate-50 sm:px-5 {{ $citaId === $c->id ? 'bg-brand-50' : '' }}">
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{ \Carbon\Carbon::createFromFormat('H:i', $c->hora_inicio)->format('g:i a') }} · {{ $c->cliente->nombre }}</span>
                            <span class="block truncate text-xs text-muted">{{ $c->barbero->nombre }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-medium text-brand-700">{{ $citaId === $c->id ? 'En el carrito' : 'Cobrar' }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-4 py-3.5 sm:px-5">
                <h2 class="section-title">Cobro</h2>
                @if($cantidadItems)
                    <button type="button" wire:click="vaciarCarrito" class="text-xs font-medium text-muted hover:text-rose-600">Vaciar</button>
                @endif
            </div>

            {{-- Cliente (opcional; obligatorio para fiar) --}}
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <p class="card-title mb-2">Cliente</p>
                @if($cliente)
                    <div class="flex items-start justify-between gap-3 rounded-lg bg-brand-50 p-3 ring-1 ring-inset ring-brand-600/15">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $cliente->nombre }}</p>
                            @if($cliente->telefono)<p class="text-xs text-muted">{{ $cliente->telefono }}</p>@endif
                            @if($membresia)
                                <p class="mt-1 text-xs text-muted">{{ $membresia->nombre_plan }} · vence {{ $membresia->fecha_vencimiento->format('d/m/Y') }}</p>
                                <p class="text-xs font-medium text-brand-700">Quedan {{ $membresia->visitas_restantes }} de {{ $membresia->visitas_total }} visitas</p>
                            @endif
                            @if((float) $cliente->saldo_favor > 0)
                                <label class="mt-1 flex items-center gap-2 text-xs font-medium text-emerald-700">
                                    <input type="checkbox" wire:model.live="usarSaldoFavor" class="h-4 w-4 rounded border-line text-brand-700">
                                    Usar saldo a favor ({{ $dinero($cliente->saldo_favor) }})
                                </label>
                            @endif
                            @if($cliente->deuda > 0)
                                <p class="mt-1 text-xs font-medium {{ $cliente->fiado_bloqueado ? 'text-rose-600' : 'text-amber-700' }}">
                                    Debe {{ $dinero($cliente->deuda) }} · {{ $cliente->servicios_fiados }} de {{ $cliente->limite_fiado }} servicios fiados
                                    @if($cliente->fiado_bloqueado) · debe pagar todo @endif
                                </p>
                            @endif
                        </div>
                        <button type="button" wire:click="quitarCliente" class="text-xs font-medium text-muted hover:text-rose-600">Quitar</button>
                    </div>
                @else
                    <input type="search" wire:model.live.debounce.300ms="buscarCliente" placeholder="Buscar por nombre o teléfono" class="input">
                    @if(mb_strlen(trim($buscarCliente)) >= 2)
                        <div class="mt-2 overflow-hidden rounded-lg ring-1 ring-inset ring-slate-200">
                            @forelse($clientesEncontrados as $c)
                                @php $m = $c->membresias->first(); @endphp
                                <button type="button" wire:key="cli-{{ $c->id }}" wire:click="seleccionarCliente({{ $c->id }})"
                                        class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-slate-50">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium">{{ $c->nombre }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $m ? $m->nombre_plan : 'Sin membresía' }}{{ $c->telefono ? ' · ' . $c->telefono : '' }}</span>
                                    </span>
                                    @if($m)
                                        <span class="badge badge-blue shrink-0">{{ $m->visitas_restantes }} {{ $m->visitas_restantes === 1 ? 'visita' : 'visitas' }}</span>
                                    @elseif($c->permite_fiado)
                                        <span class="badge badge-amber shrink-0">Cartera</span>
                                    @endif
                                </button>
                            @empty
                                <p class="px-3 py-3 text-center text-xs text-muted">No hay clientes con ese nombre o teléfono.</p>
                            @endforelse
                        </div>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                        <a href="{{ route('clientes') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">Nuevo cliente →</a>
                        <a href="{{ route('membresias') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">Vender una membresía →</a>
                    </div>
                @endif
            </div>

            {{-- 1. Lo que se va a cobrar --}}
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <p class="card-title mb-2">Detalle</p>
                @forelse($carrito as $key => $item)
                    <div wire:key="item-{{ $key }}" class="flex items-center gap-2 py-1.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $item['nombre_servicio'] }}</p>
                            <p class="text-xs text-muted">{{ $dinero($item['precio']) }} c/u</p>
                            @if($membresia && empty($item['es_producto']))
                                <button type="button" wire:click="alternarMembresia('{{ $key }}')"
                                        class="mt-1 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ !empty($item['cubierto']) ? 'bg-brand-700 text-white ring-brand-700' : 'text-slate-500 ring-slate-200 hover:text-ink' }}">
                                    {{ !empty($item['cubierto']) ? '✓ Con membresía' : 'Usar membresía' }}
                                </button>
                            @endif
                        </div>
                        <div class="flex items-center rounded-lg ring-1 ring-inset ring-slate-200">
                            <button type="button" wire:click="restarItem('{{ $key }}')" class="flex h-8 w-8 items-center justify-center text-slate-500 hover:text-ink" aria-label="Quitar uno">−</button>
                            <span class="w-6 text-center text-sm font-semibold">{{ $item['cantidad'] }}</span>
                            <button type="button"
                                    wire:click="{{ $item['inventario_id'] ? 'agregarProductoInventario(' . $item['inventario_id'] . ')' : 'agregarServicio(' . $item['servicio_id'] . ')' }}"
                                    class="flex h-8 w-8 items-center justify-center text-slate-500 hover:text-ink" aria-label="Agregar uno">+</button>
                        </div>
                        <p class="w-20 text-right text-sm font-semibold">{{ $dinero($item['subtotal']) }}</p>
                    </div>
                @empty
                    <p class="py-3 text-center text-sm text-muted">Toca un servicio o producto para agregarlo.</p>
                @endforelse
            </div>

            {{-- 2. Barbero --}}
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <label for="barbero" class="card-title mb-2 block">Barbero</label>
                <select id="barbero" wire:model.live="barberoId" class="input">
                    <option value="0">Seleccionar…</option>
                    @foreach($barberos as $b)
                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
                @if($barberos->isEmpty())
                    <a href="{{ route('barberos') }}" class="mt-2 inline-block text-xs font-medium text-brand-600">Agregar barberos →</a>
                @endif
            </div>

            {{-- 3. Método de pago --}}
            <div class="px-4 py-4 sm:px-5">
                <p class="card-title mb-2">Método de pago</p>
                <div class="segmentado flex w-full">
                    @foreach(['efectivo' => 'Efectivo', 'nequi' => 'Nequi', 'combinado' => 'Combinado', 'fiado' => 'Fiado'] as $valor => $texto)
                        <button type="button" wire:click="$set('metodoPago', '{{ $valor }}')"
                                class="segmento {{ $metodoPago === $valor ? 'segmento-activo' : '' }}">
                            {{ $texto }}
                        </button>
                    @endforeach
                </div>

                @if($metodoPago === 'fiado')
                    <p class="mt-3 text-xs {{ $cliente ? 'text-muted' : 'font-medium text-rose-600' }}">
                        @if($cliente)
                            Queda en la cartera de {{ $cliente->nombre }}. Se le pueden fiar hasta {{ $cliente->limite_fiado }} servicios; después debe pagar todo.
                        @else
                            Escoge el cliente arriba para fiarle.
                        @endif
                    </p>
                @endif

                @if($metodoPago === 'combinado')
                    @php $suma = (float) $montoEfectivo + (float) $montoNequi; @endphp
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="montoEfectivo">Efectivo</label>
                            <input id="montoEfectivo" wire:model.live.debounce.400ms="montoEfectivo" type="number" inputmode="numeric" min="0" step="1000" class="input">
                        </div>
                        <div>
                            <label class="label" for="montoNequi">Nequi</label>
                            <input id="montoNequi" wire:model.live.debounce.400ms="montoNequi" type="number" inputmode="numeric" min="0" step="1000" class="input">
                        </div>
                        @if($suma > 0)
                            <p class="col-span-2 text-xs font-medium {{ abs($suma - $this->porCobrar) <= 1 ? 'text-emerald-700' : 'text-rose-600' }}">
                                Suma {{ $dinero($suma) }}
                                @if(abs($suma - $this->porCobrar) <= 1)
                                    · cuadra con el total
                                @elseif($suma < $this->porCobrar)
                                    · faltan {{ $dinero($this->porCobrar - $suma) }}
                                @else
                                    · sobran {{ $dinero($suma - $this->porCobrar) }}
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Total y botón --}}
            <div class="border-t border-line bg-slate-50 px-4 py-4 sm:px-5">
                @php [$anticipoCita, $saldoUsado, $porCobrar] = $desglose; @endphp
                @if($anticipoCita > 0 || $saldoUsado > 0)
                    <div class="mb-2 flex flex-col gap-0.5 text-sm text-muted">
                        <div class="flex justify-between"><span>Total</span><span>{{ $dinero($this->totalCarrito) }}</span></div>
                        @if($anticipoCita > 0)<div class="flex justify-between"><span>Anticipo pagado en línea</span><span>−{{ $dinero($anticipoCita) }}</span></div>@endif
                        @if($saldoUsado > 0)<div class="flex justify-between"><span>Saldo a favor</span><span>−{{ $dinero($saldoUsado) }}</span></div>@endif
                    </div>
                @endif
                <div class="mb-3 flex items-baseline justify-between">
                    <span class="text-sm font-medium text-muted">{{ $anticipoCita > 0 || $saldoUsado > 0 ? 'Por cobrar' : 'Total' }}</span>
                    <span class="text-3xl font-semibold tracking-tight text-ink">{{ $dinero($porCobrar) }}</span>
                </div>
                <button type="button" wire:click="cobrar" wire:loading.attr="disabled" @disabled(!$cantidadItems)
                        class="btn btn-primary w-full !min-h-[48px] text-base">
                    <span wire:loading.remove wire:target="cobrar">{{ $metodoPago === 'fiado' ? 'Registrar fiado' : 'Registrar cobro' }}</span>
                    <span wire:loading wire:target="cobrar">Guardando…</span>
                </button>
            </div>
        </div>

        {{-- Ventas de hoy --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-4 py-3.5 sm:px-5">
                <h2 class="section-title">Últimas ventas de hoy</h2>
                <a href="{{ route('ventas') }}" class="text-xs font-medium text-brand-600 hover:text-brand-800">Ver todas</a>
            </div>
            @forelse($ventasHoy as $v)
                <div class="border-b border-slate-100 px-4 py-3 text-sm last:border-b-0 sm:px-5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium">{{ $v->barbero?->nombre ?? ($v->items->contains('es_membresia', true) ? 'Venta de membresía' : 'Venta directa') }}</span>
                        <span class="font-semibold">{{ $dinero($v->total) }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-muted">{{ $v->items->map(fn($i) => $i->nombre_servicio . ($i->cubierto_membresia ? ' (membresía)' : ''))->implode(', ') }}</span>
                        <span class="badge {{ match ($v->metodo_pago) { 'efectivo' => 'badge-green', 'combinado' => 'badge-violet', 'fiado' => 'badge-amber', default => 'badge-blue' } }}">{{ ucfirst($v->metodo_pago) }}</span>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-muted">Sin ventas registradas hoy.</p>
            @endforelse
        </div>
    </section>

    {{-- Barra flotante en el teléfono para ir al cobro --}}
    @if($cantidadItems)
        <a href="#cobro"
           class="fixed inset-x-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 flex items-center justify-between rounded-xl bg-ink px-4 py-3 text-white shadow-elevada lg:hidden">
            <span class="text-sm text-slate-300">{{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} · <strong class="font-semibold text-white">{{ $dinero($this->totalCarrito) }}</strong></span>
            <span class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-ink">Cobrar</span>
        </a>
    @endif
</div>
