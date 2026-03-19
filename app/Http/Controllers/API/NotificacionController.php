<?php

/**
 * API Controller para `Notificacion`.
 *
 * Maneja listado, creación y gestión de notificaciones dirigidas a usuarios.
 * Implementa caching y autorización via policies.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use App\Services\NotificacionService;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class NotificacionController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['notificaciones'];
    protected int $cacheTTL = 300; // 5 minutos (muy dinámico)

    public function __construct(
        private readonly NotificacionService $service
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/notificaciones',
        summary: 'Listar notificaciones',
        description: 'Obtiene las notificaciones del usuario autenticado',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'tipo',
                description: 'Filtrar por tipo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['info', 'warning', 'error', 'success'])
            ),
            new OA\Parameter(
                name: 'leida',
                description: 'Filtrar por estado de lectura',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'prioridad',
                description: 'Filtrar por prioridad mínima',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'no_leidas_count', type: 'integer')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Notificacion::class);

        $usuarioId = auth('sanctum')->id();
        $filtros = array_merge($request->all(), ['usuario_id' => $usuarioId]);

        $cacheKey = $this->generateCacheKey('notificaciones.index', $filtros);

        return $this->getCached($cacheKey, function () use ($filtros) {
            $notificaciones = $this->service->listar($filtros);

            $noLeidasCount = $this->service->contarNoLeidas((int) $filtros['usuario_id']);

            $response = $notificaciones->toArray();
            $response['no_leidas_count'] = $noLeidasCount;

            return response()->json($response);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/notificaciones',
        summary: 'Crear notificación',
        description: 'Crea una nueva notificación para un usuario',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['usuario_id', 'tipo', 'titulo', 'mensaje'],
                properties: [
                    new OA\Property(property: 'usuario_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['info', 'warning', 'error', 'success'], example: 'info'),
                    new OA\Property(property: 'titulo', type: 'string', example: 'Nueva actualización'),
                    new OA\Property(property: 'mensaje', type: 'string', example: 'Se ha actualizado el sistema'),
                    new OA\Property(property: 'datos', type: 'object', example: ['extra' => 'data']),
                    new OA\Property(property: 'url', type: 'string', example: '/ventas/123'),
                    new OA\Property(property: 'prioridad', type: 'integer', enum: [0, 1, 2], example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notificación creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Notificacion::class);

        $validated = $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'tipo' => 'required|in:info,warning,error,success',
            'titulo' => 'required|string|max:200',
            'mensaje' => 'required|string',
            'datos' => 'nullable|array',
            'url' => 'nullable|string|max:500',
            'prioridad' => 'nullable|integer|in:0,1,2',
        ]);

        $validated['empresa_id'] = auth('sanctum')->user()->empresa_id;

        $notificacion = $this->service->crear($validated);

        $this->clearCache();

        return response()->json([
            'message' => 'Notificación creada exitosamente',
            'data' => $notificacion
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/notificaciones/{id}',
        summary: 'Obtener notificación específica',
        description: 'Obtiene los detalles de una notificación',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la notificación',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificación obtenida exitosamente'
            )
        ]
    )]
    public function show(string $id): \Illuminate\Http\JsonResponse
    {
        $notificacion = Notificacion::findOrFail($id);
        $this->authorize('view', $notificacion);

        // Auto-marcar como leída al visualizar
        if (!$notificacion->leida) {
            $notificacion = $this->service->marcarLeida($notificacion);
            $this->clearCache();
        }

        return response()->json(['data' => $notificacion]);
    }

    /**
     * Marcar como leída
     */
    #[OA\Post(
        path: '/api/notificaciones/{id}/marcar-leida',
        summary: 'Marcar notificación como leída',
        description: 'Marca una notificación como leída',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la notificación',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificación marcada como leída'
            )
        ]
    )]
    public function marcarLeida(string $id): \Illuminate\Http\JsonResponse
    {
        $notificacion = Notificacion::findOrFail($id);
        $this->authorize('update', $notificacion);

        $notificacion = $this->service->marcarLeida($notificacion);
        $this->clearCache();

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'data' => $notificacion
        ]);
    }

    /**
     * Marcar todas como leídas
     */
    #[OA\Post(
        path: '/api/notificaciones/marcar-todas-leidas',
        summary: 'Marcar todas como leídas',
        description: 'Marca todas las notificaciones del usuario como leídas',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificaciones marcadas como leídas'
            )
        ]
    )]
    public function marcarTodasLeidas(): \Illuminate\Http\JsonResponse
    {
        $updated = $this->service->marcarTodasLeidas((int) auth('sanctum')->id());

        $this->clearCache();

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas',
            'count' => $updated
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/notificaciones/{id}',
        summary: 'Eliminar notificación',
        description: 'Elimina una notificación',
        security: [['sanctum' => []]],
        tags: ['Notificaciones'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la notificación',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificación eliminada exitosamente'
            )
        ]
    )]
    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $notificacion = Notificacion::findOrFail($id);
        $this->authorize('delete', $notificacion);

        $this->service->eliminar($notificacion);
        $this->clearCache();

        return $this->deletedResponse('Notificación eliminada exitosamente');
    }
}
