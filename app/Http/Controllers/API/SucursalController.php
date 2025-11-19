<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSucursalRequest;
use App\Http\Requests\UpdateSucursalRequest;

class SucursalController extends Controller
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
            
            $query = Sucursal::with('empresa');
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $sucursales = $query->orderBy('nombre', 'asc')
                                ->paginate($perPage);
            
            return response()->json($sucursales);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener sucursales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreSucursalRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSucursalRequest $request)
    {
        try {
            // Si es principal, desmarcar otras sucursales principales
            if ($request->boolean('es_principal')) {
                Sucursal::where('empresa_id', $request->empresa_id)
                        ->update(['es_principal' => false]);
            }
            
            $sucursal = Sucursal::create($request->validated());
            $sucursal->load('empresa');
            
            return response()->json([
                'message' => 'Sucursal creada exitosamente',
                'data' => $sucursal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear sucursal',
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
            $sucursal = Sucursal::with([
                'empresa',
                'almacenes',
                'cajas'
            ])->findOrFail($id);
            
            return response()->json($sucursal);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateSucursalRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSucursalRequest $request, int $id)
    {
        try {
            $sucursal = Sucursal::findOrFail($id);
            
            // Si es principal, desmarcar otras sucursales principales
            if ($request->has('es_principal') && $request->boolean('es_principal')) {
                Sucursal::where('empresa_id', $sucursal->empresa_id)
                        ->where('id', '!=', $id)
                        ->update(['es_principal' => false]);
            }
            
            $sucursal->update($request->validated());
            $sucursal->load('empresa');
            
            return response()->json([
                'message' => 'Sucursal actualizada exitosamente',
                'data' => $sucursal
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar sucursal',
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
            $sucursal = Sucursal::findOrFail($id);
            
            // No permitir eliminar sucursal principal
            if ($sucursal->es_principal) {
                return response()->json([
                    'message' => 'No se puede eliminar la sucursal principal'
                ], 422);
            }
            
            // Soft delete
            $sucursal->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Sucursal eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
