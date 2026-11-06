{{-- Mover una cita a otro día u hora (agenda del dueño, del barbero y panel del cliente) --}}
@php
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
    $paraCliente = $paraCliente ?? false;
    $esActual = fn($h) => $reFecha === $citaReagendar->fecha->toDateString() && $h === $citaReagendar->hora_inicio
        && (int) ($reBarberoId ?: $citaReagendar->barbero_id) === $citaReagendar->barbero_id;
@endphp
<div class="modal-fondo" wire:click.self="cerrarReagendar">
    <form wire:submit="guardarReagendar" class="modal-panel max-w-lg">
        <h3 class="modal-titulo">{{ $paraCliente ? 'Escoger otra hora' : 'Reagendar cita' }}</h3>

        <div class="rounded-lg bg-slate-50 p-3 text-sm">
            <p class="font-semibold text-ink">{{ $paraCliente ? 'Tu cita actual' : $citaReagendar->cliente->nombre }}</p>
            <p class="text-muted">
                {{ ucfirst($citaReagendar->fecha->translatedFormat('l j \d\e F')) }} · {{ $hora12($citaReagendar->hora_inicio) }} – {{ $hora12($citaReagendar->hora_fin) }} con {{ $citaReagendar->barbero->nombre }}
            </p>
            <p class="text-xs text-muted">{{ $citaReagendar->servicios->pluck('nombre')->implode(', ') }} · {{ $citaReagendar->duracion }} min</p>
            @if($citaReagendar->anticipo_estado === 'pagado')
                <p class="mt-1 text-xs font-medium text-emerald-700">El anticipo pagado se pasa a la nueva hora.</p>
            @endif
        </div>

        @error('reagendar')<div class="alert-error">{{ $message }}</div>@enderror
        @error('reHora')<div class="alert-error">{{ $message }}</div>@enderror

        <div class="grid gap-3 sm:grid-cols-2">
            @if($barberosReagendar->isNotEmpty())
                <div>
                    <label class="label" for="re-barbero">Barbero</label>
                    <select id="re-barbero" wire:model.live="reBarberoId" class="input">
                        @foreach($barberosReagendar as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="{{ $barberosReagendar->isEmpty() ? 'sm:col-span-2' : '' }}">
                <label class="label" for="re-fecha">Nuevo día</label>
                <input id="re-fecha" type="date" wire:model.live="reFecha" min="{{ now()->toDateString() }}" class="input">
            </div>
        </div>

        <div>
            <p class="label">Nueva hora</p>
            @if(empty($horasReagendar))
                <p class="rounded-lg bg-slate-50 p-3 text-sm text-muted">No hay horas libres ese día. Prueba con otro día{{ $barberosReagendar->isNotEmpty() ? ' u otro barbero' : '' }}.</p>
            @else
                <div class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4">
                    @foreach($horasReagendar as $h)
                        @if($esActual($h))
                            <span wire:key="re-hora-{{ $h }}" class="rounded-lg bg-slate-100 px-2 py-2 text-center text-sm text-muted line-through" title="Hora actual">{{ $hora12($h) }}</span>
                        @else
                            <button type="button" wire:key="re-hora-{{ $h }}" wire:click="$set('reHora', '{{ $h }}')"
                                    class="rounded-lg px-2 py-2 text-sm font-medium ring-1 ring-inset {{ $reHora === $h ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-ink ring-slate-200 hover:bg-slate-50' }}">
                                {{ $hora12($h) }}
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        @unless($paraCliente)
            <div>
                <label class="label" for="re-motivo">Motivo para el cliente <span class="font-normal text-muted">(opcional)</span></label>
                <input id="re-motivo" type="text" wire:model="reMotivo" maxlength="250" placeholder="Ej.: el barbero tuvo una calamidad" class="input">
                <p class="mt-1 text-xs text-muted">El cliente verá la nueva hora en su enlace y podrá aceptarla, escoger otra o cancelar sin perder el anticipo.</p>
            </div>
        @endunless

        <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
            <button type="button" wire:click="cerrarReagendar" class="btn btn-light">Volver</button>
            <button type="submit" class="btn btn-primary" @disabled(!$reHora) wire:loading.attr="disabled">
                {{ $reHora ? 'Mover a las ' . $hora12($reHora) : 'Escoge una hora' }}
            </button>
        </div>
    </form>
</div>
