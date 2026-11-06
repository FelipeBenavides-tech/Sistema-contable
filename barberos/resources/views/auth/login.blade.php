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

                @php
                    $iconos = [
                        'cliente' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0',
                        'barbero' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                        'negocio' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.35m-16.5 11.65V9.35m0 0a3 3 0 003.75-.62 3 3 0 004.5 0 3 3 0 004.5 0 3 3 0 003.75.62m-16.5 0a3 3 0 01-.62-4.72L4.32 3.66A1.5 1.5 0 015.38 3h13.24a1.5 1.5 0 011.06.44l1.19 1.19a3 3 0 01-.62 4.72',
                        'admin'   => 'M9 12.75L11.25 15 15 9.75m-3-7.04A11.96 11.96 0 013.6 6 12 12 0 003 9.75c0 5.6 3.82 10.3 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.29z',
                    ];
                    $ayudas = [
                        'cliente' => 'Ver, cambiar o cancelar tus citas',
                        'barbero' => 'Tu agenda del día',
                        'negocio' => 'Caja, ventas y reportes de tu barbería',
                        'admin'   => 'Barberías y suscripciones',
                    ];
                @endphp

                @if(!$como)
                    {{-- Paso 1: ¿quién eres? --}}
                    <h1 class="text-2xl font-semibold tracking-tight text-ink">Iniciar sesión</h1>
                    <p class="mt-2 text-sm text-muted">¿Cómo vas a entrar?</p>

                    @if($errors->any())
                        <div class="alert-error mt-6">{{ $errors->first() }}</div>
                    @endif

                    <div class="mt-8 grid grid-cols-2 gap-3">
                        @foreach($tipos as $clave => $nombre)
                            <a href="{{ route('login', ['como' => $clave]) }}" dusk="tipo-{{ $clave }}"
                               class="group flex flex-col items-start gap-3 rounded-xl border border-line bg-white p-4 text-left shadow-sm transition hover:border-brand-500 hover:shadow focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-brand-700 group-hover:bg-brand-700 group-hover:text-white">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconos[$clave] }}"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold leading-tight text-ink">{{ $nombre }}</span>
                                    <span class="mt-1 block text-xs leading-snug text-muted">{{ $ayudas[$clave] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Cambiar tipo de usuario
                    </a>

                    <div class="mt-6 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-700 text-white">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconos[$como] }}"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-muted">Ingreso</p>
                            <h1 class="text-xl font-semibold tracking-tight text-ink">{{ $tipos[$como] }}</h1>
                        </div>
                    </div>

                    @if($como === 'cliente')
                        {{-- El cliente no tiene contraseña: le mandamos el enlace privado de "Mis citas" --}}
                        <p class="mt-4 text-sm text-muted">Escribe el correo o el celular con el que reservas. Te enviamos al correo el enlace para ver y manejar tus citas.</p>

                        <form method="POST" action="{{ route('login.cliente') }}" class="mt-6 space-y-5">
                            @csrf

                            @if(session('status'))
                                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('status') }}</div>
                            @endif
                            @if($errors->any())
                                <div class="alert-error">{{ $errors->first() }}</div>
                            @endif

                            <div>
                                <label for="contacto" class="label">Correo o celular</label>
                                <input id="contacto" type="text" name="contacto" value="{{ old('contacto') }}"
                                       autocomplete="email" placeholder="tu@correo.com o 3001234567" class="input" required autofocus>
                            </div>

                            <button type="submit" class="btn btn-primary w-full">Enviarme el enlace</button>
                        </form>
                    @else
                        <p class="mt-4 text-sm text-muted">
                            @if($como === 'barbero')
                                Entra con el correo que te dio el dueño de la barbería.
                            @elseif($como === 'admin')
                                Acceso solo para el administrador del sistema.
                            @else
                                Ingresa con el correo que te dio el administrador.
                            @endif
                        </p>

                        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                            @csrf
                            <input type="hidden" name="tipo" value="{{ $como }}">

                            @if($errors->any())
                                <div class="alert-error">{{ $errors->first() }}</div>
                            @endif

                            <div>
                                <label for="email" class="label">Correo electrónico</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" inputmode="email"
                                       autocomplete="username" placeholder="tu@correo.com" class="input" required @if(!old('email')) autofocus @endif>
                            </div>

                            <div>
                                <label for="password" class="label">Contraseña</label>
                                <input id="password" type="password" name="password" autocomplete="current-password"
                                       class="input" required @if(old('email')) autofocus @endif>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                                Mantener la sesión abierta
                            </label>

                            <button type="submit" class="btn btn-primary w-full">Entrar</button>
                        </form>
                    @endif
                @endif
            </div>
        </main>
    </div>
</body>
</html>
