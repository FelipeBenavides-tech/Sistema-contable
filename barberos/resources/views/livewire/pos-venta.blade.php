<div class="flex flex-col lg:flex-row gap-5" style="min-height: 80vh">

    {{-- LADO IZQUIERDO --}}
    <div class="flex flex-col gap-4 flex-1">

        {{-- KPIs compactos --}}
        <div class="rounded-2xl px-4 py-3 flex items-center justify-between gap-2"
             style="background:var(--surface);border:1px solid var(--border)">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background:#EFF6FF">
                    <svg width="14" height="14" fill="none" stroke="#1A3A5C" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs" style="color:var(--muted)">Total hoy</p>
                    <p class="text-sm font-bold" style="color:var(--primary)">
                        ${{ number_format($resumenDia['total'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            <div style="width:1px;height:32px;background:var(--border)"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background:#F0FDF4">
                    <svg width="14" height="14" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs" style="color:var(--muted)">Efectivo</p>
                    <p class="text-sm font-bold" style="color:var(--success)">
                        ${{ number_format($resumenDia['total_efectivo'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            <div style="width:1px;height:32px;background:var(--border)"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background:#EFF6FF">
                    <svg width="14" height="14" fill="none" stroke="#2563EB" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs" style="color:var(--muted)">Nequi</p>
                    <p class="text-sm font-bold" style="color:#2563EB">
                        ${{ number_format($resumenDia['total_nequi'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            <div style="width:1px;height:32px;background:var(--border)"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background:#FFFBEB">
                    <svg width="14" height="14" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs" style="color:var(--muted)">Servicios</p>
                    <p class="text-sm font-bold" style="color:#D97706">
                        {{ $resumenDia['cantidad_ventas'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Filtro de categoría --}}
        <div class="flex gap-2">
            @foreach(['todos' => 'Todos', 'servicio' => 'Servicios', 'producto' => 'Productos'] as $val => $label)
            <button wire:click="$set('categoria', '{{ $val }}')"
                    class="px-4 py-2 rounded-2xl text-xs font-semibold transition-all"
                    style="{{ $categoria === $val
                        ? 'background:var(--primary);color:white;border:2px solid var(--primary);'
                        : 'background:var(--surface);color:var(--muted);border:2px solid var(--border);' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Grid de servicios Y productos del inventario juntos --}}
        <div class="grid gap-3"
             style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr))">

            {{-- Servicios --}}
            @foreach($servicios as $servicio)
            <button wire:click="agregarServicio({{ $servicio->id }})"
                    class="rounded-2xl p-4 text-left transition-all"
                    style="background:{{ isset($serviciosSeleccionados[$servicio->id]) ? '#EFF6FF' : 'var(--surface)' }};
                           border:2px solid {{ isset($serviciosSeleccionados[$servicio->id]) ? '#1A3A5C' : 'var(--border)' }};
                           cursor:pointer"
                    onmouseover="this.style.borderColor='#1A3A5C';this.style.background='#EFF6FF'"
                    onmouseout="this.style.borderColor='{{ isset($serviciosSeleccionados[$servicio->id]) ? '#1A3A5C' : 'var(--border)' }}';this.style.background='{{ isset($serviciosSeleccionados[$servicio->id]) ? '#EFF6FF' : 'var(--surface)' }}'">
                <div class="w-9 h-9 rounded-2xl flex items-center justify-center mb-2"
                     style="background:#EFF6FF">
                    <svg width="18" height="18" fill="none" stroke="#1A3A5C" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                    </svg>
                </div>
                <p class="font-semibold text-sm leading-tight mb-1" style="color:var(--text)">
                    {{ $servicio->nombre }}
                </p>
                <p class="text-base font-bold" style="color:var(--primary)">
                    ${{ number_format($servicio->precio, 0, ',', '.') }}
                </p>
            </button>
            @endforeach

            {{-- Productos del inventario --}}
            @foreach($productosInventario as $producto)
            <button wire:click="agregarProductoInventario({{ $producto->id }})"
                    class="rounded-2xl p-4 text-left transition-all"
                    style="background:{{ isset($serviciosSeleccionados['inv_'.$producto->id]) ? '#FFF7ED' : 'var(--surface)' }};
                           border:2px solid {{ isset($serviciosSeleccionados['inv_'.$producto->id]) ? '#D97706' : 'var(--border)' }};
                           cursor:pointer"
                    onmouseover="this.style.borderColor='#D97706';this.style.background='#FFF7ED'"
                    onmouseout="this.style.borderColor='{{ isset($serviciosSeleccionados['inv_'.$producto->id]) ? '#D97706' : 'var(--border)' }}';this.style.background='{{ isset($serviciosSeleccionados['inv_'.$producto->id]) ? '#FFF7ED' : 'var(--surface)' }}'">
                <div class="w-9 h-9 rounded-2xl flex items-center justify-center mb-2"
                     style="background:#FFF7ED">
                    <svg width="18" height="18" fill="none" stroke="#D97706" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="font-semibold text-sm leading-tight mb-1" style="color:var(--text)">
                    {{ $producto->nombre }}
                </p>
                <p class="text-base font-bold" style="color:#D97706">
                    ${{ number_format($producto->precio_venta, 0, ',', '.') }}
                </p>
                <p class="text-xs mt-1"
                   style="color:{{ $producto->stock_actual <= $producto->stock_minimo ? 'var(--danger)' : 'var(--muted)' }}">
                    Stock: {{ $producto->stock_actual }} {{ $producto->unidad }}
                </p>
            </button>
            @endforeach

            {{-- Mensaje vacío --}}
            @if(count($servicios) === 0 && count($productosInventario) === 0)
            <div class="col-span-full text-center py-10 rounded-2xl"
                 style="color:var(--muted);background:var(--surface);border:2px dashed var(--border)">
                <p class="font-medium text-sm">No hay servicios configurados</p>
                <a href="{{ route('servicios') }}" class="text-sm mt-1 inline-block"
                   style="color:var(--primary)">Agregar servicios →</a>
            </div>
            @endif

        </div>

    </div>

    {{-- LADO DERECHO: Panel de cobro --}}
    <div class="flex flex-col gap-3" style="width:320px;flex-shrink:0">

        {{-- Título --}}
        <div class="rounded-2xl px-5 py-4" style="background:var(--primary)">
            <p class="font-bold text-white">Realizar cobro</p>
        </div>

        {{-- Alertas --}}
        @if($ventaExitosa)
        <div class="rounded-2xl p-3 text-sm font-medium flex items-center gap-2"
             style="background:#F0FDF4;border:1px solid #BBF7D0;color:#16A34A">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ¡Venta registrada correctamente!
        </div>
        @endif

        @if($errors->any())
        <div class="rounded-2xl p-3 text-xs" style="background:#FEF2F2;color:#DC2626;border:1px solid #FECACA">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        {{-- Paso 1: Servicios seleccionados --}}
<div class="rounded-2xl p-4 flex flex-col gap-2"
     style="background:var(--surface);border:1px solid var(--border)">
    <p class="text-xs font-semibold" style="color:var(--muted)">1 · Servicios seleccionados</p>

            @if(count($carrito) > 0)
            <div class="mt-1 rounded-2xl overflow-hidden" style="border:1px solid var(--border)">
                @foreach($carrito as $id => $item)
<div class="flex items-center justify-between px-3 py-2 border-b last:border-b-0 text-xs"
     style="border-color:var(--border)">
    <div class="flex-1 min-w-0 mr-2">
        <p class="font-semibold truncate" style="color:var(--text)">
            {{ $item['nombre_servicio'] }}
        </p>
        @if($item['cantidad'] > 1)
        <p style="color:var(--muted)">× {{ $item['cantidad'] }}</p>
        @endif

    </div>
    <div class="flex items-center gap-1.5">
        <span class="font-bold" style="color:var(--primary)">
            ${{ number_format($item['subtotal'], 0, ',', '.') }}
        </span>
        <div style="display:inline">
    <input type="hidden" id="del-{{ $loop->index }}" value="{{ $id }}">
    <button type="button"
            onclick="const key = document.getElementById('del-{{ $loop->index }}').value; @this.call('quitarItem', key);"
            class="w-5 h-5 rounded-full flex items-center justify-center font-bold"
            style="background:#FEE2E2;color:#DC2626;font-size:10px">✕</button>
</div>
    </div>
</div>
@endforeach
            </div>
            @endif
        </div>

        {{-- Paso 2: Barbero --}}
        <div class="rounded-2xl p-4 flex flex-col gap-2"
             style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs font-semibold" style="color:var(--muted)">2 · Seleccione el barbero</p>
            <select onchange="@this.set('barberoId', parseInt(this.value))"
                    class="w-full rounded-2xl px-3 py-2.5 text-sm outline-none font-medium"
                    style="background:#F8FAFC;border:2px solid var(--border);color:var(--text)">
                <option value="0">— Seleccionar barbero —</option>
                @foreach($barberos as $b)
                <option value="{{ $b->id }}" {{ $barberoId == $b->id ? 'selected' : '' }}>
                    {{ $b->nombre }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Paso 3: Método de pago --}}
        <div class="rounded-2xl p-4 flex flex-col gap-2"
             style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs font-semibold" style="color:var(--muted)">3 · Método de pago</p>
            <div class="flex flex-col gap-2">
                <button wire:click="$set('metodoPago', 'efectivo')"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-semibold"
                        style="{{ $metodoPago === 'efectivo'
                            ? 'background:#F0FDF4;border:2px solid #16A34A;color:#16A34A;'
                            : 'background:#F8FAFC;border:2px solid var(--border);color:var(--muted);' }}">
                    <div class="w-8 h-8 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background:{{ $metodoPago === 'efectivo' ? '#DCFCE7' : '#F1F5F9' }}">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24"
                             stroke="{{ $metodoPago === 'efectivo' ? '#16A34A' : '#94A3B8' }}" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                        </svg>
                    </div>
                    Efectivo
                </button>
                <button wire:click="$set('metodoPago', 'nequi')"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-semibold"
                        style="{{ $metodoPago === 'nequi'
                            ? 'background:#EFF6FF;border:2px solid #2563EB;color:#2563EB;'
                            : 'background:#F8FAFC;border:2px solid var(--border);color:var(--muted);' }}">
                    <div class="w-8 h-8 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background:{{ $metodoPago === 'nequi' ? '#DBEAFE' : '#F1F5F9' }}">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24"
                             stroke="{{ $metodoPago === 'nequi' ? '#2563EB' : '#94A3B8' }}" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3h3m-3 3h3"/>
                        </svg>
                    </div>
                    Nequi / Transferencia
                </button>
                <button wire:click="$set('metodoPago', 'combinado')"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-semibold"
                        style="{{ $metodoPago === 'combinado'
                            ? 'background:#FFFBEB;border:2px solid #D97706;color:#D97706;'
                            : 'background:#F8FAFC;border:2px solid var(--border);color:var(--muted);' }}">
                    <div class="w-8 h-8 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background:{{ $metodoPago === 'combinado' ? '#FEF3C7' : '#F1F5F9' }}">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24"
                             stroke="{{ $metodoPago === 'combinado' ? '#D97706' : '#94A3B8' }}" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                    </div>
                    Combinado
                </button>
            </div>

            @if($metodoPago === 'combinado')
            <div class="rounded-2xl p-3 flex flex-col gap-2 mt-1"
                 style="background:#FFFBEB;border:1px solid #FDE68A">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs mb-1 block font-medium" style="color:#92400E">Efectivo</label>
                        <input wire:model.live="montoEfectivo" type="number" min="0" step="1000"
                               placeholder="0"
                               class="w-full rounded-xl px-3 py-2 text-sm outline-none font-medium"
                               style="background:white;border:1px solid #FDE68A;color:var(--text)">
                    </div>
                    <div>
                        <label class="text-xs mb-1 block font-medium" style="color:#92400E">Nequi</label>
                        <input wire:model.live="montoNequi" type="number" min="0" step="1000"
                               placeholder="0"
                               class="w-full rounded-xl px-3 py-2 text-sm outline-none font-medium"
                               style="background:white;border:1px solid #FDE68A;color:var(--text)">
                    </div>
                </div>
                @php $suma = $montoEfectivo + $montoNequi; @endphp
                @if($suma > 0)
                <p class="text-xs font-semibold"
                   style="color:{{ abs($suma - $this->totalCarrito) <= 1 ? '#16A34A' : '#DC2626' }}">
                    Suma: ${{ number_format($suma, 0, ',', '.') }}
                    {{ abs($suma - $this->totalCarrito) <= 1 ? '✓ Correcto' : '— Faltan $'.number_format($this->totalCarrito - $suma, 0, ',', '.') }}
                </p>
                @endif
            </div>
            @endif
        </div>

        {{-- Botón cobrar --}}
        @if(count($carrito) > 0)
        <div class="rounded-2xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold" style="color:var(--muted)">Total</span>
                <span class="text-2xl font-bold" style="color:var(--primary)">
                    ${{ number_format($this->totalCarrito, 0, ',', '.') }}
                </span>
            </div>
            <button wire:click="cobrar"
                    wire:loading.attr="disabled"
                    class="w-full py-3 rounded-2xl font-bold text-sm"
                    style="background:var(--primary);color:white">
                <span wire:loading.remove wire:target="cobrar">Registrar cobro →</span>
                <span wire:loading wire:target="cobrar">Procesando...</span>
            </button>
            <button wire:click="vaciarCarrito"
                    class="w-full mt-1.5 py-1.5 rounded-2xl text-xs"
                    style="color:var(--muted)">
                Limpiar
            </button>
        </div>
        @endif

        {{-- Historial del día --}}
        <div class="rounded-2xl overflow-hidden"
             style="border:1px solid var(--border);background:var(--surface)">
            <div class="px-4 py-3 border-b" style="border-color:var(--border);background:#F8FAFC">
                <p class="text-xs font-semibold" style="color:var(--muted)">Historial de hoy</p>
            </div>
            @forelse($ventasHoy as $v)
            <div class="px-4 py-3 border-b last:border-b-0 text-xs" style="border-color:var(--border)">
                <div class="flex justify-between items-start mb-1">
                    <span class="font-semibold" style="color:var(--text)">
    {{ $v->barbero?->nombre ?? 'Venta directa' }}
</span>
                    <span class="font-bold" style="color:var(--primary)">
                        ${{ number_format($v->total, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span style="color:var(--muted)" class="truncate mr-2">
                        {{ $v->items->pluck('nombre_servicio')->implode(', ') }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full flex-shrink-0"
                          style="background:{{ $v->metodo_pago === 'efectivo' ? '#F0FDF4' : '#EFF6FF' }};
                                 color:{{ $v->metodo_pago === 'efectivo' ? '#16A34A' : '#2563EB' }}">
                        {{ ucfirst($v->metodo_pago) }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-4 py-6 text-center text-xs" style="color:var(--muted)">
                Sin ventas registradas hoy
            </div>
            @endforelse
            <div class="px-4 py-3 flex items-center justify-between"
                 style="background:#F0F9FF;border-top:2px solid #BAE6FD">
                <span class="text-xs font-semibold" style="color:#0369A1">
                    Total de servicios hoy
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs"
                          style="background:#DBEAFE;color:#1D4ED8">
                        {{ $ventasHoy->count() }}
                    </span>
                </span>
                <span class="font-bold text-base" style="color:#0369A1">
                    ${{ number_format($ventasHoy->sum('total'), 0, ',', '.') }}
                </span>
            </div>
        </div>

    </div>
</div>
