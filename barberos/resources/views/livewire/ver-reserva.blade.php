@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $hora12 = fn($h) => \Carbon\Carbon::createFromFormat('H:i', $h)->format('g:i a');
    [$textoEstado, $claseEstado] = \App\Models\Cita::ESTADOS[$cita->estado];
    $vencida = $cita->estado === 'pendiente_pago' && $cita->expira_en?->isPast();
    $puedeCancelar = in_array($cita->estado, ['confirmada', 'pendiente_pago']) && !$cita->inicio->isPast();
@endphp
<div class="flex flex-col gap-4" @if($cita->estado === 'pendiente_pago' && !$vencida) wire:poll.5s @endif>

    @if($mensaje)
        <div class="alert-success">{{ $mensaje }}</div>
    @endif

    <section class="card card-pad flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="card-title">Tu reserva</p>
                <h1 class="mt-1 text-xl font-semibold text-ink">{{ ucfirst($cita->fecha->translatedFormat('l j \d\e F')) }}</h1>
                <p class="text-sm text-muted">{{ $hora12($cita->hora_inicio) }} – {{ $hora12($cita->hora_fin) }} con {{ $cita->barbero->nombre }}</p>
            </div>
            <span class="badge {{ $claseEstado }} shrink-0">{{ $vencida ? 'Sin pagar' : $textoEstado }}</span>
        </div>

        @if($cita->estado === 'confirmada')
            <div class="alert-success">¡Listo, {{ $cita->cliente->nombre }}! Tu cita está confirmada. Guarda este enlace para verla o cancelarla.</div>
        @elseif($cita->estado === 'pendiente_pago' && !$vencida)
            <div class="alert-warning">
                Estamos esperando la confirmación del pago. Si ya pagaste, esta página se actualiza sola.
                Si no, tienes hasta las {{ $cita->expira_en->format('g:i a') }} para pagar.
            </div>
        @elseif($vencida || ($cita->estado === 'cancelada' && $pago && in_array($pago->estado, ['DECLINED', 'VOIDED', 'ERROR'])))
            <div class="alert-error">El pago no se completó y la hora se liberó. Puedes hacer una nueva reserva.</div>
        @endif

        <div class="rounded-xl bg-slate-50 p-4 text-sm">
            @foreach($cita->servicios as $s)
                <div class="flex justify-between gap-2 py-0.5"><span>{{ $s->nombre }}</span><span>{{ $dinero($s->precio) }}</span></div>
            @endforeach
            <div class="mt-2 flex justify-between gap-2 border-t border-line pt-2 font-semibold"><span>Total</span><span>{{ $dinero($cita->total) }}</span></div>
            @if((float) $cita->anticipo > 0)
                <div class="flex justify-between gap-2 py-0.5 text-muted">
                    <span>Anticipo {{ $cita->anticipo_estado === 'pagado' ? 'pagado' : '' }}{{ (float) $cita->saldo_favor_usado > 0 ? ' (incluye ' . $dinero($cita->saldo_favor_usado) . ' de saldo a favor)' : '' }}</span>
                    <span>{{ $dinero($cita->anticipo) }}</span>
                </div>
                @if($cita->anticipo_estado === 'pagado')
                    <div class="flex justify-between gap-2 font-semibold text-brand-700"><span>Pagas en la barbería</span><span>{{ $dinero((float) $cita->total - (float) $cita->anticipo) }}</span></div>
                @endif
            @endif
        </div>

        @if($barberia->direccion)
            <p class="text-sm text-muted">📍 {{ $barberia->direccion }}</p>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
            @if($cita->estado === 'pendiente_pago' && !$vencida)
                <button type="button" wire:click="pagar" class="btn btn-primary">Ir a pagar</button>
            @endif
            @if($puedeCancelar)
                <button type="button" wire:click="cancelar"
                        wire:confirm="{{ $cita->anticipo_estado === 'pagado' && !$cita->cancela_a_tiempo ? 'Faltan menos de ' . $barberia->horas_cancelacion . ' horas: si cancelas, pierdes el anticipo. ¿Cancelar de todos modos?' : '¿Seguro que quieres cancelar tu cita?' }}"
                        class="btn btn-light">Cancelar cita</button>
            @endif
            @if(in_array($cita->estado, ['cancelada', 'no_asistio']) || $vencida)
                <a href="{{ route('reservar', $barberia->slug) }}" class="btn btn-primary">Hacer una nueva reserva</a>
            @endif
        </div>

        @if((float) $cita->anticipo > 0 && $puedeCancelar)
            <p class="text-xs text-muted">
                Si cancelas con al menos {{ $barberia->horas_cancelacion }} horas de anticipación, el anticipo te queda como saldo a favor. Si cancelas después o no llegas, la barbería se queda con el anticipo.
            </p>
        @endif
    </section>
</div>
