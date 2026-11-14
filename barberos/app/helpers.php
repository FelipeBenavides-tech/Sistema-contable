<?php

use App\Models\Barberia;
use App\Support\Negocio;

if (!function_exists('voc')) {
    /**
     * Palabra según el tipo de negocio: voc('profesional') es "barbero" en una
     * barbería y "profesional" en un salón de belleza.
     */
    function voc(string $clave, ?Barberia $barberia = null): string
    {
        return Negocio::palabra($clave, $barberia);
    }
}
