<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LogAccesoSistema;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para logs de acceso al sistema
 * Auditoría de login, logout y intentos fallidos
 */
class LogAccesoSistemaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['logs-acceso', 'auditoria'];
    protected int $cacheTTL = 600; // 10 minutos - logs muy dinámicos

    /**
     * Listar logs de acceso
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LogAccesoSistema::class);

        $cacheKey = $this->getCacheKey('index', [
            'tipo_evento' => $request->input('tipo_evento'),
            'usuario_id' => $request->input('usuario_id'),
            'ip_address' => $request->input('ip_address'),
            'dias' => $request->input('dias', 30),
            'per_page' => $request->input('per_page', 50)
        ]);

        $logs = $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = LogAccesoSistema::with('usuario');

            if ($request->filled('tipo_evento')) {
                $query->where('tipo_evento', $request->tipo_evento);
            }

            if ($request->filled('usuario_id')) {
                $query->porUsuario($request->usuario_id);
            }

            if ($request->filled('ip_address')) {
                $query->porIP($request->ip_address);
            }

            $dias = $request->input('dias', 30);
            $query->ultimos($dias);

            return $query->orderBy('creado_en', 'desc')
                ->paginate($request->input('per_page', 50));
        });

        return response()->json(['success' => true, 'data' => $logs]);
    }

    /**
     * Crear log de acceso (normalmente automático)
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', LogAccesoSistema::class);

        $validated = $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'email' => 'required|email',
            'tipo_evento' => 'required|in:login_exitoso,login_fallido,logout',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
            'metodo_autenticacion' => 'nullable|string|max:50',
            'razon_fallo' => 'nullable|string|max:255',
            'sesion_id' => 'nullable|string|max:100'
        ]);

        $log = LogAccesoSistema::create($validated);

        $this->flushCache(['logs-acceso', 'auditoria']);

        return response()->json(['success' => true, 'data' => $log], 201);
    }

    /**
     * Mostrar log específico
     */
    public function show(LogAccesoSistema $logAccesoSistema): JsonResponse
    {
        $this->authorize('view', $logAccesoSistema);
        $logAccesoSistema->load('usuario');
        return response()->json(['success' => true, 'data' => $logAccesoSistema]);
    }

    /**
     * Actualizar log (solo duracion_sesion normalmente)
     */
    public function update(Request $request, LogAccesoSistema $logAccesoSistema): JsonResponse
    {
        $this->authorize('update', $logAccesoSistema);

        $validated = $request->validate([
            'duracion_sesion' => 'nullable|integer|min:0',
            'pais' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100'
        ]);

        $logAccesoSistema->update($validated);

        $this->flushCache(['logs-acceso', 'auditoria']);

        return response()->json(['success' => true, 'data' => $logAccesoSistema]);
    }

    /**
     * Eliminar log (soft delete para auditoría)
     */
    public function destroy(LogAccesoSistema $logAccesoSistema): JsonResponse
    {
        $this->authorize('delete', $logAccesoSistema);

        $logAccesoSistema->delete();

        $this->flushCache(['logs-acceso', 'auditoria']);

        return response()->json(['success' => true, 'message' => 'Log eliminado exitosamente']);
    }
}
