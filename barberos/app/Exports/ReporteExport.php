<?php

namespace App\Exports;

use App\Models\Gasto;
use App\Models\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteExport implements WithMultipleSheets
{
    public function __construct(
        private int $mes,
        private int $anio,
        private int $barberiaId = 0
    ) {}

    public function sheets(): array
    {
        return [
            new IngresosSheet($this->mes, $this->anio, $this->barberiaId),
            new GastosSheet($this->mes, $this->anio, $this->barberiaId),
            new ResumenSheet($this->mes, $this->anio, $this->barberiaId),
        ];
    }
}

// ── Hoja 1: Ingresos ──────────────────────────────────────────────────────────
class IngresosSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private int $mes,
        private int $anio,
        private int $barberiaId = 0
    ) {}

    public function title(): string
    {
        return 'Ingresos';
    }

    public function headings(): array
    {
        return ['#', 'Fecha', 'Barbero', 'Servicios', 'Método de pago', 'Efectivo', 'Nequi/Transfer', 'Total'];
    }

    public function collection()
    {
        return Venta::with(['barbero', 'items'])
            ->where('barberia_id', $this->barberiaId)
            ->whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->orderBy('fecha')
            ->get()
            ->map(function ($v) {
                return [
                    $v->id,
                    $v->fecha->format('d/m/Y'),
                    $v->barbero?->nombre ?? 'Venta directa',
                    $v->items->pluck('nombre_servicio')->implode(', '),
                    $v->metodo_pago,
                    $v->monto_efectivo,
                    $v->monto_nequi,
                    $v->total,
                ];
            });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A3A5C']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 12,
            'C' => 20,
            'D' => 40,
            'E' => 18,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }
}

// ── Hoja 2: Gastos ────────────────────────────────────────────────────────────
class GastosSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private int $mes,
        private int $anio,
        private int $barberiaId = 0
    ) {}

    public function title(): string
    {
        return 'Gastos';
    }

    public function headings(): array
    {
        return ['#', 'Fecha', 'Concepto', 'Categoría', 'Método de pago', 'Valor'];
    }

    public function collection()
    {
        return Gasto::where('barberia_id', $this->barberiaId)
            ->whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->orderBy('fecha')
            ->get()
            ->map(function ($g) {
                return [
                    $g->id,
                    $g->fecha->format('d/m/Y'),
                    $g->concepto,
                    $g->categoria,
                    $g->metodo_pago,
                    $g->valor,
                ];
            });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A3A5C']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 12,
            'C' => 35,
            'D' => 20,
            'E' => 18,
            'F' => 15,
        ];
    }
}

// ── Hoja 3: Resumen ───────────────────────────────────────────────────────────
class ResumenSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private int $mes,
        private int $anio,
        private int $barberiaId = 0
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Concepto', 'Valor'];
    }

    public function collection()
    {
        $resumen    = Venta::resumenMes($this->mes, $this->anio, $this->barberiaId);
        $nombreMes  = \Carbon\Carbon::create($this->anio, $this->mes)->translatedFormat('F Y');

        return collect([
            ['Período',                                  $nombreMes],
            ['',                                         ''],
            ['INGRESOS',                                 ''],
            ['Total ingresos',                           $resumen['ingresos']],
            ['Total comisiones barberos',                $resumen['total_comisiones']],
            ['Ganancia del local',                       $resumen['total_local']],
            ['',                                         ''],
            ['GASTOS',                                   ''],
            ['Total gastos',                             $resumen['gastos']],
            ['',                                         ''],
            ['RESULTADO',                                ''],
            ['Ganancia neta',                            $resumen['ganancia_neta']],
            ['Total servicios vendidos',                 $resumen['cantidad_ventas']],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A3A5C']]
            ],
            3 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E8F0FE']]
            ],
            8 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FCE8E8']]
            ],
            11 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E8F5E9']]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 20,
        ];
    }
}
