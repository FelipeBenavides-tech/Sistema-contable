<?php

namespace App\Support;

use App\Models\Barberia;

/**
 * Tipos de negocio que usan Kaixa y las palabras que ve cada uno.
 *
 * - barberia: el sistema de siempre (barberos, "Mi barbería").
 * - salon:    salón de belleza, con paleta pastel y "profesionales" con especialidad.
 * - agenda:   solo agendamiento de citas (sin caja, ventas ni inventario).
 *
 * En las vistas se usa voc('profesionales'), voc('mi_negocio'), etc.
 */
class Negocio
{
    public const TIPOS = [
        'salon'    => 'Salón de belleza',
        'barberia' => 'Barbería',
        'agenda'   => 'Solo agendamiento de citas',
    ];

    // Especialidades que se ofrecen al agregar un profesional (más "Otra")
    public const ESPECIALIDADES = ['Estilista', 'Manicurista'];

    private const PALABRAS = [
        'barberia' => [
            'profesional'       => 'barbero',
            'Profesional'       => 'Barbero',
            'profesionales'     => 'barberos',
            'Profesionales'     => 'Barberos',
            'un_profesional'    => 'un barbero',
            'nuevo_profesional' => 'Nuevo barbero',
            'editar_profesional' => 'Editar barbero',
            'negocio'           => 'barbería',
            'Negocio'           => 'Barbería',
            'el_negocio'        => 'la barbería',
            'El_negocio'        => 'La barbería',
            'del_negocio'       => 'de la barbería',
            'al_negocio'        => 'a la barbería',
            'en_el_negocio'     => 'en la barbería',
            'este_negocio'      => 'esta barbería',
            'Este_negocio'      => 'Esta barbería',
            'mi_negocio'        => 'Mi barbería',
            'tu_negocio'        => 'tu barbería',
        ],
        'salon' => [
            'profesional'       => 'profesional',
            'Profesional'       => 'Profesional',
            'profesionales'     => 'profesionales',
            'Profesionales'     => 'Profesionales',
            'un_profesional'    => 'un profesional',
            'nuevo_profesional' => 'Agregar profesional',
            'editar_profesional' => 'Editar profesional',
            'negocio'           => 'salón',
            'Negocio'           => 'Salón',
            'el_negocio'        => 'el salón',
            'El_negocio'        => 'El salón',
            'del_negocio'       => 'del salón',
            'al_negocio'        => 'al salón',
            'en_el_negocio'     => 'en el salón',
            'este_negocio'      => 'este salón',
            'Este_negocio'      => 'Este salón',
            'mi_negocio'        => 'Mi salón',
            'tu_negocio'        => 'tu salón',
        ],
        'agenda' => [
            'profesional'       => 'profesional',
            'Profesional'       => 'Profesional',
            'profesionales'     => 'profesionales',
            'Profesionales'     => 'Profesionales',
            'un_profesional'    => 'un profesional',
            'nuevo_profesional' => 'Agregar profesional',
            'editar_profesional' => 'Editar profesional',
            'negocio'           => 'negocio',
            'Negocio'           => 'Negocio',
            'el_negocio'        => 'el negocio',
            'El_negocio'        => 'El negocio',
            'del_negocio'       => 'del negocio',
            'al_negocio'        => 'al negocio',
            'en_el_negocio'     => 'en el negocio',
            'este_negocio'      => 'este negocio',
            'Este_negocio'      => 'Este negocio',
            'mi_negocio'        => 'Mi negocio',
            'tu_negocio'        => 'tu negocio',
        ],
    ];

    /** Fija el negocio de la pantalla actual (páginas públicas, correos, PDF). */
    public static function usar(?Barberia $barberia): void
    {
        // Se guarda en el contenedor para que dure solo esta petición
        app()->instance('negocio.actual', $barberia);
    }

    /** Negocio de la pantalla actual: el fijado con usar() o el del usuario que entró. */
    public static function actual(): ?Barberia
    {
        $fijado = app()->bound('negocio.actual') ? app('negocio.actual') : null;

        return $fijado ?? auth()->user()?->barberia;
    }

    public static function tipo(?Barberia $barberia = null): string
    {
        $tipo = ($barberia ?? self::actual())?->tipo_negocio;

        return isset(self::PALABRAS[$tipo]) ? $tipo : 'barberia';
    }

    public static function palabra(string $clave, ?Barberia $barberia = null): string
    {
        return self::PALABRAS[self::tipo($barberia)][$clave] ?? $clave;
    }

    /**
     * Nombre de ruta según el tipo de negocio: en un salón o en solo agenda,
     * "barberos" es "profesionales" y "mi-barberia" es "mi-negocio".
     */
    public static function ruta(string $nombre, ?Barberia $barberia = null): string
    {
        if (self::tipo($barberia) === 'barberia') {
            return $nombre;
        }

        return [
            'barberos'    => 'profesionales',
            'mi-barberia' => 'mi-negocio',
        ][$nombre] ?? $nombre;
    }

    /** Tema de colores de la interfaz: el salón usa la paleta pastel. */
    public static function tema(?Barberia $barberia = null): string
    {
        return self::tipo($barberia) === 'salon' ? 'salon' : 'clasico';
    }
}
