<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAlmacenRequest;
use App\Http\Requests\UpdateAlmacenRequest;
use App\Http\Resources\AlmacenResource;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            $sucursalId = $request->input('sucursal_id');
            
            $query = Almacen::with(['empresa', 'sucursal']);
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($sucursalId) {
                $query->where('sucursal_id', $sucursalId);
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $almacenes = $query->orderBy('nombre', 'asc')
                               ->paginate($perPage);
            
            return AlmacenResource::collection($almacenes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener almacenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreAlmacenRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAlmacenRequest $request)
    {
        try {
            // Si es principal, desmarcar otros almacenes principales de la sucursal
            if ($request->boolean('es_principal')) {
                Almacen::where('sucursal_id', $request->sucursal_id)
                       ->update(['es_principal' => false]);
            }
            
            $almacen = Almacen::create($request->validated());
            $almacen->load(['empresa', 'sucursal']);
            
            return (new AlmacenResource($almacen))
                ->additional(['message' => 'Almacén creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear almacén',
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
            $almacen = Almacen::with([
                'empresa',
                'sucursal'
            ])->findOrFail($id);
            
            return new AlmacenResource($almacen);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateAlmacenRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAlmacenRequest $request, int $id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            
            // Si es principal, desmarcar otros almacenes principales de la sucursal
            if ($request->has('es_principal') && $request->boolean('es_principal')) {
                Almacen::where('sucursal_id', $almacen->sucursal_id)
                       ->where('id', '!=', $id)
                       ->update(['es_principal' => false]);
            }
            
            $almacen->update($request->validated());
            $almacen->load(['empresa', 'sucursal']);
            
            return (new AlmacenResource($almacen))
                ->additional(['message' => 'Almacén actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar almacén',
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
            $almacen = Almacen::findOrFail($id);
            
            // No permitir eliminar almacén principal
            if ($almacen->es_principal) {
                return response()->json([
                    'message' => 'No se puede eliminar el almacén principal'
                ], 422);
            }
            
            // Soft delete
            $almacen->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Almacén eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
