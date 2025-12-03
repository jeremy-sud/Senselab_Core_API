<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusUnidadRequest;
use App\Http\Requests\UpdateBusUnidadRequest;
use App\Http\Resources\BusUnidadResource;
use App\Models\BusUnidad;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Buses/Unidades de Transporte
 *
 * Gestiona las unidades físicas de transporte (buses) con placa, modelo y capacidad.
 * Incluye consultas de disponibilidad y estado de flota.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class BusUnidadController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;
    /** @var array<string> */
    protected array $cacheTags = ['buses-unidades', 'transporte'];
    protected int $cacheTTL = 1800; // 30 min - flota cambia ocasionalmente
    /**
     * Listar todos los buses de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/buses-unidades",
        summary: "Listar buses",
        description: "Obtiene la lista paginada de buses de la empresa. Permite filtrar por modelo, estado activo y buscar por placa o identificador.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "modelo_id",
                in: "query",
                description: "Filtrar por modelo de bus",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo",
                required: false,
                schema: new OA\Schema(type: "integer", enum: [0, 1], example: 1)
            ),
            new OA\Parameter(
                name: "buscar",
                in: "query",
                description: "Buscar por placa o identificador interno",
                required: false,
                schema: new OA\Schema(type: "string", example: "ABC123")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de buses obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', BusUnidad::class);
        $empresaId = $this->getEmpresaId();

        $cacheKey = $this->getCacheKey('index', [
            'empresa_id' => $empresaId,
            'modelo_id' => $request->get('modelo_id'),
            'activo' => $request->get('activo'),
            'buscar' => $request->get('buscar')
        ]);

        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId) {
            $query = BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['empresa', 'modelo']);

            // Filtro por modelo
            if ($request->filled('modelo_id')) {
                $query->where('modelo_id', $request->modelo_id);
            }

            // Filtro por activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo);
            }

            // Búsqueda por placa o identificador
            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('placa', 'like', "%{$buscar}%")
                      ->orWhere('identificador_interno', 'like', "%{$buscar}%");
                });
            }

            $buses = $query->orderBy('id')->paginate(15);

            return BusUnidadResource::collection($buses);
        });
    }

    /**
     * Crear un nuevo bus
     *
     * @param StoreBusUnidadRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/buses-unidades",
        summary: "Crear bus",
        description: "Registra un nuevo bus en la flota de la empresa con placa, modelo y capacidad.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["placa", "modelo_id", "capacidad_asientos", "identificador_interno"],
                properties: [
                    new OA\Property(property: "placa", type: "string", maxLength: 20, example: "ABC-1234"),
                    new OA\Property(property: "modelo_id", type: "integer", example: 1),
                    new OA\Property(property: "capacidad_asientos", type: "integer", example: 48),
                    new OA\Property(property: "identificador_interno", type: "string", maxLength: 50, example: "BUS-001"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1, description: "Opcional, por defecto 1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Bus creado exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreBusUnidadRequest $request): JsonResponse
    {
        $this->authorize('create', BusUnidad::class);
        $empresaId = $this->getEmpresaId();

        $bus = BusUnidad::create([
            'empresa_id' => $empresaId,
            'placa' => $request->placa,
            'modelo_id' => $request->modelo_id,
            'capacidad_asientos' => $request->capacidad_asientos,
            'identificador_interno' => $request->identificador_interno,
            'activo' => $request->activo ?? 1
        ]);

        $this->flushCache();

        return (new BusUnidadResource($bus->load(['empresa', 'modelo'])))
            ->additional(['message' => 'Bus creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un bus específico
     *
     * @param int $id
     * @param Request $request
     * @return BusUnidadResource
     */
    #[OA\Get(
        path: "/api/buses-unidades/{id}",
        summary: "Obtener bus",
        description: "Obtiene el detalle de un bus con su modelo y horarios de ruta asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Bus obtenido exitosamente"),
            new OA\Response(response: 404, description: "Bus no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id, Request $request): BusUnidadResource
    {
        $empresaId = $this->getEmpresaId();

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'modelo', 'horariosRuta'])
            ->findOrFail($id);

        $this->authorize('view', $bus);

        return new BusUnidadResource($bus);
    }

    /**
     * Actualizar un bus
     *
     * @param UpdateBusUnidadRequest $request
     * @param int $id
     * @return BusUnidadResource
     */
    #[OA\Put(
        path: "/api/buses-unidades/{id}",
        summary: "Actualizar bus",
        description: "Actualiza la información de un bus existente (placa, modelo, capacidad, identificador).",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "placa", type: "string", maxLength: 20, example: "ABC-1234"),
                    new OA\Property(property: "modelo_id", type: "integer", example: 1),
                    new OA\Property(property: "capacidad_asientos", type: "integer", example: 48),
                    new OA\Property(property: "identificador_interno", type: "string", maxLength: 50, example: "BUS-001"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Bus actualizado exitosamente"),
            new OA\Response(response: 404, description: "Bus no encontrado"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateBusUnidadRequest $request, int $id): BusUnidadResource
    {
        $empresaId = $this->getEmpresaId();

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $this->authorize('update', $bus);

        $bus->update($request->only([
            'placa',
            'modelo_id',
            'capacidad_asientos',
            'identificador_interno',
            'activo'
        ]));

        $this->flushCache();

        return (new BusUnidadResource($bus->load(['empresa', 'modelo'])))
            ->additional(['message' => 'Bus actualizado exitosamente']);
    }

    /**
     * Eliminar (soft delete) un bus
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/buses-unidades/{id}",
        summary: "Eliminar bus",
        description: "Elimina lógicamente un bus. No permite eliminar buses con horarios de ruta activos (no finalizados).",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Bus eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Bus eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Bus no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un bus con horarios activos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $this->authorize('delete', $bus);

        // Validar que no tenga horarios activos asignados
        if ($bus->horariosRuta()->where('estado', '!=', 'Finalizado')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un bus con horarios de ruta activos'
            ], 422);
        }

        $bus->update(['eliminado' => 1, 'activo' => 0]);

        $this->flushCache();

        return response()->json([
            'message' => 'Bus eliminado exitosamente'
        ]);
    }

    /**
     * Listar buses disponibles (activos sin horarios en curso)
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/buses-unidades/disponibles",
        summary: "Listar buses disponibles",
        description: "Obtiene la lista de buses activos que no están actualmente en viaje. Útil para asignar a nuevos horarios.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de buses disponibles obtenida exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "placa", type: "string", example: "ABC-1234"),
                            new OA\Property(property: "modelo_id", type: "integer", example: 1),
                            new OA\Property(property: "capacidad_asientos", type: "integer", example: 48),
                            new OA\Property(property: "identificador_interno", type: "string", example: "BUS-001")
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function disponibles(Request $request): AnonymousResourceCollection
    {
        $empresaId = $this->getEmpresaId();

        $buses = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->with(['modelo'])
            ->whereDoesntHave('horariosRuta', function ($query) {
                $query->where('estado', 'En Viaje');
            })
            ->get();

        return BusUnidadResource::collection($buses);
    }

    /**
     * Obtener resumen de la flota
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/buses-unidades/resumen-flota",
        summary: "Resumen de flota",
        description: "Obtiene estadísticas agregadas de la flota: total de buses, activos, en viaje y capacidad total.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_buses", type: "integer", example: 12),
                        new OA\Property(property: "buses_activos", type: "integer", example: 10),
                        new OA\Property(property: "buses_en_viaje", type: "integer", example: 3),
                        new OA\Property(property: "capacidad_total", type: "integer", example: 480)
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function resumenEstado(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $resumen = [
            'total_buses' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->count(),
            'buses_activos' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->where('activo', 1)
                ->count(),
            'buses_en_viaje' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->whereHas('horariosRuta', function ($query) {
                    $query->where('estado', 'En Viaje');
                })
                ->count(),
            'capacidad_total' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->where('activo', 1)
                ->sum('capacidad_asientos')
        ];

        return response()->json($resumen);
    }

    /**
     * Listar buses por modelo
     *
     * @param int $modeloId
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/buses-unidades/modelo/{modeloId}",
        summary: "Buses por modelo",
        description: "Obtiene todos los buses de un modelo específico.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "modeloId",
                in: "path",
                description: "ID del modelo de bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de buses obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function porModelo(int $modeloId, Request $request): AnonymousResourceCollection
    {
        $empresaId = $this->getEmpresaId();

        $buses = BusUnidad::where('empresa_id', $empresaId)
            ->where('modelo_id', $modeloId)
            ->where('eliminado', 0)
            ->with(['modelo'])
            ->get();

        return BusUnidadResource::collection($buses);
    }
}
