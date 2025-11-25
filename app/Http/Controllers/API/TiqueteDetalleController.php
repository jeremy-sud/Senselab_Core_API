<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTiqueteDetalleRequest;
use App\Http\Requests\UpdateTiqueteDetalleRequest;
use App\Http\Resources\TiqueteDetalleResource;
use App\Models\TiqueteDetalle;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Tiquetes de Transporte
 *
 * Gestiona los tiquetes vendidos con asiento asignado y datos del pasajero.
 * Vinculado a detalle de venta y horario de ruta.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TiqueteDetalleController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['tiquetes-detalle', 'transporte', 'ventas'];
    protected int $cacheTTL = 600; // 10min - tickets highly volatile
    /**
     * Listar todos los tiquetes
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/tiquetes-detalle",
        summary: "Listar tiquetes de transporte",
        description: "Obtiene todos los tiquetes vendidos con filtros por horario, estado o b\u00fasqueda de pasajero.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "horario_ruta_id",
                in: "query",
                description: "Filtrar por horario de ruta",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado (Vendido, Usado, Cancelado)",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Vendido", "Usado", "Cancelado"], example: "Vendido")
            ),
            new OA\Parameter(
                name: "buscar_pasajero",
                in: "query",
                description: "B\u00fasqueda por nombre o identificaci\u00f3n del pasajero",
                required: false,
                schema: new OA\Schema(type: "string", example: "Juan")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "N\u00famero de p\u00e1gina",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de tiquetes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "total", type: "integer", example: 150),
                                new OA\Property(property: "per_page", type: "integer", example: 15)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TiqueteDetalle::class);
        
        $cacheKey = $this->getCacheKey('index', [
            'horario_ruta_id' => $request->horario_ruta_id,
            'estado' => $request->estado,
            'buscar_pasajero' => $request->buscar_pasajero
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = TiqueteDetalle::with(['horarioRuta.ruta', 'horarioRuta.bus', 'detalleVenta'])
                ->where('eliminado', 0);

            // Filtro por horario de ruta
            if ($request->filled('horario_ruta_id')) {
                $query->where('horario_ruta_id', $request->horario_ruta_id);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Búsqueda por pasajero
            if ($request->filled('buscar_pasajero')) {
                $buscar = $request->buscar_pasajero;
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre_pasajero', 'like', "%{$buscar}%")
                      ->orWhere('identificacion_pasajero', 'like', "%{$buscar}%");
                });
            }

            $tiquetes = $query->orderBy('id', 'desc')->paginate(15);

            return TiqueteDetalleResource::collection($tiquetes);
        });
    }

    /**
     * Crear un nuevo tiquete
     *
     * @param StoreTiqueteDetalleRequest $request
     * @return TiqueteDetalleResource
     */
    #[OA\Post(
        path: "/api/tiquetes-detalle",
        summary: "Crear tiquete de transporte",
        description: "Registra un nuevo tiquete con asiento asignado y decrementa los asientos disponibles en el horario.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["detalle_venta_id", "horario_ruta_id", "asiento_numero", "nombre_pasajero", "precio_final_tiquete"],
                properties: [
                    new OA\Property(property: "detalle_venta_id", type: "integer", example: 15),
                    new OA\Property(property: "horario_ruta_id", type: "integer", example: 3),
                    new OA\Property(property: "asiento_numero", type: "integer", example: 12),
                    new OA\Property(property: "nombre_pasajero", type: "string", example: "Juan P\u00e9rez G\u00f3mez"),
                    new OA\Property(property: "identificacion_pasajero", type: "string", example: "1-2345-6789"),
                    new OA\Property(property: "precio_final_tiquete", type: "number", format: "decimal", example: 3500.00),
                    new OA\Property(property: "estado", type: "string", enum: ["Vendido", "Usado", "Cancelado"], example: "Vendido"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tiquete creado exitosamente"),
            new OA\Response(response: 422, description: "Error de validaci\u00f3n o asiento no disponible"),
            new OA\Response(response: 500, description: "Error al crear el tiquete"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreTiqueteDetalleRequest $request): TiqueteDetalleResource
    {
        $this->authorize('create', TiqueteDetalle::class);
        
        DB::beginTransaction();

        try {
            $tiquete = TiqueteDetalle::create([
                'detalle_venta_id' => $request->detalle_venta_id,
                'horario_ruta_id' => $request->horario_ruta_id,
                'asiento_numero' => $request->asiento_numero,
                'nombre_pasajero' => $request->nombre_pasajero,
                'identificacion_pasajero' => $request->identificacion_pasajero,
                'precio_final_tiquete' => $request->precio_final_tiquete,
                'estado' => $request->estado ?? 'Vendido',
                'activo' => $request->activo ?? 1
            ]);

            // Actualizar asientos disponibles en el horario
            $horario = \App\Models\HorarioRuta::findOrFail($request->horario_ruta_id);
            $horario->decrement('asientos_disponibles');

            DB::commit();
            
            $this->flushCache();

            return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al crear el tiquete: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un tiquete específico
     *
     * @param int $id
     * @return TiqueteDetalleResource
     */
    #[OA\Get(
        path: "/api/tiquetes-detalle/{id}",
        summary: "Obtener tiquete espec\u00edfico",
        description: "Muestra los detalles de un tiquete de transporte con informaci\u00f3n del horario, ruta y bus.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tiquete",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Tiquete obtenido exitosamente"),
            new OA\Response(response: 404, description: "Tiquete no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->with(['horarioRuta.ruta', 'horarioRuta.bus', 'detalleVenta'])
            ->findOrFail($id);
        
        $this->authorize('view', $tiquete);

        return new TiqueteDetalleResource($tiquete);
    }

    /**
     * Actualizar un tiquete
     *
     * @param UpdateTiqueteDetalleRequest $request
     * @param int $id
     * @return TiqueteDetalleResource
     */
    #[OA\Put(
        path: "/api/tiquetes-detalle/{id}",
        summary: "Actualizar tiquete",
        description: "Actualiza informaci\u00f3n del pasajero y estado. No permite modificar tiquetes usados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tiquete",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre_pasajero", type: "string", example: "Mar\u00eda Rodr\u00edguez"),
                    new OA\Property(property: "identificacion_pasajero", type: "string", example: "2-3456-7890"),
                    new OA\Property(property: "estado", type: "string", enum: ["Vendido", "Usado", "Cancelado"], example: "Vendido"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Tiquete actualizado exitosamente"),
            new OA\Response(response: 422, description: "No se puede modificar un tiquete usado"),
            new OA\Response(response: 404, description: "Tiquete no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateTiqueteDetalleRequest $request, int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('update', $tiquete);

        // Validar que no esté usado
        if ($tiquete->estado === 'Usado') {
            abort(422, 'No se puede modificar un tiquete que ya ha sido usado');
        }

        $tiquete->update($request->only([
            'nombre_pasajero',
            'identificacion_pasajero',
            'estado',
            'activo'
        ]));
        
        $this->flushCache();

        return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));
    }

    /**
     * Cancelar un tiquete
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/tiquetes-detalle/{id}/cancelar",
        summary: "Cancelar tiquete",
        description: "Marca el tiquete como Cancelado y libera el asiento en el horario. No permite cancelar tiquetes usados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tiquete a cancelar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tiquete cancelado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Tiquete cancelado exitosamente"),
                        new OA\Property(property: "tiquete", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "No se puede cancelar un tiquete usado"),
            new OA\Response(response: 404, description: "Tiquete no encontrado"),
            new OA\Response(response: 500, description: "Error al cancelar el tiquete"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function cancelar(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tiquete = TiqueteDetalle::where('eliminado', 0)
                ->findOrFail($id);

            if ($tiquete->estado === 'Usado') {
                return response()->json([
                    'message' => 'No se puede cancelar un tiquete que ya ha sido usado'
                ], 422);
            }

            $tiquete->update(['estado' => 'Cancelado']);

            // Liberar el asiento en el horario
            $horario = $tiquete->horarioRuta;
            $horario->increment('asientos_disponibles');

            DB::commit();

            return response()->json([
                'message' => 'Tiquete cancelado exitosamente',
                'tiquete' => new TiqueteDetalleResource($tiquete)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cancelar el tiquete',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar tiquete como usado
     *
     * @param int $id
     * @return TiqueteDetalleResource
     */
    #[OA\Post(
        path: "/api/tiquetes-detalle/{id}/marcar-usado",
        summary: "Marcar tiquete como usado",
        description: "Cambia el estado del tiquete de Vendido a Usado. Solo permite marcar tiquetes en estado Vendido.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tiquete",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Tiquete marcado como usado exitosamente"),
            new OA\Response(response: 422, description: "Solo se pueden marcar como usados los tiquetes en estado Vendido"),
            new OA\Response(response: 404, description: "Tiquete no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function marcarUsado(int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->findOrFail($id);

        if ($tiquete->estado !== 'Vendido') {
            abort(422, 'Solo se pueden marcar como usados los tiquetes en estado Vendido');
        }

        $tiquete->update(['estado' => 'Usado']);

        return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));
    }

    /**
     * Listar tiquetes por horario de ruta
     *
     * @param int $horarioRutaId
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/tiquetes-detalle/horario-ruta/{horarioRutaId}",
        summary: "Listar tiquetes por horario",
        description: "Obtiene todos los tiquetes de un horario espec\u00edfico ordenados por n\u00famero de asiento.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "horarioRutaId",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de tiquetes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function porHorarioRuta(int $horarioRutaId): AnonymousResourceCollection
    {
        $tiquetes = TiqueteDetalle::where('horario_ruta_id', $horarioRutaId)
            ->where('eliminado', 0)
            ->with(['horarioRuta.ruta', 'horarioRuta.bus'])
            ->orderBy('asiento_numero')
            ->get();

        return TiqueteDetalleResource::collection($tiquetes);
    }

    /**
     * Mapa de asientos ocupados y disponibles
     *
     * @param int $horarioRutaId
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tiquetes-detalle/horario-ruta/{horarioRutaId}/mapa-asientos",
        summary: "Mapa de asientos del bus",
        description: "Devuelve la disponibilidad de asientos para un horario: capacidad total, asientos ocupados y disponibles.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "horarioRutaId",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Mapa de asientos obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "horario_id", type: "integer", example: 1),
                        new OA\Property(property: "capacidad_total", type: "integer", example: 40),
                        new OA\Property(property: "asientos_ocupados", type: "array", items: new OA\Items(type: "integer"), example: [1, 5, 12, 18]),
                        new OA\Property(property: "total_ocupados", type: "integer", example: 4),
                        new OA\Property(property: "total_disponibles", type: "integer", example: 36)
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function mapaAsientos(int $horarioRutaId): JsonResponse
    {
        $horario = \App\Models\HorarioRuta::with(['bus', 'tiquetesDetalle'])
            ->findOrFail($horarioRutaId);

        $asientosOcupados = $horario->tiquetesDetalle()
            ->where('estado', '!=', 'Cancelado')
            ->pluck('asiento_numero')
            ->toArray();

        return response()->json([
            'horario_id' => $horario->id,
            'capacidad_total' => $horario->bus->capacidad_asientos,
            'asientos_ocupados' => $asientosOcupados,
            'total_ocupados' => count($asientosOcupados),
            'total_disponibles' => $horario->asientos_disponibles
        ]);
    }
}
