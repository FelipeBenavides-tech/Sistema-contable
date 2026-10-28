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

    {{-- Reservas en línea (plan Pro) --}}
    @if($barberia->es_pro)
        <form wire:submit="guardarReservas" class="card card-pad flex flex-col gap-5">
            <div>
                <p class="section-title">Reservas en línea</p>
                <p class="text-xs text-muted">Tus clientes reservan y pagan el anticipo desde una página propia de tu barbería.</p>
            </div>

            @if($mensajeReservas)
                <div class="alert-success">{{ $mensajeReservas }}</div>
            @endif
            @if($errors->hasAny(['slug', 'anticipo_porcentaje', 'horas_cancelacion', 'wompi_llave_publica', 'wompi_llave_privada', 'wompi_secreto_eventos', 'wompi_secreto_integridad']))
                <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            @endif

            <label class="flex items-center gap-3">
                <input type="checkbox" wire:model="reservas_activas" class="h-5 w-5 rounded border-line text-brand-700">
                <span class="text-sm font-medium">Recibir reservas en línea</span>
            </label>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="slug">Dirección de tu página</label>
                    <div class="flex items-center gap-1 text-sm">
                        <span class="shrink-0 text-muted">{{ url('/reservar') }}/</span>
                        <input id="slug" wire:model="slug" type="text" class="input">
                    </div>
                    @if($barberia->recibe_reservas)
                        <a href="{{ route('reservar', $barberia->slug) }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-medium text-brand-600 hover:text-brand-800">Abrir mi página de reservas →</a>
                    @endif
                </div>
                <div>
                    <label class="label" for="anticipo">Anticipo al reservar (%)</label>
                    <input id="anticipo" wire:model="anticipo_porcentaje" type="number" min="0" max="100" class="input">
                    <p class="mt-1 text-xs text-muted">0 = sin anticipo.</p>
                </div>
                <div>
                    <label class="label" for="horasCancelacion">Horas mínimas para cancelar sin perder el anticipo</label>
                    <input id="horasCancelacion" wire:model="horas_cancelacion" type="number" min="0" max="168" class="input">
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-xl bg-slate-50 p-4">
                <div>
                    <p class="text-sm font-semibold text-ink">Cuenta de Wompi</p>
                    <p class="text-xs text-muted">Las encuentras en el panel de Wompi, en "Desarrolladores". Las secretas se guardan cifradas y no se vuelven a mostrar; deja el campo vacío para conservar la guardada.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="wPublica">Llave pública</label>
                        <input id="wPublica" wire:model="wompi_llave_publica" type="text" placeholder="pub_prod_…" autocomplete="off" class="input">
                    </div>
                    @foreach(['wompi_llave_privada' => ['Llave privada', 'prv_prod_…'], 'wompi_secreto_eventos' => ['Secreto de eventos', 'prod_events_…'], 'wompi_secreto_integridad' => ['Secreto de integridad', 'prod_integrity_…']] as $campo => [$texto, $ejemplo])
                        <div class="{{ $campo === 'wompi_llave_privada' ? 'sm:col-span-2' : '' }}">
                            <label class="label" for="{{ $campo }}">{{ $texto }} @if($barberia->$campo)<span class="font-normal text-emerald-700">· guardado ✓</span>@endif</label>
                            <input id="{{ $campo }}" wire:model="{{ $campo }}" type="password" placeholder="{{ $barberia->$campo ? '••••••••' : $ejemplo }}" autocomplete="new-password" class="input">
                        </div>
                    @endforeach
                </div>
                <div class="text-xs text-muted">
                    <p>En Wompi, en "URL de eventos", pon esta dirección:</p>
                    <p class="mt-1 select-all break-all rounded bg-white px-2 py-1 font-mono text-ink ring-1 ring-inset ring-slate-200">{{ route('webhooks.wompi') }}</p>
                    @if(str_starts_with((string) $barberia->wompi_llave_publica, 'pub_test_'))
                        <p class="mt-2 font-medium text-amber-700">Estás usando llaves de pruebas: los pagos no son reales.</p>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Guardar reservas</button>
            </div>
        </form>
    @endif
</div>
