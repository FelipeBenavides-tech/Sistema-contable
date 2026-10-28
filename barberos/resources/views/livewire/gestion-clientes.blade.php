@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
@endphp
<div class="flex flex-col gap-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre o teléfono" class="input sm:w-72">
            <div class="segmentado w-full sm:w-auto">
                @foreach(['todos' => 'Todos', 'cartera' => 'En cartera', 'cumpleanos' => 'Cumplen este mes'] as $valor => $texto)
                    <button type="button" wire:click="$set('filtro', '{{ $valor }}')" class="segmento {{ $filtro === $valor ? 'segmento-activo' : '' }}">{{ $texto }}</button>
                @endforeach
            </div>
        </div>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ Nuevo cliente</button>
    </div>

    @if($aviso)
        <div class="alert-warning">{{ $aviso }}</div>
    @endif

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_150px_90px_120px_220px]">
            <span>Cliente</span>
            <span>Teléfono</span>
            <span class="text-center">Visitas</span>
            <span class="text-right">Debe</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($clientes as $c)
            <div wire:key="cliente-{{ $c->id }}" class="tabla-row md:grid-cols-[1fr_150px_90px_120px_220px]">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $c->nombre }}</p>
                    <p class="flex flex-wrap gap-1 pt-0.5">
                        @if($c->permite_fiado)<span class="badge badge-amber">Cartera</span>@endif
                        @if($c->fecha_nacimiento?->month === now()->month)<span class="badge badge-violet">Cumple el {{ $c->fecha_nacimiento->format('d/m') }}</span>@endif
                    </p>
                </div>
                <span class="text-muted">@if($c->telefono)<a href="tel:{{ $c->telefono }}" class="hover:text-brand-700">{{ $c->telefono }}</a>@else — @endif</span>
                <span class="md:text-center"><span class="dato">Visitas:</span>{{ $c->ventas_count }}</span>
                <span class="md:text-right {{ (float) $c->deuda_total > 0 ? 'font-semibold text-rose-600' : 'text-muted' }}"><span class="dato">Debe:</span>{{ (float) $c->deuda_total > 0 ? $dinero($c->deuda_total) : '—' }}</span>
                <div class="grid grid-cols-3 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="verDetalle({{ $c->id }})" class="btn btn-sm btn-soft">Ver</button>
                    <button type="button" wire:click="editar({{ $c->id }})" class="btn btn-sm btn-light">Editar</button>
                    <button type="button" wire:click="eliminar({{ $c->id }})" wire:confirm="¿Eliminar a {{ $c->nombre }}?" class="btn btn-sm btn-danger">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">
                @if(trim($buscar) !== '' || $filtro !== 'todos') No hay clientes con ese filtro.
                @else Aún no hay clientes. Se agregan aquí, al vender una membresía o al agendar una cita.
                @endif
            </p>
        @endforelse
    </div>

    {{-- Detalle del cliente --}}
    @if($detalle)
        @php $c = $detalle['cliente']; @endphp
        <div class="modal-fondo" wire:click.self="cerrarDetalle">
            <div class="modal-panel max-w-lg">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="modal-titulo">{{ $c->nombre }}</h3>
                        <p class="text-sm text-muted">{{ $c->telefono ?: 'Sin teléfono' }}{{ $c->email ? ' · ' . $c->email : '' }}</p>
                    </div>
                    <button type="button" wire:click="cerrarDetalle" class="text-sm text-muted hover:text-ink" aria-label="Cerrar">✕</button>
                </div>

                <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-2.5"><dt class="text-xs text-muted">Visitas</dt><dd class="font-semibold">{{ $detalle['visitas'] }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-2.5"><dt class="text-xs text-muted">Ha gastado</dt><dd class="font-semibold">{{ $dinero($detalle['gastado']) }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-2.5"><dt class="text-xs text-muted">Debe</dt><dd class="font-semibold {{ $c->deuda > 0 ? 'text-rose-600' : '' }}">{{ $dinero($c->deuda) }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-2.5"><dt class="text-xs text-muted">Última visita</dt><dd class="font-semibold">{{ $detalle['ultima'] ? \Carbon\Carbon::parse($detalle['ultima'])->format('d/m/Y') : '—' }}</dd></div>
                </dl>

                <div class="flex flex-col gap-1 text-sm">
                    @if($detalle['membresia'])
                        <p><span class="text-muted">Membresía:</span> {{ $detalle['membresia']->nombre_plan }} · quedan {{ $detalle['membresia']->visitas_restantes }} visitas · vence {{ $detalle['membresia']->fecha_vencimiento->format('d/m/Y') }}</p>
                    @endif
                    @if($c->permite_fiado)
                        <p><span class="text-muted">Cartera:</span> {{ $c->servicios_fiados }} de {{ $c->limite_fiado }} servicios fiados{{ $c->fiado_bloqueado ? ' · debe pagar todo para volver a fiarle' : '' }}</p>
                    @endif
                    @if($c->fecha_nacimiento)
                        <p><span class="text-muted">Cumpleaños:</span> {{ $c->fecha_nacimiento->translatedFormat('j \d\e F') }}</p>
                    @endif
                    @if($esPro && $detalle['inasistencias'])
                        <p class="text-rose-600">No asistió a {{ $detalle['inasistencias'] }} {{ $detalle['inasistencias'] === 1 ? 'cita' : 'citas' }}.</p>
                    @endif
                    <p><span class="text-muted">Mensajes y promociones:</span> {{ $c->acepta_mensajes ? 'Autorizados' : 'No autorizados' }}</p>
                    @if($c->notas)
                        <p class="rounded-lg bg-amber-50 p-2.5 text-amber-900">{{ $c->notas }}</p>
                    @endif
                </div>

                @if($esPro && $detalle['proximas']->isNotEmpty())
                    <div>
                        <p class="card-title mb-1">Próximas citas</p>
                        @foreach($detalle['proximas'] as $cita)
                            <p class="text-sm">{{ $cita->fecha->format('d/m') }} · {{ $cita->hora_inicio }} con {{ $cita->barbero->nombre }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <p class="card-title mb-1">Últimas visitas</p>
                    @forelse($detalle['recientes'] as $v)
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-1.5 text-sm last:border-b-0">
                            <span class="min-w-0 truncate"><span class="text-muted">{{ $v->fecha->format('d/m/Y') }}</span> · {{ $v->items->pluck('nombre_servicio')->implode(', ') }}</span>
                            <span class="shrink-0 font-medium {{ $v->es_fiada && (float) $v->saldo_pendiente > 0 ? 'text-rose-600' : '' }}">{{ $dinero($v->total) }}{{ $v->es_fiada ? ' · fiado' : '' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Sin visitas registradas en la caja.</p>
                    @endforelse
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    @if($c->deuda > 0)
                        <a href="{{ route('cartera') }}" class="btn btn-light">Ver cartera</a>
                    @endif
                    <button type="button" wire:click="editar({{ $c->id }})" class="btn btn-primary">Editar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Formulario --}}
    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? 'Editar cliente' : 'Nuevo cliente' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="cli-nombre">Nombre *</label>
                        <input id="cli-nombre" wire:model="nombre" type="text" class="input" autofocus>
                    </div>
                    <div>
                        <label class="label" for="cli-telefono">Celular (WhatsApp)</label>
                        <input id="cli-telefono" wire:model="telefono" type="tel" inputmode="tel" placeholder="3001234567" class="input">
                    </div>
                    <div>
                        <label class="label" for="cli-email">Correo</label>
                        <input id="cli-email" wire:model="email" type="email" inputmode="email" class="input">
                    </div>
                    <div>
                        <label class="label" for="cli-nacimiento">Fecha de nacimiento</label>
                        <input id="cli-nacimiento" wire:model="fecha_nacimiento" type="date" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="cli-notas">Notas</label>
                        <textarea id="cli-notas" wire:model="notas" rows="2" placeholder="Ej. le gusta el degradado bajo" class="input"></textarea>
                    </div>
                    <label class="flex items-start gap-3 sm:col-span-2">
                        <input wire:model="permite_fiado" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-line text-brand-700 focus:ring-brand-500">
                        <span class="text-sm">
                            Se le puede fiar (cliente en cartera)
                            <span class="block text-xs text-muted">Paga después de {{ auth()->user()->barberia->limite_fiado }} servicios. Al llegar a ese número no se le fía más hasta que pague todo.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 sm:col-span-2">
                        <input wire:model="acepta_mensajes" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-line text-brand-700 focus:ring-brand-500">
                        <span class="text-sm">
                            Autoriza el uso de sus datos y recibir mensajes y promociones
                            <span class="block text-xs text-muted">Ley 1581 de 2012. Sin esta autorización no se le deben enviar promociones.</span>
                        </span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
