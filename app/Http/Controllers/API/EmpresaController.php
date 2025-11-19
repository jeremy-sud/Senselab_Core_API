<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEmpresaRequest;
use App\Http\Requests\UpdateEmpresaRequest;

class EmpresaController extends Controller
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
            
            $query = Empresa::with(['regimenTributario']);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('razon_social', 'like', "%{$search}%")
                      ->orWhere('nit_ruc', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $empresas = $query->orderBy('creado_en', 'desc')
                              ->paginate($perPage);
            
            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener empresas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreEmpresaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEmpresaRequest $request)
    {
        try {
            $empresa = Empresa::create($request->validated());
            $empresa->load('regimenTributario');
            
            return response()->json([
                'message' => 'Empresa creada exitosamente',
                'data' => $empresa
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear empresa',
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
            $empresa = Empresa::with([
                'regimenTributario',
                'sucursales',
                'usuarios',
                'configuraciones'
            ])->findOrFail($id);
            
            return response()->json($empresa);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateEmpresaRequest $request
     * @param Empresa $empresa
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEmpresaRequest $request, Empresa $empresa)
    {
        try {
            $empresa->update($request->validated());
            $empresa->load('regimenTributario');
            
            return response()->json([
                'message' => 'Empresa actualizada exitosamente',
                'data' => $empresa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar empresa',
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
            $empresa = Empresa::findOrFail($id);
            
            // Soft delete - marcar como inactivo
            $empresa->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Empresa eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
