<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaixa — Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #050D1F;
            overflow-x: hidden;
            position: relative;
        }

        /* Fondo animado */
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: float 8s ease-in-out infinite;
        }
        .bg-orb-1 {
            width: 500px; height: 500px;
            background: #0047FF;
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: #FFB800;
            bottom: -100px; right: -100px;
            animation-delay: 3s;
        }
        .bg-orb-3 {
            width: 300px; height: 300px;
            background: #0047FF;
            bottom: 100px; left: 200px;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        /* Grid de puntos */
        .bg-dots {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Card */
        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            margin: 20px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        /* Logo animado */
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }
        .logo-icon {
            position: relative;
            width: 64px;
            height: 64px;
            margin-right: 14px;
        }
        .logo-ring {
            position: absolute;
            inset: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #0047FF, #FFB800);
            animation: spin-slow 6s linear infinite;
        }
        .logo-ring-inner {
            position: absolute;
            inset: 2px;
            border-radius: 16px;
            background: #050D1F;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-k {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #0047FF, #FFB800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse-text 3s ease-in-out infinite;
        }
        .logo-pulse {
            position: absolute;
            inset: -4px;
            border-radius: 22px;
            border: 2px solid rgba(0,71,255,0.3);
            animation: pulse-ring 3s ease-out infinite;
        }
        .logo-pulse-2 {
            position: absolute;
            inset: -8px;
            border-radius: 26px;
            border: 1px solid rgba(255,184,0,0.2);
            animation: pulse-ring 3s ease-out infinite 1s;
        }

        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse-ring {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.3); }
        }
        @keyframes pulse-text {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .logo-text-wrap h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFB800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        .logo-text-wrap p {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
            margin-top: 2px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Formulario */
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #0047FF;
            background: rgba(0,71,255,0.08);
            box-shadow: 0 0 0 3px rgba(0,71,255,0.15);
        }
        .form-input::placeholder { color: rgba(255,255,255,0.25); }

        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0047FF, #0066FF);
            color: white;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            margin-top: 8px;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        .btn-login:hover::before { left: 100%; }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0,71,255,0.4);
        }
        .btn-login:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }
        .divider-text {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
        }

        .error-msg {
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.3);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: #FCA5A5;
            margin-bottom: 16px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,184,0,0.1);
            border: 1px solid rgba(255,184,0,0.2);
            border-radius: 100px;
            padding: 4px 12px;
            font-size: 11px;
            color: #FFB800;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #FFB800;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>
    <div class="bg-dots"></div>

    <div class="login-card">

        {{-- Badge --}}
        <div style="display:flex;justify-content:center">
            <div class="badge">
                <div class="badge-dot"></div>
                Sistema de gestión empresarial
            </div>
        </div>

        {{-- Logo --}}
        <div class="logo-wrap">
            <div class="logo-icon">
                <div class="logo-pulse"></div>
                <div class="logo-pulse-2"></div>
                <div class="logo-ring"></div>
                <div class="logo-ring-inner">
                    <span class="logo-k">K</span>
                </div>
            </div>
            <div class="logo-text-wrap">
                <h1>Kaixa</h1>
                <p>Control total</p>
            </div>
        </div>

        {{-- Formulario --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            @if($errors->any())
            <div class="error-msg">
                {{ $errors->first() }}
            </div>
            @endif

            <div style="margin-bottom:16px">
                <label class="form-label">Correo electrónico</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="tu@correo.com"
                       class="form-input"
                       required
                       autofocus>
            </div>

            <div style="margin-bottom:24px">
                <label class="form-label">Contraseña</label>
                <input type="password"
                       name="password"
                       placeholder="••••••••"
                       class="form-input"
                       required>
            </div>

            <button type="submit" class="btn-login">
                Iniciar sesión →
            </button>
        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">Kaixa v1.0</span>
            <div class="divider-line"></div>
        </div>

        <p style="text-align:center;font-size:12px;color:rgba(255,255,255,0.2)">
            © 2026 Kaixa · Todos los derechos reservados
        </p>

    </div>
</body>
</html>
