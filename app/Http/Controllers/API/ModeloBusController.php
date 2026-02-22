<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModeloBusRequest;
use App\Http\Requests\UpdateModeloBusRequest;
use App\Http\Resources\ModeloBusResource;
use App\Models\ModeloBus;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Modelos de Buses
 *
 * Gestiona el catálogo de modelos de buses (tabla global, no multi-tenant).
 * Ej: Paradiso 1800 DD, Viaggio 1050
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ModeloBusController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['modelos-buses', 'transporte', 'catalogos'];
    protected int $cacheTTL = 7200; // 2 horas - catálogo estable
    /**
     * Listar todos los modelos de buses
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/modelos-buses",
        summary: "Listar modelos de buses",
        description: "Obtiene la lista paginada de modelos de buses disponibles en el catálogo global. Incluye contador de buses asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de modelos obtenida exitosamente",
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
        $this->authorize('viewAny', ModeloBus::class);

        $cacheKey = $this->getCacheKey('index', []);

        return $this->cacheQueryIfEnabled($cacheKey, function () {
            $modelos = ModeloBus::withCount('busesUnidades')
                ->orderBy('nombre')
                ->paginate(20);

            return ModeloBusResource::collection($modelos);
        });
    }

    /**
     * Crear un nuevo modelo de bus
     *
     * @param StoreModeloBusRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/modelos-buses",
        summary: "Crear modelo de bus",
        description: "Crea un nuevo modelo de bus en el catálogo global. Ejemplos: Paradiso 1800 DD, Viaggio 1050.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 100, example: "Marcopolo Paradiso 1800 DD")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Modelo creado exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreModeloBusRequest $request): JsonResponse
    {
        $this->authorize('create', ModeloBus::class);

        $modelo = ModeloBus::create([
            'nombre' => $request->nombre
        ]);

        $this->flushCache();

        return (new ModeloBusResource($modelo))
            ->additional(['message' => 'Modelo creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un modelo de bus específico
     *
     * @param int $id
     * @return ModeloBusResource
     */
    #[OA\Get(
        path: "/api/modelos-buses/{id}",
        summary: "Obtener modelo de bus",
        description: "Obtiene el detalle de un modelo de bus con el contador de buses asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del modelo de bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Modelo obtenido exitosamente"),
            new OA\Response(response: 404, description: "Modelo no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id): ModeloBusResource
    {
        $modelo = ModeloBus::withCount('busesUnidades')
            ->findOrFail($id);

        $this->authorize('view', $modelo);

        return new ModeloBusResource($modelo);
    }

    /**
     * Actualizar un modelo de bus
     *
     * @param UpdateModeloBusRequest $request
     * @param int $id
     * @return ModeloBusResource
     */
    #[OA\Put(
        path: "/api/modelos-buses/{id}",
        summary: "Actualizar modelo de bus",
        description: "Actualiza un modelo de bus existente.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del modelo de bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 100, example: "Marcopolo Paradiso 1800 DD")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Modelo actualizado exitosamente"),
            new OA\Response(response: 404, description: "Modelo no encontrado"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateModeloBusRequest $request, int $id): ModeloBusResource
    {
        $modelo = ModeloBus::findOrFail($id);

        $this->authorize('update', $modelo);

        $modelo->update([
            'nombre' => $request->nombre
        ]);

        $this->flushCache();

        return (new ModeloBusResource($modelo))
            ->additional(['message' => 'Modelo actualizado exitosamente']);
    }

    /**
     * Eliminar un modelo de bus
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/modelos-buses/{id}",
        summary: "Eliminar modelo de bus",
        description: "Elimina un modelo de bus. No permite eliminar modelos con buses asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del modelo de bus",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Modelo eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Modelo de bus eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Modelo no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un modelo con buses asociados"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $modelo = ModeloBus::findOrFail($id);

        $this->authorize('delete', $modelo);

        // Validar que no tenga buses asociados
        if ($modelo->busesUnidades()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un modelo con buses asociados'
            ], 422);
        }

        $modelo->delete();

        $this->flushCache();

        return response()->json([
            'message' => 'Modelo de bus eliminado exitosamente'
        ]);
    }

    /**
     * Listar modelos activos para formularios
     *
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/modelos-buses/activos",
        summary: "Listar modelos activos",
        description: "Obtiene una lista simplificada de modelos de buses para uso en selectores.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de modelos activos obtenida exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nombre", type: "string", example: "Marcopolo Paradiso 1800 DD")
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function activos(): AnonymousResourceCollection
    {
        $modelos = ModeloBus::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return ModeloBusResource::collection($modelos);
    }
}
