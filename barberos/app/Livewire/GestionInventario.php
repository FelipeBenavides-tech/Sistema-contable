<?php

namespace App\Livewire;

use App\Models\Inventario;
use Livewire\Component;

class GestionInventario extends Component
{
    public string $nombre = '';
    public string $categoria = 'insumo';
    public int $stock_actual = 0;
    public int $stock_minimo = 3;
    public float $precio_costo = 0;
    public float $precio_venta = 0;
    public string $unidad = 'unidad';
    public ?int $editandoId = null;
    public bool $mostrarForm = false;

    private function barberiaId(): int
    {
        return auth()->user()->barberia_id ?? 0;
    }

    public function nuevo(): void
    {
        $this->resetErrorBag();
        $this->reset([
            'nombre',
            'categoria',
            'stock_actual',
            'stock_minimo',
            'precio_costo',
            'precio_venta',
            'unidad',
            'editandoId'
        ]);
        $this->categoria    = 'insumo';
        $this->stock_minimo = 3;
        $this->unidad       = 'unidad';
        $this->mostrarForm  = true;
    }

    public function editar(int $id): void
    {
        $this->resetErrorBag();
        $item = Inventario::where('barberia_id', $this->barberiaId())->findOrFail($id);
        $this->editandoId   = $id;
        $this->nombre       = $item->nombre;
        $this->categoria    = $item->categoria;
        $this->stock_actual = $item->stock_actual;
        $this->stock_minimo = $item->stock_minimo;
        $this->precio_costo = $item->precio_costo;
        $this->precio_venta = $item->precio_venta;
        $this->unidad       = $item->unidad;
        $this->mostrarForm  = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'       => 'required|min:2|max:255',
            'categoria'    => 'required|in:insumo,venta',
            'stock_actual' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio_costo' => 'required|numeric|min:0|max:99999999',
            'precio_venta' => 'required|numeric|min:0|max:99999999',
            'unidad'       => 'required|in:unidad,caja,ml,gr',
        ]);

        if ($this->editandoId) {
            Inventario::where('barberia_id', $this->barberiaId())
                ->findOrFail($this->editandoId)
                ->update([
                    'nombre'       => $this->nombre,
                    'categoria'    => $this->categoria,
                    'stock_actual' => $this->stock_actual,
                    'stock_minimo' => $this->stock_minimo,
                    'precio_costo' => $this->precio_costo,
                    'precio_venta' => $this->precio_venta,
                    'unidad'       => $this->unidad,
                ]);
        } else {
            Inventario::create([
                'barberia_id'  => $this->barberiaId(),
                'nombre'       => $this->nombre,
                'categoria'    => $this->categoria,
                'stock_actual' => $this->stock_actual,
                'stock_minimo' => $this->stock_minimo,
                'precio_costo' => $this->precio_costo,
                'precio_venta' => $this->precio_venta,
                'unidad'       => $this->unidad,
            ]);
        }

        $this->reset([
            'nombre',
            'categoria',
            'stock_actual',
            'stock_minimo',
            'precio_costo',
            'precio_venta',
            'unidad',
            'editandoId'
        ]);
        $this->mostrarForm = false;
    }

    public function eliminar(int $id): void
    {
        Inventario::where('barberia_id', $this->barberiaId())->findOrFail($id)->delete();
    }

    public function cancelar(): void
    {
        $this->resetErrorBag();
        $this->reset([
            'nombre',
            'categoria',
            'stock_actual',
            'stock_minimo',
            'precio_costo',
            'precio_venta',
            'unidad',
            'editandoId'
        ]);
        $this->mostrarForm = false;
    }

    public function render()
    {
        $items   = Inventario::where('barberia_id', $this->barberiaId())
            ->orderBy('nombre')
            ->get();
        $alertas = Inventario::where('barberia_id', $this->barberiaId())
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();

        return view('livewire.gestion-inventario', [
            'items'   => $items,
            'alertas' => $alertas,
        ])->layout('layouts.app', ['title' => 'Inventario']);
    }
}
