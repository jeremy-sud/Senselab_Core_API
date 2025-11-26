<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoCliente;
use App\Http\Requests\StoreTipoClienteRequest;
use App\Http\Requests\UpdateTipoClienteRequest;
use App\Http\Resources\TipoClienteResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller para gestionar tipos de clientes
 * Catálogo de tipos (Mayorista, Minorista, Gobierno, etc.)
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoClienteController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['tipos-cliente', 'catalogos'];
    protected int $cacheTTL = 3600; // 1 hora - catálogo estable

    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TipoCliente::class);
        
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        
        $cacheKey = $this->getCacheKey('index', [
            'per_page' => $perPage,
            'search' => $search,
            'activo' => $request->input('activo'),
            'activos' => $request->input('activos'),
            'con_descuento' => $request->boolean('con_descuento'),
            'con_credito' => $request->boolean('con_credito')
        ]);

        $tiposCliente = $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $search, $perPage) {
            $query = TipoCliente::where('eliminado', false);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }
            
            // Filtro por estado activo
            if ($request->has('activo') || $request->has('activos')) {
                $esActivo = $request->boolean('activo') || $request->boolean('activos');
                if ($esActivo) {
                    $query->activos();
                } else {
                    $query->where('activo', false);
                }
            }
            
            // Filtros adicionales
            if ($request->boolean('con_descuento')) {
                $query->conDescuento();
            }
            
            if ($request->boolean('con_credito')) {
                $query->conCredito();
            }
            
            return $query->orderBy('nombre', 'asc')
                            ->paginate($perPage);
        });
        
        return TipoClienteResource::collection($tiposCliente);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreTipoClienteRequest $request
     * @return JsonResponse
     */
    public function store(StoreTipoClienteRequest $request): JsonResponse
    {
        $this->authorize('create', TipoCliente::class);
        
        try {
            $tipoCliente = TipoCliente::create($request->validated());
            
            $this->flushCache();
            
            return (new TipoClienteResource($tipoCliente))
                ->additional(['message' => 'Tipo de cliente creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear tipo de cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return TipoClienteResource
     */
    public function show(int $id): TipoClienteResource
    {
        $tipoCliente = TipoCliente::findOrFail($id);
        
        $this->authorize('view', $tipoCliente);
        
        return new TipoClienteResource($tipoCliente);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateTipoClienteRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTipoClienteRequest $request, int $id): JsonResponse
    {
        try {
            $tipoCliente = TipoCliente::findOrFail($id);
            
            $this->authorize('update', $tipoCliente);
            
            $validated = $request->validated();
            Log::info('Update TipoCliente', ['id' => $id, 'validated' => $validated]);
            
            $tipoCliente->update($validated);
            $tipoCliente->refresh(); // Refrescar modelo después del update
            
            $this->flushCache();
            
            return response()->json([
                'data' => new TipoClienteResource($tipoCliente),
                'message' => 'Tipo de cliente actualizado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar tipo de cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $tipoCliente = TipoCliente::findOrFail($id);
            
            $this->authorize('delete', $tipoCliente);
            
            // Soft delete - marcar como inactivo
            $tipoCliente->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            $this->flushCache();
            
            return response()->json([
                'message' => 'Tipo de cliente eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar tipo de cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
