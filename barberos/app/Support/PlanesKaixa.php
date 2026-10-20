<?php

namespace App\Support;

/**
 * Catálogo de planes de Kaixa con el ahorro de cada uno frente a pagar mes a mes.
 */
class PlanesKaixa
{
    public static function catalogo(): array
    {
        $mensual = (float) config('suscripcion.planes.mensual.precio');

        return collect(config('suscripcion.planes'))
            ->map(function (array $plan, string $clave) use ($mensual) {
                $sinDescuento = $mensual * $plan['meses'];
                $ahorro       = max(0, $sinDescuento - $plan['precio']);

                return $plan + [
                    'clave'            => $clave,
                    'precio_mes'       => $plan['precio'] / $plan['meses'],
                    'ahorro'           => $ahorro,
                    'ahorro_porcentaje' => $sinDescuento > 0 ? (int) round($ahorro * 100 / $sinDescuento) : 0,
                ];
            })
            ->all();
    }

    // Texto para mostrar en una lista desplegable
    public static function opciones(): array
    {
        $dinero = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
        $opciones = [];

        foreach (self::catalogo() as $clave => $plan) {
            $opciones[$clave] = "{$plan['nombre']} — {$dinero($plan['precio'])}"
                . ($plan['meses'] === 1 ? '/mes (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)' : '')
                . ($plan['ahorro'] > 0 ? " · ahorra {$dinero($plan['ahorro'])}" : '');
        }

        $opciones['porcentaje'] = config('suscripcion.porcentaje.nombre') . ' — '
            . str_replace('.', ',', config('suscripcion.porcentaje.valor')) . '% por cada venta, con recargas (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)';

        return $opciones;
    }
}
