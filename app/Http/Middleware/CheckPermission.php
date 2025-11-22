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
     * @param  string  ...$permissions  Slug(s) del/los permiso(s) requerido(s)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // Verificar si el usuario tiene al menos uno de los permisos
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
                'permisos_requeridos' => $permissions
            ], 403);
        }

        return $next($request);
    }
}
