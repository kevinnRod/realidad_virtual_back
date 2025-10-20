<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado y sea admin
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json([
                'message' => 'No autorizado. Se requieren permisos de administrador.'
            ], 403);
        }

        return $next($request);
    }
}