<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePresupuestoRequest;
use App\Http\Requests\UpdatePresupuestoRequest;
use App\Http\Resources\PresupuestoResource;
use App\Models\Presupuesto;
use App\Services\PresupuestoService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controlador para Presupuestos Financieros
 *
 * Gestiona presupuestos maestros con sus períodos y estados.
 *
 * @package App\Http\Controllers\API
 * @author Senselab - Jeremy Arias Solano
 */
class PresupuestoController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private PresupuestoService $service) {}
    /**
     * Listar presupuestos de la empresa
     */
    #[OA\Get(
        path: "/api/presupuestos",
        summary: "Listar presupuestos",
        description: "Obtiene listado paginado de presupuestos financieros de la empresa con sus detalles de cuentas.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "total", type: "integer", example: 12),
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Presupuesto::class);

        $presupuestos = $this->service->listar($this->getEmpresaId());

        return response()->json([
            'success' => true,
            'data' => PresupuestoResource::collection($presupuestos),
            'meta' => [
                'current_page' => $presupuestos->currentPage(),
                'total' => $presupuestos->total(),
                'per_page' => $presupuestos->perPage()
            ]
        ]);
    }

    /**
     * Crear nuevo presupuesto
     */
    #[OA\Post(
        path: "/api/presupuestos",
        summary: "Crear presupuesto",
        description: "Crea un nuevo presupuesto financiero en estado Borrador.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "periodo_inicio", "periodo_fin"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 255, example: "Presupuesto 2024"),
                    new OA\Property(property: "periodo_inicio", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "periodo_fin", type: "string", format: "date", example: "2024-12-31")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Presupuesto creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Presupuesto creado exitosamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Error de validación"),
            new OA\Response(response: 500, description: "Error del servidor")
        ]
    )]
    public function store(StorePresupuestoRequest $request): JsonResponse
    {
        $this->authorize('create', Presupuesto::class);

        $presupuesto = $this->service->crear([
            'empresa_id' => $this->getEmpresaId(),
            'nombre' => $request->nombre,
            'periodo_inicio' => $request->periodo_inicio,
            'periodo_fin' => $request->periodo_fin,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto creado exitosamente',
            'data' => new PresupuestoResource($presupuesto)
        ], 201);
    }

    /**
     * Mostrar presupuesto específico
     */
    #[OA\Get(
        path: "/api/presupuestos/{id}",
        summary: "Obtener presupuesto",
        description: "Obtiene los detalles completos de un presupuesto incluyendo todas las cuentas presupuestadas.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Presupuesto encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $presupuesto = $this->service->obtener($this->getEmpresaId(), $id);
        $this->authorize('view', $presupuesto);

        return response()->json([
            'success' => true,
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Actualizar presupuesto
     */
    #[OA\Put(
        path: "/api/presupuestos/{id}",
        summary: "Actualizar presupuesto",
        description: "Actualiza nombre y fechas de un presupuesto. No permite modificar presupuestos finalizados.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 255, example: "Presupuesto 2024 Actualizado"),
                    new OA\Property(property: "periodo_inicio", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "periodo_fin", type: "string", format: "date", example: "2024-12-31")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Presupuesto actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Presupuesto actualizado exitosamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un presupuesto finalizado"),
            new OA\Response(response: 500, description: "Error del servidor")
        ]
    )]
    public function update(UpdatePresupuestoRequest $request, int $id): JsonResponse
    {
        $presupuesto = $this->service->obtener($this->getEmpresaId(), $id);
        $this->authorize('update', $presupuesto);

        $presupuesto = $this->service->actualizar($presupuesto, $request->only([
            'nombre',
            'periodo_inicio',
            'periodo_fin'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto actualizado exitosamente',
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Eliminar presupuesto
     */
    #[OA\Delete(
        path: "/api/presupuestos/{id}",
        summary: "Eliminar presupuesto",
        description: "Elimina un presupuesto. No permite eliminar presupuestos activos.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Presupuesto eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Presupuesto eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un presupuesto activo"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $presupuesto = $this->service->obtener($this->getEmpresaId(), $id);
        $this->authorize('delete', $presupuesto);

        $this->service->eliminar($presupuesto);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto eliminado exitosamente'
        ]);
    }

    /**
     * Activar presupuesto
     */
    #[OA\Post(
        path: "/api/presupuestos/{id}/activar",
        summary: "Activar presupuesto",
        description: "Cambia el estado de un presupuesto a Activo. Requiere que el presupuesto tenga al menos una cuenta detallada.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Presupuesto activado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Presupuesto activado exitosamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 422, description: "El presupuesto ya está activo o no tiene cuentas detalladas"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function activar(Request $request, int $id): JsonResponse
    {
        $presupuesto = $this->service->obtener($this->getEmpresaId(), $id);
        $presupuesto = $this->service->activar($presupuesto);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto activado exitosamente',
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Finalizar presupuesto
     */
    #[OA\Post(
        path: "/api/presupuestos/{id}/finalizar",
        summary: "Finalizar presupuesto",
        description: "Cambia el estado de un presupuesto a Finalizado. Un presupuesto finalizado no puede ser modificado.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Presupuesto finalizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Presupuesto finalizado exitosamente"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 422, description: "El presupuesto ya está finalizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function finalizar(Request $request, int $id): JsonResponse
    {
        $presupuesto = $this->service->obtener($this->getEmpresaId(), $id);
        $presupuesto = $this->service->finalizar($presupuesto);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto finalizado exitosamente',
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Obtener presupuestos activos
     */
    #[OA\Get(
        path: "/api/presupuestos/activos",
        summary: "Listar presupuestos activos",
        description: "Obtiene todos los presupuestos en estado Activo de la empresa.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function activos(Request $request): JsonResponse
    {
        $presupuestos = $this->service->activos($this->getEmpresaId());

        return response()->json([
            'success' => true,
            'data' => PresupuestoResource::collection($presupuestos)
        ]);
    }

    /**
     * Resumen de presupuesto
     */
    #[OA\Get(
        path: "/api/presupuestos/{id}/resumen",
        summary: "Resumen de presupuesto",
        description: "Obtiene un resumen del presupuesto incluyendo total presupuestado, cantidad de cuentas y duración del período.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "presupuesto", type: "object"),
                                new OA\Property(property: "total_presupuestado", type: "string", example: "5,500,000.00"),
                                new OA\Property(property: "total_cuentas", type: "integer", example: 45),
                                new OA\Property(property: "periodo_dias", type: "integer", example: 365)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function resumen(Request $request, int $id): JsonResponse
    {
        $data = $this->service->resumen($this->getEmpresaId(), $id);

        return response()->json([
            'success' => true,
            'data' => [
                'presupuesto' => new PresupuestoResource($data['presupuesto']),
                'total_presupuestado' => number_format($data['total_presupuestado'], 2),
                'total_cuentas' => $data['cantidad_lineas'],
                'periodo_dias' => $data['duracion_dias']
            ]
        ]);
    }
}
