@php
    $logoActual = $logo && !$errors->has('logo') ? $logo->temporaryUrl() : $barberia->logoDataUri();
@endphp
<div class="mx-auto flex w-full max-w-3xl flex-col gap-4">

    <p class="text-sm text-muted">Estos datos aparecen en el encabezado de los reportes PDF y en el menú del sistema.</p>

    @if($mensaje)
        <div class="alert-success">{{ $mensaje }}</div>
    @endif

    <form wire:submit="guardar" class="card card-pad flex flex-col gap-5">
        @if($errors->any())
            <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        {{-- Logo --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-900 ring-1 ring-line">
                @if($logoActual)
                    <img src="{{ $logoActual }}" alt="Logo" class="h-full w-full bg-white object-contain">
                @else
                    <span class="text-2xl font-semibold text-accent-500">{{ $barberia->iniciales }}</span>
                @endif
            </div>
            <div class="flex flex-col gap-2">
                <p class="section-title">Logo</p>
                <p class="text-xs text-muted">PNG o JPG de máximo 1 MB. Si no subes uno, se usan las iniciales del nombre.</p>
                <div class="flex flex-wrap gap-2">
                    <label class="btn btn-sm btn-light cursor-pointer">
                        {{ $barberia->logo ? 'Cambiar logo' : 'Subir logo' }}
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg" class="hidden">
                    </label>
                    @if($barberia->logo && !$logo)
                        <button type="button" wire:click="quitarLogo" wire:confirm="¿Quitar el logo?" class="btn btn-sm btn-danger">Quitar</button>
                    @endif
                </div>
                <p wire:loading wire:target="logo" class="text-xs text-muted">Cargando imagen…</p>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="nombre">Nombre de la barbería</label>
                <input id="nombre" wire:model="nombre" type="text" placeholder="Barbería El Estilo" class="input">
            </div>
            <div>
                <label class="label" for="nit">NIT o documento <span class="font-normal text-muted">(opcional)</span></label>
                <input id="nit" wire:model="nit" type="text" placeholder="900.123.456-7" class="input">
            </div>
            <div>
                <label class="label" for="propietario">Propietario <span class="font-normal text-muted">(opcional)</span></label>
                <input id="propietario" wire:model="propietario" type="text" class="input">
            </div>
            <div>
                <label class="label" for="telefono">Teléfono <span class="font-normal text-muted">(opcional)</span></label>
                <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" class="input">
            </div>
            <div>
                <label class="label" for="direccion">Dirección <span class="font-normal text-muted">(opcional)</span></label>
                <input id="direccion" wire:model="direccion" type="text" class="input">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Guardar cambios</button>
        </div>
    </form>
</div>
