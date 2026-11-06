<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las pantallas administrativas de la barbería son solo para el dueño.
 * Un barbero que intente entrar va a su agenda.
 */
class VerificarDueno
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->esBarbero()) {
            return redirect()->route('mi-agenda');
        }

        return $next($request);
    }
}
