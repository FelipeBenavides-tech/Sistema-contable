<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Tipos de ingreso que muestra el login, con su nombre para pantalla. */
    public const TIPOS = [
        'cliente' => 'Cliente',
        'barbero' => 'Barbero',
        'negocio' => 'Negocio',
        'admin'   => 'Administrador del sistema',
    ];

    /**
     * Display the login view. Sin ?como= muestra los 4 botones; con él, el formulario de ese tipo.
     */
    public function create(Request $request): View
    {
        $como = $request->query('como');

        return view('auth.login', [
            'como'  => array_key_exists($como, self::TIPOS) ? $como : null,
            'tipos' => self::TIPOS,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();
        $tipoReal = self::tipoDe($user);
        $tipoElegido = $request->input('tipo');

        // Entró por el botón equivocado: se le dice cuál usar en vez de mandarlo a otro panel sin aviso
        if ($tipoElegido && array_key_exists($tipoElegido, self::TIPOS) && $tipoElegido !== $tipoReal) {
            Auth::guard('web')->logout();

            return redirect()->route('login', ['como' => $tipoReal])
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Esta cuenta es de tipo «' . self::TIPOS[$tipoReal] . '». Escribe de nuevo tu contraseña aquí.']);
        }

        $request->session()->regenerate();

        return match ($tipoReal) {
            'admin'   => redirect()->route('admin.panel'),
            'barbero' => redirect()->route('mi-agenda'),
            default   => redirect()->intended(route('pos', absolute: false)),
        };
    }

    /**
     * Cliente: no tiene contraseña. Escribe su correo o celular y le enviamos al correo
     * el enlace privado de "Mis citas" de cada barbería donde está registrado.
     */
    public function enlaceCliente(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            ['contacto' => ['required', 'string', 'max:120']],
            ['contacto.required' => 'Escribe tu correo o tu celular.']
        );

        $contacto = trim($datos['contacto']);
        $clave = 'enlace-cliente:' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return back()->withInput()->withErrors(['contacto' => 'Hiciste demasiados intentos. Espera un momento.']);
        }
        RateLimiter::hit($clave, 3600);

        $esCorreo = str_contains($contacto, '@');
        $valor = $esCorreo ? mb_strtolower($contacto) : Cliente::normalizarTelefono($contacto);

        $clientes = $valor === '' ? collect() : Cliente::with('barberia')
            ->when($esCorreo,
                fn($q) => $q->whereRaw('LOWER(email) = ?', [$valor]),
                fn($q) => $q->where('telefono', $valor))
            ->whereNotNull('email')->where('email', '!=', '')
            ->get()
            ->filter(fn($c) => $c->barberia?->recibe_reservas);

        // Un correo por dirección, con los enlaces de todas sus barberías
        foreach ($clientes->groupBy(fn($c) => mb_strtolower($c->email)) as $grupo) {
            $primero = $grupo->first();
            $lineas = $grupo->map(fn($c) => "- {$c->barberia->nombre}: " . $c->urlPanel())->implode("\n");

            try {
                Mail::raw(
                    "Hola {$primero->nombre}, estos son tus enlaces para ver y manejar tus citas:\n\n{$lineas}\n\nGuárdalos: son personales.",
                    fn($m) => $m->to($primero->email)->subject('Tus citas')
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el enlace de Mis citas desde el login', ['cliente' => $primero->id, 'error' => $e->getMessage()]);
            }
        }

        // Mismo mensaje siempre, para no revelar quién está registrado
        return redirect()->route('login', ['como' => 'cliente'])
            ->with('status', 'Si tus datos están registrados en una barbería, te enviamos al correo el enlace de tus citas. Si no te llega, escríbele a tu barbería.');
    }

    /** Qué panel le corresponde a una cuenta. */
    public static function tipoDe(User $user): string
    {
        return match (true) {
            $user->isAdmin()    => 'admin',
            $user->esBarbero()  => 'barbero',
            default             => 'negocio',
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
