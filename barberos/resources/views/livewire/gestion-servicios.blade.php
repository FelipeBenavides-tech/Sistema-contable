<div class="flex flex-col gap-5">

    {{-- Botón nuevo --}}
    <div class="flex justify-end">
        <button wire:click="nuevo"
                class="px-4 py-2.5 rounded-lg text-sm font-medium"
                style="background:#C9A84C;color:#111318">
            + Nuevo servicio
        </button>
    </div>

    {{-- Formulario --}}
    @if($mostrarForm)
    <div class="rounded-xl p-5 flex flex-col gap-4"
         style="background:var(--surface);border:1px solid var(--border)">
        <h3 class="font-medium" style="color:#C9A84C">
            {{ $editandoId ? 'Editar servicio' : 'Nuevo servicio' }}
        </h3>

        @if($errors->any())
        <div class="rounded-lg p-3 text-sm" style="background:rgba(244,67,54,0.12);color:#EF9A9A">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Nombre del servicio</label>
                <input wire:model="nombre" type="text" placeholder="Corte clásico"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Precio (COP)</label>
                <input wire:model="precio" type="number" min="0" step="1000"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Categoría</label>
                <select wire:model="categoria"
                        class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                        style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
                    <option value="servicio">Servicio</option>
                    <option value="producto">Producto</option>
                </select>
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

    {{-- Tabla de servicios --}}
    <div style="overflow-x:auto;border-radius:12px;border:1px solid var(--border)">
<div style="min-width:Xpx">
    {{-- contenido de la tabla --}}
</div>
</div>
        <div class="px-4 py-3 text-xs font-medium grid gap-2"
             style="background:var(--surface);color:var(--muted);grid-template-columns:1fr 100px 100px 80px 100px">
            <span>Nombre</span>
            <span class="text-center">Categoría</span>
            <span class="text-right">Precio</span>
            <span class="text-center">Estado</span>
            <span class="text-center">Acción</span>
        </div>

        @forelse($servicios as $servicio)
        <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
             style="border-color:var(--border);grid-template-columns:1fr 100px 100px 80px 100px">
            <span class="font-medium">{{ $servicio->nombre }}</span>
            <span class="text-center">
                <span class="px-2 py-0.5 rounded-full text-xs"
                      style="background:rgba(201,168,76,0.15);color:#C9A84C">
                    {{ $servicio->categoria }}
                </span>
            </span>
            <span class="text-right font-medium" style="color:#C9A84C">
                ${{ number_format($servicio->precio, 0, ',', '.') }}
            </span>
            <span class="text-center">
                @if($servicio->activo)
                    <span class="px-2 py-0.5 rounded-full text-xs"
                          style="background:rgba(76,175,80,0.15);color:#81C784">Activo</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs"
                          style="background:rgba(244,67,54,0.15);color:#EF9A9A">Inactivo</span>
                @endif
            </span>
            <div class="flex gap-2 justify-center flex-wrap">
    <button wire:click="editar({{ $servicio->id }})"
            class="px-2 py-1 rounded-lg text-xs font-medium"
            style="background:#EFF6FF;color:#2563EB">
        Editar
    </button>
    <button wire:click="toggleActivo({{ $servicio->id }})"
            class="px-2 py-1 rounded-lg text-xs font-medium"
            style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
        {{ $servicio->activo ? 'Desactivar' : 'Activar' }}
    </button>
    <button wire:click="eliminar({{ $servicio->id }})"
            wire:confirm="¿Seguro que deseas eliminar este servicio?"
            class="px-2 py-1 rounded-lg text-xs font-medium"
            style="background:#FEF2F2;color:#DC2626">
        Eliminar
    </button>
</div>
        </div>
        @empty
        <div class="px-4 py-10 text-center text-sm" style="color:var(--muted)">
            No hay servicios registrados
        </div>
        @endforelse
    </div>

</div>
