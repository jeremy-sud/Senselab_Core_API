<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaProductoResource;
use App\Models\CategoriaProducto;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de categorías de productos
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class CategoriaProductoController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['categorias-productos', 'catalogos'];
    protected int $cacheTTL = 3600; // 1 hora
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

        $cacheKey = $this->generateCacheKey('categoria_producto.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = CategoriaProducto::query();

            if ($request->filled('nombre')) {
                $query->where('nombre', 'like', "%{$request->nombre}%");
            }

            if ($request->filled('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            $categorias = $query->orderBy('nombre')->paginate($perPage);

            return CategoriaProductoResource::collection($categorias);
        });
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
    public function store(Request $request): CategoriaProductoResource|JsonResponse
    {
        $this->authorize('create', CategoriaProducto::class);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_productos,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $categoria = CategoriaProducto::create($validated);

            DB::commit();
            $this->clearCache();

            return (new CategoriaProductoResource($categoria))
                ->additional(['message' => 'Categoría creada exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear categoría',
                'error' => $e->getMessage()
            ], 500);
        }
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
    public function show(string $id): CategoriaProductoResource
    {
        $categoria = CategoriaProducto::findOrFail($id);
        $this->authorize('view', $categoria);

        $cacheKey = $this->generateCacheKey("categoria_producto.show.{$id}");

        return $this->getCached($cacheKey, function () use ($categoria) {
            return new CategoriaProductoResource($categoria);
        });
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
    public function update(Request $request, string $id): CategoriaProductoResource|JsonResponse
    {
        $categoria = CategoriaProducto::findOrFail($id);
        $this->authorize('update', $categoria);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100|unique:categorias_productos,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean'
        ]);

        DB::beginTransaction();
        try {
            $categoria->update($validated);

            DB::commit();
            $this->clearCache();

            return (new CategoriaProductoResource($categoria->fresh()))
                ->additional(['message' => 'Categoría actualizada exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
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
    public function destroy(string $id): JsonResponse
    {
        $categoria = CategoriaProducto::findOrFail($id);
        $this->authorize('delete', $categoria);

        DB::beginTransaction();
        try {
            $categoria->delete();

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Categoría eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
