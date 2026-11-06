<?php

namespace App\Support;

/**
 * Catálogo de planes de Kaixa con el ahorro de cada uno frente a pagar mes a mes.
 */
class PlanesKaixa
{
    public static function catalogo(): array
    {
        $mensual    = (float) config('suscripcion.planes.mensual.precio');
        $mensualPro = (float) config('suscripcion.planes.mensual.precio_pro', $mensual);

        return collect(config('suscripcion.planes'))
            ->map(function (array $plan, string $clave) use ($mensual, $mensualPro) {
                $sinDescuento = $mensual * $plan['meses'];
                $ahorro       = max(0, $sinDescuento - $plan['precio']);
                $precioPro    = (float) ($plan['precio_pro'] ?? $plan['precio']);
                $ahorroPro    = max(0, $mensualPro * $plan['meses'] - $precioPro);

                return $plan + [
                    'clave'            => $clave,
                    'precio_mes'       => $plan['precio'] / $plan['meses'],
                    'ahorro'           => $ahorro,
                    'ahorro_porcentaje' => $sinDescuento > 0 ? (int) round($ahorro * 100 / $sinDescuento) : 0,
                    'precio_pro'       => $precioPro,
                    'precio_mes_pro'   => $precioPro / $plan['meses'],
                    'ahorro_pro'       => $ahorroPro,
                ];
            })
            ->all();
    }

    // Texto para mostrar en una lista desplegable (precios del nivel indicado)
    public static function opciones(string $nivel = 'base'): array
    {
        $dinero = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
        $pro = $nivel === 'pro';
        $opciones = [];

        foreach (self::catalogo() as $clave => $plan) {
            $precio = $pro ? $plan['precio_pro'] : $plan['precio'];
            $ahorro = $pro ? $plan['ahorro_pro'] : $plan['ahorro'];
            $opciones[$clave] = "{$plan['nombre']} — {$dinero($precio)}"
                . ($plan['meses'] === 1 ? '/mes (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)' : '')
                . ($ahorro > 0 ? " · ahorra {$dinero($ahorro)}" : '');
        }

        $porcentaje = config($pro ? 'suscripcion.porcentaje.valor_pro' : 'suscripcion.porcentaje.valor');
        $opciones['porcentaje'] = config('suscripcion.porcentaje.nombre') . ' — '
            . str_replace('.', ',', $porcentaje) . '% por cada venta, con recargas (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)';

        return $opciones;
    }
}
