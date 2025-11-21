<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;

class ClienteController extends Controller
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
            $tipoIdentificacion = $request->input('tipo_identificacion');
            
            $query = Cliente::with('empresa');
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%")
                      ->orWhere('nombre_comercial', 'like', "%{$search}%")
                      ->orWhere('numero_identificacion', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($tipoIdentificacion) {
                $query->porTipoIdentificacion($tipoIdentificacion);
            }
            
            if ($request->boolean('activos')) {
                $query->activos();
            }
            
            $clientes = $query->orderBy('nombre', 'asc')
                              ->paginate($perPage);
            
            return ClienteResource::collection($clientes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreClienteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreClienteRequest $request)
    {
        try {
            $cliente = Cliente::create($request->validated());
            $cliente->load('empresa');
            
            return (new ClienteResource($cliente))
                ->additional(['message' => 'Cliente creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear cliente',
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
            $cliente = Cliente::with([
                'empresa',
                'ventas' => function($query) {
                    $query->latest()->limit(10);
                },
                'cuentasPorCobrar' => function($query) {
                    $query->where('estado', 'pendiente');
                }
            ])->findOrFail($id);
            
            return new ClienteResource($cliente);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateClienteRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateClienteRequest $request, int $id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            
            $cliente->update($request->validated());
            $cliente->load('empresa');
            
            return (new ClienteResource($cliente))
                ->additional(['message' => 'Cliente actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar cliente',
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
            $cliente = Cliente::findOrFail($id);
            
            // Soft delete - marcar como inactivo
            $cliente->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
