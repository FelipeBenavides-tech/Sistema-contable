@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
@endphp
<div class="flex flex-col gap-4">

    @unless($barbero)
        <div class="alert-warning">Tu usuario no está enlazado a un barbero. Pídele al dueño que lo revise en Barberos.</div>
    @else
        @if($exito)
            <div class="alert-success">{{ $exito }}</div>
        @endif

        <div class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="irA('anterior')" class="btn btn-light !px-3" aria-label="Día anterior">‹</button>
                <input type="date" wire:model.live="fecha" class="input w-auto">
                <button type="button" wire:click="irA('siguiente')" class="btn btn-light !px-3" aria-label="Día siguiente">›</button>
                @unless($dia->isToday())
                    <button type="button" wire:click="irA('hoy')" class="btn btn-soft">Hoy</button>
                @endunless
            </div>
            <div class="sm:text-right">
                <p class="text-sm font-medium text-ink">{{ ucfirst($dia->translatedFormat('l j \d\e F')) }}</p>
                <p class="text-xs text-muted">{{ $horario ? 'Trabajas de ' . $hora12($horario['inicio']) . ' a ' . $hora12($horario['fin']) : 'No trabajas este día' }}</p>
            </div>
        </div>

        {{-- Próximos días --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            @for($i = 0; $i < 7; $i++)
                @php $d = now()->addDays($i); $clave = $d->toDateString(); @endphp
                <button type="button" wire:key="dia-{{ $clave }}" wire:click="$set('fecha', '{{ $clave }}')"
                        class="flex min-w-[64px] flex-col items-center rounded-lg px-2 py-2 text-xs ring-1 ring-inset {{ $dia->toDateString() === $clave ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white ring-slate-200' }}">
                    <span class="font-medium">{{ ucfirst(mb_substr($d->translatedFormat('D'), 0, 3)) }}</span>
                    <span class="text-base font-semibold">{{ $d->day }}</span>
                    <span class="{{ $dia->toDateString() === $clave ? 'text-white/80' : 'text-muted' }}">{{ $semana[$clave] ?? 0 }} citas</span>
                </button>
            @endfor
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
            {{-- Citas --}}
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h2 class="section-title">Mis citas</h2>
                    @if($horario && $dia->gte(today()))
                        <button type="button" wire:click="nuevaCita" class="btn btn-sm btn-accent">+ Agendar</button>
                    @endif
                </div>
                @forelse($citas as $cita)
                    @php [$textoEstado, $claseEstado] = \App\Models\Cita::ESTADOS[$cita->estado]; @endphp
                    <div wire:key="mi-cita-{{ $cita->id }}" class="flex items-start justify-between gap-2 border-b border-slate-100 px-4 py-3 last:border-b-0 {{ in_array($cita->estado, ['cancelada', 'no_asistio']) ? 'opacity-60' : '' }}">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ $hora12($cita->hora_inicio) }} – {{ $hora12($cita->hora_fin) }}</p>
                            <p class="truncate text-sm">{{ $cita->cliente->nombre }}</p>
                            <p class="truncate text-xs text-muted">{{ $cita->servicios->pluck('nombre')->implode(', ') }}</p>
                            @if($cita->notas)<p class="mt-0.5 truncate text-xs text-amber-800">{{ $cita->notas }}</p>@endif
                        </div>
                        <span class="badge {{ $claseEstado }} shrink-0">{{ $textoEstado }}</span>
                    </div>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-muted">No tienes citas este día.</p>
                @endforelse
            </section>

            {{-- Lo que lleva del día --}}
            <section class="card overflow-hidden">
                <div class="border-b border-line px-4 py-3">
                    <h2 class="section-title">Mis servicios del día</h2>
                </div>
                <div class="grid grid-cols-2 gap-2 p-4">
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-muted">Atendidos</p><p class="text-lg font-semibold">{{ $ventas->count() }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-muted">Mi comisión</p><p class="text-lg font-semibold text-emerald-700">{{ $dinero($ventas->sum('comision_barbero')) }}</p></div>
                </div>
                @foreach($ventas as $v)
                    <div wire:key="mi-venta-{{ $v->id }}" class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-2 text-sm">
                        <span class="min-w-0 truncate"><span class="text-muted">{{ $v->created_at->format('g:i a') }}</span> · {{ $v->items->reject(fn($i) => $i->es_producto)->pluck('nombre_servicio')->implode(', ') ?: 'Productos' }}</span>
                        <span class="shrink-0 font-medium">{{ $dinero($v->comision_barbero) }}</span>
                    </div>
                @endforeach
            </section>
        </div>
    @endunless

    @if($mostrarCita)
        @include('livewire.partials.form-cita', ['puedeBuscar' => false, 'barberoFijo' => $barbero?->id])
    @endif
</div>
