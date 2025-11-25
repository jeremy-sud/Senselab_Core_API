<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DetalleAsientoResource;
use App\Http\Requests\StoreDetalleAsientoRequest;
use App\Http\Requests\UpdateDetalleAsientoRequest;
use App\Http\Requests\LibroMayorRequest;
use App\Http\Requests\BalanceComprobacionRequest;
use App\Models\DetalleAsiento;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Detalles de Asientos Contables
 *
 * Gestiona las líneas individuales de los asientos contables (debe/haber por cuenta).
 * Normalmente se gestionan a través del AsientoContableController, pero este controlador
 * permite consultas específicas sobre los movimientos.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleAsientoController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    protected array $cacheTags = ['detalles-asientos', 'contabilidad', 'asientos-contables'];
    protected int $cacheTTL = 3600; // 1h - accounting movements stable
    /**
     * Listar todos los detalles de asientos de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/detalles-asientos",
        summary: "Listar detalles de asientos contables",
        description: "Obtiene los movimientos individuales (debe/haber) de los asientos contables. Permite filtrar por asiento, cuenta, tipo de movimiento.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "asiento_contable_id",
                in: "query",
                description: "Filtrar por ID de asiento contable",
                required: false,
                schema: new OA\Schema(type: "integer", example: 15)
            ),
            new OA\Parameter(
                name: "cuenta_contable_id",
                in: "query",
                description: "Filtrar por ID de cuenta contable",
                required: false,
                schema: new OA\Schema(type: "integer", example: 8)
            ),
            new OA\Parameter(
                name: "solo_debe",
                in: "query",
                description: "Filtrar solo movimientos al debe",
                required: false,
                schema: new OA\Schema(type: "integer", enum: [0, 1], example: 1)
            ),
            new OA\Parameter(
                name: "solo_haber",
                in: "query",
                description: "Filtrar solo movimientos al haber",
                required: false,
                schema: new OA\Schema(type: "integer", enum: [0, 1], example: 1)
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo de ordenamiento",
                required: false,
                schema: new OA\Schema(type: "string", default: "created_at")
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
                description: "Registros por página",
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
                            items: new OA\Items(ref: "#/components/schemas/DetalleAsiento")
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "No autenticado"
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DetalleAsiento::class);
        
        $empresaId = $this->getEmpresaId();
        
        $cacheKey = $this->getCacheKey('index', [
            'asiento_contable_id' => $request->asiento_contable_id,
            'cuenta_contable_id' => $request->cuenta_contable_id,
            'solo_debe' => $request->solo_debe,
            'solo_haber' => $request->solo_haber,
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', 15)
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId);
                })
                ->where('eliminado', 0)
                ->with(['asientoContable', 'cuentaContable']);

            // Filtro por asiento contable
            if ($request->filled('asiento_contable_id')) {
                $query->where('asiento_contable_id', $request->asiento_contable_id);
            }

            // Filtro por cuenta contable
            if ($request->filled('cuenta_contable_id')) {
                $query->where('cuenta_contable_id', $request->cuenta_contable_id);
            }

            // Filtro solo movimientos al debe
            if ($request->filled('solo_debe') && $request->solo_debe == 1) {
                $query->where('debe', '>', 0);
            }

            // Filtro solo movimientos al haber
            if ($request->filled('solo_haber') && $request->solo_haber == 1) {
                $query->where('haber', '>', 0);
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));

            $detalles = $query->paginate($request->get('per_page', 15));

            return DetalleAsientoResource::collection($detalles);
        });
    }

    /**
     * Mostrar un detalle de asiento específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/detalles-asientos/{id}",
        summary: "Obtener detalle de asiento",
        description: "Obtiene información completa de un movimiento específico (debe/haber) de un asiento contable.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del detalle de asiento",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/DetalleAsiento")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Detalle no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $detalle = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable'])
            ->firstOrFail();
        
        $this->authorize('view', $detalle);

        return response()->json([
            'success' => true,
            'data' => new DetalleAsientoResource($detalle)
        ]);
    }

    /**
     * Obtener movimientos (debe y haber) de una cuenta contable específica
     *
     * @param int $cuentaContableId
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/detalles-asientos/cuenta/{cuentaContableId}",
        summary: "Movimientos por cuenta contable",
        description: "Obtiene todos los movimientos (debe/haber) de una cuenta contable específica. Solo incluye asientos mayorizados. Calcula totales y saldo.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "cuentaContableId",
                in: "path",
                description: "ID de la cuenta contable",
                required: true,
                schema: new OA\Schema(type: "integer", example: 5)
            ),
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha de inicio del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha de fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Movimientos obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "movimientos", type: "array", items: new OA\Items(ref: "#/components/schemas/DetalleAsiento")),
                                new OA\Property(property: "total_debe", type: "number", format: "decimal", example: 350000.00),
                                new OA\Property(property: "total_haber", type: "number", format: "decimal", example: 200000.00),
                                new OA\Property(property: "saldo", type: "number", format: "decimal", example: 150000.00)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function porCuenta(int $cuentaContableId, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado'); // Solo asientos mayorizados
            })
            ->where('cuenta_contable_id', $cuentaContableId)
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable']);

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereHas('asientoContable', function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->desde, $request->hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')->get();

        $totalDebe = $detalles->sum('debe');
        $totalHaber = $detalles->sum('haber');
        $saldo = $totalDebe - $totalHaber;

        return response()->json([
            'success' => true,
            'data' => [
                'movimientos' => DetalleAsientoResource::collection($detalles),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'saldo' => $saldo
            ]
        ]);
    }

    /**
     * Obtener libro mayor (mayor analítico) de todas las cuentas
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/detalles-asientos/libro-mayor",
        summary: "Libro mayor analítico",
        description: "Genera el libro mayor analítico agrupando todos los movimientos por cuenta contable. Solo incluye asientos mayorizados. Calcula debe, haber y saldo por cada cuenta.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha de inicio del período",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha de fin del período",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Libro mayor generado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "cuenta_contable", ref: "#/components/schemas/CuentaContable"),
                                    new OA\Property(property: "movimientos", type: "array", items: new OA\Items(ref: "#/components/schemas/DetalleAsiento")),
                                    new OA\Property(property: "total_debe", type: "number", format: "decimal", example: 500000.00),
                                    new OA\Property(property: "total_haber", type: "number", format: "decimal", example: 350000.00),
                                    new OA\Property(property: "saldo", type: "number", format: "decimal", example: 150000.00),
                                    new OA\Property(property: "cantidad_movimientos", type: "integer", example: 25)
                                ],
                                type: "object"
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function libroMayor(LibroMayorRequest $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId, $request) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado');
                
                if ($request->filled('desde') && $request->filled('hasta')) {
                    $q->whereBetween('fecha', [$request->desde, $request->hasta]);
                }
            })
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable'])
            ->orderBy('cuenta_contable_id')
            ->orderBy('created_at')
            ->get();

        // Agrupar por cuenta contable
        $libroMayor = $query->groupBy('cuenta_contable_id')->map(function ($detalles) {
            $totalDebe = $detalles->sum('debe');
            $totalHaber = $detalles->sum('haber');
            
            return [
                'cuenta_contable' => $detalles->first()->cuentaContable,
                'movimientos' => DetalleAsientoResource::collection($detalles),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'saldo' => $totalDebe - $totalHaber,
                'cantidad_movimientos' => $detalles->count()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $libroMayor
        ]);
    }

    /**
     * Obtener balance de comprobación
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/detalles-asientos/balance-comprobacion",
        summary: "Balance de comprobación",
        description: "Genera el balance de comprobación mostrando sumas y saldos de todas las cuentas. Solo incluye asientos mayorizados. Verifica que debe = haber.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha de inicio del período",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha de fin del período",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Balance de comprobación generado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "balance",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "cuenta_contable", ref: "#/components/schemas/CuentaContable"),
                                            new OA\Property(property: "total_debe", type: "number", format: "decimal", example: 500000.00),
                                            new OA\Property(property: "total_haber", type: "number", format: "decimal", example: 350000.00),
                                            new OA\Property(property: "saldo_deudor", type: "number", format: "decimal", example: 150000.00),
                                            new OA\Property(property: "saldo_acreedor", type: "number", format: "decimal", example: 0.00)
                                        ],
                                        type: "object"
                                    )
                                ),
                                new OA\Property(
                                    property: "totales",
                                    properties: [
                                        new OA\Property(property: "total_debe", type: "number", format: "decimal", example: 2500000.00),
                                        new OA\Property(property: "total_haber", type: "number", format: "decimal", example: 2500000.00),
                                        new OA\Property(property: "total_saldos_deudores", type: "number", format: "decimal", example: 1500000.00),
                                        new OA\Property(property: "total_saldos_acreedores", type: "number", format: "decimal", example: 1500000.00)
                                    ],
                                    type: "object"
                                )
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function balanceComprobacion(BalanceComprobacionRequest $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId, $request) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado');
                
                if ($request->filled('desde') && $request->filled('hasta')) {
                    $q->whereBetween('fecha', [$request->desde, $request->hasta]);
                }
            })
            ->where('eliminado', 0)
            ->selectRaw('cuenta_contable_id, SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->groupBy('cuenta_contable_id')
            ->with('cuentaContable.tipoCuenta')
            ->get();

        $balance = $query->map(function ($item) {
            $saldo = $item->total_debe - $item->total_haber;
            
            return [
                'cuenta_contable' => $item->cuentaContable,
                'total_debe' => $item->total_debe,
                'total_haber' => $item->total_haber,
                'saldo_deudor' => $saldo > 0 ? $saldo : 0,
                'saldo_acreedor' => $saldo < 0 ? abs($saldo) : 0
            ];
        });

        $totales = [
            'total_debe' => $query->sum('total_debe'),
            'total_haber' => $query->sum('total_haber'),
            'total_saldos_deudores' => $balance->sum('saldo_deudor'),
            'total_saldos_acreedores' => $balance->sum('saldo_acreedor')
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'totales' => $totales
            ]
        ]);
    }
}
