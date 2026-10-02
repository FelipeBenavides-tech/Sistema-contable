@php $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.'); @endphp
<div class="flex flex-col gap-4">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $items->count() }} productos</p>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ Agregar producto</button>
    </div>

    @if($alertas > 0)
        <div class="alert-warning flex items-center gap-2">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            {{ $alertas }} {{ $alertas === 1 ? 'producto tiene' : 'productos tienen' }} stock bajo o agotado.
        </div>
    @endif

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_100px_110px_80px_100px_100px_170px]">
            <span>Producto</span>
            <span class="text-center">Uso</span>
            <span class="text-center">Stock</span>
            <span class="text-center">Mínimo</span>
            <span class="text-right">Costo</span>
            <span class="text-right">Venta</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($items as $item)
            <div wire:key="inv-{{ $item->id }}" class="tabla-row md:grid-cols-[1fr_100px_110px_80px_100px_100px_170px]">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $item->nombre }}</span>
                    <span class="md:hidden">
                        <span class="badge {{ $item->stock_bajo ? 'badge-red' : 'badge-green' }}">{{ $item->stock_actual }} {{ $item->unidad }}</span>
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs md:contents md:text-sm">
                    <span class="md:text-center"><span class="badge {{ $item->categoria === 'venta' ? 'badge-amber' : 'badge-gray' }}">{{ $item->categoria === 'venta' ? 'Para venta' : 'Insumo' }}</span></span>
                    <span class="hidden text-center md:block">
                        <span class="badge {{ $item->stock_bajo ? 'badge-red' : 'badge-green' }}">{{ $item->stock_actual }} {{ $item->unidad }}</span>
                    </span>
                    <span class="text-muted md:text-center"><span class="dato">Mín.</span>{{ $item->stock_minimo }}</span>
                    <span class="text-muted md:text-right"><span class="dato">Costo</span>{{ $dinero($item->precio_costo) }}</span>
                    <span class="hidden font-semibold md:block md:text-right">{{ $item->precio_venta > 0 ? $dinero($item->precio_venta) : '—' }}</span>
                </div>
                @if($item->precio_venta > 0)
                    <span class="text-sm font-medium text-ink md:hidden"><span class="text-muted">Precio de venta</span> {{ $dinero($item->precio_venta) }}</span>
                @endif
                <div class="grid grid-cols-2 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="editar({{ $item->id }})" class="btn btn-sm btn-soft">Editar</button>
                    <button type="button" wire:click="eliminar({{ $item->id }})" wire:confirm="¿Seguro que deseas eliminar {{ $item->nombre }}?" class="btn btn-sm btn-danger">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">No hay productos en el inventario.</p>
        @endforelse
    </div>

    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? 'Editar producto' : 'Nuevo producto' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="label" for="nombre">Nombre del producto</label>
                        <input id="nombre" wire:model="nombre" type="text" placeholder="Cera, gel, toallas…" class="input" autofocus>
                        <p class="mt-1 text-xs text-muted">Aquí van productos físicos. Los cortes y demás servicios se crean en <a href="{{ route('servicios') }}" class="font-medium text-brand-600 hover:text-brand-800">Servicios</a>.</p>
                    </div>
                    <div>
                        <label class="label" for="categoria">Uso</label>
                        <select id="categoria" wire:model.live="categoria" class="input">
                            <option value="insumo">Insumo (uso interno)</option>
                            <option value="venta">Para vender</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="unidad">Unidad</label>
                        <select id="unidad" wire:model="unidad" class="input">
                            <option value="unidad">Unidad</option>
                            <option value="caja">Caja</option>
                            <option value="ml">ml</option>
                            <option value="gr">gr</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="stock_actual">Stock actual</label>
                        <input id="stock_actual" wire:model="stock_actual" type="number" inputmode="numeric" min="0" class="input">
                    </div>
                    <div>
                        <label class="label" for="stock_minimo">Avisar cuando queden</label>
                        <input id="stock_minimo" wire:model="stock_minimo" type="number" inputmode="numeric" min="0" class="input">
                    </div>
                    <div>
                        <label class="label" for="precio_costo">Precio de costo</label>
                        <input id="precio_costo" wire:model="precio_costo" type="number" inputmode="numeric" min="0" step="100" class="input">
                    </div>
                    <div>
                        <label class="label" for="precio_venta">Precio de venta</label>
                        <input id="precio_venta" wire:model="precio_venta" type="number" inputmode="numeric" min="0" step="100" class="input">
                    </div>
                </div>
                @if($categoria === 'venta')
                    <p class="text-xs text-muted">Aparece en la caja si tiene precio de venta. No genera comisión al barbero.</p>
                @endif

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
