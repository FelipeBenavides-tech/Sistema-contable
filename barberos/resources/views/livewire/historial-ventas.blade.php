@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $fechaCarbon = \Carbon\Carbon::parse($fecha ?: now());
    $metodos = [
        'efectivo'      => ['Efectivo', 'badge-green'],
        'nequi'         => ['Nequi', 'badge-blue'],
        'transferencia' => ['Transferencia', 'badge-blue'],
        'combinado'     => ['Combinado', 'badge-violet'],
    ];
    $activas = $ventas->where('fue_eliminada', false);
@endphp
<div class="flex flex-col gap-4">

    {{-- ─────────── Filtro por día ─────────── --}}
    <div class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="$set('fecha', '{{ $fechaCarbon->copy()->subDay()->toDateString() }}')" class="btn btn-light !px-3" aria-label="Día anterior">‹</button>
            <input wire:model.live="fecha" type="date" class="input flex-1 sm:w-44 sm:flex-none">
            <button type="button" wire:click="$set('fecha', '{{ $fechaCarbon->copy()->addDay()->toDateString() }}')" class="btn btn-light !px-3" aria-label="Día siguiente">›</button>
        </div>
        <p class="text-sm text-muted">{{ ucfirst($fechaCarbon->translatedFormat('l j \d\e F \d\e Y')) }}</p>
    </div>

    {{-- ─────────── Totales del día ─────────── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Total del día</p>
            <p class="kpi-value">{{ $dinero($activas->sum('total')) }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-slate-400"></span>Ventas</p>
            <p class="kpi-value">{{ $activas->count() }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Efectivo</p>
            <p class="kpi-value">{{ $dinero($activas->sum('monto_efectivo')) }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-sky-500"></span>Nequi / transf.</p>
            <p class="kpi-value">{{ $dinero($activas->sum('monto_nequi')) }}</p>
        </div>
    </div>

    {{-- ─────────── Lista de ventas ─────────── --}}
    <div class="tabla">
        <div class="tabla-head md:grid-cols-[70px_1fr_140px_120px_110px_190px]">
            <span>#</span>
            <span>Detalle</span>
            <span>Barbero</span>
            <span>Pago</span>
            <span class="text-right">Total</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($ventas as $venta)
            <div wire:key="venta-{{ $venta->id }}"
                 class="tabla-row md:grid-cols-[70px_1fr_140px_120px_110px_190px] {{ $venta->fue_eliminada ? 'bg-rose-50/40' : ($venta->fue_editada ? 'bg-amber-50/40' : '') }}">

                {{-- Número y estado (en teléfono va junto al total) --}}
                <div class="flex items-center justify-between md:block">
                    <div class="flex items-center gap-2 md:flex-col md:items-start md:gap-1">
                        <span class="text-xs font-semibold text-muted">#{{ $venta->id }} · {{ $venta->created_at->format('h:i a') }}</span>
                        @if($venta->fue_eliminada)
                            <span class="badge badge-red">Eliminada</span>
                        @elseif($venta->fue_editada)
                            <span class="badge badge-amber">Editada</span>
                        @endif
                    </div>
                    <span class="text-lg font-semibold md:hidden {{ $venta->fue_eliminada ? 'text-muted line-through' : 'text-ink' }}">
                        {{ $dinero($venta->fue_eliminada ? $venta->total_original : $venta->total) }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-1 {{ $venta->fue_eliminada ? 'opacity-50' : '' }}">
                    @foreach($venta->items as $item)
                        <span class="badge {{ $item->es_producto ? 'badge-amber' : 'badge-blue' }} {{ $venta->fue_eliminada ? 'line-through' : '' }}">
                            {{ $item->nombre_servicio }}@if($item->cantidad > 1) ×{{ $item->cantidad }}@endif
                        </span>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-2 md:contents">
                    <span class="font-medium {{ $venta->fue_eliminada ? 'text-muted line-through' : '' }}">
                        {{ $venta->barbero?->nombre ?? 'Venta directa' }}
                    </span>
                    <span><span class="badge {{ $metodos[$venta->metodo_pago][1] ?? 'badge-gray' }}">{{ $metodos[$venta->metodo_pago][0] ?? $venta->metodo_pago }}</span></span>
                </div>

                <div class="hidden text-right md:block">
                    @if($venta->fue_eliminada)
                        <span class="font-semibold text-muted line-through">{{ $dinero($venta->total_original) }}</span>
                        <span class="block text-xs text-rose-600">$0</span>
                    @else
                        <span class="font-semibold text-ink">{{ $dinero($venta->total) }}</span>
                        @if($venta->fue_editada && $venta->total_original)
                            <span class="block text-xs text-muted line-through">Antes {{ $dinero($venta->total_original) }}</span>
                        @endif
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="verAuditoria({{ $venta->id }})" class="btn btn-sm btn-success">Ver</button>
                    @unless($venta->fue_eliminada)
                        <button type="button" wire:click="confirmarAccion({{ $venta->id }}, 'editar')" class="btn btn-sm btn-soft">Editar</button>
                        <button type="button" wire:click="confirmarAccion({{ $venta->id }}, 'eliminar')" class="btn btn-sm btn-danger">Eliminar</button>
                    @endunless
                </div>
            </div>
        @empty
            <p class="tabla-vacia">No hay ventas registradas este día.</p>
        @endforelse
    </div>

    {{-- ─────────── Ventana: historial de cambios ─────────── --}}
    @if($mostrarAuditoria)
        <div class="modal-fondo" wire:click.self="cerrarAuditoria">
            <div class="modal-panel sm:max-w-xl">
                <div class="flex items-center justify-between">
                    <h3 class="modal-titulo">Venta #{{ $auditoriaData['venta']['id'] }}</h3>
                    <button type="button" wire:click="cerrarAuditoria" class="btn btn-sm btn-light" aria-label="Cerrar">✕</button>
                </div>

                <div class="rounded-lg border border-line bg-slate-50 p-4">
                    <div class="mb-3 flex flex-wrap gap-1">
                        @foreach($auditoriaData['venta']['items'] as $item)
                            <span class="badge badge-blue">{{ $item['nombre'] }} × {{ $item['cantidad'] }}</span>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-muted">Barbero</p>
                            <p class="font-semibold">{{ $auditoriaData['venta']['barbero'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Pago</p>
                            <p class="font-semibold">{{ $metodos[$auditoriaData['venta']['metodo']][0] ?? $auditoriaData['venta']['metodo'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Total</p>
                            @if($auditoriaData['venta']['eliminada'])
                                <p class="font-semibold text-rose-600"><span class="line-through">{{ $dinero($auditoriaData['venta']['total_orig']) }}</span> $0</p>
                            @else
                                <p class="font-semibold text-ink">{{ $dinero($auditoriaData['venta']['total']) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <p class="card-title">Registro de cambios</p>
                    @forelse($auditoriaData['auditorias'] as $auditoria)
                        @php $eliminada = $auditoria['accion'] === 'eliminada'; @endphp
                        <div class="rounded-xl border p-4 text-sm {{ $eliminada ? 'border-rose-200 bg-rose-50/50' : 'border-amber-200 bg-amber-50/50' }}">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="badge {{ $eliminada ? 'badge-red' : 'badge-amber' }}">{{ ucfirst($auditoria['accion']) }}</span>
                                <span class="text-xs text-muted">{{ $auditoria['fecha'] }} · {{ $auditoria['usuario'] }}</span>
                            </div>
                            <p class="text-xs font-semibold text-muted">Motivo</p>
                            <p class="mb-2">{{ $auditoria['motivo'] }}</p>
                            <div class="grid grid-cols-2 gap-3 border-t border-black/5 pt-2">
                                <div>
                                    <p class="text-xs font-semibold text-muted">Antes</p>
                                    <p>{{ $dinero($auditoria['total_antes']) }}</p>
                                    @if($auditoria['metodo_antes'])
                                        <p class="text-xs text-muted">{{ $metodos[$auditoria['metodo_antes']][0] ?? $auditoria['metodo_antes'] }}</p>
                                    @endif
                                </div>
                                @unless($eliminada)
                                    <div>
                                        <p class="text-xs font-semibold text-muted">Después</p>
                                        <p class="font-semibold text-ink">{{ $dinero($auditoria['total_despues']) }}</p>
                                        @if($auditoria['metodo_despues'])
                                            <p class="text-xs text-muted">{{ $metodos[$auditoria['metodo_despues']][0] ?? $auditoria['metodo_despues'] }}</p>
                                        @endif
                                    </div>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-center text-sm text-muted">Esta venta no ha tenido cambios.</p>
                    @endforelse
                </div>

                @if($auditoriaData['venta']['eliminada'] && $auditoriaData['venta']['tiene_productos'])
                    @if($auditoriaData['venta']['restaurado'])
                        <p class="alert-success">Los productos de esta venta ya se devolvieron al inventario.</p>
                    @else
                        <div class="flex flex-col gap-3 rounded-xl border border-line bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-ink">¿Devolver los productos al inventario?</p>
                            <p class="text-xs text-muted">Úsalo si los productos de esta venta eliminada volvieron a la tienda. Solo se puede hacer una vez.</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="restaurarInventario({{ $auditoriaData['venta']['id'] }}, true)" class="btn btn-primary">Sí, devolver</button>
                                <button type="button" wire:click="restaurarInventario({{ $auditoriaData['venta']['id'] }}, false)" class="btn btn-light">No</button>
                            </div>
                        </div>
                    @endif
                @endif

                <button type="button" wire:click="cerrarAuditoria" class="btn btn-light w-full">Cerrar</button>
            </div>
        </div>
    @endif

    {{-- ─────────── Ventana: confirmar con contraseña ─────────── --}}
    @if($mostrarModal)
        <div class="modal-fondo" wire:click.self="cancelarModal">
            <div class="modal-panel">
                <div>
                    <h3 class="modal-titulo">{{ $accion === 'eliminar' ? 'Eliminar venta' : 'Editar venta' }}</h3>
                    <p class="mt-1 text-sm text-muted">Por seguridad, escribe el motivo y tu contraseña.</p>
                </div>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div>
                    <label class="label" for="motivo">Motivo <span class="text-rose-600">*</span></label>
                    <textarea id="motivo" wire:model.live.debounce.300ms="motivo" rows="3"
                              placeholder="Mínimo 10 letras, sin números ni símbolos"
                              class="input resize-none"></textarea>
                    <p class="mt-1 text-xs {{ mb_strlen($motivo) >= 10 ? 'text-emerald-700' : 'text-muted' }}">{{ mb_strlen($motivo) }}/10 letras mínimo</p>
                </div>

                <div>
                    <label class="label" for="password-confirm">Contraseña <span class="text-rose-600">*</span></label>
                    <input id="password-confirm" wire:model="passwordConfirm" type="password" autocomplete="current-password"
                           wire:keydown.enter="verificarPassword" class="input">
                    @if($errorPassword)
                        <p class="mt-1 text-xs text-rose-600">{{ $errorPassword }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelarModal" class="btn btn-light">Cancelar</button>
                    <button type="button" wire:click="verificarPassword" class="btn {{ $accion === 'eliminar' ? 'btn-danger-solid' : 'btn-primary' }}">
                        {{ $accion === 'eliminar' ? 'Eliminar' : 'Continuar' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ─────────── Ventana: editar venta ─────────── --}}
    @if($mostrarEdicion)
        <div class="modal-fondo">
            <div class="modal-panel">
                <h3 class="modal-titulo">Editar venta #{{ $ventaId }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="edit-barbero">Barbero</label>
                        <select id="edit-barbero" wire:model="editBarberoId" class="input">
                            <option value="0">Sin barbero (venta directa)</option>
                            @foreach($barberos as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="edit-metodo">Método de pago</label>
                        <select id="edit-metodo" wire:model.live="editMetodoPago" class="input">
                            @foreach($metodos as $valor => [$texto])
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($editMetodoPago === 'combinado')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Efectivo</label>
                            <input wire:model="editEfectivo" type="number" inputmode="numeric" min="0" class="input">
                        </div>
                        <div>
                            <label class="label">Nequi</label>
                            <input wire:model="editNequi" type="number" inputmode="numeric" min="0" class="input">
                        </div>
                    </div>
                @endif

                <div>
                    <p class="label">Ítems de la venta</p>
                    <div class="flex flex-col gap-2">
                        @foreach($editItems as $index => $item)
                            <div wire:key="edit-{{ $item['id'] }}" class="flex items-center gap-2 rounded-lg border border-line px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $item['nombre_servicio'] }}</p>
                                    <p class="text-xs text-muted">{{ $dinero($item['precio']) }} c/u</p>
                                </div>
                                <button type="button" wire:click="disminuirCantidad({{ $index }})" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-inset ring-slate-200 hover:text-ink">−</button>
                                <span class="w-6 text-center text-sm font-bold">{{ $item['cantidad'] }}</span>
                                <button type="button" wire:click="aumentarCantidad({{ $index }})" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-inset ring-slate-200 hover:text-ink">+</button>
                                <span class="w-20 text-right text-sm font-semibold text-ink">{{ $dinero($item['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-line pt-3">
                        <span class="text-sm font-medium text-muted">Total</span>
                        <span class="text-xl font-semibold text-ink">{{ $dinero(collect($editItems)->sum('subtotal')) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted">El inventario se ajusta solo cuando guardas.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelarEdicion" class="btn btn-light">Cancelar</button>
                    <button type="button" wire:click="guardarEdicion" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </div>
    @endif
</div>
