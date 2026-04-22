<div class="flex flex-col gap-5">

    {{-- KPI total mes --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl p-4" style="background:var(--surface);border:1px solid var(--border)">
            <p class="text-xs mb-2" style="color:var(--muted)">Total gastos este mes</p>
            <p class="text-2xl font-brand" style="color:#EF9A9A">
                ${{ number_format($totalMes, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Botón nuevo --}}
    <div class="flex justify-end">
        <button wire:click="nuevo"
                class="px-4 py-2.5 rounded-lg text-sm font-medium"
                style="background:#C9A84C;color:#111318">
            + Registrar gasto
        </button>
    </div>

    {{-- Formulario --}}
    @if($mostrarForm)
    <div class="rounded-xl p-5 flex flex-col gap-4"
         style="background:var(--surface);border:1px solid var(--border)">
        <h3 class="font-medium" style="color:#C9A84C">Nuevo gasto</h3>

        @if($errors->any())
        <div class="rounded-lg p-3 text-sm" style="background:rgba(244,67,54,0.12);color:#EF9A9A">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Concepto</label>
                <input wire:model="concepto" type="text" placeholder="Arriendo, insumos, servicios..."
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Categoría</label>
                <select wire:model="categoria"
                        class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                        style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
                    <option value="arriendo">Arriendo</option>
                    <option value="servicios_publicos">Servicios públicos</option>
                    <option value="insumos">Insumos</option>
                    <option value="nomina">Nómina</option>
                    <option value="publicidad">Publicidad</option>
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="operativo">Operativo</option>
                    <option value="otros">Otros</option>
                </select>
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Valor (COP)</label>
                <input wire:model="valor" type="number" min="0" step="1000"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Método de pago</label>
                <select wire:model="metodo_pago"
                        class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                        style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            <div>
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Fecha</label>
                <input wire:model="fecha" type="date"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
            <div class="col-span-2">
                <label class="text-xs mb-1.5 block" style="color:var(--muted)">Observación (opcional)</label>
                <input wire:model="observacion" type="text"
                       class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                       style="background:var(--dark);border:1px solid var(--border);color:var(--text)">
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <button wire:click="cancelar"
                    class="px-4 py-2 rounded-lg text-sm"
                    style="background:var(--dark);color:var(--muted);border:1px solid var(--border)">
                Cancelar
            </button>
            <button wire:click="guardar"
                    class="px-4 py-2 rounded-lg text-sm font-medium"
                    style="background:#C9A84C;color:#111318">
                Guardar
            </button>
        </div>
    </div>
    @endif

    {{-- Tabla de gastos --}}
    <div style="overflow-x:auto;border-radius:12px;border:1px solid var(--border)">
<div style="min-width:Xpx">
    {{-- contenido de la tabla --}}
</div>
</div>
        <div class="px-4 py-3 text-xs font-medium grid gap-2"
             style="background:var(--surface);color:var(--muted);grid-template-columns:1fr 120px 100px 90px 80px 60px">
            <span>Concepto</span>
            <span>Categoría</span>
            <span>Método</span>
            <span class="text-center">Fecha</span>
            <span class="text-right">Valor</span>
            <span class="text-center">Acción</span>
        </div>

        @forelse($gastos as $gasto)
        <div class="px-4 py-3 text-sm grid gap-2 border-t items-center"
             style="border-color:var(--border);grid-template-columns:1fr 120px 100px 90px 80px 60px">
            <div>
                <p class="font-medium">{{ $gasto->concepto }}</p>
                @if($gasto->observacion)
                <p class="text-xs mt-0.5" style="color:var(--muted)">{{ $gasto->observacion }}</p>
                @endif
            </div>
            <span>
                <span class="px-2 py-0.5 rounded-full text-xs"
                      style="background:rgba(156,39,176,0.15);color:#CE93D8">
                    {{ $gasto->categoria }}
                </span>
            </span>
            <span style="color:var(--muted)">{{ $gasto->metodo_pago }}</span>
            <span class="text-center text-xs" style="color:var(--muted)">
                {{ $gasto->fecha->format('d/m/Y') }}
            </span>
            <span class="text-right font-medium" style="color:#EF9A9A">
                ${{ number_format($gasto->valor, 0, ',', '.') }}
            </span>
            <div class="flex justify-center">
                <button wire:click="eliminar({{ $gasto->id }})"
                        wire:confirm="¿Seguro que deseas eliminar este gasto?"
                        class="px-2 py-1 rounded text-xs"
                        style="background:rgba(244,67,54,0.15);color:#EF9A9A">
                    Eliminar
                </button>
            </div>
        </div>
        @empty
        <div class="px-4 py-10 text-center text-sm" style="color:var(--muted)">
            No hay gastos registrados
        </div>
        @endforelse
    </div>

</div>
