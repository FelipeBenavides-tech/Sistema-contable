<?php

/*
 * Planes con los que se vende Kaixa a cada barbería.
 * Los precios están en pesos colombianos.
 */
return [

    // Planes por tiempo: precio total y meses que cubre cada pago
    // precio = plan Base; precio_pro = plan Pro (incluye la agenda de citas)
    'planes' => [
        'mensual'   => ['nombre' => 'Mensual',   'meses' => 1,  'precio' => 130000,  'precio_pro' => 180000],
        'semestral' => ['nombre' => 'Semestral', 'meses' => 6,  'precio' => 700000,  'precio_pro' => 970000],
        'anual'     => ['nombre' => 'Anual',     'meses' => 12, 'precio' => 1200000, 'precio_pro' => 1660000],
    ],

    // Niveles que se combinan con cualquier plan
    'niveles' => [
        'base' => [
            'nombre'      => 'Base',
            'descripcion' => 'Caja, ventas, membresías, cartera de clientes, inventario, gastos y reportes.',
        ],
        'pro' => [
            'nombre'      => 'Pro',
            'descripcion' => 'Todo lo del plan Base más la agenda de citas, horarios y usuarios para que cada barbero vea su agenda.',
        ],
    ],

    // Plan por uso: se cobra este porcentaje de cada servicio o artículo vendido
    // y se descuenta de un saldo que la barbería recarga por adelantado
    'porcentaje' => [
        'nombre'           => 'Por uso',
        'valor'            => 2.5,
        // Porcentaje del plan por uso en el nivel Pro
        'valor_pro'        => 3.5,
        'recarga_minima'   => 20000,
        // La primera recarga al entrar al plan debe ser de al menos este valor
        'primera_recarga_minima' => 200000,
        'aviso_saldo_bajo' => 10000,
    ],

    // Los planes mensual y por uso exigen un contrato mínimo de estos meses.
    // En el plan por uso, el saldo a favor se devuelve al terminar el contrato
    'contrato_minimo_meses' => 6,

    // Pago único por la instalación y entrega del sistema (aplica a todos los planes)
    'instalacion' => 65000,

    // Días después del vencimiento en los que todavía se puede usar el sistema
    'dias_gracia' => 5,

    // Número de WhatsApp de soporte para pagar (solo dígitos, con indicativo)
    'whatsapp_soporte' => env('KAIXA_WHATSAPP', ''),
];
