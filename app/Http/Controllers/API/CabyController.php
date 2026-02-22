<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCabyRequest;
use App\Http\Requests\UpdateCabyRequest;
use App\Http\Requests\BuscarCabyRequest;
use App\Http\Resources\CabyResource;
use App\Models\Cabys;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

/**
 * Controlador API para CAByS
 *
 * Gestiona el Catálogo de Bienes y Servicios (CAByS) de Costa Rica.
 * Tabla global sin empresa_id, utilizada para clasificación fiscal de productos.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CabyController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['cabys', 'catalogos', 'hacienda'];
    protected int $cacheTTL = 86400; // 24 horas - catálogo fiscal muy estable

    /**
     * Listar todos los códigos CAByS
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/cabys",
        summary: "Listar códigos CAByS",
        description: "Obtiene el listado del Catálogo de Bienes y Servicios (CAByS) de Costa Rica. Permite búsqueda por código o descripción.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "buscar",
                in: "query",
                description: "Buscar por código o descripción",
                required: false,
                schema: new OA\Schema(type: "string", example: "8529")
            ),
            new OA\Parameter(
                name: "codigo",
                in: "query",
                description: "Filtrar por código específico",
                required: false,
                schema: new OA\Schema(type: "string", example: "8529901000000")
            ),
            new OA\Parameter(
                name: "impuesto_iva",
                in: "query",
                description: "Filtrar por tasa de IVA predeterminada",
                required: false,
                schema: new OA\Schema(type: "number", format: "decimal", example: 13.00)
            ),
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo por el cual ordenar",
                required: false,
                schema: new OA\Schema(type: "string", default: "codigo")
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
                            items: new OA\Items(ref: "#/components/schemas/Caby")
                        ),
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 15),
                        new OA\Property(property: "total", type: "integer", example: 1500)
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
        $this->authorize('viewAny', Cabys::class);

        $cacheKey = $this->getCacheKey('index', [
            'buscar' => $request->input('buscar'),
            'codigo' => $request->input('codigo'),
            'impuesto_iva' => $request->input('impuesto_iva'),
            'activo' => $request->input('activo'),
            'sort_by' => $request->input('sort_by', 'codigo'),
            'sort_order' => $request->input('sort_order', 'asc'),
            'per_page' => $request->input('per_page', 15)
        ]);

        $cabys = $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = Cabys::where('eliminado', 0);

            // Búsqueda por código o descripción
            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                      ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            }

            // Filtro por código específico
            if ($request->filled('codigo')) {
                $query->where('codigo', $request->codigo);
            }

            // Filtro por tasa IVA
            if ($request->filled('impuesto_iva')) {
                $query->where('impuesto_iva_predeterminado', $request->impuesto_iva);
            }

            // Filtro por estado activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo);
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'codigo'), $request->get('sort_order', 'asc'));

            return $query->paginate($request->get('per_page', 15));
        });

        return CabyResource::collection($cabys);
    }

    /**
     * Crear un nuevo código CAByS
     *
     * @param StoreCabyRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/cabys",
        summary: "Crear código CAByS",
        description: "Crea un nuevo código CAByS. El código debe tener hasta 20 caracteres según catálogo oficial de Hacienda Costa Rica.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["codigo", "descripcion"],
                properties: [
                    new OA\Property(property: "codigo", type: "string", maxLength: 20, example: "8529901000000"),
                    new OA\Property(property: "descripcion", type: "string", example: "Antenas de telefonía móvil"),
                    new OA\Property(property: "impuesto_iva_predeterminado", type: "number", format: "decimal", example: 13.00),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Código CAByS creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Código CAByS creado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Caby")
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
    public function store(StoreCabyRequest $request): CabyResource|JsonResponse
    {
        $this->authorize('create', Cabys::class);
        $caby = Cabys::create($request->validated());

        $this->flushCache();

        return (new CabyResource($caby))
            ->additional([
                'success' => true,
                'message' => 'Código CAByS creado exitosamente'
            ]);
    }

    /**
     * Mostrar un código CAByS específico
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/cabys/{id}",
        summary: "Obtener código CAByS",
        description: "Obtiene los detalles de un código CAByS específico.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del código CAByS",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Código CAByS encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Caby")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Código CAByS no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): CabyResource|JsonResponse
    {
        $caby = Cabys::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $this->authorize('view', $caby);

        return (new CabyResource($caby))
            ->additional(['success' => true]);
    }

    /**
     * Actualizar un código CAByS existente
     *
     * @param UpdateCabyRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/cabys/{id}",
        summary: "Actualizar código CAByS",
        description: "Actualiza los datos de un código CAByS existente.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del código CAByS a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "codigo", type: "string", maxLength: 20, example: "8529901000000"),
                    new OA\Property(property: "descripcion", type: "string", example: "Descripción actualizada"),
                    new OA\Property(property: "impuesto_iva_predeterminado", type: "number", format: "decimal", example: 13.00),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Código CAByS actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Código CAByS actualizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Caby")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Código CAByS no encontrado"
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
    public function update(UpdateCabyRequest $request, int $id): CabyResource|JsonResponse
    {
        $caby = Cabys::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $this->authorize('update', $caby);

        $caby->update($request->validated());

        $this->flushCache();

        return (new CabyResource($caby))
            ->additional([
                'success' => true,
                'message' => 'Código CAByS actualizado exitosamente'
            ]);
    }

    /**
     * Eliminar (soft delete) un código CAByS
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/cabys/{id}",
        summary: "Eliminar código CAByS",
        description: "Elimina un código CAByS (soft delete). No se puede eliminar si está asignado a productos.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del código CAByS a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Código CAByS eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Código CAByS eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Código CAByS no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar - está asignado a productos"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $caby = Cabys::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $this->authorize('delete', $caby);

        // Validar que no esté asignado a productos
        $productosCount = $caby->productos()->count();
        if ($productosCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el código CAByS. Está asignado a {$productosCount} producto(s)"
            ], 422);
        }

        $caby->update(['eliminado' => 1, 'activo' => 0]);

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Código CAByS eliminado exitosamente'
        ]);
    }

    /**
     * Buscar códigos CAByS por término de búsqueda
     *
     * @param BuscarCabyRequest $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/cabys/buscar",
        summary: "Buscar códigos CAByS",
        description: "Busca códigos CAByS por término de búsqueda en código o descripción. Retorna máximo 50 resultados.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Fiscales"],
        parameters: [
            new OA\Parameter(
                name: "termino",
                in: "query",
                description: "Término de búsqueda (mínimo 3 caracteres)",
                required: true,
                schema: new OA\Schema(type: "string", minLength: 3, example: "852")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Búsqueda realizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Caby")
                        ),
                        new OA\Property(property: "total", type: "integer", example: 15)
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación - término debe tener mínimo 3 caracteres"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function buscar(BuscarCabyRequest $request): AnonymousResourceCollection
    {
        $termino = $request->termino;

        $resultados = Cabys::where('eliminado', 0)
            ->where('activo', 1)
            ->where(function ($q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                  ->orWhere('descripcion', 'like', "%{$termino}%");
            })
            ->limit(50)
            ->get();

        return CabyResource::collection($resultados)
            ->additional([
                'success' => true,
                'total' => $resultados->count()
            ]);
    }
}
