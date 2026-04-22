<div class="flex flex-col gap-5">

    {{-- Modal de credenciales --}}
@if($mostrarCredenciales)
<div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:center;justify-content:center">
    <div class="rounded-2xl p-6 flex flex-col gap-4"
         style="background:var(--surface);border:1px solid var(--border);width:420px;max-width:90vw">
        <div>
            <h3 class="font-semibold text-base" style="color:var(--text)">
                Credenciales de acceso
            </h3>
            <p class="text-xs mt-1" style="color:var(--muted)">
                Email actual: <span class="font-medium" style="color:var(--primary)">{{ $credencialesEmail }}</span>
            </p>
        </div>

        @if($errors->any())
        <div class="rounded-xl p-3 text-xs"
             style="background:#FEF2F2;color:#DC2626;border:1px solid #FECACA">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <div>
            <label class="text-xs font-semibold mb-1.5 block" style="color:var(--muted)">
                Nuevo email
            </label>
            <input wire:model="nuevoEmail"
                   type="email"
                   placeholder="correo@ejemplo.com"
                   class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                   style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
        </div>

        <div>
            <label class="text-xs font-semibold mb-1.5 block" style="color:var(--muted)">
                Nueva contraseña
            </label>
            <input wire:model="nuevaPassword"
                   type="text"
                   placeholder="Dejar vacío para no cambiar"
                   class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                   style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            <p class="text-xs mt-1" style="color:var(--muted)">
                Si dejas la contraseña vacía no se cambiará.
            </p>
        </div>

        <div class="flex gap-3 justify-end">
            <button wire:click="cerrarCredenciales"
                    class="px-4 py-2 rounded-xl text-sm"
                    style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                Cancelar
            </button>
            <button wire:click="actualizarCredenciales"
                    class="px-4 py-2 rounded-xl text-sm font-semibold"
                    style="background:var(--primary);color:white">
                Guardar cambios
            </button>
        </div>
    </div>
</div>
@endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-1" style="color:var(--muted)">Total barberías</p>
            <p class="text-3xl font-bold" style="color:var(--primary)">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-1" style="color:var(--muted)">Activas</p>
            <p class="text-3xl font-bold" style="color:var(--success)">{{ $stats['activas'] }}</p>
        </div>
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-1" style="color:var(--muted)">Vencidas</p>
            <p class="text-3xl font-bold" style="color:var(--danger)">{{ $stats['vencidas'] }}</p>
        </div>
    </div>

    {{-- Botón nuevo --}}
    <div class="flex justify-end">
        <button wire:click="nuevo"
                class="px-4 py-2.5 rounded-xl text-sm font-semibold"
                style="background:var(--primary);color:white">
            + Nueva barbería
        </button>
    </div>

    {{-- Formulario --}}
    @if($mostrarForm)
    <div class="rounded-xl p-5 flex flex-col gap-4"
         style="background:var(--surface);border:1px solid var(--border)">
        <h3 class="font-semibold" style="color:var(--primary)">
            {{ $editandoId ? 'Editar barbería' : 'Nueva barbería' }}
        </h3>

        @if($errors->any())
        <div class="rounded-xl p-3 text-sm"
             style="background:#FEF2F2;border:1px solid #FECACA;color:#DC2626">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Nombre de la barbería *
                </label>
                <input wire:model="nombre" type="text" placeholder="Barbería El Estilo"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Propietario
                </label>
                <input wire:model="propietario" type="text" placeholder="Carlos Mendoza"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Teléfono
                </label>
                <input wire:model="telefono" type="text" placeholder="3001234567"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Dirección
                </label>
                <input wire:model="direccion" type="text" placeholder="Calle 10 # 5-20, Cali"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Plan *
                </label>
                <select wire:model="plan"
                        class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
                    <option value="mensual">Mensual — $59.900/mes</option>
                    <option value="semestral">Semestral — $299.000</option>
                    <option value="anual">Anual — $549.000</option>
                </select>
            </div>

            @if(!$editandoId)
            <div>
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Email de acceso *
                </label>
                <input wire:model="email" type="email" placeholder="barberia@ejemplo.com"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
            </div>
            <div class="col-span-2">
                <label class="text-xs font-medium mb-1.5 block" style="color:var(--muted)">
                    Contraseña inicial *
                </label>
                <input wire:model="password" type="text" placeholder="Mínimo 6 caracteres"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
                <p class="text-xs mt-1" style="color:var(--muted)">
                    Comparte estas credenciales con el cliente para que pueda ingresar.
                </p>
            </div>
            @endif
        </div>

        <div class="flex gap-3 justify-end">
            <button wire:click="cancelar"
                    class="px-4 py-2 rounded-xl text-sm"
                    style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                Cancelar
            </button>
            <button wire:click="guardar"
                    class="px-4 py-2 rounded-xl text-sm font-semibold"
                    style="background:var(--primary);color:white">
                Guardar
            </button>
        </div>
    </div>
    @endif

    {{-- Tabla de barberías --}}
    <div class="rounded-xl overflow-hidden" style="border:1px solid var(--border)">
        <div class="px-4 py-3 text-xs font-semibold grid gap-2"
             style="background:#F8FAFC;color:var(--muted);border-bottom:1px solid var(--border);
                    grid-template-columns:1fr 100px 90px 110px 80px 160px">
            <span>Barbería</span>
            <span class="text-center">Plan</span>
            <span class="text-center">Ventas</span>
            <span class="text-center">Vencimiento</span>
            <span class="text-center">Estado</span>
            <span class="text-center">Acciones</span>
        </div>

        @forelse($barberias as $barberia)
        <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
             style="border-color:var(--border);background:var(--surface);
                    grid-template-columns:1fr 100px 90px 110px 80px 160px">
            <div>
                <p class="font-semibold">{{ $barberia->nombre }}</p>
                <p class="text-xs mt-0.5" style="color:var(--muted)">
                    {{ $barberia->propietario ?? '—' }}
                    @if($barberia->telefono)
                        · {{ $barberia->telefono }}
                    @endif
                </p>
            </div>
            <span class="text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                      style="background:#EFF6FF;color:#2563EB">
                    {{ ucfirst($barberia->plan) }}
                </span>
            </span>
            <span class="text-center font-medium" style="color:var(--primary)">
                {{ $barberia->ventas_count }}
            </span>
            <span class="text-center text-xs"
                  style="color:{{ $barberia->vencida ? 'var(--danger)' : 'var(--muted)' }}">
                @if($barberia->fecha_vencimiento)
                    {{ $barberia->fecha_vencimiento->format('d/m/Y') }}
                    @if(!$barberia->vencida)
                        <br>
                        <span style="color:var(--success)">
                            {{ $barberia->dias_restantes }} días
                        </span>
                    @else
                        <br><span style="color:var(--danger)">Vencida</span>
                    @endif
                @else
                    —
                @endif
            </span>
            <span class="text-center">
                @if($barberia->activo)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background:#F0FDF4;color:#16A34A">Activa</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background:#FEF2F2;color:#DC2626">Inactiva</span>
                @endif
            </span>
            <div class="flex gap-1 justify-center flex-wrap">
                <button wire:click="verCredenciales({{ $barberia->id }})"
        class="px-2 py-1 rounded-lg text-xs font-medium"
        style="background:#F5F3FF;color:#7C3AED">
    Credenciales
</button>
                <button wire:click="editar({{ $barberia->id }})"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#EFF6FF;color:#2563EB">
                    Editar
                </button>
                <button wire:click="renovar({{ $barberia->id }})"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#F0FDF4;color:#16A34A">
                    Renovar
                </button>
                <button wire:click="toggleActivo({{ $barberia->id }})"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                    {{ $barberia->activo ? 'Desactivar' : 'Activar' }}
                </button>
                <button wire:click="eliminar({{ $barberia->id }})"
                        wire:confirm="¿Seguro? Se eliminarán todos los datos de esta barbería."
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#FEF2F2;color:#DC2626">
                    Eliminar
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-10 text-center text-sm" style="color:var(--muted)">
            No hay barberías registradas. Agrega tu primer cliente.
        </div>
        @endforelse
    </div>

</div>
