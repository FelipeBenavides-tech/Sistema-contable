<?php

namespace Tests\Concerns;

use App\Models\Barberia;
use App\Models\Barbero;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\PlanMembresia;
use App\Models\Servicio;
use App\Models\User;

trait CreaDatosDePrueba
{
    protected function crearBarberia(string $nombre = 'Barbería Uno', string $nivel = 'base'): Barberia
    {
        return Barberia::create([
            'nombre'            => $nombre,
            'activo'            => true,
            'plan'              => 'mensual',
            'nivel'             => $nivel,
            'fecha_vencimiento' => now()->addMonth(),
        ]);
    }

    protected function crearUsuario(Barberia $barberia): User
    {
        return User::factory()->create(['barberia_id' => $barberia->id]);
    }

    protected function crearAdmin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function crearBarbero(Barberia $barberia, float $comision = 50): Barbero
    {
        return Barbero::create([
            'barberia_id'         => $barberia->id,
            'nombre'              => 'Barbero ' . $barberia->id,
            'comision_porcentaje' => $comision,
            'activo'              => true,
        ]);
    }

    protected function crearServicio(Barberia $barberia, float $precio = 20000, string $categoria = 'servicio'): Servicio
    {
        return Servicio::create([
            'barberia_id' => $barberia->id,
            'nombre'      => 'Corte ' . $precio,
            'precio'      => $precio,
            'categoria'   => $categoria,
            'activo'      => true,
        ]);
    }

    protected function crearProducto(Barberia $barberia, int $stock = 5, float $precio = 10000): Inventario
    {
        return Inventario::create([
            'barberia_id'  => $barberia->id,
            'nombre'       => 'Cera',
            'categoria'    => 'venta',
            'stock_actual' => $stock,
            'stock_minimo' => 1,
            'precio_costo' => 5000,
            'precio_venta' => $precio,
            'unidad'       => 'unidad',
        ]);
    }

    protected function crearCliente(Barberia $barberia, bool $permiteFiado = false, string $nombre = 'Juan Pérez'): Cliente
    {
        return Cliente::create([
            'barberia_id'   => $barberia->id,
            'nombre'        => $nombre,
            'telefono'      => '300' . random_int(1000000, 9999999),
            'permite_fiado' => $permiteFiado,
        ]);
    }

    protected function crearUsuarioBarbero(Barberia $barberia, Barbero $barbero): User
    {
        return User::factory()->create([
            'barberia_id' => $barberia->id,
            'barbero_id'  => $barbero->id,
            'rol'         => 'barbero',
        ]);
    }

    protected function crearPlan(Barberia $barberia, float $precio = 80000, int $visitas = 5, int $dias = 30): PlanMembresia
    {
        return PlanMembresia::create([
            'barberia_id'   => $barberia->id,
            'nombre'        => 'Mensual ' . $visitas,
            'precio'        => $precio,
            'visitas'       => $visitas,
            'duracion_dias' => $dias,
            'activo'        => true,
        ]);
    }
}
