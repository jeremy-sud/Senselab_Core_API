<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovimientoCajaChicaResource;
use App\Models\MovimientoCajaChica;
use App\Services\MovimientoCajaChicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class MovimientoCajaChicaController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['movimientos_caja_chica', 'caja_chica', 'tesoreria'];
    protected int $cacheTTL = 600;

    public function __construct(private readonly MovimientoCajaChicaService $service)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/movimientos-caja-chica',
        summary: 'Listar movimientos de caja chica',
        description: 'Obtiene un listado paginado de movimientos',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'caja_chica_id',
                description: 'Filtrar por fondo de caja chica',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'tipo_movimiento',
                description: 'Filtrar por tipo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['Ingreso', 'Egreso', 'Reembolso', 'Ajuste'])
            ),
            new OA\Parameter(
                name: 'fecha_desde',
                description: 'Filtrar desde fecha',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_hasta',
                description: 'Filtrar hasta fecha',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MovimientoCajaChica::class);

        $cacheKey = $this->generateCacheKey('movimientos_caja_chica.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $filtros = $request->only(['caja_chica_id', 'tipo_movimiento', 'fecha_desde', 'fecha_hasta']);
            $movimientos = $this->service->listar($filtros, (int) $request->input('per_page', 15));

            return MovimientoCajaChicaResource::collection($movimientos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/movimientos-caja-chica',
        summary: 'Registrar movimiento de caja chica',
        description: 'Registra un nuevo movimiento y actualiza el saldo',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['caja_chica_id', 'fecha_movimiento', 'tipo_movimiento', 'monto', 'concepto'],
                properties: [
                    new OA\Property(property: 'caja_chica_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_movimiento', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'tipo_movimiento', type: 'string', enum: ['Ingreso', 'Egreso', 'Reembolso', 'Ajuste'], example: 'Egreso'),
                    new OA\Property(property: 'monto', type: 'number', format: 'decimal', example: 5000.00),
                    new OA\Property(property: 'numero_comprobante', type: 'string', example: 'COMP-001'),
                    new OA\Property(property: 'concepto', type: 'string', example: 'Compra de útiles de oficina'),
                    new OA\Property(property: 'cuenta_contable_id', type: 'integer', example: 10),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Movimiento registrado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MovimientoCajaChica::class);

        $validated = $request->validate([
            'caja_chica_id' => 'required|exists:caja_chica,id',
            'fecha_movimiento' => 'required|date',
            'tipo_movimiento' => 'required|in:Ingreso,Egreso,Reembolso,Ajuste',
            'monto' => 'required|numeric|min:0.01',
            'numero_comprobante' => 'nullable|string|max:100',
            'concepto' => 'required|string',
            'cuenta_contable_id' => 'nullable|exists:cuentas_contables,id',
        ]);

        $movimiento = $this->service->crear($validated);
        $this->clearCache();

        return (new MovimientoCajaChicaResource($movimiento))
            ->additional([
                'message' => 'Movimiento registrado exitosamente',
                'saldo_actual' => $movimiento->cajaChica->fresh()->saldo_actual
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Obtener movimiento específico',
        description: 'Obtiene los detalles de un movimiento',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id): MovimientoCajaChicaResource
    {
        $movimiento = $this->service->obtener((int) $id);
        $this->authorize('view', $movimiento);

        $cacheKey = $this->generateCacheKey("movimientos_caja_chica.show.{$id}");

        return $this->getCached($cacheKey, function () use ($movimiento) {
            return new MovimientoCajaChicaResource($movimiento);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Actualizar movimiento',
        description: 'Actualiza información de un movimiento',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'numero_comprobante', type: 'string'),
                    new OA\Property(property: 'concepto', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): MovimientoCajaChicaResource|JsonResponse
    {
        $movimiento = $this->service->obtener((int) $id);
        $this->authorize('update', $movimiento);

        $validated = $request->validate([
            'numero_comprobante' => 'sometimes|string|max:100',
            'concepto' => 'sometimes|string',
        ]);

        $movimiento = $this->service->actualizar($movimiento, $validated);
        $this->clearCache();

        return (new MovimientoCajaChicaResource($movimiento))
            ->additional(['message' => 'Movimiento actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Anular movimiento',
        description: 'Anula un movimiento y revierte el saldo',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento anulado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $movimiento = $this->service->obtener((int) $id);
        $this->authorize('delete', $movimiento);

        $this->service->eliminar($movimiento);
        $this->clearCache();

        return response()->json([
            'message' => 'Movimiento anulado exitosamente',
        ]);
    }
}
