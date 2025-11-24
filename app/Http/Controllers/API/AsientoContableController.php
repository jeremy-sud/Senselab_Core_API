<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsientoContableRequest;
use App\Http\Requests\UpdateAsientoContableRequest;
use App\Http\Resources\AsientoContableResource;
use App\Models\AsientoContable;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Asientos Contables
 *
 * Gestiona los asientos contables con sistema de doble partida (debe = haber).
 * Incluye estados: Borrador, Mayorizado, Anulado.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class AsientoContableController extends Controller
{
    use HasCacheableQueries;

    /**
     * Tags para invalidación de cache
     * @var array<string>
     */
    protected array $cacheTags = ['asientos-contables', 'contabilidad'];

    /**
     * TTL del cache en segundos (1 hora)
     * Datos semi-estables: asientos cambian moderadamente
     * @var int
     */
    protected int $cacheTTL = 3600;
    /**
     * Listar todos los asientos contables de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/asientos-contables",
        summary: "Listar asientos contables",
        description: "Obtiene un listado de todos los asientos contables de la empresa con sistema de doble partida. Permite filtrar por estado, rango de fechas y cuenta contable.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado del asiento",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Borrador", "Mayorizado", "Anulado"])
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
                description: "Fecha fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            ),
            new OA\Parameter(
                name: "cuenta_contable_id",
                in: "query",
                description: "Filtrar por cuenta contable involucrada",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo para ordenar",
                required: false,
                schema: new OA\Schema(type: "string", example: "fecha_asiento")
            ),
            new OA\Parameter(
                name: "sort_order",
                in: "query",
                description: "Orden ascendente o descendente",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], example: "desc")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Cantidad de registros por página",
                required: false,
                schema: new OA\Schema(type: "integer", example: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de asientos obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/AsientoContable")
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
        $this->authorize('viewAny', AsientoContable::class);

        $empresaId = $request->user()->empresa_id;
        
        $cacheKey = $this->getCacheKey('index', [
            'estado' => $request->estado,
            'desde' => $request->desde,
            'hasta' => $request->hasta,
            'cuenta_contable_id' => $request->cuenta_contable_id,
            'sort_by' => $request->get('sort_by', 'fecha_asiento'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', 15)
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId) {
            $query = AsientoContable::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['detalles.cuentaContable', 'empresa']);

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por rango de fechas
            if ($request->filled('desde') && $request->filled('hasta')) {
                $query->whereBetween('fecha_asiento', [$request->desde, $request->hasta]);
            }

            // Filtro por cuenta contable (a través de detalles)
            if ($request->filled('cuenta_contable_id')) {
                $query->whereHas('detalles', function ($q) use ($request) {
                    $q->where('cuenta_contable_id', $request->cuenta_contable_id);
                });
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'fecha_asiento'), $request->get('sort_order', 'desc'));

            $asientos = $query->paginate($request->get('per_page', 15));

            return AsientoContableResource::collection($asientos);
        }, [
            'estado' => $request->input('estado'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'cuenta_contable_id' => $request->input('cuenta_contable_id'),
            'sort_by' => $request->input('sort_by'),
            'sort_order' => $request->input('sort_order'),
            'per_page' => $request->input('per_page')
        ]);
    }

    /**
     * Crear un nuevo asiento contable
     *
     * @param StoreAsientoContableRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/asientos-contables",
        summary: "Crear asiento contable",
        description: "Registra un nuevo asiento contable con sus detalles. El total del debe debe ser igual al total del haber (doble partida). Los totales se calculan automáticamente.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["fecha_asiento", "detalles"],
                properties: [
                    new OA\Property(property: "fecha_asiento", type: "string", format: "date-time", example: "2024-01-15T10:30:00Z"),
                    new OA\Property(property: "descripcion", type: "string", example: "Registro de venta #1234"),
                    new OA\Property(property: "estado", type: "string", enum: ["Borrador", "Mayorizado", "Anulado"], example: "Borrador"),
                    new OA\Property(
                        property: "detalles",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "cuenta_contable_id", type: "integer", example: 1),
                                new OA\Property(property: "debe", type: "number", format: "decimal", example: 150000.00),
                                new OA\Property(property: "haber", type: "number", format: "decimal", example: 0.00),
                                new OA\Property(property: "descripcion", type: "string", example: "Registro de venta")
                            ]
                        )
                    )
                ]
            )
        ),
        tags: ["Contabilidad"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Asiento creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Asiento contable creado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/AsientoContable")
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
    public function store(StoreAsientoContableRequest $request): JsonResponse
    {
        $this->authorize('create', AsientoContable::class);
        
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['empresa_id'] = $request->user()->empresa_id;

            // Calcular totales de debe y haber
            $detalles = $validated['detalles'];
            $totalDebe = collect($detalles)->sum('debe');
            $totalHaber = collect($detalles)->sum('haber');

            $validated['total_debe'] = $totalDebe;
            $validated['total_haber'] = $totalHaber;
            $validated['estado'] = $validated['estado'] ?? 'Borrador';

            // Crear asiento
            $asiento = AsientoContable::create($validated);

            // Crear detalles
            foreach ($detalles as $detalle) {
                $asiento->detalles()->create($detalle);
            }

            $asiento->load(['detalles.cuentaContable', 'empresa']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable creado exitosamente',
                'data' => new AsientoContableResource($asiento)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el asiento contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un asiento contable específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/asientos-contables/{id}",
        summary: "Obtener asiento contable",
        description: "Obtiene los detalles completos de un asiento contable específico, incluyendo todos sus detalles y cuentas relacionadas.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del asiento contable",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asiento encontrado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/AsientoContable")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Asiento no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable', 'empresa'])
            ->firstOrFail();
        $this->authorize('view', $asiento);

        return response()->json([
            'success' => true,
            'data' => new AsientoContableResource($asiento)
        ]);
    }

    /**
     * Actualizar un asiento contable existente
     *
     * @param UpdateAsientoContableRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/asientos-contables/{id}",
        summary: "Actualizar asiento contable",
        description: "Actualiza los datos de un asiento contable existente. No permite modificar asientos en estado 'Mayorizado'. Los totales se recalculan automáticamente.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "fecha_asiento", type: "string", format: "date-time", example: "2024-01-15T10:30:00Z"),
                    new OA\Property(property: "descripcion", type: "string", example: "Registro de venta #1234"),
                    new OA\Property(property: "estado", type: "string", enum: ["Borrador", "Mayorizado", "Anulado"], example: "Borrador"),
                    new OA\Property(
                        property: "detalles",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "cuenta_contable_id", type: "integer", example: 1),
                                new OA\Property(property: "debe", type: "number", format: "decimal", example: 150000.00),
                                new OA\Property(property: "haber", type: "number", format: "decimal", example: 0.00),
                                new OA\Property(property: "descripcion", type: "string", example: "Registro de venta")
                            ]
                        )
                    )
                ]
            )
        ),
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del asiento a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asiento actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Asiento contable actualizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/AsientoContable")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Asiento no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede modificar un asiento mayorizado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function update(UpdateAsientoContableRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('update', $asiento);

        // No permitir modificar asientos mayorizados
        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un asiento contable que ya ha sido mayorizado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Si se actualizan los detalles, recalcular totales
            if (isset($validated['detalles'])) {
                $detalles = $validated['detalles'];
                $totalDebe = collect($detalles)->sum('debe');
                $totalHaber = collect($detalles)->sum('haber');

                $validated['total_debe'] = $totalDebe;
                $validated['total_haber'] = $totalHaber;

                // Eliminar detalles antiguos y crear nuevos
                $asiento->detalles()->delete();
                foreach ($detalles as $detalle) {
                    $asiento->detalles()->create($detalle);
                }
            }

            $asiento->update($validated);
            $asiento->load(['detalles.cuentaContable', 'empresa']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable actualizado exitosamente',
                'data' => new AsientoContableResource($asiento)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el asiento contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (soft delete) un asiento contable
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/asientos-contables/{id}",
        summary: "Eliminar asiento contable",
        description: "Realiza un soft delete del asiento contable y sus detalles. No permite eliminar asientos en estado 'Mayorizado'.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del asiento a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asiento eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Asiento contable eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Asiento no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar un asiento mayorizado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('delete', $asiento);

        // No permitir eliminar asientos mayorizados
        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un asiento contable que ya ha sido mayorizado'
            ], 422);
        }

        $asiento->update(['eliminado' => 1, 'activo' => 0]);
        $asiento->detalles()->update(['eliminado' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Asiento contable eliminado exitosamente'
        ]);
    }

    /**
     * Mayorizar un asiento contable (cambiar estado a Mayorizado)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/asientos-contables/{id}/mayorizar",
        summary: "Mayorizar asiento contable",
        description: "Cambia el estado del asiento a 'Mayorizado' y actualiza los saldos de las cuentas contables involucradas. Valida que el total del debe sea igual al total del haber antes de mayorizar.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del asiento a mayorizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Asiento mayorizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Asiento contable mayorizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/AsientoContable")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Asiento no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "El asiento ya está mayorizado o no está balanceado (debe != haber)"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function mayorizar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with('detalles.cuentaContable')
            ->firstOrFail();

        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'El asiento contable ya está mayorizado'
            ], 422);
        }

        // Validar que debe = haber
        if ($asiento->total_debe != $asiento->total_haber) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede mayorizar: el total del debe no coincide con el haber'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Actualizar saldo de las cuentas contables
            foreach ($asiento->detalles as $detalle) {
                $cuenta = $detalle->cuentaContable;
                $diferencia = $detalle->debe - $detalle->haber;
                $cuenta->increment('saldo_actual', $diferencia);
            }

            $asiento->update(['estado' => 'Mayorizado']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable mayorizado exitosamente',
                'data' => new AsientoContableResource($asiento->fresh(['detalles.cuentaContable']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al mayorizar el asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar que un asiento esté balanceado (debe = haber)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/asientos-contables/{id}/validar",
        summary: "Validar balance del asiento",
        description: "Verifica que el asiento contable esté balanceado (total del debe = total del haber). Retorna información sobre el estado del balance.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del asiento a validar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Validación realizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "balanceado", type: "boolean", example: true),
                                new OA\Property(property: "total_debe", type: "number", format: "decimal", example: 150000.00),
                                new OA\Property(property: "total_haber", type: "number", format: "decimal", example: 150000.00),
                                new OA\Property(property: "diferencia", type: "number", format: "decimal", example: 0.00),
                                new OA\Property(property: "estado", type: "string", example: "Mayorizado")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Asiento no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function validar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $balanceado = $asiento->total_debe == $asiento->total_haber;
        $diferencia = abs($asiento->total_debe - $asiento->total_haber);

        return response()->json([
            'success' => true,
            'data' => [
                'balanceado' => $balanceado,
                'total_debe' => $asiento->total_debe,
                'total_haber' => $asiento->total_haber,
                'diferencia' => $diferencia,
                'estado' => $asiento->estado
            ]
        ]);
    }
}
