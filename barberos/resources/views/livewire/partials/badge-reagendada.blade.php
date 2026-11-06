{{-- Aviso de que la cita se movió de hora (agendas del dueño y del barbero) --}}
@if($cita->reagendada_en && in_array($cita->estado, ['confirmada', 'atendida']))
    @php
        $desde = $cita->reagendada_desde ?? [];
        $antes = isset($desde['fecha'], $desde['hora_inicio'])
            ? \Carbon\Carbon::parse($desde['fecha'] . ' ' . $desde['hora_inicio'])->translatedFormat('d/m g:i a') : null;
    @endphp
    <p class="mt-0.5 flex flex-wrap items-center gap-1">
        @if($cita->reagendada_por === 'cliente')
            <span class="badge badge-violet">Reagendada por el cliente</span>
        @elseif($cita->reagendo_respuesta === 'pendiente')
            <span class="badge badge-amber">Reagendada · esperando al cliente</span>
        @else
            <span class="badge badge-green">Reagendada · cliente aceptó</span>
        @endif
        @if($antes)<span class="text-xs text-muted">Antes: {{ $antes }}</span>@endif
    </p>
@endif
