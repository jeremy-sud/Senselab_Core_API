<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoComprobanteFe;
use App\Http\Requests\StoreTipoComprobanteFeRequest;
use App\Http\Requests\UpdateTipoComprobanteFeRequest;
use App\Http\Resources\TipoComprobanteFeResource;
use Illuminate\Http\Request;

/**
 * Controller para gestionar tipos de comprobantes de facturación electrónica
 * Catálogo según DGT Costa Rica (01-Factura, 02-Nota Débito, 03-Nota Crédito, 04-Tiquete)
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoComprobanteFeController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoComprobanteFe::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            
            $query = TipoComprobanteFe::where('eliminado', false);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo_dgt', 'like', "%{$search}%")
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
            
            // Filtros específicos de FE
            if ($request->boolean('requiere_referencia')) {
                $query->queRequierenReferencia();
            }
            
            if ($request->boolean('permite_exportacion')) {
                $query->permiteExportacion();
            }
            
            // Filtro por código DGT específico
            if ($request->has('codigo_dgt')) {
                $query->porCodigo($request->input('codigo_dgt'));
            }
            
            $tiposComprobante = $query->orderBy('codigo_dgt', 'asc')
                                      ->paginate($perPage);
            
            return TipoComprobanteFeResource::collection($tiposComprobante);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener tipos de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreTipoComprobanteFeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTipoComprobanteFeRequest $request)
    {
        $this->authorize('create', TipoComprobanteFe::class);
        
        try {
            $tipoComprobante = TipoComprobanteFe::create($request->validated());
            
            return (new TipoComprobanteFeResource($tipoComprobante))
                ->additional(['message' => 'Tipo de comprobante FE creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear tipo de comprobante FE',
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
            $tipoComprobante = TipoComprobanteFe::findOrFail($id);
            
            $this->authorize('view', $tipoComprobante);
            
            return new TipoComprobanteFeResource($tipoComprobante);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de comprobante FE no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener tipo de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateTipoComprobanteFeRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTipoComprobanteFeRequest $request, int $id)
    {
        try {
            $tipoComprobante = TipoComprobanteFe::findOrFail($id);
            
            $this->authorize('update', $tipoComprobante);
            
            $tipoComprobante->update($request->validated());
            
            return (new TipoComprobanteFeResource($tipoComprobante))
                ->additional(['message' => 'Tipo de comprobante FE actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de comprobante FE no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar tipo de comprobante FE',
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
            $tipoComprobante = TipoComprobanteFe::findOrFail($id);
            
            $this->authorize('delete', $tipoComprobante);
            
            // Soft delete - marcar como inactivo
            $tipoComprobante->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Tipo de comprobante FE eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de comprobante FE no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar tipo de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
