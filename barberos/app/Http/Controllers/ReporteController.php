<?php

namespace App\Http\Controllers;

use App\Exports\ReporteExport;
use App\Models\Gasto;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function exportarExcel(int $mes, int $anio)
    {
        $nombreMes = \Carbon\Carbon::create($anio, $mes)->translatedFormat('F_Y');
        $filename  = "reporte_{$nombreMes}.xlsx";

        return Excel::download(new ReporteExport($mes, $anio, $this->barberiaId()), $filename);
    }

    public function exportarPdf(int $mes, int $anio)
    {
        $barberiaId = $this->barberiaId();
        $nombreMes  = \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y');

        $ventas = Venta::with(['barbero', 'items'])
            ->activas()
            ->where('barberia_id', $barberiaId)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        $gastos = Gasto::where('barberia_id', $barberiaId)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        $resumen  = Venta::resumenMes($mes, $anio, $barberiaId);
        $barberia = auth()->user()->barberia;

        // Cómo entró la plata: el pago combinado se reparte entre efectivo y Nequi
        $porMetodo = [
            'Efectivo'              => $ventas->where('metodo_pago', 'efectivo')->sum('total')
                                      + $ventas->where('metodo_pago', 'combinado')->sum('monto_efectivo'),
            'Nequi / transferencia' => $ventas->whereIn('metodo_pago', ['nequi', 'transferencia'])->sum('total')
                                      + $ventas->where('metodo_pago', 'combinado')->sum('monto_nequi'),
        ];

        $porBarbero = $ventas->groupBy('barbero_id')
            ->map(fn ($grupo) => [
                'nombre'   => $grupo->first()->barbero?->nombre ?? 'Venta directa',
                'ventas'   => $grupo->count(),
                'total'    => $grupo->sum('total'),
                'comision' => $grupo->sum('comision_barbero'),
            ])
            ->sortByDesc('total')
            ->values();

        $gastosPorCategoria = $gastos->groupBy('categoria')
            ->map(fn ($grupo, $categoria) => [
                'nombre' => ucfirst(str_replace('_', ' ', $categoria)),
                'total'  => $grupo->sum('valor'),
            ])
            ->sortByDesc('total')
            ->values();

        $ahora = now()->format('d/m/Y h:i a');

        $pdf = Pdf::loadView('reportes.pdf', compact(
            'ventas',
            'gastos',
            'resumen',
            'nombreMes',
            'ahora',
            'barberia',
            'porMetodo',
            'porBarbero',
            'gastosPorCategoria'
        ))->setPaper('a4', 'portrait');

        // Pie de página en cada hoja: barbería y número de página
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('Helvetica');
        $gris   = [0.39, 0.45, 0.55];
        $alto   = $canvas->get_height();
        $pie    = ($barberia?->nombre ?? 'Mi barbería') . " · Reporte de {$nombreMes} · Generado con Kaixa";
        if (method_exists($canvas, 'page_line')) $canvas->page_line(36, $alto - 36, $canvas->get_width() - 36, $alto - 36, [0.9, 0.91, 0.92], 0.6);
        $canvas->page_text(36, $alto - 29, $pie, $fuente, 7.5, $gris);
        $canvas->page_text($canvas->get_width() - 98, $alto - 29, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 7.5, $gris);

        $archivo = \Illuminate\Support\Str::slug(($barberia?->nombre ?? 'barberia') . " reporte {$nombreMes}") . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $archivo . '"',
        ]);
    }
}
