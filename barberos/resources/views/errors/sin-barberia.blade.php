@php
    $textos = [
        'vencida' => [
            'etiqueta' => 'Suscripción vencida',
            'titulo'   => 'Tu plan ha expirado',
            'detalle'  => 'Tu período de suscripción llegó a su fin. Para seguir usando Kaixa y ver tus datos, renueva tu plan.',
            'ayuda'    => 'Para renovar, contacta a soporte de Kaixa.',
            'badge'    => 'badge-amber',
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
            </dl>
            <p class="mt-4 text-sm text-slate-600">{{ $t['ayuda'] }}</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="btn btn-light w-full">Cerrar sesión</button>
            </form>
        </div>
    </main>
</body>
</html>
