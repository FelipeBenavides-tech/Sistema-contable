@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $maxServicio = max(1, (float) $topServicios->max('total_valor'));
@endphp
<div class="flex flex-col gap-4">

    {{-- Mes y descargas --}}
    <div class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="grid grid-cols-2 gap-2 sm:flex">
            <select wire:model.live="mes" class="input sm:w-40" aria-label="Mes">
                @foreach($meses as $i => $nombre)
                    <option value="{{ $i + 1 }}">{{ $nombre }}</option>
                @endforeach
            </select>
            <select wire:model.live="anio" class="input sm:w-28" aria-label="Año">
                @foreach($anios as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex">
            <a href="{{ route('reportes.excel', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-light">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Excel
            </a>
            <a href="{{ route('reportes.pdf', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-light">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                PDF
            </a>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Ingresos</p>
            <p class="kpi-value">{{ $dinero($resumenMes['ingresos']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $resumenMes['cantidad_ventas'] }} ventas</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-rose-500"></span>Gastos</p>
            <p class="kpi-value">{{ $dinero($resumenMes['gastos']) }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-amber-500"></span>Comisiones</p>
            <p class="kpi-value">{{ $dinero($resumenMes['total_comisiones']) }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Ganancia neta</p>
            <p class="kpi-value {{ $resumenMes['ganancia_neta'] >= 0 ? '' : 'text-rose-600' }}">{{ $dinero($resumenMes['ganancia_neta']) }}</p>
            <p class="mt-1 text-xs text-muted">Ingresos − gastos</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Más vendidos --}}
        <div class="card card-pad">
            <p class="section-title mb-3">Lo más vendido</p>
            @forelse($topServicios as $s)
                <div class="py-2">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="truncate font-medium">{{ $s->nombre_servicio }} <span class="text-xs text-muted">× {{ $s->total_cant }}</span></span>
                        <span class="font-semibold text-ink">{{ $dinero($s->total_valor) }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ round($s->total_valor / $maxServicio * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-muted">Sin ventas este mes.</p>
            @endforelse
        </div>

        {{-- Comisiones --}}
        <div class="card card-pad">
            <p class="section-title mb-3">Comisiones por barbero</p>
            @forelse($comisionesBarberos as $b)
                <div class="flex items-center justify-between gap-3 border-b border-line py-2.5 last:border-b-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $b['barbero'] }}</p>
                        <p class="text-xs text-muted">{{ $b['servicios'] }} ventas · generó {{ $dinero($b['total_generado']) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold">{{ $dinero($b['comision_total']) }}</p>
                        <p class="text-xs text-muted">{{ rtrim(rtrim(number_format($b['porcentaje'], 2, ',', ''), '0'), ',') }}%</p>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-muted">Sin ventas con barbero este mes.</p>
            @endforelse
        </div>
    </div>

    {{-- Gastos por categoría --}}
    <div class="card card-pad">
        <p class="section-title mb-3">Gastos por categoría</p>
        @forelse($gastosPorCategoria as $g)
            <div class="flex items-center justify-between border-b border-line py-2.5 text-sm last:border-b-0">
                <span class="capitalize">{{ str_replace('_', ' ', $g->categoria) }}</span>
                <span class="font-semibold">{{ $dinero($g->total) }}</span>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-muted">Sin gastos este mes.</p>
        @endforelse
    </div>
</div>
