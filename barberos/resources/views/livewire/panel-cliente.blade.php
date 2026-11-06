@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
    $nombreCorto = \Illuminate\Support\Str::of($cliente->nombre)->explode(' ')->first();
@endphp
<div class="flex flex-col gap-4" @if($proximas->contains('estado', 'pendiente_pago')) wire:poll.10s @endif>

    {{-- Encabezado del panel --}}
    <section class="card card-pad flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="card-title">Mis citas</p>
            <h1 class="mt-1 text-xl font-semibold text-ink">Hola, {{ $nombreCorto }}</h1>
            <p class="text-sm text-muted">Aquí ves tus reservas en {{ $barberia->nombre }}, pagas el anticipo y las cambias o cancelas.</p>
        </div>
        <a href="{{ route('reservar', ['slug' => $barberia->slug, 'c' => $cliente->tokenPanel()]) }}" class="btn btn-primary !min-h-[48px] shrink-0 text-base">+ Reservar una cita</a>
    </section>

    <div class="grid grid-cols-2 gap-3">
        <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Próximas citas</p><p class="kpi-value">{{ $proximas->count() }}</p></div>
        <div class="kpi"><p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Saldo a favor</p><p class="kpi-value {{ (float) $cliente->saldo_favor > 0 ? 'text-emerald-700' : '' }}">{{ $dinero($cliente->saldo_favor) }}</p></div>
    </div>
    @if((float) $cliente->saldo_favor > 0)
        <p class="-mt-2 text-xs text-muted">Tu saldo a favor se descuenta solo del anticipo de tu próxima reserva y también lo puedes usar al pagar en la barbería.</p>
    @endif

    @if($exito)
        <div class="alert-success">{{ $exito }}</div>
    @endif

    {{-- Próximas citas --}}
    <section class="flex flex-col gap-3">
        <h2 class="section-title">Próximas citas</h2>
        @forelse($proximas as $cita)
            @php
                [$textoEstado, $claseEstado] = \App\Models\Cita::ESTADOS[$cita->estado];
                $desde = $cita->reagendada_desde ?? [];
                $antes = isset($desde['fecha'], $desde['hora_inicio']) ? \Carbon\Carbon::parse($desde['fecha'] . ' ' . $desde['hora_inicio']) : null;
                $porPagar = $cita->estado === 'pendiente_pago' ? (float) $cita->anticipo - (float) $cita->saldo_favor_usado : 0;
            @endphp
            <article wire:key="prox-{{ $cita->id }}" class="card card-pad flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-ink">{{ ucfirst($cita->fecha->translatedFormat('l j \d\e F')) }}</p>
                        <p class="text-sm text-muted">{{ $hora12($cita->hora_inicio) }} – {{ $hora12($cita->hora_fin) }} con {{ $cita->barbero->nombre }}</p>
                        <p class="truncate text-xs text-muted">{{ $cita->servicios->pluck('nombre')->implode(', ') }} · {{ $dinero($cita->total) }}</p>
                    </div>
                    <span class="badge {{ $cita->espera_respuesta ? 'badge-amber' : $claseEstado }} shrink-0">{{ $cita->espera_respuesta ? 'Cambio de hora' : $textoEstado }}</span>
                </div>

                @if($cita->espera_respuesta)
                    <div class="alert-warning flex flex-col gap-1">
                        <p class="font-semibold">La barbería no puede atenderte a la hora que habías reservado.</p>
                        @if($antes)<p>Tenías la cita el {{ $antes->translatedFormat('l j \d\e F') }} a las {{ $antes->format('g:i a') }}. Te la pasamos a la hora de arriba.</p>@endif
                        @if($cita->reagendo_motivo)<p>Motivo: {{ $cita->reagendo_motivo }}</p>@endif
                        <p class="text-xs">Si no te sirve, escoge otra hora o cancela: no pierdes el anticipo y recibes un descuento del {{ $barberia->descuento_cancelacion }}% como saldo a favor.</p>
                    </div>
                @endif

                @if($cita->estado === 'pendiente_pago')
                    <div class="alert-warning">
                        Falta pagar el anticipo de {{ $dinero($porPagar) }}. Tienes hasta las {{ $cita->expira_en->format('g:i a') }}; después la hora se libera.
                    </div>
                @elseif((float) $cita->anticipo > 0 && $cita->anticipo_estado === 'pagado')
                    <div class="flex justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <span class="text-muted">Anticipo pagado {{ $dinero($cita->anticipo) }}</span>
                        <span class="font-semibold text-brand-700">Pagas allá {{ $dinero((float) $cita->total - (float) $cita->anticipo) }}</span>
                    </div>
                @endif

                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
                    @if($cita->estado === 'pendiente_pago')
                        <button type="button" wire:click="pagar({{ $cita->id }})" class="btn btn-primary">Pagar {{ $dinero($porPagar) }}</button>
                    @endif
                    @if($cita->espera_respuesta)
                        <button type="button" wire:click="aceptar({{ $cita->id }})" class="btn btn-primary">Me sirve la nueva hora</button>
                    @endif
                    @if($cita->cliente_puede_reagendar)
                        <button type="button" wire:click="abrirReagendar({{ $cita->id }})" class="btn btn-soft">Escoger otra hora</button>
                    @endif
                    @if(!$cita->inicio->isPast())
                        <button type="button" wire:click="cancelar({{ $cita->id }})"
                                wire:confirm="{{ $cita->anticipo_estado === 'pagado' && !$cita->cancela_a_tiempo && !$cita->espera_respuesta ? 'Faltan menos de ' . $barberia->horas_cancelacion . ' horas: si cancelas, pierdes el anticipo. ¿Cancelar de todos modos?' : '¿Seguro que quieres cancelar esta cita?' }}"
                                class="btn btn-light">Cancelar</button>
                    @endif
                </div>
                @if($cita->estado === 'confirmada' && !$cita->cliente_puede_reagendar && !$cita->inicio->isPast())
                    <p class="text-xs text-muted">Faltan menos de {{ $barberia->horas_cancelacion }} horas: ya no puedes cambiar la hora desde aquí. Escríbele a la barbería si necesitas algo.</p>
                @endif
            </article>
        @empty
            <div class="card card-pad text-center">
                <p class="text-sm text-muted">No tienes citas próximas.</p>
                <a href="{{ route('reservar', ['slug' => $barberia->slug, 'c' => $cliente->tokenPanel()]) }}" class="btn btn-soft mt-3">Reservar ahora</a>
            </div>
        @endforelse
    </section>

    {{-- Historial --}}
    @if($historial->isNotEmpty())
        <section class="card overflow-hidden">
            <div class="border-b border-line px-4 py-3"><h2 class="section-title">Citas anteriores</h2></div>
            @foreach($historial as $cita)
                @php
                    $vencida = $cita->estado === 'pendiente_pago';
                    [$textoEstado, $claseEstado] = $vencida ? ['Sin pagar', 'badge-gray'] : \App\Models\Cita::ESTADOS[$cita->estado];
                @endphp
                <div wire:key="hist-{{ $cita->id }}" class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 text-sm last:border-b-0">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $cita->fecha->translatedFormat('d M Y') }} · {{ $hora12($cita->hora_inicio) }}</p>
                        <p class="truncate text-xs text-muted">{{ $cita->servicios->pluck('nombre')->implode(', ') }} · {{ $cita->barbero->nombre }}</p>
                    </div>
                    <span class="badge {{ $claseEstado }} shrink-0">{{ $textoEstado }}</span>
                </div>
            @endforeach
        </section>
    @endif

    <p class="text-center text-xs text-muted">Guarda este enlace: es tu acceso personal a tus citas. No lo compartas.</p>

    @if($citaReagendar)
        @include('livewire.partials.form-reagendar', ['paraCliente' => true])
    @endif
</div>
