{{-- Formulario para agendar una cita (agenda del dueño y del barbero) --}}
@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $puedeBuscar = $puedeBuscar ?? true;
    $totalCita = $serviciosCita->whereIn('id', array_map('intval', $citaServicios))->sum('precio');
@endphp
<div class="modal-fondo" wire:click.self="cerrarCita">
    <form wire:submit="guardarCita" class="modal-panel max-w-lg">
        <h3 class="modal-titulo">Nueva cita</h3>

        @if($errors->any())
            <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        {{-- Cliente --}}
        <div>
            <p class="label">Cliente *</p>
            @if($citaClienteId)
                <div class="flex items-center justify-between gap-3 rounded-lg bg-brand-50 p-3 ring-1 ring-inset ring-brand-600/15">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $citaNombre }}</span>
                        @if($citaTelefono)<span class="block text-xs text-muted">{{ $citaTelefono }}</span>@endif
                    </span>
                    <button type="button" wire:click="quitarClienteCita" class="text-xs font-medium text-muted hover:text-rose-600">Cambiar</button>
                </div>
            @else
                @if($puedeBuscar)
                    <input type="search" wire:model.live.debounce.300ms="citaBuscarCliente" placeholder="Buscar cliente existente" class="input">
                    @if(mb_strlen(trim($citaBuscarCliente)) >= 2)
                        <div class="mt-2 overflow-hidden rounded-lg ring-1 ring-inset ring-slate-200">
                            @forelse($clientesCita as $c)
                                <button type="button" wire:key="cita-cli-{{ $c->id }}" wire:click="elegirClienteCita({{ $c->id }})"
                                        class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-slate-50">
                                    <span class="truncate font-medium">{{ $c->nombre }}</span>
                                    <span class="shrink-0 text-xs text-muted">{{ $c->telefono }}</span>
                                </button>
                            @empty
                                <p class="px-3 py-2 text-center text-xs text-muted">No está registrado. Escríbelo abajo.</p>
                            @endforelse
                        </div>
                    @endif
                    <p class="mt-2 text-xs text-muted">O escribe uno nuevo:</p>
                @endif
                <div class="mt-1 grid gap-2 sm:grid-cols-2">
                    <input wire:model="citaNombre" type="text" placeholder="Nombre" class="input" aria-label="Nombre del cliente">
                    <input wire:model="citaTelefono" type="tel" inputmode="tel" placeholder="Celular" class="input" aria-label="Celular del cliente">
                </div>
                @unless($puedeBuscar)
                    <p class="mt-1 text-xs text-muted">Si el celular ya está registrado, la cita queda a nombre de ese cliente.</p>
                @endunless
            @endif
        </div>

        {{-- Barbero --}}
        @if(empty($barberoFijo))
            <div>
                <label class="label" for="citaBarbero">Barbero *</label>
                <select id="citaBarbero" wire:model.live="citaBarberoId" class="input">
                    <option value="0">Seleccionar…</option>
                    @foreach($barberos as $b)
                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Servicios --}}
        <div>
            <p class="label">Servicios *</p>
            <div class="grid gap-1.5 sm:grid-cols-2">
                @forelse($serviciosCita as $s)
                    <label wire:key="cita-srv-{{ $s->id }}" class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-inset {{ in_array($s->id, array_map('intval', $citaServicios)) ? 'bg-brand-50 ring-brand-500' : 'ring-slate-200 hover:bg-slate-50' }}">
                        <input type="checkbox" wire:model.live="citaServicios" value="{{ $s->id }}" class="h-4 w-4 rounded border-line text-brand-700">
                        <span class="min-w-0 flex-1 truncate">{{ $s->nombre }}</span>
                        <span class="shrink-0 text-xs text-muted">{{ $s->duracion_minutos }} min</span>
                    </label>
                @empty
                    <p class="text-sm text-muted">No hay servicios activos. Agrégalos en Servicios.</p>
                @endforelse
            </div>
            @if($duracionCita)
                <p class="mt-1 text-xs text-muted">Duración {{ $duracionCita }} min · {{ $dinero($totalCita) }}</p>
            @endif
        </div>

        {{-- Fecha y hora --}}
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="label" for="citaFecha">Fecha *</label>
                <input id="citaFecha" wire:model.live="citaFecha" type="date" min="{{ now()->toDateString() }}" class="input">
            </div>
            <div>
                <label class="label" for="citaHora">Hora *</label>
                <select id="citaHora" wire:model="citaHora" class="input" @disabled(!$duracionCita || !$citaBarberoId)>
                    <option value="">{{ !$citaBarberoId || !$duracionCita ? 'Escoge barbero y servicios' : (empty($horasLibres) ? 'Sin horas libres ese día' : 'Seleccionar…') }}</option>
                    @foreach($horasLibres as $h)
                        <option value="{{ $h }}">{{ \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a') }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="label" for="citaNotas">Notas</label>
            <input id="citaNotas" wire:model="citaNotas" type="text" placeholder="Opcional" class="input">
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <button type="button" wire:click="cerrarCita" class="btn btn-light">Cancelar</button>
            <button type="submit" class="btn btn-primary">Agendar</button>
        </div>
    </form>
</div>
