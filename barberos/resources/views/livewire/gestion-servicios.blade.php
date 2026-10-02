<div class="flex flex-col gap-4">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $servicios->where('activo', true)->count() }} activos de {{ $servicios->count() }}</p>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ Nuevo servicio</button>
    </div>

    @if($aviso)
        <div class="alert-warning">{{ $aviso }}</div>
    @endif

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_110px_120px_100px_260px]">
            <span>Nombre</span>
            <span class="text-center">Tipo</span>
            <span class="text-right">Precio</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($servicios as $servicio)
            <div wire:key="servicio-{{ $servicio->id }}" class="tabla-row md:grid-cols-[1fr_110px_120px_100px_260px]">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $servicio->nombre }}</span>
                    <span class="text-base font-semibold text-ink md:hidden">${{ number_format($servicio->precio, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center gap-2 md:contents">
                    <span class="md:text-center"><span class="badge {{ $servicio->categoria === 'producto' ? 'badge-amber' : 'badge-blue' }}">{{ ucfirst($servicio->categoria) }}</span></span>
                    <span class="hidden text-right font-semibold text-ink md:block">${{ number_format($servicio->precio, 0, ',', '.') }}</span>
                    <span class="md:text-center"><span class="badge {{ $servicio->activo ? 'badge-green' : 'badge-red' }}">{{ $servicio->activo ? 'Activo' : 'Inactivo' }}</span></span>
                </div>
                <div class="grid grid-cols-3 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="editar({{ $servicio->id }})" class="btn btn-sm btn-soft">Editar</button>
                    <button type="button" wire:click="toggleActivo({{ $servicio->id }})" class="btn btn-sm btn-light">{{ $servicio->activo ? 'Desactivar' : 'Activar' }}</button>
                    <button type="button" wire:click="eliminar({{ $servicio->id }})" wire:confirm="¿Seguro que deseas eliminar este servicio?" class="btn btn-sm btn-danger">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">Aún no hay servicios. Agrega cortes, barbas y demás para venderlos en la caja.</p>
        @endforelse
    </div>

    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? 'Editar servicio' : 'Nuevo servicio' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="nombre">Nombre</label>
                        <input id="nombre" wire:model="nombre" type="text" placeholder="Corte clásico" class="input" autofocus>
                    </div>
                    <div>
                        <label class="label" for="precio">Precio (COP)</label>
                        <input id="precio" wire:model="precio" type="number" inputmode="numeric" min="0" step="1000" class="input">
                    </div>
                    <div>
                        <label class="label" for="categoria">Tipo</label>
                        <select id="categoria" wire:model="categoria" class="input">
                            <option value="servicio">Servicio (genera comisión)</option>
                            <option value="producto">Producto (sin comisión)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
