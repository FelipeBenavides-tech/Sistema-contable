@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $estados = [
        'al_dia'     => ['Al día', 'badge-green'],
        'por_vencer' => ['Por vencer', 'badge-amber'],
        'en_gracia'  => ['En gracia', 'badge-red'],
        'bloqueada'  => ['Bloqueada', 'badge-red'],
    ];
@endphp
<div class="flex flex-col gap-4">

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-brand-500"></span>Barberías</p>
            <p class="kpi-value">{{ $stats['total'] }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-emerald-500"></span>Activas</p>
            <p class="kpi-value">{{ $stats['activas'] }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-amber-500"></span>En gracia</p>
            <p class="kpi-value">{{ $stats['en_gracia'] }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-rose-500"></span>Bloqueadas</p>
            <p class="kpi-value">{{ $stats['bloqueadas'] }}</p>
        </div>
        <div class="kpi col-span-2 md:col-span-1">
            <p class="kpi-label"><span class="kpi-dot bg-slate-400"></span>Recaudo del mes</p>
            <p class="kpi-value">{{ $dinero($stats['recaudo']) }}</p>
        </div>
    </div>

    @error('devolucion')<div class="alert-error">{{ $message }}</div>@enderror

    <div class="flex justify-end">
        <button type="button" wire:click="nuevo" class="btn btn-primary">+ Nueva barbería</button>
    </div>

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_100px_70px_150px_90px_340px]">
            <span>Barbería</span>
            <span class="text-center">Plan</span>
            <span class="text-center">Ventas</span>
            <span class="text-center">Vence / saldo</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($barberias as $barberia)
            <div wire:key="barberia-{{ $barberia->id }}" class="tabla-row md:grid-cols-[1fr_100px_70px_150px_90px_340px]">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $barberia->nombre }}</p>
                        <p class="text-xs text-muted">
                            {{ $barberia->propietario ?: '—' }}
                            @if($barberia->telefono) · <a href="tel:{{ $barberia->telefono }}" class="hover:text-brand-700">{{ $barberia->telefono }}</a>@endif
                        </p>
                    </div>
                    <span class="md:hidden">
                        <span class="badge {{ $barberia->activo ? 'badge-green' : 'badge-red' }}">{{ $barberia->activo ? 'Activa' : 'Inactiva' }}</span>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs md:contents md:text-sm">
                    <span class="md:text-center"><span class="badge badge-blue">{{ $barberia->nombre_plan }}</span></span>
                    <span class="text-muted md:text-center md:font-semibold md:text-ink"><span class="dato">Ventas:</span>{{ $barberia->ventas_count }}</span>
                    <span class="md:text-center">
                        @if($barberia->por_uso)
                            <span class="dato">Saldo:</span><span class="{{ $barberia->estado_pago === 'al_dia' ? 'text-muted' : 'text-rose-600' }}">{{ $dinero($barberia->saldo) }}</span>
                            <span class="block text-xs text-muted max-md:inline max-md:ml-1">saldo</span>
                        @elseif($barberia->fecha_vencimiento)
                            <span class="dato">Vence:</span><span class="{{ in_array($barberia->estado_pago, ['en_gracia', 'bloqueada']) ? 'text-rose-600' : 'text-muted' }}">{{ $barberia->fecha_vencimiento->format('d/m/Y') }}</span>
                            <span class="block text-xs max-md:inline max-md:ml-1">
                                <span class="badge {{ $estados[$barberia->estado_pago][1] }}">{{ $barberia->estado_pago === 'al_dia' || $barberia->estado_pago === 'por_vencer' ? $barberia->dias_restantes . ' días' : $estados[$barberia->estado_pago][0] }}</span>
                            </span>
                        @else
                            —
                        @endif
                        @if($barberia->en_contrato_minimo)
                            <span class="block text-[11px] text-muted max-md:inline max-md:ml-1">Contrato hasta {{ $barberia->contrato_hasta->format('d/m/y') }}</span>
                        @endif
                    </span>
                    <span class="hidden text-center md:block">
                        <span class="badge {{ $barberia->activo ? 'badge-green' : 'badge-red' }}">{{ $barberia->activo ? 'Activa' : 'Inactiva' }}</span>
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 md:flex md:flex-wrap md:justify-end md:gap-1">
                    <button type="button" wire:click="verCredenciales({{ $barberia->id }})" class="btn btn-sm btn-violet">Acceso</button>
                    <button type="button" wire:click="editar({{ $barberia->id }})" class="btn btn-sm btn-soft">Editar</button>
                    <button type="button" wire:click="abrirPago({{ $barberia->id }})" class="btn btn-sm btn-primary">{{ $barberia->por_uso ? 'Recargar' : 'Registrar pago' }}</button>
                    @if((float) $barberia->saldo > 0)
                        <button type="button" wire:click="devolverSaldo({{ $barberia->id }})" wire:confirm="¿Devolver a {{ $barberia->nombre }} su saldo a favor de {{ $dinero($barberia->saldo) }}? El saldo queda en $0." class="btn btn-sm btn-light">Devolver saldo</button>
                    @endif
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
                        <select id="plan" wire:model.live="plan" class="input">
                            @foreach($opciones as $clave => $texto)
                                <option value="{{ $clave }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                        @if($editandoId)
                            <p class="mt-1 text-xs text-muted">Cambiar el plan no cambia la fecha pagada: el nuevo plan se cobra en el siguiente pago. Si pasa al plan por uso, recarga su saldo para que pueda vender.</p>
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
                        <div class="sm:col-span-2">
                            <label class="label" for="valorPago">{{ $plan === 'porcentaje' ? 'Recarga inicial' : 'Primer pago recibido' }}</label>
                            <input id="valorPago" wire:model="valorPago" type="number" min="0" step="1000" inputmode="numeric" class="input">
                            <p class="mt-1 text-xs text-muted">
                                @if($plan === 'porcentaje')
                                    Mínimo {{ $dinero(config('suscripcion.porcentaje.primera_recarga_minima')) }}. Déjalo en 0 si aún no ha pagado: no podrá entrar hasta recargar.
                                @else
                                    Déjalo en 0 si aún no ha pagado: tendrá {{ config('suscripcion.dias_gracia') }} días de gracia.
                                @endif
                                Se anota en los gastos de la barbería.
                            </p>
                        </div>
                        <label class="flex items-center gap-2 text-sm sm:col-span-2">
                            <input type="checkbox" wire:model="cobrarInstalacion" class="rounded border-slate-300 text-brand-700">
                            Pagó la instalación y entrega ({{ $dinero(config('suscripcion.instalacion')) }})
                        </label>
                        <p class="text-xs text-muted sm:col-span-2">Comparte el correo y la contraseña con el cliente para que pueda entrar.</p>
                    @endunless
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Registrar pago o recarga --}}
    @if($mostrarPago && $pagoDe)
        <div class="modal-fondo" wire:click.self="cerrarPago">
            <form wire:submit="registrarPago" class="modal-panel">
                <div>
                    <h3 class="modal-titulo">{{ $pagoDe->por_uso ? 'Recargar saldo' : 'Registrar pago' }}</h3>
                    <p class="mt-1 text-xs text-muted">
                        {{ $pagoDe->nombre }} · Plan {{ $pagoDe->nombre_plan }}
                        @if($pagoDe->por_uso)
                            · Saldo actual {{ $dinero($pagoDe->saldo) }}
                        @elseif($pagoDe->fecha_vencimiento)
                            · Pagado hasta {{ $pagoDe->fecha_vencimiento->format('d/m/Y') }}
                        @endif
                    </p>
                </div>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    @if($pagoDe->plan === 'mensual')
                        <div>
                            <label class="label" for="pagoCantidad">Meses que paga</label>
                            <input id="pagoCantidad" wire:model.live.debounce.400ms="pagoCantidad" type="number" min="1" max="24" inputmode="numeric" class="input">
                        </div>
                    @endif
                    <div class="{{ $pagoDe->plan === 'mensual' ? '' : 'sm:col-span-2' }}">
                        <label class="label" for="pagoValor">Valor recibido</label>
                        <input id="pagoValor" wire:model="pagoValor" type="number" min="0" step="1000" inputmode="numeric" class="input">
                    </div>
                    <div>
                        <label class="label" for="pagoMetodo">Método</label>
                        <select id="pagoMetodo" wire:model="pagoMetodo" class="input">
                            <option value="transferencia">Transferencia</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="pagoNota">Nota</label>
                        <input id="pagoNota" wire:model="pagoNota" type="text" placeholder="Referencia, opcional" class="input">
                    </div>
                </div>
                @unless($pagoDe->instalacion_pagada)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="pagoInstalacion" class="rounded border-slate-300 text-brand-700">
                        También pagó la instalación y entrega ({{ $dinero(config('suscripcion.instalacion')) }})
                    </label>
                @endunless
                <p class="text-xs text-muted">
                    @if($pagoDe->por_uso)
                        El valor se suma al saldo. Cada venta descuenta el {{ str_replace('.', ',', config('suscripcion.porcentaje.valor')) }}% de su valor.
                    @else
                        Si paga dentro de los días de gracia, el periodo sigue desde su vencimiento; si ya estaba bloqueada, desde hoy.
                    @endif
                    El pago queda anotado en los gastos de la barbería.
                </p>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cerrarPago" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar pago</button>
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
                    <p class="mt-1 text-xs text-muted">Correo actual: <span class="font-semibold text-ink">{{ $credencialesEmail }}</span></p>
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
