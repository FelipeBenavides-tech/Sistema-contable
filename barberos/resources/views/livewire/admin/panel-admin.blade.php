<div class="flex flex-col gap-4">

    <div class="grid grid-cols-3 gap-3">
        <div class="kpi">
            <p class="kpi-label">Barberías</p>
            <p class="kpi-value text-brand-500">{{ $stats['total'] }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label">Activas</p>
            <p class="kpi-value text-green-600">{{ $stats['activas'] }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label">Vencidas</p>
            <p class="kpi-value text-red-600">{{ $stats['vencidas'] }}</p>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="nuevo" class="btn btn-primary">+ Nueva barbería</button>
    </div>

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_100px_80px_130px_90px_320px]">
            <span>Barbería</span>
            <span class="text-center">Plan</span>
            <span class="text-center">Ventas</span>
            <span class="text-center">Vence</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($barberias as $barberia)
            <div wire:key="barberia-{{ $barberia->id }}" class="tabla-row md:grid-cols-[1fr_100px_80px_130px_90px_320px]">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $barberia->nombre }}</p>
                        <p class="text-xs text-muted">
                            {{ $barberia->propietario ?: '—' }}
                            @if($barberia->telefono) · <a href="tel:{{ $barberia->telefono }}" class="hover:text-brand-500">{{ $barberia->telefono }}</a>@endif
                        </p>
                    </div>
                    <span class="md:hidden">
                        <span class="badge {{ $barberia->activo ? 'badge-green' : 'badge-red' }}">{{ $barberia->activo ? 'Activa' : 'Inactiva' }}</span>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs md:contents md:text-sm">
                    <span class="md:text-center"><span class="badge badge-blue">{{ ucfirst($barberia->plan) }}</span></span>
                    <span class="text-muted md:text-center md:font-semibold md:text-brand-500"><span class="dato">Ventas:</span>{{ $barberia->ventas_count }}</span>
                    <span class="md:text-center">
                        @if($barberia->fecha_vencimiento)
                            <span class="{{ $barberia->vencida ? 'text-red-600' : 'text-muted' }}">{{ $barberia->fecha_vencimiento->format('d/m/Y') }}</span>
                            <span class="block text-xs {{ $barberia->vencida ? 'text-red-600' : 'text-green-700' }} max-md:inline max-md:ml-1">
                                {{ $barberia->vencida ? 'Vencida' : $barberia->dias_restantes . ' días' }}
                            </span>
                        @else
                            —
                        @endif
                    </span>
                    <span class="hidden text-center md:block">
                        <span class="badge {{ $barberia->activo ? 'badge-green' : 'badge-red' }}">{{ $barberia->activo ? 'Activa' : 'Inactiva' }}</span>
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 md:flex md:flex-wrap md:justify-end md:gap-1">
                    <button type="button" wire:click="verCredenciales({{ $barberia->id }})" class="btn btn-sm btn-violet">Acceso</button>
                    <button type="button" wire:click="editar({{ $barberia->id }})" class="btn btn-sm btn-soft">Editar</button>
                    <button type="button" wire:click="renovar({{ $barberia->id }})" wire:confirm="¿Renovar el plan {{ $barberia->plan }} de {{ $barberia->nombre }}?" class="btn btn-sm btn-success">Renovar</button>
                    <button type="button" wire:click="toggleActivo({{ $barberia->id }})" class="btn btn-sm btn-light">{{ $barberia->activo ? 'Desactivar' : 'Activar' }}</button>
                    <button type="button" wire:click="eliminar({{ $barberia->id }})" wire:confirm="¿Seguro? Se borrarán TODOS los datos de {{ $barberia->nombre }}." class="btn btn-sm btn-danger col-span-2">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">No hay barberías registradas. Agrega tu primer cliente.</p>
        @endforelse
    </div>

    {{-- Nueva / editar barbería --}}
    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? 'Editar barbería' : 'Nueva barbería' }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="nombre">Nombre de la barbería *</label>
                        <input id="nombre" wire:model="nombre" type="text" placeholder="Barbería El Estilo" class="input">
                    </div>
                    <div>
                        <label class="label" for="propietario">Propietario</label>
                        <input id="propietario" wire:model="propietario" type="text" class="input">
                    </div>
                    <div>
                        <label class="label" for="telefono">Teléfono</label>
                        <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" class="input">
                    </div>
                    <div>
                        <label class="label" for="direccion">Dirección</label>
                        <input id="direccion" wire:model="direccion" type="text" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="plan">Plan *</label>
                        <select id="plan" wire:model="plan" class="input">
                            <option value="mensual">Mensual — $59.900/mes</option>
                            <option value="semestral">Semestral — $299.000</option>
                            <option value="anual">Anual — $549.000</option>
                        </select>
                        @if($editandoId)
                            <p class="mt-1 text-xs text-muted">La fecha de vencimiento solo cambia si cambias el plan.</p>
                        @endif
                    </div>
                    @unless($editandoId)
                        <div>
                            <label class="label" for="email">Correo de acceso *</label>
                            <input id="email" wire:model="email" type="email" inputmode="email" autocomplete="off" class="input">
                        </div>
                        <div>
                            <label class="label" for="password">Contraseña inicial *</label>
                            <input id="password" wire:model="password" type="text" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="input">
                        </div>
                        <p class="text-xs text-muted sm:col-span-2">Comparte estos datos con el cliente para que pueda entrar.</p>
                    @endunless
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Credenciales --}}
    @if($mostrarCredenciales)
        <div class="modal-fondo" wire:click.self="cerrarCredenciales">
            <form wire:submit="actualizarCredenciales" class="modal-panel">
                <div>
                    <h3 class="modal-titulo">Datos de acceso</h3>
                    <p class="mt-1 text-xs text-muted">Correo actual: <span class="font-semibold text-brand-500">{{ $credencialesEmail }}</span></p>
                </div>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div>
                    <label class="label" for="nuevoEmail">Correo</label>
                    <input id="nuevoEmail" wire:model="nuevoEmail" type="email" inputmode="email" class="input">
                </div>
                <div>
                    <label class="label" for="nuevaPassword">Nueva contraseña</label>
                    <input id="nuevaPassword" wire:model="nuevaPassword" type="text" autocomplete="new-password" placeholder="Déjala vacía para no cambiarla" class="input">
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cerrarCredenciales" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
