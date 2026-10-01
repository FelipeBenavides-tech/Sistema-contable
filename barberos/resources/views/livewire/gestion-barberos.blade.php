<div class="flex flex-col gap-4">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $barberos->where('activo', true)->count() }} activos de {{ $barberos->count() }}</p>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ Nuevo barbero</button>
    </div>

    @if($aviso)
        <div class="alert-warning">{{ $aviso }}</div>
    @endif

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_140px_100px_100px_260px]">
            <span>Nombre</span>
            <span>Teléfono</span>
            <span class="text-center">Comisión</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($barberos as $barbero)
            <div wire:key="barbero-{{ $barbero->id }}" class="tabla-row md:grid-cols-[1fr_140px_100px_100px_260px]">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $barbero->nombre }}</span>
                    <span class="md:hidden">
                        <span class="badge {{ $barbero->activo ? 'badge-green' : 'badge-red' }}">{{ $barbero->activo ? 'Activo' : 'Inactivo' }}</span>
                    </span>
                </div>
                <div class="flex items-center gap-4 text-muted md:contents">
                    <span>@if($barbero->telefono)<a href="tel:{{ $barbero->telefono }}" class="hover:text-brand-500">{{ $barbero->telefono }}</a>@else — @endif</span>
                    <span class="md:text-center"><span class="dato">Comisión</span><span class="badge badge-amber">{{ rtrim(rtrim(number_format($barbero->comision_porcentaje, 2, ',', ''), '0'), ',') }}%</span></span>
                </div>
                <span class="hidden text-center md:block">
                    <span class="badge {{ $barbero->activo ? 'badge-green' : 'badge-red' }}">{{ $barbero->activo ? 'Activo' : 'Inactivo' }}</span>
                </span>
                <div class="grid grid-cols-3 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="editar({{ $barbero->id }})" class="btn btn-sm btn-soft">Editar</button>
                    <button type="button" wire:click="toggleActivo({{ $barbero->id }})" class="btn btn-sm btn-light">{{ $barbero->activo ? 'Desactivar' : 'Activar' }}</button>
                    <button type="button" wire:click="eliminar({{ $barbero->id }})" wire:confirm="¿Seguro que deseas eliminar a {{ $barbero->nombre }}?" class="btn btn-sm btn-danger">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">Aún no hay barberos. Agrega el primero para poder registrar servicios.</p>
        @endforelse
    </div>

    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? 'Editar barbero' : 'Nuevo barbero' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="nombre">Nombre completo</label>
                        <input id="nombre" wire:model="nombre" type="text" placeholder="Carlos Mendoza" class="input" autofocus>
                    </div>
                    <div>
                        <label class="label" for="telefono">Teléfono</label>
                        <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" placeholder="3001234567" class="input">
                    </div>
                    <div>
                        <label class="label" for="comision">Comisión sobre servicios (%)</label>
                        <input id="comision" wire:model="comision_porcentaje" type="number" inputmode="decimal" min="0" max="100" step="1" class="input">
                    </div>
                    @if($editandoId)
                        <label class="flex items-center gap-3 sm:col-span-2">
                            <input wire:model="activo" type="checkbox" class="h-5 w-5 rounded border-line text-brand-500 focus:ring-brand-500">
                            <span class="text-sm">Activo (aparece en la caja)</span>
                        </label>
                    @endif
                </div>
                <p class="text-xs text-muted">La comisión se calcula solo sobre los servicios, no sobre los productos.</p>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
