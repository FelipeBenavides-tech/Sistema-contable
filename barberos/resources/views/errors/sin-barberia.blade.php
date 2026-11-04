@php
    $textos = [
        'vencida' => [
            'etiqueta' => 'Sistema deshabilitado por falta de pago',
            'titulo'   => 'Tu plan está vencido',
            'detalle'  => 'Pasaron los ' . config('suscripcion.dias_gracia') . ' días de gracia sin registrar el pago. Tus datos están guardados: apenas se registre el pago, vuelves a entrar con todo como lo dejaste.',
            'ayuda'    => 'Para pagar, contacta a soporte de Kaixa.',
            'badge'    => 'badge-red',
        ],
        'sin_saldo' => [
            'etiqueta' => 'Sin saldo',
            'titulo'   => 'Se acabó tu saldo de Kaixa',
            'detalle'  => 'En el plan por uso, cada venta descuenta el ' . str_replace('.', ',', (isset($barberia) ? $barberia->porcentaje_uso : config('suscripcion.porcentaje.valor'))) . '% de su valor. Recarga para seguir vendiendo; tus datos están guardados.',
            'ayuda'    => 'Para recargar, contacta a soporte de Kaixa.',
            'badge'    => 'badge-red',
        ],
        'inactiva' => [
            'etiqueta' => 'Cuenta suspendida',
            'titulo'   => 'Acceso suspendido',
            'detalle'  => 'Tu cuenta está suspendida temporalmente. Puede deberse a un pago pendiente o a una decisión administrativa.',
            'ayuda'    => 'Contacta al administrador para reactivar tu cuenta.',
            'badge'    => 'badge-red',
        ],
        'sin_asignar' => [
            'etiqueta' => 'Sin acceso asignado',
            'titulo'   => 'Cuenta no configurada',
            'detalle'  => 'Tu cuenta no tiene una barbería asignada. El administrador debe configurar tu acceso.',
            'ayuda'    => 'Contacta al administrador para configurar tu acceso.',
            'badge'    => 'badge-gray',
        ],
    ];
    $t = $textos[$tipo] ?? $textos['sin_asignar'];
    $barberia = $barberia ?? null;
    $whatsapp = preg_replace('/\D/', '', (string) config('suscripcion.whatsapp_soporte'));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
    <title>Acceso restringido · Kaixa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="card w-full max-w-md p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('icon.svg') }}" alt="" class="h-9 w-9 rounded-lg">
                <span class="font-semibold tracking-tight">Kaixa</span>
            </div>

            <span class="badge {{ $t['badge'] }}">{{ $t['etiqueta'] }}</span>
            <h1 class="mt-3 text-xl font-semibold text-ink">{{ $t['titulo'] }}</h1>
            <p class="mt-2 text-sm text-muted">{{ $t['detalle'] }}</p>

            <dl class="mt-6 divide-y divide-slate-100 rounded-lg border border-line text-sm">
                <div class="flex justify-between gap-4 px-4 py-3">
                    <dt class="text-muted">Usuario</dt>
                    <dd class="truncate font-medium">{{ auth()->user()->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 px-4 py-3">
                    <dt class="text-muted">Correo</dt>
                    <dd class="truncate font-medium">{{ auth()->user()->email }}</dd>
                </div>
                @if($barberia)
                    <div class="flex justify-between gap-4 px-4 py-3">
                        <dt class="text-muted">Plan</dt>
                        <dd class="font-medium">{{ $barberia->nombre_plan }}</dd>
                    </div>
                    @if($barberia->por_uso)
                        <div class="flex justify-between gap-4 px-4 py-3">
                            <dt class="text-muted">Saldo</dt>
                            <dd class="font-medium text-rose-600">${{ number_format((float) $barberia->saldo, 0, ',', '.') }}</dd>
                        </div>
                    @elseif($barberia->fecha_vencimiento)
                        <div class="flex justify-between gap-4 px-4 py-3">
                            <dt class="text-muted">Venció el</dt>
                            <dd class="font-medium text-rose-600">{{ $barberia->fecha_vencimiento->format('d/m/Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 px-4 py-3">
                            <dt class="text-muted">Valor a pagar</dt>
                            <dd class="font-medium">${{ number_format($barberia->precio_plan, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                @endif
            </dl>
            <p class="mt-4 text-sm text-slate-600">{{ $t['ayuda'] }}</p>
            @if($barberia && $whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero pagar Kaixa para ' . $barberia->nombre) }}"
                   target="_blank" rel="noopener" class="btn btn-primary mt-4 w-full">Escribir a soporte por WhatsApp</a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="btn btn-light w-full">Cerrar sesión</button>
            </form>
        </div>
    </main>
</body>
</html>
