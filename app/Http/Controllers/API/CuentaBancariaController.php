<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use App\Http\Requests\StoreCuentaBancariaRequest;
use App\Http\Requests\UpdateCuentaBancariaRequest;
use App\Http\Resources\CuentaBancariaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller para gestionar cuentas bancarias
 * Gestión de cuentas bancarias de la empresa
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CuentaBancariaController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CuentaBancaria::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = Auth::user()->empresa_id;
            
            $query = CuentaBancaria::with(['empresa', 'cuentaContable'])
                                   ->where('empresa_id', $empresaId)
                                   ->where('eliminado', false);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('banco', 'like', "%{$search}%")
                      ->orWhere('numero_cuenta', 'like', "%{$search}%")
                      ->orWhere('iban', 'like', "%{$search}%");
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
            
            // Filtro por moneda
            if ($request->has('moneda')) {
                $query->porMoneda($request->input('moneda'));
            }
            
            // Filtro por cuenta principal
            if ($request->boolean('principales')) {
                $query->principales();
            }
            
            // Filtro por tipo de cuenta
            if ($request->has('tipo_cuenta')) {
                $query->where('tipo_cuenta', $request->input('tipo_cuenta'));
            }
            
            // Filtro por banco
            if ($request->has('banco')) {
                $query->where('banco', 'like', '%' . $request->input('banco') . '%');
            }
            
            $cuentasBancarias = $query->orderBy('banco', 'asc')
                                      ->orderBy('numero_cuenta', 'asc')
                                      ->paginate($perPage);
            
            return CuentaBancariaResource::collection($cuentasBancarias);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener cuentas bancarias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreCuentaBancariaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCuentaBancariaRequest $request)
    {
        $this->authorize('create', CuentaBancaria::class);
        
        try {
            $data = $request->validated();
            
            // Asignar empresa_id del usuario autenticado
            $data['empresa_id'] = Auth::user()->empresa_id;
            
            // Si es cuenta principal, desactivar otras cuentas principales de la misma moneda
            if (isset($data['es_principal']) && $data['es_principal']) {
                CuentaBancaria::where('empresa_id', $data['empresa_id'])
                              ->where('moneda', $data['moneda'])
                              ->where('es_principal', true)
                              ->update(['es_principal' => false]);
            }
            
            $cuentaBancaria = CuentaBancaria::create($data);
            $cuentaBancaria->load(['empresa', 'cuentaContable']);
            
            return (new CuentaBancariaResource($cuentaBancaria))
                ->additional(['message' => 'Cuenta bancaria creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear cuenta bancaria',
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
            $cuentaBancaria = CuentaBancaria::with([
                'empresa', 
                'cuentaContable'
            ])->findOrFail($id);
            
            $this->authorize('view', $cuentaBancaria);
            
            return new CuentaBancariaResource($cuentaBancaria);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cuenta bancaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener cuenta bancaria',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateCuentaBancariaRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCuentaBancariaRequest $request, int $id)
    {
        try {
            $cuentaBancaria = CuentaBancaria::findOrFail($id);
            
            $this->authorize('update', $cuentaBancaria);
            
            $data = $request->validated();
            
            // Si se marca como principal, desactivar otras cuentas principales de la misma moneda
            if (isset($data['es_principal']) && $data['es_principal']) {
                $moneda = $data['moneda'] ?? $cuentaBancaria->moneda;
                
                CuentaBancaria::where('empresa_id', $cuentaBancaria->empresa_id)
                              ->where('moneda', $moneda)
                              ->where('id', '!=', $id)
                              ->where('es_principal', true)
                              ->update(['es_principal' => false]);
            }
            
            $cuentaBancaria->update($data);
            $cuentaBancaria->load(['empresa', 'cuentaContable']);
            
            return (new CuentaBancariaResource($cuentaBancaria))
                ->additional(['message' => 'Cuenta bancaria actualizada exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cuenta bancaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar cuenta bancaria',
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
            $cuentaBancaria = CuentaBancaria::findOrFail($id);
            
            $this->authorize('delete', $cuentaBancaria);
            
            // Soft delete - marcar como inactiva
            $cuentaBancaria->update([
                'activa' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Cuenta bancaria eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cuenta bancaria no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar cuenta bancaria',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
