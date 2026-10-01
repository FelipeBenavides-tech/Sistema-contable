<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head', ['themeColor' => '#0A1628'])
    <title>{{ $title ?? 'Panel' }} · Kaixa Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
    <header class="sticky top-0 z-30 bg-ink text-white pt-[env(safe-area-inset-top)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent-500 text-ink">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold">Kaixa Admin</p>
                    <p class="truncate text-xs text-white/60">{{ auth()->user()->name }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-white/20 px-3 py-1.5 text-xs text-white/80 hover:bg-white/10">Salir</button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl p-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:p-6">
        <h1 class="mb-4 text-lg font-bold">{{ $title ?? 'Panel' }}</h1>
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
