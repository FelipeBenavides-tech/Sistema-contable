@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $categorias = [
        'arriendo'           => 'Arriendo',
        'servicios_publicos' => 'Servicios públicos',
        'insumos'            => 'Insumos',
        'nomina'             => 'Nómina',
        'publicidad'         => 'Publicidad',
        'mantenimiento'      => 'Mantenimiento',
        'operativo'          => 'Operativo',
        'otros'              => 'Otros',
    ];
    $metodos = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'];
@endphp
<div class="flex flex-col gap-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="kpi sm:min-w-[240px]">
            <p class="kpi-label">Gastos de {{ now()->translatedFormat('F') }}</p>
            <p class="kpi-value text-red-600">{{ $dinero($totalMes) }}</p>
        </div>
        <button type="button" wire:click="nuevo" class="btn btn-accent">+ Registrar gasto</button>
    </div>

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_150px_120px_100px_110px_100px]">
            <span>Concepto</span>
            <span>Categoría</span>
            <span>Pago</span>
            <span class="text-center">Fecha</span>
            <span class="text-right">Valor</span>
            <span class="text-right">Acción</span>
        </div>

        @forelse($gastos as $gasto)
            <div wire:key="gasto-{{ $gasto->id }}" class="tabla-row md:grid-cols-[1fr_150px_120px_100px_110px_100px]">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $gasto->concepto }}</p>
                        @if($gasto->observacion)
                            <p class="mt-0.5 text-xs text-muted">{{ $gasto->observacion }}</p>
                        @endif
                    </div>
                    <span class="font-bold text-red-600 md:hidden">{{ $dinero($gasto->valor) }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-muted md:contents">
                    <span><span class="badge badge-violet">{{ $categorias[$gasto->categoria] ?? $gasto->categoria }}</span></span>
                    <span>{{ $metodos[$gasto->metodo_pago] ?? $gasto->metodo_pago }}</span>
                    <span class="text-xs md:text-center">{{ $gasto->fecha->format('d/m/Y') }}</span>
                </div>
                <span class="hidden text-right font-semibold text-red-600 md:block">{{ $dinero($gasto->valor) }}</span>
                <div class="md:flex md:justify-end">
                    <button type="button" wire:click="eliminar({{ $gasto->id }})" wire:confirm="¿Seguro que deseas eliminar este gasto?" class="btn btn-sm btn-danger w-full md:w-auto">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">No hay gastos registrados.</p>
        @endforelse
    </div>

    @if($mostrarForm)
        <div class="modal-fondo" wire:click.self="cancelar">
            <form wire:submit="guardar" class="modal-panel">
                <h3 class="modal-titulo">Nuevo gasto</h3>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="label" for="concepto">Concepto</label>
                        <input id="concepto" wire:model="concepto" type="text" placeholder="Arriendo, luz, insumos…" class="input" autofocus>
                    </div>
                    <div>
                        <label class="label" for="valor">Valor (COP)</label>
                        <input id="valor" wire:model="valor" type="number" inputmode="numeric" min="0" step="1000" class="input">
                    </div>
                    <div>
                        <label class="label" for="fecha">Fecha</label>
                        <input id="fecha" wire:model="fecha" type="date" class="input">
                    </div>
                    <div>
                        <label class="label" for="categoria">Categoría</label>
                        <select id="categoria" wire:model="categoria" class="input">
                            @foreach($categorias as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="metodo_pago">Pagado con</label>
                        <select id="metodo_pago" wire:model="metodo_pago" class="input">
                            @foreach($metodos as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="label" for="observacion">Observación (opcional)</label>
                        <input id="observacion" wire:model="observacion" type="text" class="input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cancelar" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
