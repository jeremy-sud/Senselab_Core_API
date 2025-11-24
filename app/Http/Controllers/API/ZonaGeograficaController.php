<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ZonaGeografica;
use App\Http\Requests\StoreZonaGeograficaRequest;
use App\Http\Requests\UpdateZonaGeograficaRequest;
use App\Http\Resources\ZonaGeograficaResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para gestionar zonas geográficas
 * Provincias, cantones, distritos, zonas de ventas y rutas
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ZonaGeograficaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['zonas-geograficas', 'geografico'];
    protected int $cacheTTL = 3600; // 1 hora - cambia ocasionalmente

    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ZonaGeografica::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = Auth::user()->empresa_id;
            
            $cacheKey = $this->getCacheKey('index', [
                'search' => $search,
                'activa' => $request->get('activa'),
                'activas' => $request->get('activas'),
                'tipo' => $request->get('tipo'),
                'provincias' => $request->boolean('provincias'),
                'cantones' => $request->boolean('cantones'),
                'zonas_ventas' => $request->boolean('zonas_ventas'),
                'zona_padre_id' => $request->get('zona_padre_id'),
                'vendedor_asignado_id' => $request->get('vendedor_asignado_id'),
                'per_page' => $perPage
            ]);

            return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId, $search, $perPage) {
                $query = ZonaGeografica::with(['empresa', 'zonaPadre', 'vendedorAsignado'])
                                       ->where('empresa_id', $empresaId)
                                       ->where('eliminado', false);
                
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                          ->orWhere('codigo', 'like', "%{$search}%");
                    });
                }
                
                // Filtro por estado activo
                if ($request->has('activa') || $request->has('activas')) {
                    $esActivo = $request->boolean('activa') || $request->boolean('activas');
                    if ($esActivo) {
                        $query->activas();
                    } else {
                        $query->where('activa', false);
                    }
                }
                
                // Filtros por tipo
                if ($request->has('tipo')) {
                    $query->porTipo($request->input('tipo'));
                }
                
                if ($request->boolean('provincias')) {
                    $query->provincias();
                }
                
                if ($request->boolean('cantones')) {
                    $query->cantones();
                }
                
                if ($request->boolean('zonas_ventas')) {
                    $query->zonasVentas();
                }
                
                // Filtro por zona padre
                if ($request->has('zona_padre_id')) {
                    $query->where('zona_padre_id', $request->input('zona_padre_id'));
                }
                
                // Filtro por vendedor asignado
                if ($request->has('vendedor_asignado_id')) {
                    $query->where('vendedor_asignado_id', $request->input('vendedor_asignado_id'));
                }
                
                $zonasGeograficas = $query->orderBy('nombre', 'asc')
                                          ->cursorPaginate($perPage);
                
                return ZonaGeograficaResource::collection($zonasGeograficas);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener zonas geográficas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreZonaGeograficaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreZonaGeograficaRequest $request)
    {
        $this->authorize('create', ZonaGeografica::class);
        
        try {
            $data = $request->validated();
            
            // Asignar empresa_id del usuario autenticado si no viene en request
            if (!isset($data['empresa_id'])) {
                $data['empresa_id'] = Auth::user()->empresa_id;
            }
            
            $zonaGeografica = ZonaGeografica::create($data);
            $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);
            
            $this->flushCache();
            
            return (new ZonaGeograficaResource($zonaGeografica))
                ->additional(['message' => 'Zona geográfica creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear zona geográfica',
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
            $zonaGeografica = ZonaGeografica::with([
                'empresa', 
                'zonaPadre', 
                'zonasHijas', 
                'vendedorAsignado'
            ])->findOrFail($id);
            
            $this->authorize('view', $zonaGeografica);
            
            return new ZonaGeograficaResource($zonaGeografica);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Zona geográfica no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener zona geográfica',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateZonaGeograficaRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateZonaGeograficaRequest $request, int $id)
    {
        try {
            $zonaGeografica = ZonaGeografica::findOrFail($id);
            
            $this->authorize('update', $zonaGeografica);
            
            $zonaGeografica->update($request->validated());
            $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);
            
            $this->flushCache();
            
            return (new ZonaGeograficaResource($zonaGeografica))
                ->additional(['message' => 'Zona geográfica actualizada exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Zona geográfica no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar zona geográfica',
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
            $zonaGeografica = ZonaGeografica::findOrFail($id);
            
            $this->authorize('delete', $zonaGeografica);
            
            // Soft delete - marcar como inactiva
            $zonaGeografica->update([
                'activa' => false,
                'eliminado' => true
            ]);
            
            $this->flushCache();
            
            return response()->json([
                'message' => 'Zona geográfica eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Zona geográfica no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar zona geográfica',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
