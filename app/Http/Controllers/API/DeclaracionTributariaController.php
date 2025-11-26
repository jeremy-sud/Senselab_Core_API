<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeclaracionTributaria;
use App\Http\Requests\StoreDeclaracionTributariaRequest;
use App\Http\Requests\UpdateDeclaracionTributariaRequest;
use App\Http\Resources\DeclaracionTributariaResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gestionar declaraciones tributarias
 * D104 (IVA), D101 (Renta), D103, D150, D151 ante Hacienda
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DeclaracionTributariaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['declaraciones-tributarias', 'contabilidad', 'hacienda'];
    protected int $cacheTTL = 2700; // 45min - tax filing queries, semi-stable
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DeclaracionTributaria::class);
        
        $cacheKey = $this->getCacheKey('index', [
            'search' => $request->input('search'),
            'tipo_declaracion' => $request->input('tipo_declaracion'),
            'solo_iva' => $request->boolean('solo_iva'),
            'solo_renta' => $request->boolean('solo_renta'),
            'estado' => $request->input('estado'),
            'periodo_fiscal' => $request->input('periodo_fiscal'),
            'anio_fiscal' => $request->input('anio_fiscal'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'con_saldo_pagar' => $request->boolean('con_saldo_pagar'),
            'con_saldo_favor' => $request->boolean('con_saldo_favor'),
            'per_page' => $request->input('per_page', 15)
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            try {
                $perPage = $request->input('per_page', 15);
                $search = $request->input('search');
                $empresaId = Auth::user()->empresa_id;
                
                $query = DeclaracionTributaria::with(['empresa'])
                                              ->where('empresa_id', $empresaId)
                                              ->where('eliminado', false);
                
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('periodo_fiscal', 'like', "%{$search}%")
                          ->orWhere('numero_confirmacion', 'like', "%{$search}%")
                          ->orWhere('notas', 'like', "%{$search}%");
                    });
                }
                
                // Filtro por tipo de declaración
                if ($request->has('tipo_declaracion')) {
                    $query->porTipo($request->input('tipo_declaracion'));
                }
                
                // Filtros rápidos por tipo
                if ($request->boolean('solo_iva')) {
                    $query->porTipo('D104');
                }
                
                if ($request->boolean('solo_renta')) {
                    $query->porTipo('D101');
                }
                
                // Filtro por estado
                if ($request->has('estado')) {
                    $estado = $request->input('estado');
                    
                    if ($estado === 'borrador') {
                        $query->borradores();
                    } elseif ($estado === 'enviada') {
                        $query->enviadas();
                    } elseif ($estado === 'aceptada') {
                        $query->aceptadas();
                    }
                }
                
                // Filtro por período fiscal
                if ($request->has('periodo_fiscal')) {
                    $query->porPeriodo($request->input('periodo_fiscal'));
                }
                
                // Filtro por año fiscal
                if ($request->has('anio_fiscal')) {
                    $query->where('periodo_fiscal', 'like', $request->input('anio_fiscal') . '%');
                }
                
                // Filtro por rango de fechas
                if ($request->has('fecha_desde')) {
                    $query->where('fecha_inicio_periodo', '>=', $request->input('fecha_desde'));
                }
                
                if ($request->has('fecha_hasta')) {
                    $query->where('fecha_fin_periodo', '<=', $request->input('fecha_hasta'));
                }
                
                // Filtro por declaraciones con saldo a pagar
                if ($request->boolean('con_saldo_pagar')) {
                    $query->where('monto_a_pagar', '>', 0);
                }
                
                // Filtro por declaraciones con saldo a favor
                if ($request->boolean('con_saldo_favor')) {
                    $query->where('monto_a_favor', '>', 0);
                }
                
                $declaraciones = $query->orderBy('fecha_inicio_periodo', 'desc')
                                       ->orderBy('created_at', 'desc')
                                       ->paginate($perPage);
                
                return DeclaracionTributariaResource::collection($declaraciones);
            } catch (\Exception $e) {
                // En caso de error, retornamos una colección vacía o lanzamos excepción
                // Para mantener compatibilidad con el tipo de retorno, lanzamos excepción
                throw $e;
            }
        });
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreDeclaracionTributariaRequest $request
     * @return DeclaracionTributariaResource|\Illuminate\Http\JsonResponse
     */
    public function store(StoreDeclaracionTributariaRequest $request): DeclaracionTributariaResource|JsonResponse
    {
        $this->authorize('create', DeclaracionTributaria::class);
        
        try {
            $data = $request->validated();
            
            // Asignar empresa_id del usuario autenticado
            $data['empresa_id'] = Auth::user()->empresa_id;
            
            // Asignar estado borrador por defecto si no viene
            if (!isset($data['estado'])) {
                $data['estado'] = 'borrador';
            }
            
            // Calcular monto a pagar o a favor si no vienen
            if (!isset($data['monto_a_pagar']) && !isset($data['monto_a_favor'])) {
                $saldoNeto = ($data['monto_debitos'] ?? 0) - ($data['monto_creditos'] ?? 0);
                
                if ($saldoNeto > 0) {
                    $data['monto_a_pagar'] = $saldoNeto;
                    $data['monto_a_favor'] = 0;
                } else {
                    $data['monto_a_pagar'] = 0;
                    $data['monto_a_favor'] = abs($saldoNeto);
                }
            }
            
            $declaracion = DeclaracionTributaria::create($data);
            $declaracion->load(['empresa']);
            
            $this->flushCache();
            
            return (new DeclaracionTributariaResource($declaracion))
                ->additional(['message' => 'Declaración tributaria creada exitosamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear declaración tributaria',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return DeclaracionTributariaResource|\Illuminate\Http\JsonResponse
     */
    public function show(int $id): DeclaracionTributariaResource|JsonResponse
    {
        try {
            $declaracion = DeclaracionTributaria::with(['empresa'])->findOrFail($id);
            
            $this->authorize('view', $declaracion);
            
            return new DeclaracionTributariaResource($declaracion);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Declaración tributaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener declaración tributaria',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateDeclaracionTributariaRequest $request
     * @param int $id
     * @return DeclaracionTributariaResource|\Illuminate\Http\JsonResponse
     */
    public function update(UpdateDeclaracionTributariaRequest $request, int $id): DeclaracionTributariaResource|JsonResponse
    {
        try {
            $declaracion = DeclaracionTributaria::findOrFail($id);
            
            $this->authorize('update', $declaracion);
            
            // No permitir edición si ya fue aceptada
            if ($declaracion->fueAceptada()) {
                return response()->json([
                    'message' => 'No se puede editar una declaración aceptada por Hacienda'
                ], 422);
            }
            
            $data = $request->validated();
            
            // Recalcular saldo neto si cambiaron débitos o créditos
            if ((isset($data['monto_debitos']) || isset($data['monto_creditos'])) && 
                !isset($data['monto_a_pagar']) && !isset($data['monto_a_favor'])) {
                
                $debitos = $data['monto_debitos'] ?? $declaracion->monto_debitos;
                $creditos = $data['monto_creditos'] ?? $declaracion->monto_creditos;
                $saldoNeto = $debitos - $creditos;
                
                if ($saldoNeto > 0) {
                    $data['monto_a_pagar'] = $saldoNeto;
                    $data['monto_a_favor'] = 0;
                } else {
                    $data['monto_a_pagar'] = 0;
                    $data['monto_a_favor'] = abs($saldoNeto);
                }
            }
            
            $declaracion->update($data);
            $declaracion->load(['empresa']);
            
            $this->flushCache();
            
            return (new DeclaracionTributariaResource($declaracion))
                ->additional(['message' => 'Declaración tributaria actualizada exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Declaración tributaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar declaración tributaria',
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
    public function destroy(int $id): JsonResponse
    {
        try {
            $declaracion = DeclaracionTributaria::findOrFail($id);
            
            $this->authorize('delete', $declaracion);
            
            // No permitir eliminación si ya fue aceptada
            if ($declaracion->fueAceptada()) {
                return response()->json([
                    'message' => 'No se puede eliminar una declaración aceptada por Hacienda'
                ], 422);
            }
            
            // Soft delete
            $declaracion->update(['eliminado' => true]);
            
            $this->flushCache();
            
            return response()->json([
                'message' => 'Declaración tributaria eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Declaración tributaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar declaración tributaria',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
