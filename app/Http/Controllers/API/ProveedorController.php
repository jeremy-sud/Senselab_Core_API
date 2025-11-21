<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Http\Resources\ProveedorResource;

class ProveedorController extends Controller
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
            $search = $request->input('search');
            $empresaId = $request->input('empresa_id');
            
            $query = Proveedor::with('empresa');
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('nombre_comercial', 'like', "%{$search}%")
                      ->orWhere('numero_identificacion', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $proveedores = $query->orderBy('nombre', 'asc')
                                 ->paginate($perPage);
            
            return ProveedorResource::collection($proveedores);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreProveedorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProveedorRequest $request)
    {
        try {
            $proveedor = Proveedor::create($request->validated());
            $proveedor->load('empresa');
            
            return (new ProveedorResource($proveedor))
                ->additional(['message' => 'Proveedor creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear proveedor',
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
            $proveedor = Proveedor::with([
                'empresa',
                'ordenesCompra' => function($query) {
                    $query->latest()->limit(10);
                },
                'cuentasPorPagar' => function($query) {
                    $query->where('estado', 'pendiente');
                }
            ])->findOrFail($id);
            
            return new ProveedorResource($proveedor);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Proveedor no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateProveedorRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProveedorRequest $request, int $id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            
            $proveedor->update($request->validated());
            $proveedor->load('empresa');
            
            return (new ProveedorResource($proveedor))
                ->additional(['message' => 'Proveedor actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Proveedor no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar proveedor',
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
            $proveedor = Proveedor::findOrFail($id);
            
            // Soft delete
            $proveedor->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Proveedor eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Proveedor no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
