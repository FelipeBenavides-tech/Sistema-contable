@php
    $dinero = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.');
    $estado = $barberia->estado_pago;
    $porcentaje = str_replace('.', ',', config('suscripcion.porcentaje.valor'));
    $etiquetas = [
        'al_dia'     => ['Al día', 'badge-green'],
        'por_vencer' => [$barberia->por_uso ? 'Saldo bajo' : 'Por vencer', 'badge-amber'],
        'en_gracia'  => ['Vencido', 'badge-red'],
        'bloqueada'  => ['Deshabilitado', 'badge-red'],
    ];
    $mensajePago = rawurlencode("Hola, quiero pagar Kaixa para {$barberia->nombre}");
@endphp
<div class="flex flex-col gap-5">

    {{-- Estado actual --}}
    <section class="card card-pad">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="card-title">Tu plan</p>
                <p class="mt-1 text-xl font-semibold text-ink">{{ $barberia->nombre_plan }}</p>
            </div>
            <span class="badge {{ $etiquetas[$estado][1] }}">{{ $etiquetas[$estado][0] }}</span>
        </div>

        @if($estado === 'en_gracia')
            <div class="alert-error mt-4">
                Tu plan venció el <strong>{{ $barberia->fecha_vencimiento->format('d/m/Y') }}</strong>.
                Tienes hasta el <strong>{{ $barberia->fecha_limite->format('d/m/Y') }}</strong> para pagar; después el sistema se deshabilita.
            </div>
        @elseif($estado === 'por_vencer')
            <div class="alert-warning mt-4">
                @if($barberia->por_uso)
                    Tu saldo está bajo. Cuando llegue a $0 no podrás registrar ventas hasta recargar.
                    Te alcanza para vender {{ $dinero((float) $barberia->saldo * 100 / (float) config('suscripcion.porcentaje.valor')) }} más.
                @else
                    Tu plan vence en {{ $barberia->dias_restantes }} {{ $barberia->dias_restantes === 1 ? 'día' : 'días' }}. Paga a tiempo para no tener interrupciones.
                @endif
            </div>
        @endif

        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            @if($barberia->por_uso)
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Saldo disponible</dt>
                    <dd class="mt-0.5 text-lg font-semibold {{ $estado === 'al_dia' ? 'text-ink' : 'text-rose-600' }}">{{ $dinero($barberia->saldo) }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Cobrado este mes</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-ink">{{ $dinero($cobradoMes) }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Contrato mínimo</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-ink">{{ $barberia->contrato_hasta ? 'Hasta ' . $barberia->contrato_hasta->format('d/m/Y') : 'No aplica' }}</dd>
                </div>
            @else
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Pagado hasta</dt>
                    <dd class="mt-0.5 text-lg font-semibold {{ in_array($estado, ['en_gracia', 'bloqueada']) ? 'text-rose-600' : 'text-ink' }}">{{ $barberia->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Valor del siguiente pago</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-ink">{{ $dinero($barberia->precio_plan) }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <dt class="text-xs text-muted">Contrato mínimo</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-ink">
                        {{ $barberia->plan === 'mensual' && $barberia->contrato_hasta ? 'Hasta ' . $barberia->contrato_hasta->format('d/m/Y') : 'No aplica' }}
                    </dd>
                </div>
            @endif
        </dl>

        <div class="mt-4 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">
                Cada pago {{ $barberia->por_uso ? 'o recarga ' : '' }}se anota solo en tus <a href="{{ route('gastos') }}" class="font-medium text-brand-700 hover:underline">Gastos</a>, en la categoría "Suscripción Kaixa".
            </p>
            @if($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}?text={{ $mensajePago }}" target="_blank" rel="noopener" class="btn btn-primary shrink-0">
                    {{ $barberia->por_uso ? 'Recargar saldo' : 'Pagar mi plan' }}
                </a>
            @else
                <p class="text-sm font-medium text-ink">Para pagar o cambiar de plan, contacta a soporte de Kaixa.</p>
            @endif
        </div>
    </section>

    {{-- Planes disponibles --}}
    <section>
        <h2 class="section-title mb-3">Planes de Kaixa</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($planes as $clave => $plan)
                @php $actual = $barberia->plan === $clave; @endphp
                <div class="card card-pad relative flex flex-col {{ $actual ? 'ring-2 ring-brand-600' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold text-ink">{{ $plan['nombre'] }}</p>
                        @if($actual)
                            <span class="badge badge-blue">Tu plan</span>
                        @elseif($plan['ahorro_porcentaje'] > 0)
                            <span class="badge badge-green">Ahorra {{ $plan['ahorro_porcentaje'] }}%</span>
                        @endif
                    </div>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">{{ $dinero($plan['precio']) }}</p>
                    <p class="text-xs text-muted">
                        {{ $plan['meses'] === 1 ? 'cada mes' : "por {$plan['meses']} meses · " . $dinero($plan['precio_mes']) . ' al mes' }}
                    </p>
                    <ul class="mt-4 flex flex-1 flex-col gap-1.5 text-sm text-slate-600">
                        <li>Ventas y servicios ilimitados</li>
                        @if($plan['ahorro'] > 0)
                            <li class="font-medium text-emerald-700">Ahorras {{ $dinero($plan['ahorro']) }} frente al mensual</li>
                        @endif
                        @if($plan['meses'] === 1)
                            <li>Contrato mínimo de {{ config('suscripcion.contrato_minimo_meses') }} meses</li>
                        @else
                            <li>Un solo pago, sin contrato adicional</li>
                        @endif
                    </ul>
                </div>
            @endforeach

            @php $actual = $barberia->por_uso; @endphp
            <div class="card card-pad relative flex flex-col {{ $actual ? 'ring-2 ring-brand-600' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold text-ink">{{ config('suscripcion.porcentaje.nombre') }}</p>
                    @if($actual)<span class="badge badge-blue">Tu plan</span>@endif
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">{{ $porcentaje }}%</p>
                <p class="text-xs text-muted">de cada servicio o artículo vendido</p>
                <ul class="mt-4 flex flex-1 flex-col gap-1.5 text-sm text-slate-600">
                    <li>Pagas solo por lo que vendes</li>
                    <li>Primera recarga de mínimo {{ $dinero(config('suscripcion.porcentaje.primera_recarga_minima')) }}; después, desde {{ $dinero(config('suscripcion.porcentaje.recarga_minima')) }}</li>
                    <li>Ejemplo: un corte de $20.000 descuenta {{ $dinero(20000 * config('suscripcion.porcentaje.valor') / 100) }}</li>
                    <li>Contrato mínimo de {{ config('suscripcion.contrato_minimo_meses') }} meses; al terminar, el saldo a favor se te devuelve</li>
                </ul>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-600">
            <span class="font-medium text-ink">Instalación y entrega del sistema: {{ $dinero(config('suscripcion.instalacion')) }}</span>, pago único para cualquier plan.
        </p>
        <p class="mt-1 text-xs text-muted">
            Si un pago vence, tienes {{ config('suscripcion.dias_gracia') }} días de gracia para pagar. Pasado ese tiempo el sistema se deshabilita hasta que se registre el pago; tus datos se conservan.
        </p>
    </section>

    {{-- Historial --}}
    <section class="tabla">
        <div class="border-b border-line px-4 py-3">
            <h2 class="section-title">Pagos y recargas</h2>
        </div>
        <div class="tabla-head md:grid-cols-[110px_1fr_150px_120px]">
            <span>Fecha</span>
            <span>Detalle</span>
            <span>Cubre</span>
            <span class="text-right">Valor</span>
        </div>
        @forelse($pagos as $pago)
            <div wire:key="pago-{{ $pago->id }}" class="tabla-row md:grid-cols-[110px_1fr_150px_120px]">
                <div class="flex items-center justify-between md:contents">
                    <span class="text-muted">{{ $pago->fecha->format('d/m/Y') }}</span>
                    <span class="font-semibold md:hidden">{{ $dinero($pago->valor) }}</span>
                </div>
                <span>
                    {{ match ($pago->plan) {
                        'porcentaje' => 'Recarga de saldo',
                        'devolucion' => 'Devolución de saldo a favor',
                        'instalacion' => 'Instalación y entrega del sistema',
                        default      => 'Plan ' . (config("suscripcion.planes.{$pago->plan}.nombre") ?? $pago->plan) . " · {$pago->meses} " . ($pago->meses === 1 ? 'mes' : 'meses'),
                    } }}
                    <span class="text-xs text-muted">· {{ ucfirst($pago->metodo_pago) }}</span>
                </span>
                <span class="text-xs text-muted md:text-sm">
                    @if($pago->cubre_hasta)<span class="dato">Cubre:</span>{{ $pago->cubre_desde->format('d/m/y') }} – {{ $pago->cubre_hasta->format('d/m/y') }}@endif
                </span>
                <span class="hidden text-right font-semibold md:block">{{ $dinero($pago->valor) }}</span>
            </div>
        @empty
            <p class="tabla-vacia">Todavía no hay pagos registrados.</p>
        @endforelse
    </section>
</div>
