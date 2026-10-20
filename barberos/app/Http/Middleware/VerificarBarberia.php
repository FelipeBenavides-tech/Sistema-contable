<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarBarberia
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // El administrador no tiene caja propia: va a su panel
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.panel');
        }

        if ($user && !$user->tieneBarberia()) {
            return response()->view('errors.sin-barberia', [
                'tipo'    => 'sin_asignar',
                'mensaje' => 'Tu cuenta no tiene una barbería asignada.',
            ], 403);
        }

        if ($user && $user->barberia && !$user->barberia->activo) {
            return response()->view('errors.sin-barberia', [
                'tipo'    => 'inactiva',
                'mensaje' => 'Tu suscripción está inactiva.',
            ], 403);
        }

        if ($user && $user->barberia && $user->barberia->vencida) {
            return response()->view('errors.sin-barberia', [
                'tipo'    => 'vencida',
                'mensaje' => 'Tu suscripción ha vencido.',
            ], 403);
        }

        return $next($request);
    }
}
