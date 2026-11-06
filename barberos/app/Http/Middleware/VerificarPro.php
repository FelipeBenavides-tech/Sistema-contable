<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La agenda de citas y los usuarios de barberos son del plan Pro.
 */
class VerificarPro
{
    public function handle(Request $request, Closure $next): Response
    {
        $barberia = $request->user()?->barberia;

        if ($barberia && !$barberia->es_pro) {
            return response()->view('errors.solo-pro', ['barberia' => $barberia], 403);
        }

        return $next($request);
    }
}
