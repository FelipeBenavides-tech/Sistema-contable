{{-- Páginas públicas de un negocio (reservas en línea). Sin menú ni sesión. --}}
@php(\App\Support\Negocio::usar($barberia))
<!DOCTYPE html>
<html lang="es" data-tema="{{ \App\Support\Negocio::tema($barberia) }}">
<head>
    @include('partials.head')
    <title>{{ $title ?? 'Reservar' }} · {{ $barberia->nombre }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <header class="border-b border-line bg-white">
        <div class="mx-auto flex w-full max-w-3xl items-center gap-3 px-4 py-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl {{ $barberia->es_salon ? 'bg-gradient-to-br from-brand-200 to-accent-400' : 'bg-brand-900' }}">
                @if($logo = $barberia->logoDataUri())
                    <img src="{{ $logo }}" alt="" class="h-full w-full bg-white object-contain">
                @else
                    <span class="font-semibold {{ $barberia->es_salon ? 'text-white' : 'text-accent-500' }}">{{ $barberia->iniciales }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate font-semibold text-ink">{{ $barberia->nombre }}</p>
                <p class="truncate text-xs text-muted">{{ $barberia->direccion ?: 'Reserva tu cita' }}</p>
            </div>
        </div>
    </header>
    <main class="mx-auto w-full max-w-3xl p-4 pb-16 sm:p-6">
        {{ $slot }}
    </main>
    <footer class="pb-8 text-center text-xs text-muted">Reservas con Kaixa</footer>
    @livewireScripts
</body>
</html>
