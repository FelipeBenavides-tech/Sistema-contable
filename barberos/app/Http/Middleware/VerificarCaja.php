<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los negocios de "solo agendamiento de citas" no tienen caja, ventas,
 * inventario, gastos, cartera, membresías ni reportes: van a su agenda.
 */
class VerificarCaja
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->barberia?->solo_agenda) {
            return redirect()->route('agenda');
        }

        return $next($request);
    }
}
