<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoImpuestoRequest;
use App\Http\Requests\UpdateTipoImpuestoRequest;
use App\Http\Resources\TipoImpuestoResource;
use App\Models\TipoImpuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Tipos de Impuesto
 *
 * Gestiona el catálogo de tipos de impuestos utilizados en la facturación.
 * Tabla global sin empresa_id, incluye códigos de Hacienda Costa Rica.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TipoImpuestoController extends Controller
{
    /**
     * Listar todos los tipos de impuesto
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/tipos-impuesto",
        summary: "Listar tipos de impuesto",
        description: "Obtiene el listado de tipos de impuesto utilizados en facturación electrónica. Incluye códigos de Hacienda Costa Rica (01=IVA, 02=Consumo, etc.).",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "buscar",
                in: "query",
                description: "Buscar por nombre o código de Hacienda",
                required: false,
                schema: new OA\Schema(type: "string", example: "IVA")
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo por el cual ordenar",
                required: false,
                schema: new OA\Schema(type: "string", default: "nombre")
            ),
            new OA\Parameter(
                name: "sort_order",
                in: "query",
                description: "Orden ascendente o descendente",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")
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
                            items: new OA\Items(ref: "#/components/schemas/TipoImpuesto")
                        ),
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 15),
                        new OA\Property(property: "total", type: "integer", example: 5)
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
        $query = TipoImpuesto::where('eliminado', 0);

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda por nombre o código
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo_hacienda', 'like', "%{$buscar}%");
            });
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'nombre'), $request->get('sort_order', 'asc'));

        $tipos = $query->paginate($request->get('per_page', 15));

        return TipoImpuestoResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de impuesto
     *
     * @param StoreTipoImpuestoRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/tipos-impuesto",
        summary: "Crear tipo de impuesto",
        description: "Crea un nuevo tipo de impuesto. Debe incluir el código oficial de Hacienda Costa Rica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["codigo_hacienda", "nombre"],
                properties: [
                    new OA\Property(property: "codigo_hacienda", type: "string", maxLength: 2, example: "01"),
                    new OA\Property(property: "nombre", type: "string", example: "Impuesto al Valor Agregado"),
                    new OA\Property(property: "descripcion", type: "string", example: "IVA aplicable a bienes y servicios"),
                    new OA\Property(property: "comentario", type: "string", example: "Tasa estándar 13%"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tipo de impuesto creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de impuesto creado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoImpuesto")
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
    public function store(StoreTipoImpuestoRequest $request): JsonResponse
    {
        $tipo = TipoImpuesto::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto creado exitosamente',
            'data' => new TipoImpuestoResource($tipo)
        ], 201);
    }

    /**
     * Mostrar un tipo de impuesto específico
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tipos-impuesto/{id}",
        summary: "Obtener tipo de impuesto",
        description: "Obtiene los detalles de un tipo de impuesto específico.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de impuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de impuesto encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de impuesto no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new TipoImpuestoResource($tipo)
        ]);
    }

    /**
     * Actualizar un tipo de impuesto existente
     *
     * @param UpdateTipoImpuestoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/tipos-impuesto/{id}",
        summary: "Actualizar tipo de impuesto",
        description: "Actualiza los datos de un tipo de impuesto existente.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de impuesto a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "codigo_hacienda", type: "string", maxLength: 2, example: "01"),
                    new OA\Property(property: "nombre", type: "string", example: "IVA Reducido"),
                    new OA\Property(property: "descripcion", type: "string", example: "Tasa reducida de IVA"),
                    new OA\Property(property: "comentario", type: "string", example: "Aplica para productos básicos"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de impuesto actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de impuesto actualizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoImpuesto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de impuesto no encontrado"
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
    public function update(UpdateTipoImpuestoRequest $request, int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tipo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto actualizado exitosamente',
            'data' => new TipoImpuestoResource($tipo)
        ]);
    }

    /**
     * Eliminar (soft delete) un tipo de impuesto
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/tipos-impuesto/{id}",
        summary: "Eliminar tipo de impuesto",
        description: "Elimina un tipo de impuesto (soft delete). No se puede eliminar el IVA (código 01) que es requerido por Hacienda.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de impuesto a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de impuesto eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de impuesto eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de impuesto no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar - es el IVA (código 01) requerido por Hacienda"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no sea el IVA (código 01) que no debe borrarse
        if ($tipo->codigo_hacienda === '01') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de impuesto IVA (01). Es requerido por Hacienda.'
            ], 422);
        }

        $tipo->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto eliminado exitosamente'
        ]);
    }

    /**
     * Obtener tipos de impuesto activos para uso en facturación
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tipos-impuesto/activos",
        summary: "Tipos de impuesto activos",
        description: "Obtiene únicamente los tipos de impuesto activos para uso en facturas y documentos fiscales.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipos de impuesto activos obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TipoImpuesto")
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
    public function activos(): JsonResponse
    {
        $tipos = TipoImpuesto::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoImpuestoResource::collection($tipos)
        ]);
    }
}
