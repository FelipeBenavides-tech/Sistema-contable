<div class="flex flex-col gap-4">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted">{{ $barberos->where('activo', true)->count() }} activos de {{ $barberos->count() }}</p>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ {{ voc('nuevo_profesional') }}</button>
    </div>

    @if($aviso)
        <div class="alert-warning">{{ $aviso }}</div>
    @endif

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_140px_100px_100px_340px]">
            <span>Nombre</span>
            <span>Teléfono</span>
            <span class="text-center">{{ $soloAgenda ? '' : 'Comisión' }}</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($barberos as $barbero)
            <div wire:key="barbero-{{ $barbero->id }}" class="tabla-row md:grid-cols-[1fr_140px_100px_100px_340px]">
                <div class="flex items-center justify-between gap-2">
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $barbero->nombre }}</span>
                        @if($usaEspecialidad && $barbero->especialidad)<span class="mt-0.5 inline-block rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200">{{ $barbero->especialidad }}</span>@endif
                        @if($esPro && $barbero->user)<span class="block truncate text-xs text-muted">Entra con {{ $barbero->user->email }}</span>@endif
                    </span>
                    <span class="md:hidden">
                        <span class="badge {{ $barbero->activo ? 'badge-green' : 'badge-red' }}">{{ $barbero->activo ? 'Activo' : 'Inactivo' }}</span>
                    </span>
                </div>
                <div class="flex items-center gap-4 text-muted md:contents">
                    <span>@if($barbero->telefono)<a href="tel:{{ $barbero->telefono }}" class="hover:text-brand-700">{{ $barbero->telefono }}</a>@else — @endif</span>
                    <span class="md:text-center">@unless($soloAgenda)<span class="dato">Comisión</span><span class="badge badge-amber">{{ rtrim(rtrim(number_format($barbero->comision_porcentaje, 2, ',', ''), '0'), ',') }}%</span>@endunless</span>
                </div>
                <span class="hidden text-center md:block">
                    <span class="badge {{ $barbero->activo ? 'badge-green' : 'badge-red' }}">{{ $barbero->activo ? 'Activo' : 'Inactivo' }}</span>
                </span>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="editar({{ $barbero->id }})" class="btn btn-sm btn-soft">Editar</button>
                    @if($esPro)
                        <button type="button" wire:click="abrirAcceso({{ $barbero->id }})" class="btn btn-sm btn-light">{{ $barbero->user ? 'Acceso' : 'Dar acceso' }}</button>
                    @endif
                    <button type="button" wire:click="toggleActivo({{ $barbero->id }})" class="btn btn-sm btn-light">{{ $barbero->activo ? 'Desactivar' : 'Activar' }}</button>
                    <button type="button" wire:click="eliminar({{ $barbero->id }})" wire:confirm="¿Seguro que deseas eliminar a {{ $barbero->nombre }}?" class="btn btn-sm btn-danger">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">Aún no hay {{ voc('profesionales') }}. Agrega el primero para poder {{ $soloAgenda ? 'agendar citas' : 'registrar servicios' }}.</p>
        @endforelse
    </div>

    @if($esPro)
        <p class="text-xs text-muted">Con "Dar acceso" cada {{ voc('profesional') }} entra con su propio correo y contraseña y solo ve su agenda{{ $soloAgenda ? '' : ' y sus servicios del día. No ve la caja, los gastos, los reportes ni los datos de los clientes' }}.</p>
    @else
        <p class="text-xs text-muted">En el plan Pro cada {{ voc('profesional') }} puede tener su propio usuario para ver su agenda. <a href="{{ route('mi-plan') }}" class="font-medium text-brand-700 hover:underline">Ver planes</a></p>
    @endif

    @if($barberoAcceso)
        <div class="modal-fondo" wire:click.self="cerrarAcceso">
            <form wire:submit="guardarAcceso" class="modal-panel">
                <h3 class="modal-titulo">Acceso de {{ $barberoAcceso->nombre }}</h3>
                <p class="text-sm text-muted">Con este usuario solo ve su agenda y sus servicios del día.</p>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3">
                    <div>
                        <label class="label" for="accesoEmail">Correo</label>
                        <input id="accesoEmail" wire:model="accesoEmail" type="email" inputmode="email" autocomplete="off" class="input">
                    </div>
                    <div>
                        <label class="label" for="accesoPassword">{{ $barberoAcceso->user ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                        <input id="accesoPassword" wire:model="accesoPassword" type="password" autocomplete="new-password" class="input">
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    @if($barberoAcceso->user)
                        <button type="button" wire:click="quitarAcceso({{ $barberoAcceso->id }})" wire:confirm="¿Quitarle el acceso a {{ $barberoAcceso->nombre }}?" class="btn btn-danger">Quitar acceso</button>
                    @else
                        <span></span>
                    @endif
                    <div class="grid grid-cols-2 gap-2 sm:flex">
                        <button type="button" wire:click="cerrarAcceso" class="btn btn-light">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">{{ $editandoId ? voc('editar_profesional') : voc('nuevo_profesional') }}</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="nombre">Nombre completo</label>
                        <input id="nombre" wire:model="nombre" type="text" placeholder="{{ $usaEspecialidad ? 'Laura Gómez' : 'Carlos Mendoza' }}" class="input" autofocus>
                    </div>
                    @if($usaEspecialidad)
                        <div class="sm:col-span-2">
                            <span class="label">Especialidad *</span>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach([...$especialidades, 'otra'] as $opcion)
                                    <button type="button" wire:click="$set('especialidad', '{{ $opcion }}')"
                                            class="btn btn-sm {{ $especialidad === $opcion ? 'bg-brand-100 text-brand-800 ring-1 ring-inset ring-brand-300' : 'btn-light' }}">
                                        {{ $opcion === 'otra' ? 'Otra' : $opcion }}
                                    </button>
                                @endforeach
                            </div>
                            @if($especialidad === 'otra')
                                <input id="especialidadOtra" wire:model="especialidadOtra" type="text" maxlength="60" placeholder="¿Cuál? Ej.: maquilladora, cosmetóloga, pestañas" class="input mt-2">
                            @endif
                        </div>
                    @endif
                    <div>
                        <label class="label" for="telefono">Teléfono</label>
                        <input id="telefono" wire:model="telefono" type="tel" inputmode="tel" placeholder="3001234567" class="input">
                    </div>
                    @unless($soloAgenda)
                    <div>
                        <label class="label" for="comision">Comisión sobre servicios (%)</label>
                        <input id="comision" wire:model="comision_porcentaje" type="number" inputmode="decimal" min="0" max="100" step="1" class="input">
                    </div>
                    @endunless
                    @if($editandoId)
                        <label class="flex items-center gap-3 sm:col-span-2">
                            <input wire:model="activo" type="checkbox" class="h-5 w-5 rounded border-line text-brand-700 focus:ring-brand-500">
                            <span class="text-sm">Activo (aparece en {{ $soloAgenda ? 'la agenda' : 'la caja' }})</span>
                        </label>
                    @endif
                </div>
                @unless($soloAgenda)
                    <p class="text-xs text-muted">La comisión se calcula solo sobre los servicios, no sobre los productos.</p>
                @endunless

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
