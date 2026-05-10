<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MovimientoBancario;
use App\Http\Requests\StoreMovimientoBancarioRequest;
use App\Http\Requests\UpdateMovimientoBancarioRequest;
use App\Http\Resources\MovimientoBancarioResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controller para gestionar movimientos bancarios
 * Depósitos, retiros, transferencias, comisiones e intereses
 *
 * @author GitHub Copilot
 * @copyright 2025 Senselab
 */

#[OA\Tag(
    name: 'Movimientos Bancarios',
    description: 'Gestión de movimientos bancarios (depósitos, retiros, transferencias)'
)]
class MovimientoBancarioController extends Controller
{
    use HasCacheableQueries;

    private const MSG_NOT_FOUND = 'Movimiento bancario no encontrado';

    /**
     * Tags para invalidación de cache
     * @var array<int, string>
     */
    protected array $cacheTags = ['movimientos-bancarios', 'finanzas', 'bancos'];

    /**
     * TTL del cache en segundos (30 minutos)
     * Datos dinámicos: movimientos bancarios frecuentes
     * @var int
     */
    protected int $cacheTTL = 1800;
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
        #[OA\Get(
        path: '/api/movimiento-bancario',
        summary: 'Listar movimientos bancarios',
        security: [['sanctum' => []]],
        tags: ['Movimientos Bancarios'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de movimientos bancarios'),
        ]
    )]

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MovimientoBancario::class);

        $perPage = $request->input('per_page', 15);
        $empresaId = Auth::user()->empresa_id;

        $cacheKey = $this->getCacheKey('index', $this->getIndexCacheParams($request, $perPage));

        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId, $perPage) {
            $query = $this->buildBaseQuery($empresaId);
            $this->applyFilters($query, $request);

            $movimientos = $query->orderBy('fecha_movimiento', 'desc')
                                 ->orderBy('created_at', 'desc')
                                 ->paginate($perPage);

            return MovimientoBancarioResource::collection($movimientos);
        });
    }

    /**
     * Build base query for movimientos bancarios
     */
    private function buildBaseQuery(int $empresaId): \Illuminate\Database\Eloquent\Builder
    {
        return MovimientoBancario::with(['empresa', 'cuentaBancaria', 'asientoContable'])
            ->where('empresa_id', $empresaId)
            ->where('eliminado', false);
    }

    /**
     * Get cache parameters for index
     *
     * @return array<string, mixed>
     */
    private function getIndexCacheParams(Request $request, int $perPage): array
    {
        return [
            'search' => $request->input('search'),
            'cuenta_bancaria_id' => $request->input('cuenta_bancaria_id'),
            'tipo_movimiento' => $request->input('tipo_movimiento'),
            'per_page' => $perPage
        ];
    }

    /**
     * Apply all filters to query
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        $this->applySearchFilter($query, $request->input('search'));
        $this->applyCuentaBancariaFilter($query, $request);
        $this->applyTipoMovimientoFilter($query, $request);
        $this->applyConciliacionFilter($query, $request);
        $this->applyFechaFilter($query, $request);
        $this->applyMontoFilter($query, $request);
    }

    /**
     * Apply search filter
     */
    private function applySearchFilter(\Illuminate\Database\Eloquent\Builder $query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('descripcion', 'like', "%{$search}%")
              ->orWhere('numero_referencia', 'like', "%{$search}%")
              ->orWhere('beneficiario', 'like', "%{$search}%");
        });
    }

    /**
     * Apply cuenta bancaria filter
     */
    private function applyCuentaBancariaFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->has('cuenta_bancaria_id')) {
            $query->where('cuenta_bancaria_id', $request->input('cuenta_bancaria_id'));
        }
    }

    /**
     * Apply tipo movimiento filter
     */
    private function applyTipoMovimientoFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->has('tipo_movimiento')) {
            $query->porTipo($request->input('tipo_movimiento'));
        }
    }

    /**
     * Apply conciliacion filter
     */
    private function applyConciliacionFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->has('conciliado')) {
            $request->boolean('conciliado') ? $query->conciliados() : $query->pendientesConciliacion();
        } elseif ($request->boolean('conciliados')) {
            $query->conciliados();
        }

        if ($request->boolean('pendientes_conciliacion')) {
            $query->pendientesConciliacion();
        }
    }

    /**
     * Apply fecha filter
     */
    private function applyFechaFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
            $query->entreFechas($request->input('fecha_desde'), $request->input('fecha_hasta'));
        }
    }

    /**
     * Apply monto filter
     */
    private function applyMontoFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->has('monto_minimo')) {
            $query->where('monto', '>=', $request->input('monto_minimo'));
        }

        if ($request->has('monto_maximo')) {
            $query->where('monto', '<=', $request->input('monto_maximo'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMovimientoBancarioRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
        #[OA\Post(
        path: '/api/movimiento-bancario',
        summary: 'Crear movimiento bancario',
        security: [['sanctum' => []]],
        tags: ['Movimientos Bancarios'],
        responses: [
            new OA\Response(response: 201, description: 'movimiento bancario creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(StoreMovimientoBancarioRequest $request): JsonResponse
    {
        $this->authorize('create', MovimientoBancario::class);

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Asignar empresa_id del usuario autenticado
            $data['empresa_id'] = Auth::user()->empresa_id;

            // Calcular saldo después del movimiento si no viene
            if (!isset($data['saldo_despues'])) {
                $cuentaBancaria = \App\Models\CuentaBancaria::find($data['cuenta_bancaria_id']);

                if (in_array($data['tipo_movimiento'], ['deposito', 'transferencia_entrada', 'interes'])) {
                    $data['saldo_despues'] = $cuentaBancaria->saldo_actual + $data['monto'];
                } else {
                    $data['saldo_despues'] = $cuentaBancaria->saldo_actual - $data['monto'];
                }

                // Actualizar saldo de la cuenta bancaria
                $cuentaBancaria->saldo_actual = $data['saldo_despues'];
                $cuentaBancaria->save();
            }

            $movimiento = MovimientoBancario::create($data);
            $movimiento->load(['empresa', 'cuentaBancaria', 'asientoContable']);

            DB::commit();
            $this->flushCache();

            return (new MovimientoBancarioResource($movimiento))
                ->additional(['message' => 'Movimiento bancario creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear movimiento bancario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return MovimientoBancarioResource
     */
        #[OA\Get(
        path: '/api/movimiento-bancario/{id}',
        summary: 'Obtener movimiento bancario',
        security: [['sanctum' => []]],
        tags: ['Movimientos Bancarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'movimiento bancario encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(int $id): MovimientoBancarioResource
    {
        try {
            $movimiento = MovimientoBancario::with([
                'empresa',
                'cuentaBancaria',
                'asientoContable'
            ])->findOrFail($id);

            $this->authorize('view', $movimiento);

            return new MovimientoBancarioResource($movimiento);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, self::MSG_NOT_FOUND);
        } catch (\Exception $e) {
            abort(500, 'Error al obtener movimiento bancario: ');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateMovimientoBancarioRequest $request
     * @param int $id
     * @return MovimientoBancarioResource
     */
        #[OA\Put(
        path: '/api/movimiento-bancario/{id}',
        summary: 'Actualizar movimiento bancario',
        security: [['sanctum' => []]],
        tags: ['Movimientos Bancarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'movimiento bancario actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(UpdateMovimientoBancarioRequest $request, int $id): MovimientoBancarioResource
    {
        try {
            $movimiento = MovimientoBancario::with([
                'empresa',
                'cuentaBancaria',
                'asientoContable'
            ])->findOrFail($id);

            $this->authorize('update', $movimiento);

            $movimiento->update($request->validated());

            return (new MovimientoBancarioResource($movimiento))
                ->additional(['message' => 'Movimiento bancario actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, self::MSG_NOT_FOUND);
        } catch (\Exception $e) {
            abort(500, 'Error al actualizar movimiento bancario: ');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
        #[OA\Delete(
        path: '/api/movimiento-bancario/{id}',
        summary: 'Eliminar movimiento bancario',
        security: [['sanctum' => []]],
        tags: ['Movimientos Bancarios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'movimiento bancario eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $movimiento = MovimientoBancario::with(['cuentaBancaria'])->findOrFail($id);

            $this->authorize('delete', $movimiento);

            // Revertir el saldo de la cuenta bancaria
            $cuentaBancaria = $movimiento->cuentaBancaria;

            if ($movimiento->esDeposito()) {
                $cuentaBancaria->saldo_actual -= $movimiento->monto;
            } else {
                $cuentaBancaria->saldo_actual += $movimiento->monto;
            }

            $cuentaBancaria->save();

            // Soft delete
            $movimiento->update(['eliminado' => true]);

            DB::commit();

            return response()->json([
                'message' => 'Movimiento bancario eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'message' => self::MSG_NOT_FOUND
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar movimiento bancario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Conciliar movimiento bancario
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function conciliar(int $id): JsonResponse
    {
        try {
            $movimiento = MovimientoBancario::findOrFail($id);

            $this->authorize('update', $movimiento);

            $movimiento->conciliar();

            return response()->json([
                'message' => 'Movimiento conciliado exitosamente',
                'data' => new MovimientoBancarioResource($movimiento)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => self::MSG_NOT_FOUND
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conciliar movimiento',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
