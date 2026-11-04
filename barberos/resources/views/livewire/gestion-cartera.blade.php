@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
@endphp
<div class="flex flex-col gap-4">

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-rose-500"></span>Por cobrar</p>
            <p class="kpi-value">{{ $dinero($totalDeuda) }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-amber-500"></span>Clientes que deben</p>
            <p class="kpi-value">{{ $deben }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-rose-700"></span>Deben pagar ya</p>
            <p class="kpi-value">{{ $bloqueados }}</p>
        </div>
        <div class="kpi">
            <p class="kpi-label"><span class="kpi-dot bg-slate-400"></span>Servicios a fiar</p>
            @if($editandoLimite)
                <form wire:submit="guardarLimite" class="mt-1 flex items-center gap-2">
                    <input wire:model="limite" type="number" min="1" max="50" class="input !min-h-[36px] w-20">
                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                </form>
                @error('limite')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            @else
                <p class="kpi-value flex items-baseline gap-2">{{ $limite }}
                    <button type="button" wire:click="$set('editandoLimite', true)" class="text-xs font-medium text-brand-600 hover:text-brand-800">Cambiar</button>
                </p>
            @endif
        </div>
    </div>

    <p class="text-sm text-muted">
        A los clientes en cartera se les fían hasta {{ $limite }} servicios. Al cumplir el servicio número {{ $limite }} no se les fía más hasta que paguen la totalidad.
        Para agregar un cliente a la cartera, márcalo en <a href="{{ route('clientes') }}" class="font-medium text-brand-700 hover:underline">Clientes</a>. Para fiarle, escoge "Fiado" al cobrar en la caja.
    </p>

    @if($exito)
        <div class="alert-success">{{ $exito }}</div>
    @endif

    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por nombre o teléfono" class="input sm:max-w-xs">

    <div class="tabla">
        <div class="tabla-head md:grid-cols-[1fr_170px_120px_110px_130px_210px]">
            <span>Cliente</span>
            <span>Servicios fiados</span>
            <span class="text-right">Debe</span>
            <span>Último fiado</span>
            <span class="text-center">Estado</span>
            <span class="text-right">Acciones</span>
        </div>

        @forelse($clientes as $c)
            @php
                $fiados = $c->servicios_fiados;
                $limiteCliente = $c->limite_fiado;
                $estado = match (true) {
                    (float) $c->deuda_total <= 0 => ['Al día', 'badge-green'],
                    $c->fiado_bloqueado          => ['Debe pagar todo', 'badge-red'],
                    default                      => ['Con deuda', 'badge-amber'],
                };
            @endphp
            <div wire:key="cartera-{{ $c->id }}" class="tabla-row md:grid-cols-[1fr_170px_120px_110px_130px_210px]">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $c->nombre }}</p>
                        @if($c->telefono)<p class="text-xs text-muted">{{ $c->telefono }}</p>@endif
                    </div>
                    <span class="badge {{ $estado[1] }} md:hidden">{{ $estado[0] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold">{{ $fiados }} / {{ $limiteCliente }}</span>
                    <span class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                        <span class="block h-full rounded-full {{ $fiados >= $limiteCliente ? 'bg-rose-500' : 'bg-amber-500' }}" style="width: {{ min(100, $limiteCliente ? round($fiados / $limiteCliente * 100) : 0) }}%"></span>
                    </span>
                </div>
                <span class="md:text-right {{ (float) $c->deuda_total > 0 ? 'font-semibold text-rose-600' : 'text-muted' }}"><span class="dato">Debe:</span>{{ $dinero($c->deuda_total) }}</span>
                <span class="text-muted"><span class="dato">Último fiado:</span>{{ $c->ultimo_fiado ? \Carbon\Carbon::parse($c->ultimo_fiado)->format('d/m/Y') : '—' }}</span>
                <span class="hidden text-center md:block"><span class="badge {{ $estado[1] }}">{{ $estado[0] }}</span></span>
                <div class="grid grid-cols-2 gap-2 md:flex md:justify-end md:gap-1">
                    <button type="button" wire:click="verDetalle({{ $c->id }})" class="btn btn-sm btn-light">Detalle</button>
                    <button type="button" wire:click="abrirPago({{ $c->id }})" @disabled((float) $c->deuda_total <= 0) class="btn btn-sm btn-success">Registrar pago</button>
                </div>
            </div>
        @empty
            <p class="tabla-vacia">No hay clientes en cartera. Márcalos en Clientes con "Se le puede fiar".</p>
        @endforelse
    </div>

    {{-- Registrar pago --}}
    @if($mostrarPago && $pagoDe)
        <div class="modal-fondo" wire:click.self="cerrarPago">
            <form wire:submit="registrarPago" class="modal-panel">
                <div>
                    <h3 class="modal-titulo">Pago de {{ $pagoDe->nombre }}</h3>
                    <p class="text-sm text-muted">Debe {{ $dinero($pagoDe->deuda_total) }} · {{ $pagoDe->servicios_fiados }} servicios fiados</p>
                </div>

                @if($errors->any())
                    <div class="alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="pagoValor">Valor que paga</label>
                        <input id="pagoValor" wire:model.live.debounce.400ms="pagoValor" type="number" inputmode="numeric" min="0" step="1000" class="input">
                        @if($pagoDe->fiado_bloqueado)
                            <p class="mt-1 text-xs text-muted">Ya llegó al límite: hasta que pague todo no se le puede fiar otra vez.</p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <p class="label">Método de pago</p>
                        <div class="segmentado flex w-full">
                            @foreach(['efectivo' => 'Efectivo', 'nequi' => 'Nequi', 'combinado' => 'Combinado'] as $valor => $texto)
                                <button type="button" wire:click="$set('pagoMetodo', '{{ $valor }}')" class="segmento {{ $pagoMetodo === $valor ? 'segmento-activo' : '' }}">{{ $texto }}</button>
                            @endforeach
                        </div>
                    </div>
                    @if($pagoMetodo === 'combinado')
                        <div>
                            <label class="label" for="pagoEfectivo">Efectivo</label>
                            <input id="pagoEfectivo" wire:model="pagoEfectivo" type="number" inputmode="numeric" min="0" step="1000" class="input">
                        </div>
                        <div>
                            <label class="label" for="pagoNequi">Nequi</label>
                            <input id="pagoNequi" wire:model="pagoNequi" type="number" inputmode="numeric" min="0" step="1000" class="input">
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="label" for="pagoNota">Nota</label>
                        <input id="pagoNota" wire:model="pagoNota" type="text" placeholder="Opcional" class="input">
                    </div>
                </div>
                <p class="text-xs text-muted">El pago entra a la caja de hoy y se descuenta de los servicios fiados más antiguos.</p>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                    <button type="button" wire:click="cerrarPago" class="btn btn-light">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar pago</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Detalle --}}
    @if($detalle)
        @php $c = $detalle['cliente']; @endphp
        <div class="modal-fondo" wire:click.self="cerrarDetalle">
            <div class="modal-panel max-w-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="modal-titulo">{{ $c->nombre }}</h3>
                        <p class="text-sm text-muted">Debe {{ $dinero($c->deuda) }} · {{ $c->servicios_fiados }} de {{ $c->limite_fiado }} servicios fiados</p>
                    </div>
                    <button type="button" wire:click="cerrarDetalle" class="text-sm text-muted hover:text-ink" aria-label="Cerrar">✕</button>
                </div>

                <div>
                    <p class="card-title mb-1">Servicios fiados</p>
                    @forelse($detalle['fiados'] as $v)
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-1.5 text-sm last:border-b-0">
                            <span class="min-w-0 truncate"><span class="text-muted">{{ $v->fecha->format('d/m/Y') }}</span> · {{ $v->items->pluck('nombre_servicio')->implode(', ') }}{{ $v->barbero ? ' · ' . $v->barbero->nombre : '' }}</span>
                            <span class="shrink-0 text-right">
                                <span class="font-medium">{{ $dinero($v->total) }}</span>
                                <span class="block text-xs {{ (float) $v->saldo_pendiente > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ (float) $v->saldo_pendiente > 0 ? 'Debe ' . $dinero($v->saldo_pendiente) : 'Pagado' }}</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No tiene servicios fiados.</p>
                    @endforelse
                </div>

                <div>
                    <p class="card-title mb-1">Pagos</p>
                    @forelse($detalle['pagos'] as $p)
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-1.5 text-sm last:border-b-0">
                            <span class="min-w-0 truncate"><span class="text-muted">{{ $p->fecha->format('d/m/Y') }}</span> · {{ ucfirst($p->metodo_pago) }}{{ $p->nota ? ' · ' . $p->nota : '' }}</span>
                            <span class="shrink-0 font-medium text-emerald-700">{{ $dinero($p->valor) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Sin pagos registrados.</p>
                    @endforelse
                </div>

                <div class="flex justify-end">
                    @if($c->deuda > 0)
                        <button type="button" wire:click="abrirPago({{ $c->id }})" class="btn btn-primary">Registrar pago</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
