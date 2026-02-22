<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogAccesoSistemaResource;
use App\Models\LogAccesoSistema;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador para logs de acceso al sistema
 * Auditoría de login, logout y intentos fallidos
 */

#[OA\Tag(
    name: 'Logs de Acceso',
    description: 'Registro de accesos al sistema (auditoría de login/logout)'
)]
class LogAccesoSistemaController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['logs-acceso', 'auditoria'];
    protected int $cacheTTL = 600; // 10 minutos - logs muy dinámicos

    /**
     * Listar logs de acceso
     */
        #[OA\Get(
        path: '/api/log-acceso-sistema',
        summary: 'Listar logs de acceso',
        security: [['sanctum' => []]],
        tags: ['Logs de Acceso'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de logs de acceso'),
        ]
    )]

    public function index(Request $request): AnonymousResourceCollection
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

        return LogAccesoSistemaResource::collection($logs);
    }

    /**
     * Crear log de acceso (normalmente automático)
     */
        #[OA\Post(
        path: '/api/log-acceso-sistema',
        summary: 'Crear log de acceso',
        security: [['sanctum' => []]],
        tags: ['Logs de Acceso'],
        responses: [
            new OA\Response(response: 201, description: 'log de acceso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

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

        $this->flushCache();

        return (new LogAccesoSistemaResource($log))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar log de acceso
     */
        #[OA\Get(
        path: '/api/log-acceso-sistema/{id}',
        summary: 'Obtener log de acceso',
        security: [['sanctum' => []]],
        tags: ['Logs de Acceso'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'log de acceso encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(LogAccesoSistema $logAccesoSistema): LogAccesoSistemaResource
    {
        $this->authorize('view', $logAccesoSistema);
        $logAccesoSistema->load('usuario');
        return new LogAccesoSistemaResource($logAccesoSistema);
    }

    /**
     * Actualizar log (solo duracion_sesion normalmente)
     */
        #[OA\Put(
        path: '/api/log-acceso-sistema/{id}',
        summary: 'Actualizar log de acceso',
        security: [['sanctum' => []]],
        tags: ['Logs de Acceso'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'log de acceso actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(Request $request, LogAccesoSistema $logAccesoSistema): LogAccesoSistemaResource
    {
        $this->authorize('update', $logAccesoSistema);

        $validated = $request->validate([
            'duracion_sesion' => 'nullable|integer|min:0',
            'pais' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100'
        ]);

        $logAccesoSistema->update($validated);

        $this->flushCache();

        return new LogAccesoSistemaResource($logAccesoSistema);
    }

    /**
     * Eliminar log (soft delete para auditoría)
     */
        #[OA\Delete(
        path: '/api/log-acceso-sistema/{id}',
        summary: 'Eliminar log de acceso',
        security: [['sanctum' => []]],
        tags: ['Logs de Acceso'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'log de acceso eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(LogAccesoSistema $logAccesoSistema): JsonResponse
    {
        $this->authorize('delete', $logAccesoSistema);

        $logAccesoSistema->delete();

        $this->flushCache();

        return response()->json(['success' => true, 'message' => 'Log eliminado exitosamente']);
    }
}
