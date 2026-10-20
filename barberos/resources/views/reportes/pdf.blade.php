@php
    $dinero  = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $nombre  = $barberia?->nombre ?? 'Mi barbería';
    $logo    = $barberia?->logoDataUri();
    $datos   = collect([
        $barberia?->nit ? 'NIT ' . $barberia->nit : null,
        $barberia?->direccion,
        $barberia?->telefono ? 'Tel. ' . $barberia->telefono : null,
    ])->filter()->implode('  ·  ');
    $margen   = $resumen['ingresos'] > 0 ? round($resumen['ganancia_neta'] / $resumen['ingresos'] * 100) : 0;
    $metodos  = [
        'efectivo'      => ['Efectivo', 'badge-green'],
        'nequi'         => ['Nequi', 'badge-blue'],
        'transferencia' => ['Transferencia', 'badge-blue'],
        'combinado'     => ['Combinado', 'badge-gray'],
    ];
    $totalMetodos = max(1, array_sum($porMetodo));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $nombre }} · Reporte {{ $nombreMes }}</title>
    <style>
        /* Misma paleta del sistema: azul marino apagado, dorado solo en la marca */
        body, div, p, span, table, tr, td, th, img { margin: 0; padding: 0; }
        @page { margin: 30px 0 76px 0; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #0F172A; line-height: 1.35; }

        .pagina { padding: 0 36px; }

        /* Encabezado */
        .banda { background: #18233A; color: #FFFFFF; padding: 22px 24px 20px; margin: 0 36px; border-radius: 10px; border-bottom: 3px solid #C2A15A; }
        .banda table { width: 100%; border-collapse: collapse; }
        .banda td { vertical-align: middle; }
        .logo { width: 54px; height: 54px; border-radius: 10px; background: #253453; text-align: center; }
        .logo img { width: 54px; height: 54px; border-radius: 10px; background: #FFFFFF; }
        .logo span { display: block; padding-top: 15px; font-size: 20px; font-weight: bold; color: #C2A15A; letter-spacing: 1px; }
        .marca-nombre { font-size: 19px; font-weight: bold; letter-spacing: 0.2px; }
        .marca-datos { font-size: 8.5px; color: #A1B1CF; margin-top: 4px; }
        .doc-tipo { font-size: 8px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; color: #C2A15A; }
        .doc-periodo { font-size: 17px; font-weight: bold; margin-top: 3px; }
        .doc-fecha { font-size: 8.5px; color: #A1B1CF; margin-top: 4px; }

        /* Indicadores */
        .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 22px -8px 4px; }
        .kpi { border: 1px solid #E5E7EB; border-radius: 8px; padding: 11px 12px; background: #FFFFFF; width: 25%; vertical-align: top; }
        .kpi-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; color: #64748B; }
        .kpi-value { font-size: 15px; font-weight: bold; margin-top: 5px; color: #0F172A; }
        .kpi-nota { font-size: 8px; color: #64748B; margin-top: 3px; }
        .dot { display: inline-block; width: 6px; height: 6px; border-radius: 3px; margin-right: 4px; }
        .verde { color: #047857; } .rojo { color: #BE123C; } .azul { color: #2F4267; }
        .bg-verde { background: #10B981; } .bg-rojo { background: #F43F5E; } .bg-azul { background: #4A6396; } .bg-ambar { background: #F59E0B; }

        /* Secciones */
        .seccion { margin-top: 22px; }
        .seccion-titulo { font-size: 11px; font-weight: bold; color: #18233A; padding-bottom: 6px; border-bottom: 1px solid #C9D3E5; margin-bottom: 8px; }
        .seccion-titulo .cuenta { font-weight: normal; color: #64748B; font-size: 9px; }
        .columnas { width: 100%; border-collapse: collapse; }
        .columnas > tbody > tr > td { vertical-align: top; }

        /* Tablas */
        table.datos { width: 100%; border-collapse: collapse; }
        .datos tr { page-break-inside: avoid; }
        .datos thead th { background: #F3F5F9; color: #253453; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.7px; padding: 7px 8px; text-align: left; border-bottom: 1px solid #C9D3E5; }
        .datos thead th.r { text-align: right; } .datos thead th.c { text-align: center; }
        .datos tbody td { padding: 6px 8px; border-bottom: 1px solid #EEF0F3; vertical-align: top; }
        .datos tbody tr.par td { background: #FAFBFC; }
        .datos .total td { background: #18233A; color: #FFFFFF; font-weight: bold; border-bottom: none; padding: 8px; }
        .datos .vacia td { text-align: center; color: #64748B; padding: 16px 8px; }
        .tenue { color: #64748B; }
        .chico { font-size: 8px; }
        .r { text-align: right; } .c { text-align: center; }
        .fuerte { font-weight: bold; }

        /* Estado de resultados */
        .estado td { padding: 6px 8px; border-bottom: 1px solid #EEF0F3; }
        .estado .grupo td { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.7px; color: #253453; background: #F3F5F9; border-bottom: 1px solid #C9D3E5; }
        .estado .sangria { padding-left: 16px; }
        .estado .final td { background: #18233A; color: #FFFFFF; font-weight: bold; font-size: 11px; padding: 9px 8px; border-bottom: none; }

        /* Barras de participación */
        .barra { height: 5px; background: #E4E9F2; border-radius: 3px; margin-top: 3px; }
        .barra div { height: 5px; background: #4A6396; border-radius: 3px; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 7.5px; font-weight: bold; }
        .badge-green { background: #ECFDF5; color: #047857; }
        .badge-blue { background: #E4E9F2; color: #2F4267; }
        .badge-gray { background: #F1F5F9; color: #475569; }

    </style>
</head>
<body>

    {{-- El pie de página (nombre y número de página) lo dibuja el controlador en cada hoja --}}
    {{-- Encabezado con la marca de la barbería --}}
    <div class="banda">
        <table>
            <tr>
                <td style="width: 66px;">
                    <div class="logo">
                        @if($logo)
                            <img src="{{ $logo }}" alt="">
                        @else
                            <span>{{ $barberia?->iniciales ?? 'K' }}</span>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="marca-nombre">{{ $nombre }}</div>
                    @if($datos)<div class="marca-datos">{{ $datos }}</div>@endif
                </td>
                <td class="r" style="width: 190px;">
                    <div class="doc-tipo">Reporte mensual</div>
                    <div class="doc-periodo">{{ ucfirst($nombreMes) }}</div>
                    <div class="doc-fecha">Generado el {{ $ahora }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="pagina">

        {{-- Indicadores del mes --}}
        <table class="kpis">
            <tr>
                <td class="kpi">
                    <div class="kpi-label"><span class="dot bg-verde"></span>Ingresos</div>
                    <div class="kpi-value">{{ $dinero($resumen['ingresos']) }}</div>
                    <div class="kpi-nota">{{ $resumen['cantidad_ventas'] }} {{ $resumen['cantidad_ventas'] === 1 ? 'venta' : 'ventas' }}</div>
                </td>
                <td class="kpi">
                    <div class="kpi-label"><span class="dot bg-ambar"></span>Comisiones</div>
                    <div class="kpi-value">{{ $dinero($resumen['total_comisiones']) }}</div>
                    <div class="kpi-nota">Pagadas a barberos</div>
                </td>
                <td class="kpi">
                    <div class="kpi-label"><span class="dot bg-rojo"></span>Gastos</div>
                    <div class="kpi-value">{{ $dinero($resumen['gastos']) }}</div>
                    <div class="kpi-nota">{{ $gastos->count() }} {{ $gastos->count() === 1 ? 'registro' : 'registros' }}</div>
                </td>
                <td class="kpi">
                    <div class="kpi-label"><span class="dot bg-azul"></span>Ganancia neta</div>
                    <div class="kpi-value {{ $resumen['ganancia_neta'] >= 0 ? 'azul' : 'rojo' }}">{{ $dinero($resumen['ganancia_neta']) }}</div>
                    <div class="kpi-nota">Margen {{ $margen }}% sobre ingresos</div>
                </td>
            </tr>
        </table>

        {{-- Estado de resultados + de dónde vino la plata --}}
        <div class="seccion">
            <table class="columnas">
                <tr>
                    <td style="width: 56%; padding-right: 14px;">
                        <div class="seccion-titulo">Estado de resultados</div>
                        <table class="datos estado">
                            <tr class="grupo"><td colspan="2">Ingresos</td></tr>
                            <tr><td class="sangria">Ventas del mes</td><td class="r">{{ $dinero($resumen['ingresos']) }}</td></tr>
                            <tr><td class="sangria">Comisiones de barberos</td><td class="r rojo">- {{ $dinero($resumen['total_comisiones']) }}</td></tr>
                            <tr><td class="sangria fuerte">Ganancia del local</td><td class="r fuerte">{{ $dinero($resumen['total_local']) }}</td></tr>
                            <tr class="grupo"><td colspan="2">Gastos</td></tr>
                            <tr><td class="sangria">Gastos operativos</td><td class="r rojo">- {{ $dinero($resumen['gastos']) }}</td></tr>
                            <tr class="final"><td>Ganancia neta (ingresos - gastos)</td><td class="r">{{ $dinero($resumen['ganancia_neta']) }}</td></tr>
                        </table>
                    </td>
                    <td style="width: 44%;">
                        <div class="seccion-titulo">Ingresos por medio de pago</div>
                        <table class="datos">
                            @foreach($porMetodo as $metodo => $valor)
                                <tr>
                                    <td>
                                        {{ $metodo }}
                                        <div class="barra"><div style="width: {{ round($valor / $totalMetodos * 100) }}%;"></div></div>
                                    </td>
                                    <td class="r" style="width: 38%;">
                                        <span class="fuerte">{{ $dinero($valor) }}</span><br>
                                        <span class="tenue chico">{{ round($valor / $totalMetodos * 100) }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Rendimiento por barbero --}}
        <div class="seccion">
            <div class="seccion-titulo">Rendimiento por barbero</div>
            <table class="datos">
                <thead>
                    <tr>
                        <th>Barbero</th>
                        <th class="c" style="width: 14%;">Ventas</th>
                        <th class="r" style="width: 20%;">Vendido</th>
                        <th class="r" style="width: 20%;">Comisión</th>
                        <th class="r" style="width: 20%;">Para el local</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($porBarbero as $i => $fila)
                        <tr class="{{ $i % 2 ? 'par' : '' }}">
                            <td class="fuerte">{{ $fila['nombre'] }}</td>
                            <td class="c">{{ $fila['ventas'] }}</td>
                            <td class="r">{{ $dinero($fila['total']) }}</td>
                            <td class="r tenue">{{ $dinero($fila['comision']) }}</td>
                            <td class="r fuerte">{{ $dinero($fila['total'] - $fila['comision']) }}</td>
                        </tr>
                    @empty
                        <tr class="vacia"><td colspan="5">No hubo ventas en {{ $nombreMes }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Detalle de ventas --}}
        <div class="seccion">
            <div class="seccion-titulo">Detalle de ventas <span class="cuenta">· {{ $ventas->count() }} {{ $ventas->count() === 1 ? 'registro' : 'registros' }}</span></div>
            <table class="datos">
                <thead>
                    <tr>
                        <th style="width: 7%;">N.º</th>
                        <th style="width: 11%;">Fecha</th>
                        <th style="width: 16%;">Barbero</th>
                        <th>Servicios y productos</th>
                        <th style="width: 19%;">Pago</th>
                        <th class="r" style="width: 13%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $i => $venta)
                        @php [$etiqueta, $clase] = $metodos[$venta->metodo_pago] ?? [ucfirst($venta->metodo_pago), 'badge-gray']; @endphp
                        <tr class="{{ $i % 2 ? 'par' : '' }}">
                            <td class="tenue">{{ $venta->id }}</td>
                            <td>{{ $venta->fecha->format('d/m/Y') }}</td>
                            <td>{{ $venta->barbero?->nombre ?? 'Venta directa' }}</td>
                            <td>{{ $venta->items->pluck('nombre_servicio')->implode(', ') }}</td>
                            <td>
                                <span class="badge {{ $clase }}">{{ $etiqueta }}</span>
                                @if($venta->metodo_pago === 'combinado')
                                    <div class="tenue chico" style="margin-top: 2px;">Ef. {{ $dinero($venta->monto_efectivo) }} · Nequi {{ $dinero($venta->monto_nequi) }}</div>
                                @endif
                            </td>
                            <td class="r fuerte">{{ $dinero($venta->total) }}</td>
                        </tr>
                    @empty
                        <tr class="vacia"><td colspan="6">No hay ventas registradas en {{ $nombreMes }}.</td></tr>
                    @endforelse
                    @if($ventas->isNotEmpty())
                        <tr class="total">
                            <td colspan="5">Total vendido</td>
                            <td class="r">{{ $dinero($ventas->sum('total')) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Gastos --}}
        <div class="seccion">
            <div class="seccion-titulo">Detalle de gastos <span class="cuenta">· {{ $gastos->count() }} {{ $gastos->count() === 1 ? 'registro' : 'registros' }}</span></div>

            @if($gastosPorCategoria->isNotEmpty())
                <table class="datos" style="margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th style="width: 40%;">Participación</th>
                            <th class="r" style="width: 18%;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gastosPorCategoria as $i => $categoria)
                            @php $porcentaje = $resumen['gastos'] > 0 ? round($categoria['total'] / $resumen['gastos'] * 100) : 0; @endphp
                            <tr class="{{ $i % 2 ? 'par' : '' }}">
                                <td>{{ $categoria['nombre'] }}</td>
                                <td>
                                    <span class="tenue chico">{{ $porcentaje }}%</span>
                                    <div class="barra"><div style="width: {{ $porcentaje }}%;"></div></div>
                                </td>
                                <td class="r">{{ $dinero($categoria['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <table class="datos">
                <thead>
                    <tr>
                        <th style="width: 11%;">Fecha</th>
                        <th>Concepto</th>
                        <th style="width: 20%;">Categoría</th>
                        <th style="width: 14%;">Pago</th>
                        <th class="r" style="width: 14%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastos as $i => $gasto)
                        <tr class="{{ $i % 2 ? 'par' : '' }}">
                            <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                            <td>{{ $gasto->concepto }}</td>
                            <td class="tenue">{{ ucfirst(str_replace('_', ' ', $gasto->categoria)) }}</td>
                            <td class="tenue">{{ ucfirst($gasto->metodo_pago) }}</td>
                            <td class="r fuerte">{{ $dinero($gasto->valor) }}</td>
                        </tr>
                    @empty
                        <tr class="vacia"><td colspan="5">No hay gastos registrados en {{ $nombreMes }}.</td></tr>
                    @endforelse
                    @if($gastos->isNotEmpty())
                        <tr class="total">
                            <td colspan="4">Total gastos</td>
                            <td class="r">{{ $dinero($gastos->sum('valor')) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
