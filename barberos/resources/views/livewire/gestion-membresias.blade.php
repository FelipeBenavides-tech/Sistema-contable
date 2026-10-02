@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $estados = [
        'activa'  => ['Activa', 'badge-green'],
        'agotada' => ['Sin visitas', 'badge-gray'],
        'vencida' => ['Vencida', 'badge-amber'],
        'anulada' => ['Anulada', 'badge-red'],
    ];
    $planesActivos = $planes->where('activo', true);
    $planVenta = $planes->firstWhere('id', $ventaPlanId);
@endphp
<div class="flex flex-col gap-4">

    {{-- Resumen y acciones --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Membresías activas</p>
            <p class="kpi-value">{{ $totalActivas }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Visitas por usar</p>
            <p class="kpi-value">{{ $visitasPendientes }}</p>
        </div>
        <div class="col-span-2 flex items-center justify-end gap-2">
            <button type="button" wire:click="nuevoPlan" class="btn btn-light">+ Plan</button>
            <button type="button" wire:click="nuevaVenta" class="btn btn-primary" @disabled($planesActivos->isEmpty())>Vender membresía</button>
        </div>
    </div>

    @if($exito)
        <div class="alert-success">{{ $exito }}</div>
    @endif
    @if($aviso)
        <div class="alert-warning">{{ $aviso }}</div>
    @endif

    <div class="segmentado w-full sm:w-auto sm:self-start">
        <button type="button" wire:click="$set('tab', 'membresias')" class="segmento {{ $tab === 'membresias' ? 'segmento-activo' : '' }}">Clientes</button>
        <button type="button" wire:click="$set('tab', 'planes')" class="segmento {{ $tab === 'planes' ? 'segmento-activo' : '' }}">Planes ({{ $planes->count() }})</button>
    </div>

    @if($tab === 'membresias')
        {{-- ─────────── Membresías de clientes ─────────── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Buscar cliente por nombre o teléfono" class="input sm:max-w-xs">
            <div class="segmentado w-full sm:w-auto">
                <button type="button" wire:click="$set('filtro', 'activas')" class="segmento {{ $filtro === 'activas' ? 'segmento-activo' : '' }}">Activas</button>
                <button type="button" wire:click="$set('filtro', 'todas')" class="segmento {{ $filtro === 'todas' ? 'segmento-activo' : '' }}">Todas</button>
            </div>
        </div>

        <div class="tabla">
            <div class="tabla-head md:grid-cols-[1fr_1fr_130px_120px_110px_190px]">
                <span>Cliente</span>
                <span>Plan</span>
                <span class="text-center">Visitas</span>
                <span>Vence</span>
                <span class="text-center">Estado</span>
                <span class="text-right">Acciones</span>
            </div>

            @forelse($membresias as $m)
                @php [$estadoTexto, $estadoClase] = $estados[$m->estado]; @endphp
                <div wire:key="membresia-{{ $m->id }}" class="tabla-row md:grid-cols-[1fr_1fr_130px_120px_110px_190px]">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $m->cliente->nombre }}</p>
                            @if($m->cliente->telefono)<p class="text-xs text-muted">{{ $m->cliente->telefono }}</p>@endif
                        </div>
                        <span class="badge {{ $estadoClase }} md:hidden">{{ $estadoTexto }}</span>
                    </div>
                    <div class="text-sm">
                        <p class="truncate">{{ $m->nombre_plan }}</p>
                        <p class="text-xs text-muted">{{ $dinero($m->precio) }} · desde {{ $m->fecha_inicio->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex items-center gap-2 md:flex-col md:items-center md:gap-1">
                        <span class="text-sm font-semibold">{{ $m->visitas_usadas }} / {{ $m->visitas_total }}</span>
                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                            <span class="block h-full rounded-full bg-brand-600" style="width: {{ $m->visitas_total ? round($m->visitas_usadas / $m->visitas_total * 100) : 0 }}%"></span>
                        </span>
                        <span class="text-xs text-muted md:hidden">· vence {{ $m->fecha_vencimiento->format('d/m/Y') }}</span>
                    </div>
                    <span class="hidden text-sm md:block">{{ $m->fecha_vencimiento->format('d/m/Y') }}</span>
                    <span class="hidden md:block md:text-center"><span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span></span>
                    <div class="grid grid-cols-2 gap-2 md:flex md:justify-end md:gap-1">
                        <button type="button" wire:click="verDetalle({{ $m->id }})" class="btn btn-sm btn-light">Visitas</button>
                        <button type="button" wire:click="nuevaVenta({{ $m->cliente_id }})" class="btn btn-sm btn-soft">Renovar</button>
                    </div>
                </div>
            @empty
                <p class="tabla-vacia">
                    @if($planesActivos->isEmpty())
                        Primero crea un plan, por ejemplo "Mensual 5 cortes", y luego véndelo a tus clientes.
                    @elseif($filtro === 'activas')
                        No hay membresías activas{{ $buscar ? ' con ese nombre' : '' }}.
                    @else
                        No hay membresías{{ $buscar ? ' con ese nombre' : '' }}.
                    @endif
                </p>
            @endforelse
        </div>
    @else
        {{-- ─────────── Planes ─────────── --}}
        <div class="tabla">
            <div class="tabla-head md:grid-cols-[1fr_120px_100px_100px_100px_260px]">
                <span>Plan</span>
                <span class="text-right">Precio</span>
                <span class="text-center">Visitas</span>
                <span class="text-center">Duración</span>
                <span class="text-center">Estado</span>
                <span class="text-right">Acciones</span>
            </div>

            @forelse($planes as $plan)
                <div wire:key="plan-{{ $plan->id }}" class="tabla-row md:grid-cols-[1fr_120px_100px_100px_100px_260px]">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $plan->nombre }}</p>
                            <p class="text-xs text-muted">{{ $plan->membresias_count }} {{ $plan->membresias_count === 1 ? 'vendida' : 'vendidas' }}</p>
                        </div>
                        <span class="text-base font-semibold md:hidden">{{ $dinero($plan->precio) }}</span>
                    </div>
                    <span class="hidden text-right font-semibold md:block">{{ $dinero($plan->precio) }}</span>
                    <div class="flex items-center gap-2 text-sm md:contents">
                        <span class="md:text-center">{{ $plan->visitas }} {{ $plan->visitas === 1 ? 'visita' : 'visitas' }}</span>
                        <span class="text-muted md:text-center md:text-ink">· {{ $plan->duracion_dias }} días</span>
                        <span class="md:text-center"><span class="badge {{ $plan->activo ? 'badge-green' : 'badge-red' }}">{{ $plan->activo ? 'Activo' : 'Inactivo' }}</span></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 md:flex md:justify-end md:gap-1">
                        <button type="button" wire:click="editarPlan({{ $plan->id }})" class="btn btn-sm btn-soft">Editar</button>
                        <button type="button" wire:click="togglePlan({{ $plan->id }})" class="btn btn-sm btn-light">{{ $plan->activo ? 'Desactivar' : 'Activar' }}</button>
                        <button type="button" wire:click="eliminarPlan({{ $plan->id }})" wire:confirm="¿Seguro que deseas eliminar este plan?" class="btn btn-sm btn-danger">Eliminar</button>
                    </div>
                </div>
            @empty
                <p class="tabla-vacia">Aún no hay planes. Crea uno, por ejemplo "Mensual 5 cortes": 30 días y 5 visitas.</p>
            @endforelse
        </div>
    @endif

    {{-- ─────────── Modal: plan ─────────── --}}
    @if($mostrarPlan)
        <div class="modal-fondo" wire:click.self="cancelarPlan">
            <form wire:submit="guardarPlan" class="modal-panel">
                <h3 class="modal-titulo">{{ $planId ? 'Editar plan' : 'Nuevo plan de membresía' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label class="label" for="planNombre">Nombre</label>
                        <input id="planNombre" wire:model="planNombre" type="text" placeholder="Mensual 5 cortes" class="input" autofocus>
                    </div>
                    <div>
                        <label class="label" for="planPrecio">Precio (COP)</label>
                        <input id="planPrecio" wire:model="planPrecio" type="number" inputmode="numeric" min="0" step="1000" class="input">
                    </div>
                    <div>
                        <label class="label" for="planVisitas">Visitas</label>
                        <input id="planVisitas" wire:model="planVisitas" type="number" inputmode="numeric" min="1" class="input">
                    </div>
                    <div>
                        <label class="label" for="planDias">Duración (días)</label>
                        <input id="planDias" wire:model="planDias" type="number" inputmode="numeric" min="1" class="input">
                    </div>
                </div>
                <p class="text-xs text-muted">La membresía se cierra cuando se acaban las visitas o pasan los días, lo que ocurra primero. Cambiar el plan no afecta las membresías ya vendidas.</p>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelarPlan" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ─────────── Modal: vender membresía ─────────── --}}
    @if($mostrarVenta)
        <div class="modal-fondo" wire:click.self="cancelarVenta">
            <form wire:submit="venderMembresia" class="modal-panel">
                <h3 class="modal-titulo">Vender membresía</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                {{-- Cliente --}}
                <div>
                    <p class="label">Cliente</p>
                    @if($clienteElegido)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $clienteElegido->nombre }}</p>
                                @if($clienteElegido->telefono)<p class="text-xs text-muted">{{ $clienteElegido->telefono }}</p>@endif
                            </div>
                            <button type="button" wire:click="cambiarCliente" class="text-xs font-medium text-brand-600 hover:text-brand-800">Cambiar</button>
                        </div>
                    @else
                        <input type="search" wire:model.live.debounce.300ms="buscarClienteVenta" placeholder="Buscar cliente existente" class="input">
                        @if($clientesEncontrados->isNotEmpty())
                            <div class="mt-2 overflow-hidden rounded-lg ring-1 ring-inset ring-slate-200">
                                @foreach($clientesEncontrados as $c)
                                    <button type="button" wire:key="cli-{{ $c->id }}" wire:click="elegirCliente({{ $c->id }})"
                                            class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-slate-50">
                                        <span class="truncate font-medium">{{ $c->nombre }}</span>
                                        <span class="text-xs text-muted">{{ $c->telefono }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <p class="mt-3 text-xs font-medium text-muted">O registra un cliente nuevo:</p>
                        <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                            <input wire:model="nuevoNombre" type="text" placeholder="Nombre" class="input" aria-label="Nombre del cliente">
                            <input wire:model="nuevoTelefono" type="tel" inputmode="tel" placeholder="Teléfono (opcional)" class="input" aria-label="Teléfono del cliente">
                        </div>
                    @endif
                </div>

                {{-- Plan --}}
                <div>
                    <label class="label" for="ventaPlanId">Plan</label>
                    <select id="ventaPlanId" wire:model.live="ventaPlanId" class="input">
                        <option value="0">Seleccionar…</option>
                        @foreach($planesActivos as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->nombre }} · {{ $dinero($plan->precio) }}</option>
                        @endforeach
                    </select>
                    @if($planVenta)
                        <p class="mt-1.5 text-xs text-muted">{{ $planVenta->visitas }} visitas durante {{ $planVenta->duracion_dias }} días, hasta el {{ now()->addDays(max(1, $planVenta->duracion_dias) - 1)->format('d/m/Y') }}.</p>
                    @endif
                </div>

                {{-- Pago --}}
                <div>
                    <p class="label">Método de pago</p>
                    <div class="segmentado flex w-full">
                        @foreach(['efectivo' => 'Efectivo', 'nequi' => 'Nequi', 'combinado' => 'Combinado'] as $valor => $texto)
                            <button type="button" wire:click="$set('metodoPago', '{{ $valor }}')"
                                    class="segmento {{ $metodoPago === $valor ? 'segmento-activo' : '' }}">{{ $texto }}</button>
                        @endforeach
                    </div>
                    @if($metodoPago === 'combinado')
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <label class="label" for="vEfectivo">Efectivo</label>
                                <input id="vEfectivo" wire:model="montoEfectivo" type="number" inputmode="numeric" min="0" step="1000" class="input">
                            </div>
                            <div>
                                <label class="label" for="vNequi">Nequi</label>
                                <input id="vNequi" wire:model="montoNequi" type="number" inputmode="numeric" min="0" step="1000" class="input">
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-baseline justify-between border-t border-line pt-3">
                    <span class="text-sm font-medium text-muted">Total a cobrar</span>
                    <span class="text-2xl font-semibold text-ink">{{ $dinero($planVenta?->precio ?? 0) }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelarVenta" class="btn btn-light">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">Registrar venta</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ─────────── Modal: visitas de una membresía ─────────── --}}
    @if($detalle)
        <div class="modal-fondo" wire:click.self="cerrarDetalle">
            <div class="modal-panel">
                <div>
                    <h3 class="modal-titulo">{{ $detalle->cliente->nombre }}</h3>
                    <p class="text-sm text-muted">{{ $detalle->nombre_plan }} · {{ $detalle->fecha_inicio->format('d/m/Y') }} al {{ $detalle->fecha_vencimiento->format('d/m/Y') }}</p>
                </div>

                <p class="text-sm">
                    Usó <strong>{{ $detalle->visitas_usadas }}</strong> de {{ $detalle->visitas_total }} visitas.
                    @if($detalle->vigente) Le quedan {{ $detalle->visitas_restantes }}. @endif
                </p>

                <div class="overflow-hidden rounded-lg ring-1 ring-inset ring-slate-200">
                    @forelse($detalle->usos->sortByDesc('created_at') as $uso)
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5 text-sm last:border-b-0 {{ $uso->venta?->fue_eliminada ? 'opacity-50 line-through' : '' }}">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $uso->nombre_servicio }}{{ $uso->cantidad > 1 ? ' ×' . $uso->cantidad : '' }}</p>
                                <p class="text-xs text-muted">{{ $uso->venta?->barbero?->nombre ?? 'Sin barbero' }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-muted">{{ $uso->created_at->format('d/m/Y h:i a') }}</span>
                        </div>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-muted">Todavía no ha usado visitas.</p>
                    @endforelse
                </div>

                @if($detalle->anulada)
                    <p class="text-xs text-rose-600">Esta membresía se anuló porque se eliminó su venta en el historial.</p>
                @else
                    <p class="text-xs text-muted">Para anularla, elimina su venta en el historial de ventas del {{ $detalle->fecha_inicio->format('d/m/Y') }}.</p>
                @endif

                <div class="flex justify-end">
                    <button type="button" wire:click="cerrarDetalle" class="btn btn-light">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
