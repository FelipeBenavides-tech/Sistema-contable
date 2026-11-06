<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
    <title>@yield('titulo') · Kaixa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="card w-full max-w-md p-6 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('icon.svg') }}" alt="" class="h-9 w-9 rounded-lg">
                <span class="font-semibold tracking-tight">Kaixa</span>
            </div>

            <span class="badge badge-gray">Error @yield('codigo')</span>
            <h1 class="mt-3 text-xl font-semibold text-ink">@yield('titulo')</h1>
            <p class="mt-2 text-sm text-muted">@yield('detalle')</p>

            <a href="{{ url('/') }}" class="btn btn-primary mt-6 w-full">@yield('boton', 'Volver al inicio')</a>
        </div>
    </main>
</body>
</html>
