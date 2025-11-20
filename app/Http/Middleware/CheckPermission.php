<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Slug del permiso requerido
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // Verificar si el usuario tiene el permiso
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
                'permiso_requerido' => $permission
            ], 403);
        }

        return $next($request);
    }
}
