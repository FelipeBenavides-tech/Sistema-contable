<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaixa — Acceso restringido</title>
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
        }
        .bg-orb-2 {
            width: 400px; height: 400px;
            background: {{ $tipo === 'vencida' ? '#FF6B00' : ($tipo === 'inactiva' ? '#DC2626' : '#FFB800') }};
            bottom: -100px; right: -100px;
            animation-delay: 3s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        .bg-dots {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            margin: 20px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 48px 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            text-align: center;
        }

        .icon-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 28px;
        }
        .icon-ring {
            position: absolute;
            inset: 0;
            border-radius: 22px;
            background: linear-gradient(135deg,
                {{ $tipo === 'vencida' ? '#FF6B00, #FFB800' : ($tipo === 'inactiva' ? '#DC2626, #FF6B00' : '#0047FF, #FFB800') }});
            animation: spin-slow 6s linear infinite;
        }
        .icon-ring-inner {
            position: absolute;
            inset: 2px;
            border-radius: 20px;
            background: #050D1F;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        .icon-pulse {
            position: absolute;
            inset: -6px;
            border-radius: 28px;
            border: 2px solid rgba(255,255,255,0.1);
            animation: pulse-ring 3s ease-out infinite;
        }
        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse-ring {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.3); }
        }

        .logo-name {
            font-size: 14px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF, #FFB800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            background: {{ $tipo === 'vencida' ? 'rgba(255,107,0,0.15)' : ($tipo === 'inactiva' ? 'rgba(220,38,38,0.15)' : 'rgba(255,184,0,0.15)') }};
            border: 1px solid {{ $tipo === 'vencida' ? 'rgba(255,107,0,0.3)' : ($tipo === 'inactiva' ? 'rgba(220,38,38,0.3)' : 'rgba(255,184,0,0.3)') }};
            color: {{ $tipo === 'vencida' ? '#FF6B00' : ($tipo === 'inactiva' ? '#FCA5A5' : '#FFB800') }};
        }
        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        h1 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .subtitle {
            font-size: 14px;
            color: rgba(255,255,255,0.45);
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .info-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 28px;
            text-align: left;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }
        .info-row:not(:last-child) {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            background: {{ $tipo === 'vencida' ? '#FF6B00' : ($tipo === 'inactiva' ? '#DC2626' : '#FFB800') }};
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0047FF, #0066FF);
            color: white;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,71,255,0.4);
        }

        .footer {
            margin-top: 28px;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-dots"></div>

    <div class="card">

        <p class="logo-name">Kaixa</p>

        {{-- Ícono animado --}}
        <div class="icon-wrap">
            <div class="icon-pulse"></div>
            <div class="icon-ring"></div>
            <div class="icon-ring-inner">
                @if($tipo === 'vencida') ⏰
                @elseif($tipo === 'inactiva') 🔒
                @else ⚠️
                @endif
            </div>
        </div>

        {{-- Badge de estado --}}
        <div>
            <span class="badge">
                <span class="badge-dot"></span>
                @if($tipo === 'vencida') Suscripción vencida
                @elseif($tipo === 'inactiva') Cuenta suspendida
                @else Sin acceso asignado
                @endif
            </span>
        </div>

        {{-- Título --}}
        <h1>
            @if($tipo === 'vencida') Tu plan ha expirado
            @elseif($tipo === 'inactiva') Acceso suspendido
            @else Cuenta no configurada
            @endif
        </h1>

        {{-- Subtítulo --}}
        <p class="subtitle">
            @if($tipo === 'vencida')
                Tu período de suscripción ha llegado a su fin. Para continuar usando Kaixa
                y acceder a todos tus datos, renueva tu plan.
            @elseif($tipo === 'inactiva')
                Tu cuenta ha sido suspendida temporalmente. Esto puede deberse a un pago
                pendiente o a una decisión administrativa.
            @else
                Tu cuenta no tiene un negocio asignado. Contacta al administrador
                para que configure tu acceso correctamente.
            @endif
        </p>

        {{-- Info box --}}
        <div class="info-box">
            <div class="info-row">
                <span class="info-dot"></span>
                <span>{{ auth()->user()->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-dot"></span>
                <span>{{ auth()->user()->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-dot"></span>
                <span>
                    @if($tipo === 'vencida') Para renovar contacta a soporte de Kaixa
                    @elseif($tipo === 'inactiva') Contacta al administrador para reactivar tu cuenta
                    @else Contacta al administrador para configurar tu acceso
                    @endif
                </span>
            </div>
        </div>

        {{-- Botón cerrar sesión --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                ← Cerrar sesión
            </button>
        </form>

        <p class="footer">© 2026 Kaixa · Todos los derechos reservados</p>

    </div>
</body>
</html>
