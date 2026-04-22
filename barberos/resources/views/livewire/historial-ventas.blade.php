<div class="flex flex-col gap-5">

    {{-- Modal de auditoría --}}
    @if($mostrarAuditoria)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:center;justify-content:center;padding:20px">
        <div class="rounded-2xl flex flex-col gap-4"
             style="background:var(--surface);border:1px solid var(--border);width:580px;max-width:95vw;max-height:85vh;overflow-y:auto;padding:24px">

            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-base" style="color:var(--text)">
                    Historial de cambios — Venta #{{ $auditoriaData['venta']['id'] }}
                </h3>
                <button wire:click="cerrarAuditoria"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-sm"
                        style="background:#F1F5F9;color:var(--muted)">✕</button>
            </div>

            <div class="rounded-xl p-4 flex flex-col gap-2"
                 style="background:#F8FAFC;border:1px solid var(--border)">
                <p class="text-xs font-semibold" style="color:var(--muted)">Detalle de la venta</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($auditoriaData['venta']['items'] as $item)
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="background:#EFF6FF;color:#2563EB">
                        {{ $item['nombre'] }} × {{ $item['cantidad'] }}
                    </span>
                    @endforeach
                </div>
                <div class="grid grid-cols-3 gap-3 mt-1">
                    <div>
                        <p class="text-xs" style="color:var(--muted)">Barbero</p>
                        <p class="text-sm font-semibold">{{ $auditoriaData['venta']['barbero'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--muted)">Método</p>
                        <p class="text-sm font-semibold">{{ ucfirst($auditoriaData['venta']['metodo']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color:var(--muted)">Total actual</p>
                        <p class="text-sm font-semibold"
                           style="color:{{ $auditoriaData['venta']['eliminada'] ? '#DC2626' : 'var(--primary)' }}">
                            @if($auditoriaData['venta']['eliminada'])
                                <span class="line-through">${{ number_format($auditoriaData['venta']['total_orig'], 0, ',', '.') }}</span>
                                <span class="ml-1">$0</span>
                            @else
                                ${{ number_format($auditoriaData['venta']['total'], 0, ',', '.') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if(count($auditoriaData['auditorias']) > 0)
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold" style="color:var(--muted)">Registro de cambios</p>
                @foreach($auditoriaData['auditorias'] as $auditoria)
                <div class="rounded-xl p-4 flex flex-col gap-2"
                     style="background:{{ $auditoria['accion'] === 'eliminada' ? '#FEF2F2' : '#FFFBEB' }};
                            border:1px solid {{ $auditoria['accion'] === 'eliminada' ? '#FECACA' : '#FDE68A' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                              style="background:{{ $auditoria['accion'] === 'eliminada' ? '#DC2626' : '#D97706' }};color:white">
                            {{ ucfirst($auditoria['accion']) }}
                        </span>
                        <span class="text-xs" style="color:var(--muted)">
                            {{ $auditoria['fecha'] }} — {{ $auditoria['usuario'] }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold mb-1" style="color:var(--muted)">Motivo:</p>
                        <p class="text-sm" style="color:var(--text)">{{ $auditoria['motivo'] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-2" style="border-top:1px solid rgba(0,0,0,0.08)">
                        <div>
                            <p class="text-xs font-semibold mb-1" style="color:var(--muted)">Antes</p>
                            <p class="text-sm">Total: <strong>${{ number_format($auditoria['total_antes'], 0, ',', '.') }}</strong></p>
                            @if($auditoria['barbero_antes'])
                            <p class="text-xs" style="color:var(--muted)">Barbero: {{ $auditoria['barbero_antes'] }}</p>
                            @endif
                            @if($auditoria['metodo_antes'])
                            <p class="text-xs" style="color:var(--muted)">Método: {{ ucfirst($auditoria['metodo_antes']) }}</p>
                            @endif
                        </div>
                        @if($auditoria['accion'] === 'editada')
                        <div>
                            <p class="text-xs font-semibold mb-1" style="color:var(--muted)">Después</p>
                            <p class="text-sm">Total: <strong style="color:var(--primary)">${{ number_format($auditoria['total_despues'], 0, ',', '.') }}</strong></p>
                            @if($auditoria['metodo_despues'])
                            <p class="text-xs" style="color:var(--muted)">Método: {{ ucfirst($auditoria['metodo_despues']) }}</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="rounded-xl p-4 text-center text-sm" style="color:var(--muted);background:#F8FAFC">
                No hay cambios registrados para esta venta.
            </div>
            @endif

            @if($auditoriaData['venta']['eliminada'])
            <div class="rounded-xl p-4 flex flex-col gap-3"
                 style="background:#F0FDF4;border:1px solid #BBF7D0">
                <p class="text-sm font-semibold" style="color:#16A34A">¿Restaurar productos al inventario?</p>
                <p class="text-xs" style="color:#16A34A;opacity:0.8">
                    Esta venta fue eliminada. ¿Deseas devolver los productos vendidos al inventario?
                </p>
                <div class="flex gap-3">
                    <button wire:click="restaurarInventario({{ $auditoriaData['venta']['id'] }}, true)"
                            class="px-4 py-2 rounded-xl text-sm font-semibold"
                            style="background:#16A34A;color:white">
                        Sí, restaurar stock
                    </button>
                    <button wire:click="restaurarInventario({{ $auditoriaData['venta']['id'] }}, false)"
                            class="px-4 py-2 rounded-xl text-sm"
                            style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                        No, cerrar
                    </button>
                </div>
            </div>
            @endif

            <button wire:click="cerrarAuditoria"
                    class="w-full py-2.5 rounded-xl text-sm font-medium"
                    style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                Cerrar
            </button>
        </div>
    </div>
    @endif

    {{-- Modal de confirmación con contraseña --}}
    @if($mostrarModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:center;justify-content:center">
        <div class="rounded-2xl p-6 flex flex-col gap-4"
             style="background:var(--surface);border:1px solid var(--border);width:480px;max-width:90vw">
            <div>
                <h3 class="font-semibold text-base" style="color:var(--text)">
                    {{ $accion === 'eliminar' ? 'Eliminar venta' : 'Editar venta' }}
                </h3>
                <p class="text-sm mt-1" style="color:var(--muted)">
                    Por seguridad ingresa el motivo y tu contraseña.
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
                    Motivo <span style="color:#DC2626">*</span>
                </label>
                <textarea wire:model="motivo"
                          placeholder="Escribe el motivo (mínimo 10 letras, sin números ni símbolos)..."
                          rows="3"
                          class="w-full rounded-xl px-3 py-2.5 text-sm outline-none resize-none"
                          style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)"></textarea>
                <p class="text-xs mt-1" style="color:var(--muted)">
                    {{ strlen($motivo) }}/10 caracteres mínimo · Solo letras y espacios
                </p>
            </div>

            <div>
                <label class="text-xs font-semibold mb-1.5 block" style="color:var(--muted)">
                    Contraseña <span style="color:#DC2626">*</span>
                </label>
                <input wire:model="passwordConfirm"
                       type="password"
                       placeholder="Tu contraseña"
                       wire:keydown.enter="verificarPassword"
                       class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                       style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
                @if($errorPassword)
                <p class="text-xs mt-1" style="color:#DC2626">{{ $errorPassword }}</p>
                @endif
            </div>

            <div class="flex gap-3 justify-end">
                <button wire:click="cancelarModal"
                        class="px-4 py-2 rounded-xl text-sm"
                        style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                    Cancelar
                </button>
                <button wire:click="verificarPassword"
                        class="px-4 py-2 rounded-xl text-sm font-semibold"
                        style="background:{{ $accion === 'eliminar' ? '#DC2626' : 'var(--primary)' }};color:white">
                    {{ $accion === 'eliminar' ? 'Eliminar' : 'Continuar' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal de edición --}}
    @if($mostrarEdicion)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:center;justify-content:center">
        <div class="rounded-2xl p-6 flex flex-col gap-4"
             style="background:var(--surface);border:1px solid var(--border);width:520px;max-width:90vw;max-height:90vh;overflow-y:auto">
            <h3 class="font-semibold text-base" style="color:var(--text)">
                Editar venta #{{ $ventaId }}
            </h3>

            @if($errors->any())
            <div class="rounded-xl p-3 text-sm"
                 style="background:#FEF2F2;border:1px solid #FECACA;color:#DC2626">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif

            <div>
                <label class="text-xs font-semibold mb-1.5 block" style="color:var(--muted)">Barbero</label>
                <select wire:model="editBarberoId"
                        class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
                    <option value="0">— Sin barbero (venta directa) —</option>
                    @foreach($barberos as $b)
                    <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold mb-1.5 block" style="color:var(--muted)">Método de pago</label>
                <select wire:model.live="editMetodoPago"
                        class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        style="background:#F8FAFC;border:1px solid var(--border);color:var(--text)">
                    <option value="efectivo">Efectivo</option>
                    <option value="nequi">Nequi</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="combinado">Combinado</option>
                </select>
            </div>

            @if($editMetodoPago === 'combinado')
            <div class="grid grid-cols-2 gap-3 rounded-xl p-3"
                 style="background:#FFFBEB;border:1px solid #FDE68A">
                <div>
                    <label class="text-xs mb-1 block font-medium" style="color:#92400E">Efectivo</label>
                    <input wire:model="editEfectivo" type="number" min="0"
                           class="w-full rounded-xl px-3 py-2 text-sm outline-none"
                           style="background:white;border:1px solid #FDE68A;color:var(--text)">
                </div>
                <div>
                    <label class="text-xs mb-1 block font-medium" style="color:#92400E">Nequi</label>
                    <input wire:model="editNequi" type="number" min="0"
                           class="w-full rounded-xl px-3 py-2 text-sm outline-none"
                           style="background:white;border:1px solid #FDE68A;color:var(--text)">
                </div>
            </div>
            @endif

            <div>
                <label class="text-xs font-semibold mb-2 block" style="color:var(--muted)">Servicios incluidos</label>
                <div class="flex flex-col gap-2">
                    @foreach($editItems as $index => $item)
                    <div class="flex items-center justify-between rounded-xl px-3 py-2.5"
                         style="background:#F8FAFC;border:1px solid var(--border)">
                        <div class="flex-1 min-w-0 mr-2">
                            <p class="text-sm font-medium">{{ $item['nombre_servicio'] }}</p>
                            <p class="text-xs" style="color:var(--muted)">
                                ${{ number_format($item['precio'], 0, ',', '.') }} c/u
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="disminuirCantidad({{ $index }})"
                                    class="w-7 h-7 rounded-xl flex items-center justify-center font-bold text-sm"
                                    style="background:#FEE2E2;color:#DC2626">−</button>
                            <span class="text-sm font-bold w-6 text-center" style="color:var(--text)">
                                {{ $item['cantidad'] }}
                            </span>
                            <button wire:click="aumentarCantidad({{ $index }})"
                                    class="w-7 h-7 rounded-xl flex items-center justify-center font-bold text-sm"
                                    style="background:#DCFCE7;color:#16A34A">+</button>
                            <span class="font-semibold text-sm ml-1" style="color:var(--primary);min-width:70px;text-align:right">
                                ${{ number_format($item['subtotal'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="flex justify-between items-center mt-3 pt-3"
                     style="border-top:1px solid var(--border)">
                    <span class="text-sm font-medium" style="color:var(--muted)">Total</span>
                    <span class="font-bold text-lg" style="color:var(--primary)">
                        ${{ number_format(collect($editItems)->sum('subtotal'), 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button wire:click="cancelarEdicion"
                        class="px-4 py-2 rounded-xl text-sm"
                        style="background:#F8FAFC;color:var(--muted);border:1px solid var(--border)">
                    Cancelar
                </button>
                <button wire:click="guardarEdicion"
                        class="px-4 py-2 rounded-xl text-sm font-semibold"
                        style="background:var(--primary);color:white">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Filtro por fecha --}}
    <div class="flex gap-3 items-center">
        <input wire:model.live="fecha"
               type="date"
               class="rounded-xl px-4 py-2.5 text-sm outline-none"
               style="background:var(--surface);border:1px solid var(--border);color:var(--text)">
        <span class="text-sm" style="color:var(--muted)">
            {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
        </span>
    </div>

    {{-- Tabla de ventas con scroll horizontal --}}
    <div style="overflow-x:auto;border-radius:12px;border:1px solid var(--border)">
    <div style="min-width:700px">

        <div class="px-4 py-3 text-xs font-semibold grid gap-2"
             style="background:#F8FAFC;color:var(--muted);border-bottom:1px solid var(--border);
                    grid-template-columns:80px 1fr 130px 110px 100px 120px">
            <span>#</span>
            <span>Servicios</span>
            <span>Barbero</span>
            <span>Método</span>
            <span class="text-right">Total</span>
            <span class="text-center">Acciones</span>
        </div>

        @forelse($ventas as $venta)
        <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
             style="border-color:var(--border);
                    background:{{ $venta->fue_eliminada ? '#FEF2F2' : ($venta->fue_editada ? '#FFFBEB' : 'var(--surface)') }};
                    grid-template-columns:80px 1fr 130px 110px 100px 120px">
            <div>
                <span class="text-xs font-medium" style="color:var(--muted)">#{{ $venta->id }}</span>
                @if($venta->fue_eliminada)
                <span class="block text-xs px-1.5 py-0.5 rounded-full mt-0.5"
                      style="background:#FEE2E2;color:#DC2626;font-size:10px">Eliminada</span>
                @elseif($venta->fue_editada)
                <span class="block text-xs px-1.5 py-0.5 rounded-full mt-0.5"
                      style="background:#FEF3C7;color:#D97706;font-size:10px">Editada</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-1">
                @foreach($venta->items as $item)
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background:#EFF6FF;color:#2563EB;
                             {{ $venta->fue_eliminada ? 'opacity:0.5;text-decoration:line-through;' : '' }}">
                    {{ $item->nombre_servicio }}
                </span>
                @endforeach
            </div>
            <span class="font-medium {{ $venta->fue_eliminada ? 'line-through' : '' }}"
                  style="{{ $venta->fue_eliminada ? 'color:var(--muted)' : '' }}">
                {{ $venta->barbero?->nombre ?? 'Venta directa' }}
            </span>
            <span>
                @if($venta->metodo_pago === 'efectivo')
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="background:#F0FDF4;color:#16A34A">Efectivo</span>
                @elseif($venta->metodo_pago === 'nequi')
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="background:#EFF6FF;color:#2563EB">Nequi</span>
                @elseif($venta->metodo_pago === 'transferencia')
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="background:#EFF6FF;color:#2563EB">Transferencia</span>
                @else
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          style="background:#F5F3FF;color:#7C3AED">Combinado</span>
                @endif
            </span>
            <div class="text-right">
                @if($venta->fue_eliminada)
                <span class="font-semibold line-through" style="color:var(--muted)">
                    ${{ number_format($venta->total_original ?? 0, 0, ',', '.') }}
                </span>
                <span class="block text-xs" style="color:#DC2626">$0</span>
                @else
                <span class="font-semibold" style="color:var(--primary)">
                    ${{ number_format($venta->total, 0, ',', '.') }}
                </span>
                @if($venta->fue_editada && $venta->total_original)
                <span class="block text-xs line-through" style="color:var(--muted)">
                    Antes: ${{ number_format($venta->total_original, 0, ',', '.') }}
                </span>
                @endif
                @endif
            </div>
            <div class="flex gap-1 justify-center flex-wrap">
                <button wire:click="verAuditoria({{ $venta->id }})"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#F0FDF4;color:#16A34A">
                    Ver
                </button>
                @if(!$venta->fue_eliminada)
                <button wire:click="confirmarAccion({{ $venta->id }}, 'editar')"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#EFF6FF;color:#2563EB">
                    Editar
                </button>
                <button wire:click="confirmarAccion({{ $venta->id }}, 'eliminar')"
                        class="px-2 py-1 rounded-lg text-xs font-medium"
                        style="background:#FEF2F2;color:#DC2626">
                    Eliminar
                </button>
                @else
                <span class="text-xs px-2 py-1 rounded-lg"
                      style="background:#F8FAFC;color:var(--muted)">
                    Eliminada
                </span>
                @endif
            </div>
        </div>
        @empty
        <div class="px-4 py-10 text-center text-sm" style="color:var(--muted)">
            No hay ventas registradas para esta fecha
        </div>
        @endforelse

    </div>
    </div>

    {{-- Total del día --}}
    @if($ventas->count() > 0)
    <div class="flex justify-between items-center rounded-xl px-5 py-4"
         style="background:var(--surface);border:1px solid var(--border)">
        <span class="text-sm font-medium" style="color:var(--muted)">
            Total del día — {{ $ventas->where('fue_eliminada', false)->count() }} servicios activos
        </span>
        <span class="text-2xl font-bold" style="color:var(--primary)">
            ${{ number_format($ventas->where('fue_eliminada', false)->sum('total'), 0, ',', '.') }}
        </span>
    </div>
    @endif

</div>
