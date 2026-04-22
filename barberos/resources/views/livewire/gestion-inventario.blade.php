<div class="flex flex-col gap-5">

    {{-- Alerta stock bajo --}}
    @if($alertas > 0)
    <div class="rounded-xl p-4 text-sm"
         style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.4);color:#EF9A9A">
        ⚠ {{ $alertas }} producto(s) con stock bajo o agotado
    </div>
    @endif

    {{-- Botón nuevo --}}
    <div class="flex justify-end">
        <button wire:click="nuevo"
                class="px-4 py-2.5 rounded-lg text-sm font-medium"
                style="background:#C9A84C;color:#111318">
            + Agregar producto
        </button>
    </div>

    {{-- Formulario --}}
    @if($mostrarForm)
    <div class="rounded-xl p-5 flex flex-col gap-4"
         style="background:var(--surface);border:1px solid var(--border)">
        <h3 class="font-medium" style="color:#C9A84C">
            {{ $editandoId ? 'Editar producto' : 'Nuevo producto' }}
        </h3>

        @if($errors->any())
        <div class="rounded-lg p-3 text-sm"
             style="background:rgba(244,67,54,0.12);color:#EF9A9A">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Nombre del producto</label>
                <input wire:model="nombre" type="text" placeholder="Gel fijador, toallas..."
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Categoría</label>
                <select wire:model="categoria"
                        class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                        style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
                    <option value="insumo">Insumo</option>
                    <option value="venta">Para venta</option>
                </select>
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Stock actual</label>
                <input wire:model="stock_actual" type="number" min="0"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Stock mínimo</label>
                <input wire:model="stock_minimo" type="number" min="0"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Unidad</label>
                <select wire:model="unidad"
                        class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                        style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
                    <option value="unidad">Unidad</option>
                    <option value="caja">Caja</option>
                    <option value="ml">ML</option>
                    <option value="gr">GR</option>
                </select>
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Precio costo</label>
                <input wire:model="precio_costo" type="number" min="0" step="1000"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Precio venta</label>
                <input wire:model="precio_venta" type="number" min="0" step="1000"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <button wire:click="cancelar"
                    class="px-4 py-2 rounded-lg text-sm"
                    style="background:var(--dark);color:var(--muted);border:1px solid var(--border)">
                Cancelar
            </button>
            <button wire:click="guardar"
                    class="px-4 py-2 rounded-lg text-sm font-medium"
                    style="background:#C9A84C;color:#111318">
                Guardar
            </button>
        </div>
    </div>
    @endif

    {{-- Tabla inventario --}}
    <div style="overflow-x:auto;border-radius:12px;border:1px solid var(--border)">
<div style="min-width:Xpx">
    {{-- contenido de la tabla --}}
</div>
</div>
        <div class="px-4 py-3 text-xs font-medium grid gap-2"
             style="background:var(--surface);color:var(--muted);
                    grid-template-columns:1fr 80px 70px 70px 90px 90px 80px">
            <span>Producto</span>
            <span class="text-center">Categoría</span>
            <span class="text-center">Stock</span>
            <span class="text-center">Mínimo</span>
            <span class="text-right">Costo</span>
            <span class="text-right">Venta</span>
            <span class="text-center">Acción</span>
        </div>

        @forelse($items as $item)
        <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
             style="border-color:var(--border);
                    grid-template-columns:1fr 80px 70px 70px 90px 90px 80px">
            <span class="font-medium">{{ $item->nombre }}</span>
            <span class="text-center">
                <span class="px-2 py-0.5 rounded-full text-xs"
                      style="background:rgba(201,168,76,0.15);color:#C9A84C">
                    {{ $item->categoria }}
                </span>
            </span>
            <span class="text-center font-medium"
                  style="color:{{ $item->stock_actual <= $item->stock_minimo ? '#EF9A9A' : '#81C784' }}">
                {{ $item->stock_actual }} {{ $item->unidad }}
            </span>
            <span class="text-center" style="color:var(--muted)">
                {{ $item->stock_minimo }}
            </span>
            <span class="text-right" style="color:var(--muted)">
                ${{ number_format($item->precio_costo, 0, ',', '.') }}
            </span>
            <span class="text-right" style="color:#C9A84C">
                @if($item->precio_venta > 0)
                    ${{ number_format($item->precio_venta, 0, ',', '.') }}
                @else
                    —
                @endif
            </span>
            <div class="flex gap-1 justify-center">
    <button wire:click="editar({{ $item->id }})"
            class="px-2 py-1 rounded-lg text-xs font-medium"
            style="background:#EFF6FF;color:#2563EB">
        Editar
    </button>
    <button wire:click="eliminar({{ $item->id }})"
            wire:confirm="¿Seguro que deseas eliminar este producto?"
            class="px-2 py-1 rounded-lg text-xs font-medium"
            style="background:#FEF2F2;color:#DC2626">
        Eliminar
    </button>
</div>
        </div>
        @empty
        <div class="px-4 py-10 text-center text-sm" style="color:var(--muted)">
            No hay productos en el inventario
        </div>
        @endforelse
    </div>

</div>
