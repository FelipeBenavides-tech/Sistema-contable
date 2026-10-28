@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
@endphp
<div class="flex flex-col gap-4">

    <div class="segmentado w-full sm:w-auto sm:self-start">
        <button type="button" wire:click="$set('tab', 'agenda')" class="segmento {{ $tab === 'agenda' ? 'segmento-activo' : '' }}">Agenda</button>
        <button type="button" wire:click="$set('tab', 'horarios')" class="segmento {{ $tab === 'horarios' ? 'segmento-activo' : '' }}">Horarios de barberos</button>
    </div>

    @if($exito)
        <div class="alert-success">{{ $exito }}</div>
    @endif
    @error('agenda')
        <div class="alert-error">{{ $message }}</div>
    @enderror

    @if($tab === 'agenda')
        {{-- Día y filtros --}}
        <div class="card card-pad flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="irA('anterior')" class="btn btn-light !px-3" aria-label="Día anterior">‹</button>
                <input type="date" wire:model.live="fecha" class="input w-auto">
                <button type="button" wire:click="irA('siguiente')" class="btn btn-light !px-3" aria-label="Día siguiente">›</button>
                @unless($dia->isToday())
                    <button type="button" wire:click="irA('hoy')" class="btn btn-soft">Hoy</button>
                @endunless
            </div>
            <p class="text-sm font-medium text-ink">{{ ucfirst($dia->translatedFormat('l j \d\e F')) }}</p>
            <div class="flex items-center gap-2">
                <select wire:model.live="barberoFiltro" class="input w-full lg:w-48" aria-label="Filtrar por barbero">
                    <option value="0">Todos los barberos</option>
                    @foreach($barberos as $b)
                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="nuevaCita({{ $barberoFiltro }})" class="btn btn-accent shrink-0">+ Nueva cita</button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Citas</p><p class="kpi-value">{{ $resumen['citas'] }}</p></div>
            <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-sky-500"></span>Por atender</p><p class="kpi-value">{{ $resumen['pendientes'] }}</p></div>
            <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Atendidas</p><p class="kpi-value">{{ $resumen['atendidas'] }}</p></div>
            <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-slate-400"></span>Valor agendado</p><p class="kpi-value">{{ $dinero($resumen['valor']) }}</p></div>
        </div>

        @if($barberos->isEmpty())
            <p class="tabla-vacia card">Agrega barberos para poder agendar citas.</p>
        @else
            {{-- Una columna por barbero --}}
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($columnas as $col)
                    <section wire:key="col-{{ $col['barbero']->id }}" class="card overflow-hidden">
                        <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                            <div class="min-w-0">
                                <h2 class="section-title truncate">{{ $col['barbero']->nombre }}</h2>
                                <p class="text-xs text-muted">
                                    {{ $col['horario'] ? $hora12($col['horario']['inicio']) . ' – ' . $hora12($col['horario']['fin']) : 'No trabaja este día' }}
                                </p>
                            </div>
                            @if($col['horario'])
                                <button type="button" wire:click="nuevaCita({{ $col['barbero']->id }})" class="btn btn-sm btn-soft shrink-0">+ Cita</button>
                            @endif
                        </div>
                        @forelse($col['citas'] as $cita)
                            @php [$textoEstado, $claseEstado] = \App\Models\Cita::ESTADOS[$cita->estado]; @endphp
                            <div wire:key="cita-{{ $cita->id }}" class="border-b border-slate-100 px-4 py-3 last:border-b-0 {{ in_array($cita->estado, ['cancelada', 'no_asistio']) ? 'opacity-60' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold">{{ $hora12($cita->hora_inicio) }} – {{ $hora12($cita->hora_fin) }}</p>
                                        <p class="truncate text-sm">{{ $cita->cliente->nombre }}
                                            @if($cita->cliente->telefono)<a href="tel:{{ $cita->cliente->telefono }}" class="text-xs text-muted hover:text-brand-700">· {{ $cita->cliente->telefono }}</a>@endif
                                        </p>
                                        <p class="truncate text-xs text-muted">{{ $cita->servicios->pluck('nombre')->implode(', ') }} · {{ $dinero($cita->total) }}</p>
                                        @if($cita->notas)<p class="mt-0.5 truncate text-xs text-amber-800">{{ $cita->notas }}</p>@endif
                                    </div>
                                    <span class="badge {{ $claseEstado }} shrink-0">{{ $textoEstado }}</span>
                                </div>
                                @if($cita->estado === 'confirmada')
                                    <div class="mt-2 grid grid-cols-3 gap-1.5">
                                        <a href="{{ route('pos', ['cita' => $cita->id]) }}" class="btn btn-sm btn-primary">Cobrar</a>
                                        <button type="button" wire:click="cambiarEstado({{ $cita->id }}, 'no_asistio')" wire:confirm="¿Marcar que {{ $cita->cliente->nombre }} no asistió?" class="btn btn-sm btn-light">No vino</button>
                                        <button type="button" wire:click="cambiarEstado({{ $cita->id }}, 'cancelada')" wire:confirm="¿Cancelar la cita de {{ $cita->cliente->nombre }}?" class="btn btn-sm btn-danger">Cancelar</button>
                                    </div>
                                @elseif(in_array($cita->estado, ['cancelada', 'no_asistio']) && $dia->gte(today()))
                                    <button type="button" wire:click="cambiarEstado({{ $cita->id }}, 'confirmada')" class="mt-2 text-xs font-medium text-brand-600 hover:text-brand-800">Volver a confirmar</button>
                                @endif
                            </div>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-muted">{{ $col['horario'] ? 'Sin citas.' : '—' }}</p>
                        @endforelse
                    </section>
                @endforeach
            </div>
        @endif
    @else
        {{-- Horarios --}}
        <p class="text-sm text-muted">Las citas solo se pueden agendar dentro del horario de cada barbero. Si a un barbero no se le configura, usa el horario general (lunes a sábado de 9:00 a. m. a 7:00 p. m.).</p>
        <div class="tabla">
            <div class="tabla-head md:grid-cols-[200px_1fr_140px]">
                <span>Barbero</span>
                <span>Horario</span>
                <span class="text-right">Acciones</span>
            </div>
            @forelse($barberos as $b)
                <div wire:key="horario-{{ $b->id }}" class="tabla-row md:grid-cols-[200px_1fr_140px]">
                    <span class="font-semibold">{{ $b->nombre }}</span>
                    <span class="text-sm text-muted">
                        @foreach(\App\Models\HorarioBarbero::DIAS as $num => $nombreDia)
                            @php $h = $b->horarioDelDia($num); @endphp
                            @if($h)<span class="mr-3 inline-block">{{ mb_substr($nombreDia, 0, 3) }} {{ $h['inicio'] }}–{{ $h['fin'] }}</span>@endif
                        @endforeach
                        @if($b->horarios->isEmpty())<span class="badge badge-gray">Horario general</span>@endif
                    </span>
                    <div class="md:text-right">
                        <button type="button" wire:click="editarHorario({{ $b->id }})" class="btn btn-sm btn-soft">Editar horario</button>
                    </div>
                </div>
            @empty
                <p class="tabla-vacia">Aún no hay barberos activos.</p>
            @endforelse
        </div>
    @endif

    {{-- Editar horario --}}
    @if($barberoHorario)
        <div class="modal-fondo" wire:click.self="cerrarHorario">
            <form wire:submit="guardarHorario" class="modal-panel">
                <h3 class="modal-titulo">Horario de {{ $barberoHorario->nombre }}</h3>
                @error('horario')<div class="alert-error">{{ $message }}</div>@enderror
                <div class="flex flex-col gap-2">
                    @foreach(\App\Models\HorarioBarbero::DIAS as $num => $nombreDia)
                        <div wire:key="dia-{{ $num }}" class="grid grid-cols-[110px_1fr_1fr] items-center gap-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model.live="horario.{{ $num }}.trabaja" class="h-4 w-4 rounded border-line text-brand-700">
                                {{ $nombreDia }}
                            </label>
                            @if(!empty($horario[$num]['trabaja']))
                                <input type="time" wire:model="horario.{{ $num }}.inicio" class="input" aria-label="Entrada {{ $nombreDia }}">
                                <input type="time" wire:model="horario.{{ $num }}.fin" class="input" aria-label="Salida {{ $nombreDia }}">
                            @else
                                <span class="col-span-2 text-sm text-muted">No trabaja</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cerrarHorario" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif

    @if($mostrarCita)
        @include('livewire.partials.form-cita', ['barberos' => $barberos])
    @endif
</div>
