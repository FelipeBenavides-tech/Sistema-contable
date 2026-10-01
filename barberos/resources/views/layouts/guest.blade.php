<!DOCTYPE html>
<html lang="es">
    <head>
        @include('partials.head', ['themeColor' => '#050D1F'])
        <title>Kaixa</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="!bg-[#050D1F]">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('login') }}" class="mb-6 flex items-center gap-3 text-white">
                <img src="{{ asset('icon.svg') }}" alt="" class="h-11 w-11">
                <span class="text-2xl font-extrabold">Kaixa</span>
            </a>

            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl sm:p-8">
                {{ $slot }}
            </div>

            <a href="{{ route('login') }}" class="mt-6 text-sm text-white/60 hover:text-white">← Volver a iniciar sesión</a>
        </div>
    </body>
</html>
