<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTasaImpuestoRequest;
use App\Http\Requests\UpdateTasaImpuestoRequest;
use App\Http\Requests\TasaImpuestoVigenteRequest;
use App\Http\Resources\TasaImpuestoResource;
use App\Models\TasaImpuesto;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Tasas de Impuesto
 *
 * Gestiona las tasas de impuestos con vigencia temporal.
 * Permite mantener histórico de cambios en tasas (ej: IVA 13% -> 15%).
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TasaImpuestoController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['tasas-impuesto', 'fiscal'];
    protected int $cacheTTL = 86400; // 24 horas - tasas fiscales muy estables

    /**
     * Listar todas las tasas de impuesto
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/tasas-impuesto",
        summary: "Listar tasas de impuesto",
        description: "Obtiene el listado de tasas de impuesto con vigencia temporal. Permite mantener histórico de cambios en tasas (ej: IVA 13% -> 15%).",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "tipo_impuesto_id",
                in: "query",
                description: "Filtrar por tipo de impuesto",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "vigentes",
                in: "query",
                description: "Filtrar solo tasas vigentes a la fecha actual",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo por el cual ordenar",
                required: false,
                schema: new OA\Schema(type: "string", default: "fecha_inicio_vigencia")
            ),
            new OA\Parameter(
                name: "sort_order",
                in: "query",
                description: "Orden ascendente o descendente",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "desc")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Número de registros por página",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TasaImpuesto")
                        ),
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 15),
                        new OA\Property(property: "total", type: "integer", example: 10)
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TasaImpuesto::class);
        
        $query = TasaImpuesto::where('eliminado', 0)->with('tipoImpuesto');

        // Filtro por tipo de impuesto
        if ($request->filled('tipo_impuesto_id')) {
            $query->where('tipo_impuesto_id', $request->tipo_impuesto_id);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Filtro por vigencia actual
        if ($request->filled('vigentes')) {
            $now = Carbon::now();
            $query->where('fecha_inicio_vigencia', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('fecha_fin_vigencia')
                      ->orWhere('fecha_fin_vigencia', '>=', $now);
                });
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'fecha_inicio_vigencia'), $request->get('sort_order', 'desc'));

        $tasas = $query->paginate($request->get('per_page', 15));

        return TasaImpuestoResource::collection($tasas);
    }

    /**
     * Crear una nueva tasa de impuesto
     *
     * @param StoreTasaImpuestoRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/tasas-impuesto",
        summary: "Crear tasa de impuesto",
        description: "Crea una nueva tasa de impuesto con vigencia temporal. Permite registrar cambios históricos en las tasas.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["tipo_impuesto_id", "tasa_porcentaje", "fecha_inicio_vigencia"],
                properties: [
                    new OA\Property(property: "tipo_impuesto_id", type: "integer", example: 1),
                    new OA\Property(property: "tasa_porcentaje", type: "number", format: "float", example: 13.0, description: "Porcentaje de la tasa (0-100)"),
                    new OA\Property(property: "fecha_inicio_vigencia", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "fecha_fin_vigencia", type: "string", format: "date", nullable: true, example: "2024-12-31"),
                    new OA\Property(property: "descripcion", type: "string", example: "Tasa IVA estándar 2024"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tasa de impuesto creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tasa de impuesto creada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TasaImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function store(StoreTasaImpuestoRequest $request): JsonResponse
    {
        $this->authorize('create', TasaImpuesto::class);
        
        $tasa = TasaImpuesto::create($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto creada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ], 201);
    }

    /**
     * Mostrar una tasa de impuesto específica
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tasas-impuesto/{id}",
        summary: "Obtener tasa de impuesto",
        description: "Obtiene los detalles de una tasa de impuesto específica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la tasa de impuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tasa de impuesto encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/TasaImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tasa de impuesto no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->with('tipoImpuesto')
            ->firstOrFail();
        
        $this->authorize('view', $tasa);

        return response()->json([
            'success' => true,
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Actualizar una tasa de impuesto existente
     *
     * @param UpdateTasaImpuestoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/tasas-impuesto/{id}",
        summary: "Actualizar tasa de impuesto",
        description: "Actualiza los datos de una tasa de impuesto existente.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la tasa de impuesto a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "tipo_impuesto_id", type: "integer", example: 1),
                    new OA\Property(property: "tasa_porcentaje", type: "number", format: "float", example: 15.0),
                    new OA\Property(property: "fecha_inicio_vigencia", type: "string", format: "date", example: "2024-07-01"),
                    new OA\Property(property: "fecha_fin_vigencia", type: "string", format: "date", nullable: true, example: null),
                    new OA\Property(property: "descripcion", type: "string", example: "Tasa IVA incrementada"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tasa de impuesto actualizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tasa de impuesto actualizada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TasaImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tasa de impuesto no encontrada"
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function update(UpdateTasaImpuestoRequest $request, int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        
        $this->authorize('update', $tasa);

        $tasa->update($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto actualizada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Eliminar (soft delete) una tasa de impuesto
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/tasas-impuesto/{id}",
        summary: "Eliminar tasa de impuesto",
        description: "Elimina una tasa de impuesto (soft delete).",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la tasa de impuesto a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tasa de impuesto eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tasa de impuesto eliminada exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tasa de impuesto no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        
        $this->authorize('delete', $tasa);

        $tasa->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto eliminada exitosamente'
        ]);
    }

    /**
     * Obtener tasa vigente para un tipo de impuesto en una fecha específica
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tasas-impuesto/vigente",
        summary: "Obtener tasa vigente",
        description: "Obtiene la tasa vigente para un tipo de impuesto específico en una fecha determinada. Si no se especifica fecha, usa la fecha actual.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "tipo_impuesto_id",
                in: "query",
                description: "ID del tipo de impuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "fecha",
                in: "query",
                description: "Fecha para consultar vigencia (formato: YYYY-MM-DD). Si se omite, usa fecha actual",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-15")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tasa vigente encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/TasaImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "No se encontró una tasa vigente para la fecha especificada"
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación - tipo_impuesto_id requerido"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function vigente(TasaImpuestoVigenteRequest $request): JsonResponse
    {
        $fecha = $request->filled('fecha') ? Carbon::parse($request->fecha) : Carbon::now();

        $tasa = TasaImpuesto::where('tipo_impuesto_id', $request->tipo_impuesto_id)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin_vigencia')
                  ->orWhere('fecha_fin_vigencia', '>=', $fecha);
            })
            ->with('tipoImpuesto')
            ->first();

        if (!$tasa) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una tasa vigente para la fecha especificada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Obtener todas las tasas vigentes actuales
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tasas-impuesto/vigentes-actuales",
        summary: "Obtener tasas vigentes actuales",
        description: "Obtiene todas las tasas de impuesto vigentes a la fecha actual para todos los tipos de impuesto.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tasas vigentes obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TasaImpuesto")
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function vigentesActuales(): JsonResponse
    {
        $now = Carbon::now();

        $tasas = TasaImpuesto::where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('fecha_fin_vigencia')
                  ->orWhere('fecha_fin_vigencia', '>=', $now);
            })
            ->with('tipoImpuesto')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TasaImpuestoResource::collection($tasas)
        ]);
    }

    /**
     * Obtener histórico de tasas para un tipo de impuesto
     *
     * @param int $tipoImpuestoId
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tasas-impuesto/historico/{tipoImpuestoId}",
        summary: "Obtener histórico de tasas",
        description: "Obtiene el histórico completo de tasas para un tipo de impuesto específico, ordenadas por fecha de vigencia.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "tipoImpuestoId",
                in: "path",
                description: "ID del tipo de impuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Histórico de tasas obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TasaImpuesto"),
                            description: "Tasas ordenadas por fecha de inicio de vigencia (más reciente primero)"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function historico(int $tipoImpuestoId): JsonResponse
    {
        $tasas = TasaImpuesto::where('tipo_impuesto_id', $tipoImpuestoId)
            ->where('eliminado', 0)
            ->with('tipoImpuesto')
            ->orderBy('fecha_inicio_vigencia', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TasaImpuestoResource::collection($tasas)
        ]);
    }
}
