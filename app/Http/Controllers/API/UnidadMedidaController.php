<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnidadMedidaRequest;
use App\Http\Requests\UpdateUnidadMedidaRequest;
use App\Http\Resources\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de unidades de medida
 * 
 * Nota: Las unidades de medida son globales (sin empresa_id) según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class UnidadMedidaController extends Controller
{
    /**
     * Listar todas las unidades de medida
     */
    #[OA\Get(
        path: "/api/unidades-medida",
        summary: "Listar unidades de medida",
        description: "Obtiene un listado de todas las unidades de medida del sistema. Las unidades son globales (sin empresa_id) y siguen los códigos de la Dirección General de Tributación (DGT) de Costa Rica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por unidades activas o inactivas",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de unidades obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/UnidadMedida")
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
        $this->authorize('viewAny', UnidadMedida::class);
        
        // Cache key única basada en parámetros
        $cacheKey = 'unidades_medida_list_' . md5(json_encode($request->all()));
        
        // Cache con tags (24 horas - catálogo estable)
        $unidades = Cache::tags(['unidades_medida', 'catalogos'])->remember($cacheKey, 86400, function() use ($request) {
            $query = UnidadMedida::query();

            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            return $query->get();
        });

        return UnidadMedidaResource::collection($unidades);
    }

    /**
     * Crear una nueva unidad de medida
     */
    #[OA\Post(
        path: "/api/unidades-medida",
        summary: "Crear unidad de medida",
        description: "Registra una nueva unidad de medida en el sistema. El código DGT debe ser único.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "codigo_dgt", type: "string", example: "Unid"),
                    new OA\Property(property: "nombre", type: "string", example: "Unidad"),
                    new OA\Property(property: "descripcion", type: "string", example: "Unidad individual de producto")
                ]
            )
        ),
        tags: ["Catálogos de Productos"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Unidad creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UnidadMedida")
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
    public function store(StoreUnidadMedidaRequest $request): JsonResponse
    {
        $this->authorize('create', UnidadMedida::class);
        $unidad = UnidadMedida::create($request->validated());
        
        // Invalidar cache de unidades de medida
        Cache::tags(['unidades_medida', 'catalogos'])->flush();

        return (new UnidadMedidaResource($unidad))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una unidad de medida específica
     */
    #[OA\Get(
        path: "/api/unidades-medida/{id}",
        summary: "Obtener unidad de medida",
        description: "Obtiene los detalles de una unidad de medida específica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la unidad de medida",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Unidad encontrada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UnidadMedida")
            ),
            new OA\Response(
                response: 404,
                description: "Unidad no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): UnidadMedidaResource
    {
        $unidad = UnidadMedida::findOrFail($id);

        $this->authorize('view', $unidad);

        return new UnidadMedidaResource($unidad);
    }

    /**
     * Actualizar una unidad de medida existente
     */
    #[OA\Put(
        path: "/api/unidades-medida/{id}",
        summary: "Actualizar unidad de medida",
        description: "Actualiza los datos de una unidad de medida existente.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "codigo_dgt", type: "string", example: "Unid"),
                    new OA\Property(property: "nombre", type: "string", example: "Unidad"),
                    new OA\Property(property: "descripcion", type: "string", example: "Unidad individual de producto")
                ]
            )
        ),
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la unidad a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Unidad actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UnidadMedida")
            ),
            new OA\Response(
                response: 404,
                description: "Unidad no encontrada"
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
    public function update(UpdateUnidadMedidaRequest $request, int $id): UnidadMedidaResource
    {
        $unidad = UnidadMedida::findOrFail($id);

        $this->authorize('update', $unidad);

        $unidad->update($request->validated());
        
        // Invalidar cache de unidades de medida
        Cache::tags(['unidades_medida', 'catalogos'])->flush();

        return new UnidadMedidaResource($unidad);
    }

    /**
     * Eliminar una unidad de medida (soft delete)
     */
    #[OA\Delete(
        path: "/api/unidades-medida/{id}",
        summary: "Eliminar unidad de medida",
        description: "Realiza un soft delete de la unidad de medida especificada.",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la unidad a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Unidad eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unidad de medida eliminada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/UnidadMedida")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Unidad no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $unidad = UnidadMedida::findOrFail($id);

        $this->authorize('delete', $unidad);

        $unidad->eliminado = 1;
        $unidad->activo = 0;
        $unidad->save();
        
        // Invalidar cache de unidades de medida
        Cache::tags(['unidades_medida', 'catalogos'])->flush();

        return response()->json([
            'message' => 'Unidad de medida eliminada exitosamente',
            'data' => new UnidadMedidaResource($unidad)
        ], 200);
    }
}
