<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaProductoRequest;
use App\Http\Requests\UpdateCategoriaProductoRequest;
use App\Http\Resources\CategoriaProductoResource;
use App\Models\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de categorías de productos
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class CategoriaProductoController extends Controller
{
    /**
     * Listar todas las categorías de la empresa
     */
    #[OA\Get(
        path: "/api/categorias-productos",
        summary: "Listar categorías de productos",
        description: "Obtiene un listado de todas las categorías de productos de la empresa autenticada. Permite filtrar por categorías activas.",
        security: [["sanctum" => []]],
        tags: ["Categorías de Productos"],
        parameters: [
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por categorías activas o inactivas",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de categorías obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CategoriaProducto")
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
        $this->authorize('viewAny', CategoriaProducto::class);

        $empresaId = auth()->user()->empresa_id;
        
        $query = CategoriaProducto::where('empresa_id', $empresaId);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $categorias = $query->get();

        return CategoriaProductoResource::collection($categorias);
    }

    /**
     * Crear una nueva categoría
     */
    #[OA\Post(
        path: "/api/categorias-productos",
        summary: "Crear categoría de producto",
        description: "Registra una nueva categoría de producto para la empresa autenticada. El nombre debe ser único (case-insensitive).",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Electrónica"),
                    new OA\Property(property: "descripcion", type: "string", example: "Productos electrónicos y tecnología"),
                    new OA\Property(property: "categoria_padre_id", type: "integer", example: 5)
                ]
            )
        ),
        tags: ["Categorías de Productos"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Categoría creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoriaProducto")
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
    public function store(StoreCategoriaProductoRequest $request): JsonResponse
    {
        $this->authorize('create', CategoriaProducto::class);

        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::create($validated);

        return (new CategoriaProductoResource($categoria))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una categoría específica
     */
    #[OA\Get(
        path: "/api/categorias-productos/{id}",
        summary: "Obtener categoría de producto",
        description: "Obtiene los detalles de una categoría de producto específica.",
        security: [["sanctum" => []]],
        tags: ["Categorías de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la categoría",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría encontrada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoriaProducto")
            ),
            new OA\Response(
                response: 404,
                description: "Categoría no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): CategoriaProductoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)
            ->findOrFail($id);
        $this->authorize('view', $categoria);

        return new CategoriaProductoResource($categoria);
    }

    /**
     * Actualizar una categoría existente
     */
    #[OA\Put(
        path: "/api/categorias-productos/{id}",
        summary: "Actualizar categoría de producto",
        description: "Actualiza los datos de una categoría de producto existente. El nombre debe ser único.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Electrónica"),
                    new OA\Property(property: "descripcion", type: "string", example: "Productos electrónicos y tecnología"),
                    new OA\Property(property: "categoria_padre_id", type: "integer", example: 5)
                ]
            )
        ),
        tags: ["Categorías de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la categoría a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoriaProducto")
            ),
            new OA\Response(
                response: 404,
                description: "Categoría no encontrada"
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
    public function update(UpdateCategoriaProductoRequest $request, int $id): CategoriaProductoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)->findOrFail($id);
        $this->authorize('update', $categoria);

        $categoria->update($request->validated());

        return new CategoriaProductoResource($categoria);
    }

    /**
     * Eliminar una categoría (soft delete)
     */
    #[OA\Delete(
        path: "/api/categorias-productos/{id}",
        summary: "Eliminar categoría de producto",
        description: "Realiza un soft delete de la categoría especificada.",
        security: [["sanctum" => []]],
        tags: ["Categorías de Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la categoría a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Categoría eliminada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/CategoriaProducto")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Categoría no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)->findOrFail($id);
        $this->authorize('delete', $categoria);

        $categoria->eliminado = 1;
        $categoria->activo = 0;
        $categoria->save();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente',
            'data' => new CategoriaProductoResource($categoria)
        ], 200);
    }
}
