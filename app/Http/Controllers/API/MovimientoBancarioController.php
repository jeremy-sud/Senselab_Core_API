<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MovimientoBancario;
use App\Http\Requests\StoreMovimientoBancarioRequest;
use App\Http\Requests\UpdateMovimientoBancarioRequest;
use App\Http\Resources\MovimientoBancarioResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller para gestionar movimientos bancarios
 * Depósitos, retiros, transferencias, comisiones e intereses
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MovimientoBancarioController extends Controller
{
    use HasCacheableQueries;

    /**
     * Tags para invalidación de cache
     * @var array<string>
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', MovimientoBancario::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = Auth::user()->empresa_id;
            
            $cacheKey = $this->getCacheKey('index', [
                'search' => $search,
                'cuenta_bancaria_id' => $request->input('cuenta_bancaria_id'),
                'tipo_movimiento' => $request->input('tipo_movimiento'),
                'per_page' => $perPage
            ]);
            
            return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId, $search, $perPage) {
                $query = MovimientoBancario::with(['empresa', 'cuentaBancaria', 'asientoContable'])
                                           ->where('empresa_id', $empresaId)
                                           ->where('eliminado', false);
                
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('descripcion', 'like', "%{$search}%")
                          ->orWhere('numero_referencia', 'like', "%{$search}%")
                          ->orWhere('beneficiario', 'like', "%{$search}%");
                    });
                }
                
                // Filtro por cuenta bancaria
                if ($request->has('cuenta_bancaria_id')) {
                    $query->where('cuenta_bancaria_id', $request->input('cuenta_bancaria_id'));
                }
                
                // Filtro por tipo de movimiento
                if ($request->has('tipo_movimiento')) {
                    $query->porTipo($request->input('tipo_movimiento'));
                }
                
                // Filtro por estado de conciliación
                if ($request->boolean('conciliados')) {
                    $query->conciliados();
                }
                
                if ($request->boolean('pendientes_conciliacion')) {
                    $query->pendientesConciliacion();
                }
                
                // Filtro por rango de fechas
                if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                    $query->entreFechas($request->input('fecha_desde'), $request->input('fecha_hasta'));
                }
                
                // Filtro por monto mínimo/máximo
                if ($request->has('monto_minimo')) {
                    $query->where('monto', '>=', $request->input('monto_minimo'));
                }
                
                if ($request->has('monto_maximo')) {
                    $query->where('monto', '<=', $request->input('monto_maximo'));
                }
                
                $movimientos = $query->orderBy('fecha_movimiento', 'desc')
                                     ->orderBy('created_at', 'desc')
                                     ->paginate($perPage);
                
                return MovimientoBancarioResource::collection($movimientos);
            }, [
                'search' => $search,
                'cuenta_bancaria_id' => $request->input('cuenta_bancaria_id'),
                'tipo_movimiento' => $request->input('tipo_movimiento'),
                'conciliados' => $request->boolean('conciliados'),
                'pendientes_conciliacion' => $request->boolean('pendientes_conciliacion'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta'),
                'monto_minimo' => $request->input('monto_minimo'),
                'monto_maximo' => $request->input('monto_maximo'),
                'per_page' => $perPage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener movimientos bancarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreMovimientoBancarioRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMovimientoBancarioRequest $request)
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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
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
            return response()->json([
                'message' => 'Movimiento bancario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener movimiento bancario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateMovimientoBancarioRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateMovimientoBancarioRequest $request, int $id)
    {
        try {
            $movimiento = MovimientoBancario::findOrFail($id);
            
            $this->authorize('update', $movimiento);
            
            $movimiento->update($request->validated());
            $movimiento->load(['empresa', 'cuentaBancaria', 'asientoContable']);
            
            return (new MovimientoBancarioResource($movimiento))
                ->additional(['message' => 'Movimiento bancario actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Movimiento bancario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar movimiento bancario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();
            
            $movimiento = MovimientoBancario::findOrFail($id);
            
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
                'message' => 'Movimiento bancario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al eliminar movimiento bancario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Conciliar un movimiento bancario
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function conciliar(int $id)
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
                'message' => 'Movimiento bancario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al conciliar movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
