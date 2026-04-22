<div class="flex flex-col gap-5">

    {{-- Selector de mes + botones exportación --}}
    <div class="flex gap-3 items-center justify-between flex-wrap">
        <div class="flex gap-3">
            <select wire:model.live="mes"
                    class="rounded-lg px-3 py-2.5 text-sm outline-none"
                    style="background:var(--surface);border:1px solid var(--border);color:var(--text)">
                <option value="1">Enero</option>
                <option value="2">Febrero</option>
                <option value="3">Marzo</option>
                <option value="4">Abril</option>
                <option value="5">Mayo</option>
                <option value="6">Junio</option>
                <option value="7">Julio</option>
                <option value="8">Agosto</option>
                <option value="9">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
            </select>
            <select wire:model.live="anio"
                    class="rounded-lg px-3 py-2.5 text-sm outline-none"
                    style="background:var(--surface);border:1px solid var(--border);color:var(--text)">
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
            </select>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('reportes.excel', ['mes' => $mes, 'anio' => $anio]) }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2"
               style="background:#2e7d32;color:white">
                Descargar Excel
            </a>
            <a href="{{ route('reportes.pdf', ['mes' => $mes, 'anio' => $anio]) }}"
               class="px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2"
               style="background:#1A3A5C;color:white">
                Descargar PDF
            </a>
        </div>
    </div>

    {{-- KPIs del mes --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-2" style="color:var(--muted)">Ingresos del mes</p>
            <p class="text-2xl font-bold" style="color:var(--success)">
                ${{ number_format($resumenMes['ingresos'], 0, ',', '.') }}
            </p>
            <p class="text-xs mt-1" style="color:var(--muted)">{{ $resumenMes['cantidad_ventas'] }} servicios</p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-2" style="color:var(--muted)">Gastos del mes</p>
            <p class="text-2xl font-bold" style="color:var(--danger)">
                ${{ number_format($resumenMes['gastos'], 0, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-2" style="color:var(--muted)">Ganancia neta</p>
            <p class="text-2xl font-bold"
               style="color:{{ $resumenMes['ganancia_neta'] >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                ${{ number_format($resumenMes['ganancia_neta'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-5">

        {{-- Servicios más vendidos --}}
        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-medium" style="color:var(--muted)">Servicios más vendidos</h3>
            <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
                <div class="px-4 py-3 text-xs font-semibold grid gap-2"
                     style="background:#F8FAFC;color:var(--muted);border-bottom:1px solid var(--border);
                            grid-template-columns:1fr 60px 90px">
                    <span>Servicio</span>
                    <span class="text-center">Cant.</span>
                    <span class="text-right">Total</span>
                </div>
                @forelse($topServicios as $s)
                <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
                     style="border-color:var(--border);grid-template-columns:1fr 60px 90px">
                    <span>{{ $s->nombre_servicio }}</span>
                    <span class="text-center" style="color:var(--muted)">{{ $s->total_cant }}</span>
                    <span class="text-right font-semibold" style="color:var(--primary)">
                        ${{ number_format($s->total_valor, 0, ',', '.') }}
                    </span>
                </div>
                @empty
                <div class="px-4 py-6 text-center text-sm" style="color:var(--muted)">
                    Sin datos este mes
                </div>
                @endforelse
            </div>
        </div>

        {{-- Comisiones por barbero --}}
        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-medium" style="color:var(--muted)">Comisiones por barbero</h3>
            <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
                <div class="px-4 py-3 text-xs font-semibold grid gap-2"
                     style="background:#F8FAFC;color:var(--muted);border-bottom:1px solid var(--border);
                            grid-template-columns:1fr 60px 90px">
                    <span>Barbero</span>
                    <span class="text-center">Serv.</span>
                    <span class="text-right">Comisión</span>
                </div>
                @forelse($comisionesBarberos as $b)
                @if($b['total_generado'] > 0)
                <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
                     style="border-color:var(--border);grid-template-columns:1fr 60px 90px">
                    <div>
                        <p class="font-medium">{{ $b['barbero'] }}</p>
                        <p class="text-xs" style="color:var(--muted)">
                            Generó ${{ number_format($b['total_generado'], 0, ',', '.') }}
                        </p>
                    </div>
                    <span class="text-center" style="color:var(--muted)">{{ $b['servicios'] }}</span>
                    <span class="text-right font-semibold" style="color:var(--danger)">
                        ${{ number_format($b['comision_total'], 0, ',', '.') }}
                    </span>
                </div>
                @endif
                @empty
                <div class="px-4 py-6 text-center text-sm" style="color:var(--muted)">
                    Sin datos este mes
                </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Gastos por categoría --}}
    <div class="flex flex-col gap-3">
        <h3 class="text-sm font-medium" style="color:var(--muted)">Gastos por categoría</h3>
        <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
            <div class="px-4 py-3 text-xs font-semibold grid gap-2"
                 style="background:#F8FAFC;color:var(--muted);border-bottom:1px solid var(--border);
                        grid-template-columns:1fr 100px">
                <span>Categoría</span>
                <span class="text-right">Total</span>
            </div>
            @forelse($gastosPorCategoria as $g)
            <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
                 style="border-color:var(--border);grid-template-columns:1fr 100px">
                <span class="capitalize">{{ str_replace('_', ' ', $g->categoria) }}</span>
                <span class="text-right font-semibold" style="color:var(--danger)">
                    ${{ number_format($g->total, 0, ',', '.') }}
                </span>
            </div>
            @empty
            <div class="px-4 py-6 text-center text-sm" style="color:var(--muted)">
                Sin gastos este mes
            </div>
            @endforelse
        </div>
    </div>

</div>
