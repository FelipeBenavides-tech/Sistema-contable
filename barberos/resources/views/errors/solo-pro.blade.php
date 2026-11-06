@php
    $whatsapp = preg_replace('/\D/', '', (string) config('suscripcion.whatsapp_soporte'));
    $esBarbero = auth()->user()?->esBarbero();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
    <title>Plan Pro · Kaixa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="card w-full max-w-md p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('icon.svg') }}" alt="" class="h-9 w-9 rounded-lg">
                <span class="font-semibold tracking-tight">Kaixa</span>
            </div>

            <span class="badge badge-amber">Plan Pro</span>
            <h1 class="mt-3 text-xl font-semibold text-ink">La agenda de citas es del plan Pro</h1>
            <p class="mt-2 text-sm text-muted">
                {{ $barberia->nombre }} tiene el plan {{ $barberia->nombre_plan }}.
                {{ config('suscripcion.niveles.pro.descripcion') }}
            </p>

            @if($esBarbero)
                <p class="mt-4 text-sm text-slate-600">Pídele al dueño de la barbería que active el plan Pro.</p>
            @else
                @if($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero pasar a Kaixa Pro para ' . $barberia->nombre) }}"
                       target="_blank" rel="noopener" class="btn btn-primary mt-6 w-full">Pedir el plan Pro</a>
                @else
                    <p class="mt-4 text-sm text-slate-600">Para pasar a Pro, contacta a soporte de Kaixa.</p>
                @endif
                <a href="{{ route('mi-plan') }}" class="btn btn-light mt-2 w-full">Ver mi plan</a>
                <a href="{{ route('pos') }}" class="btn btn-light mt-2 w-full">Volver a la caja</a>
            @endif

            @if($esBarbero)
                <form method="POST" action="{{ route('logout') }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn btn-light w-full">Cerrar sesión</button>
                </form>
            @endif
        </div>
    </main>
</body>
</html>
