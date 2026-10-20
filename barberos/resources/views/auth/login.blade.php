<!DOCTYPE html>
<html lang="es">
<head>
    @include('partials.head')
    <title>Iniciar sesión · Kaixa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Panel de marca (solo computador) --}}
        <aside class="relative hidden overflow-hidden bg-ink p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                 style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 48px 48px;"></div>

            <div class="relative flex items-center gap-3">
                <img src="{{ asset('icon.svg') }}" alt="" class="h-10 w-10 rounded-lg">
                <span class="text-lg font-semibold tracking-tight">Kaixa</span>
            </div>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-semibold leading-tight tracking-tight">La caja de tu barbería, clara y en orden.</h2>
                <ul class="mt-8 space-y-4 text-sm text-slate-300">
                    @foreach([
                        'Ventas del día en efectivo y Nequi, al instante.',
                        'Comisiones de cada barbero calculadas solas.',
                        'Inventario, gastos y reportes en Excel o PDF.',
                    ] as $punto)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/10 text-accent-400">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $punto }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-slate-500">© {{ date('Y') }} Kaixa</p>
        </aside>

        {{-- Formulario --}}
        <main class="flex flex-col items-center justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('icon.svg') }}" alt="" class="h-10 w-10 rounded-lg">
                    <span class="text-lg font-semibold tracking-tight">Kaixa</span>
                </div>

                <h1 class="text-2xl font-semibold tracking-tight text-ink">Iniciar sesión</h1>
                <p class="mt-2 text-sm text-muted">Ingresa con el correo que te dio el administrador.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    @if($errors->any())
                        <div class="alert-error">{{ $errors->first() }}</div>
                    @endif

                    <div>
                        <label for="email" class="label">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" inputmode="email"
                               autocomplete="username" placeholder="tu@correo.com" class="input" required autofocus>
                    </div>

                    <div>
                        <label for="password" class="label">Contraseña</label>
                        <input id="password" type="password" name="password" autocomplete="current-password"
                               class="input" required>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        Mantener la sesión abierta
                    </label>

                    <button type="submit" class="btn btn-primary w-full">Entrar</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
