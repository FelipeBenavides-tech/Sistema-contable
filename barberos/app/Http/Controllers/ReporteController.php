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

        $resumen = Venta::resumenMes($mes, $anio, $barberiaId);

        $ahora = now()->format('d/m/Y h:i:s a');

        $pdf = Pdf::loadView('reportes.pdf', compact(
            'ventas',
            'gastos',
            'resumen',
            'nombreMes',
            'ahora'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("reporte_{$mes}_{$anio}.pdf");
    }
}
