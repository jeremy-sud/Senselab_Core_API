<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PermissionService;

class CheckPermission
{
    /**
     * The permission service instance.
     *
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * Create a new middleware instance.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

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

        // Verificar si el usuario tiene al menos uno de los permisos usando cache
        $hasPermission = false;
        
        if (count($permissions) === 1) {
            // Un solo permiso - verificación directa con cache
            $hasPermission = $this->permissionService->userHasPermission($user, $permissions[0]);
        } else {
            // Múltiples permisos - verificar si tiene alguno con cache
            $hasPermission = $this->permissionService->userHasAnyPermission($user, $permissions);
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
