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
        $mensualAgenda = (float) config('suscripcion.planes.mensual.precio_agenda', $mensual);

        return collect(config('suscripcion.planes'))
            ->map(function (array $plan, string $clave) use ($mensual, $mensualPro, $mensualAgenda) {
                $sinDescuento = $mensual * $plan['meses'];
                $ahorro       = max(0, $sinDescuento - $plan['precio']);
                $precioPro    = (float) ($plan['precio_pro'] ?? $plan['precio']);
                $ahorroPro    = max(0, $mensualPro * $plan['meses'] - $precioPro);
                $precioAgenda = (float) ($plan['precio_agenda'] ?? $plan['precio']);
                $sinDescuentoAgenda = $mensualAgenda * $plan['meses'];
                $ahorroAgenda = max(0, $sinDescuentoAgenda - $precioAgenda);

                return $plan + [
                    'clave'            => $clave,
                    'precio_mes'       => $plan['precio'] / $plan['meses'],
                    'ahorro'           => $ahorro,
                    'ahorro_porcentaje' => $sinDescuento > 0 ? (int) round($ahorro * 100 / $sinDescuento) : 0,
                    'precio_pro'       => $precioPro,
                    'precio_mes_pro'   => $precioPro / $plan['meses'],
                    'ahorro_pro'       => $ahorroPro,
                    'precio_agenda'    => $precioAgenda,
                    'precio_mes_agenda' => $precioAgenda / $plan['meses'],
                    'ahorro_agenda'    => $ahorroAgenda,
                    'ahorro_porcentaje_agenda' => $sinDescuentoAgenda > 0 ? (int) round($ahorroAgenda * 100 / $sinDescuentoAgenda) : 0,
                ];
            })
            ->all();
    }

    // Texto para mostrar en una lista desplegable (precios del nivel indicado)
    // En "solo agendamiento" ($tipo = agenda) muestra sus precios y no ofrece el plan por uso
    public static function opciones(string $nivel = 'base', string $tipo = 'barberia'): array
    {
        $dinero = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
        $pro = $nivel === 'pro';
        $agenda = $tipo === 'agenda';
        $opciones = [];

        foreach (self::catalogo() as $clave => $plan) {
            $precio = $agenda ? $plan['precio_agenda'] : ($pro ? $plan['precio_pro'] : $plan['precio']);
            $ahorro = $agenda ? $plan['ahorro_agenda'] : ($pro ? $plan['ahorro_pro'] : $plan['ahorro']);
            $opciones[$clave] = "{$plan['nombre']} — {$dinero($precio)}"
                . ($plan['meses'] === 1 ? '/mes (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)' : '')
                . ($ahorro > 0 ? " · ahorra {$dinero($ahorro)}" : '');
        }

        if ($agenda) {
            return $opciones;
        }

        $porcentaje = config($pro ? 'suscripcion.porcentaje.valor_pro' : 'suscripcion.porcentaje.valor');
        $opciones['porcentaje'] = config('suscripcion.porcentaje.nombre') . ' — '
            . str_replace('.', ',', $porcentaje) . '% por cada venta, con recargas (contrato mínimo ' . config('suscripcion.contrato_minimo_meses') . ' meses)';

        return $opciones;
    }
}
