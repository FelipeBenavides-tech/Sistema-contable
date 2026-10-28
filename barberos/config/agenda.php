<?php

/*
 * Agenda de citas (plan Pro).
 */
return [

    // Horario que se usa para los barberos a los que no se les ha configurado uno.
    // 0 = domingo … 6 = sábado. Un día sin entrada = no se trabaja.
    'horario_general' => [
        1 => ['09:00', '19:00'],
        2 => ['09:00', '19:00'],
        3 => ['09:00', '19:00'],
        4 => ['09:00', '19:00'],
        5 => ['09:00', '19:00'],
        6 => ['09:00', '19:00'],
    ],

    // Cada cuántos minutos se ofrecen horas para agendar (9:00, 9:15, 9:30…)
    'intervalo_minutos' => 15,

    // Duración que se usa si un servicio no tiene una
    'duracion_por_defecto' => 30,
];
