<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarcaRequest;
use App\Http\Requests\UpdateMarcaRequest;
use App\Http\Resources\MarcaResource;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de marcas de productos
 * 
 * Nota: Las marcas son globales (sin empresa_id) según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class MarcaController extends Controller
{
    /**
     * Listar todas las marcas activas
     */
    #[OA\Get(
        path: "/api/marcas",
        summary: "Listar marcas de productos",
        description: "Obtiene un listado de todas las marcas de productos del sistema. Las marcas son globales (sin empresa_id).",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por marcas activas o inactivas",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de marcas obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Marca")
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
        $this->authorize('viewAny', Marca::class);
        $query = Marca::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $marcas = $query->get();

        return MarcaResource::collection($marcas);
    }

    /**
     * Crear una nueva marca
     */
    #[OA\Post(
        path: "/api/marcas",
        summary: "Crear marca de producto",
        description: "Registra una nueva marca de producto en el sistema. El nombre debe ser único (case-insensitive).",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Samsung"),
                    new OA\Property(property: "descripcion", type: "string", example: "Productos electrónicos y electrodomésticos")
                ]
            )
        ),
        tags: ["Catálogos de Productos"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Marca creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Marca")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación (nombre duplicado)"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function store(StoreMarcaRequest $request): JsonResponse
    {
        $this->authorize('create', Marca::class);
        $marca = Marca::create($request->validated());

        return (new MarcaResource($marca))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una marca específica
     */
    #[OA\Get(
        path: "/api/marcas/{id}",
        summary: "Obtener marca de producto",
        description: "Obtiene los detalles de una marca de producto específica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la marca",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Marca encontrada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Marca")
            ),
            new OA\Response(
                response: 404,
                description: "Marca no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): MarcaResource
    {
        $marca = Marca::findOrFail($id);

        $this->authorize('view', $marca);

        return new MarcaResource($marca);
    }

    /**
     * Actualizar una marca existente
     */
    #[OA\Put(
        path: "/api/marcas/{id}",
        summary: "Actualizar marca de producto",
        description: "Actualiza los datos de una marca de producto existente. El nombre debe ser único.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Samsung"),
                    new OA\Property(property: "descripcion", type: "string", example: "Productos electrónicos y electrodomésticos")
                ]
            )
        ),
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la marca a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Marca actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Marca")
            ),
            new OA\Response(
                response: 404,
                description: "Marca no encontrada"
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
    public function update(UpdateMarcaRequest $request, int $id): MarcaResource
    {
        $marca = Marca::findOrFail($id);

        $this->authorize('update', $marca);

        $marca->update($request->validated());

        return new MarcaResource($marca);
    }

    /**
     * Eliminar una marca (soft delete)
     */
    #[OA\Delete(
        path: "/api/marcas/{id}",
        summary: "Eliminar marca de producto",
        description: "Realiza un soft delete de la marca especificada.",
        security: [["sanctum" => []]],
        tags: ["Catálogos de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la marca a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Marca eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Marca eliminada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Marca")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Marca no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $marca = Marca::findOrFail($id);

        $this->authorize('delete', $marca);

        $marca->eliminado = 1;
        $marca->activo = 0;
        $marca->save();

        return response()->json([
            'message' => 'Marca eliminada exitosamente',
            'data' => new MarcaResource($marca)
        ], 200);
    }
}
