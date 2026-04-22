<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte {{ $nombreMes }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a2e; }

        .header { background: #1A3A5C; color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; letter-spacing: 2px; }
        .header p { font-size: 11px; opacity: 0.8; margin-top: 4px; }

        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 8px; }
        .kpi-box { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; text-align: center; width: 33%; }
        .kpi-label { font-size: 10px; color: #666; margin-bottom: 4px; }
        .kpi-value { font-size: 18px; font-weight: bold; }
        .kpi-value.green { color: #2e7d32; }
        .kpi-value.red { color: #c62828; }
        .kpi-value.blue { color: #1A3A5C; }

        .section-title { font-size: 13px; font-weight: bold; color: #1A3A5C; padding: 8px 0; margin-bottom: 8px; border-bottom: 2px solid #1A3A5C; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        thead tr { background: #1A3A5C; color: white; }
        thead th { padding: 8px 10px; text-align: left; font-weight: bold; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody tr:hover { background: #e8f0fe; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #dee2e6; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-green { background: #e8f5e9; color: #2e7d32; }
        .badge-blue { background: #e8f0fe; color: #1565c0; }
        .badge-purple { background: #f3e5f5; color: #6a1b9a; }

        .total-row { background: #1A3A5C !important; color: white; font-weight: bold; }
        .total-row td { color: white; border: none; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #dee2e6; font-size: 10px; color: #999; text-align: center; }

        .resumen-table td { padding: 8px 10px; }
        .resumen-table .label { font-weight: bold; color: #1A3A5C; width: 60%; }
        .resumen-table .section-row { background: #e8f0fe; font-weight: bold; }
        .resumen-table .section-row td { color: #1A3A5C; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <h1>REPORTE MENSUAL</h1>
        <p>Barbería El Estilo &nbsp;·&nbsp; {{ strtoupper($nombreMes) }} &nbsp;·&nbsp; Generado el {{ $ahora }}</p>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="kpi-label">Total ingresos</div>
            <div class="kpi-value green">${{ number_format($resumen['ingresos'], 0, ',', '.') }}</div>
            <div class="kpi-label">{{ $resumen['cantidad_ventas'] }} servicios</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Total gastos</div>
            <div class="kpi-value red">${{ number_format($resumen['gastos'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Ganancia neta</div>
            <div class="kpi-value {{ $resumen['ganancia_neta'] >= 0 ? 'blue' : 'red' }}">
                ${{ number_format($resumen['ganancia_neta'], 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Resumen financiero --}}
    <div class="section-title">Resumen financiero</div>
    <table class="resumen-table">
        <tbody>
            <tr class="section-row"><td colspan="2">INGRESOS</td></tr>
            <tr><td class="label">Total ingresos del mes</td><td class="text-right">${{ number_format($resumen['ingresos'], 0, ',', '.') }}</td></tr>
            <tr><td class="label">Total comisiones pagadas a barberos</td><td class="text-right" style="color:#c62828">${{ number_format($resumen['total_comisiones'], 0, ',', '.') }}</td></tr>
            <tr><td class="label">Ganancia del local (ingresos - comisiones)</td><td class="text-right" style="color:#2e7d32">${{ number_format($resumen['total_local'], 0, ',', '.') }}</td></tr>
            <tr class="section-row"><td colspan="2">GASTOS</td></tr>
            <tr><td class="label">Total gastos operativos</td><td class="text-right" style="color:#c62828">${{ number_format($resumen['gastos'], 0, ',', '.') }}</td></tr>
            <tr class="total-row"><td>GANANCIA NETA (ingresos - gastos)</td><td class="text-right">${{ number_format($resumen['ganancia_neta'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    {{-- Tabla de ventas --}}
    <div class="section-title">Detalle de ventas ({{ $ventas->count() }} registros)</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Barbero</th>
                <th>Servicios</th>
                <th>Método</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
            <tr>
                <td style="color:#999">{{ $venta->id }}</td>
                <td>{{ $venta->fecha->format('d/m/Y') }}</td>
                <td>{{ $venta->barbero?->nombre ?? 'Venta directa' }}</td>
                <td>{{ $venta->items->pluck('nombre_servicio')->implode(', ') }}</td>
                <td>
    @if($venta->metodo_pago === 'efectivo')
        <span class="badge badge-green">Efectivo</span>
    @elseif($venta->metodo_pago === 'nequi')
        <span class="badge badge-blue">Nequi / Transferencia</span>
    @elseif($venta->metodo_pago === 'transferencia')
        <span class="badge badge-blue">Transferencia</span>
    @elseif($venta->metodo_pago === 'combinado')
        <span class="badge badge-purple">Combinado</span>
        <br>
        <small style="font-size:9px;color:#555">
            Efectivo: ${{ number_format($venta->monto_efectivo, 0, ',', '.') }}
            &nbsp;·&nbsp;
            Nequi: ${{ number_format($venta->monto_nequi, 0, ',', '.') }}
        </small>
    @endif
</td>
                <td class="text-right"><strong>${{ number_format($venta->total, 0, ',', '.') }}</strong></td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">TOTAL</td>
                <td class="text-right">${{ number_format($ventas->sum('total'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Tabla de gastos --}}
    <div class="section-title">Detalle de gastos ({{ $gastos->count() }} registros)</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Categoría</th>
                <th>Método</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gastos as $gasto)
            <tr>
                <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                <td>{{ $gasto->concepto }}</td>
                <td>{{ str_replace('_', ' ', $gasto->categoria) }}</td>
                <td>{{ $gasto->metodo_pago }}</td>
                <td class="text-right" style="color:#c62828">
                    <strong>${{ number_format($gasto->valor, 0, ',', '.') }}</strong>
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL GASTOS</td>
                <td class="text-right">${{ number_format($gastos->sum('valor'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        BarberOS · Sistema de gestión para barberías · Reporte generado automáticamente
    </div>

</body>
</html>
