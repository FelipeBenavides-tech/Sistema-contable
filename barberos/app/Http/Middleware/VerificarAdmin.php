<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo deja pasar a los administradores del SaaS (is_admin = true).
 */
class VerificarAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isAdmin()) {
            abort(403, 'Solo el administrador puede entrar aquí.');
        }

        return $next($request);
    }
}
