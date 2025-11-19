<?php

namespace App\Http\Controllers;

use App\Models\ConsecutivoFE;
use App\Http\Requests\StoreConsecutivoFERequest;
use App\Http\Requests\UpdateConsecutivoFERequest;
use App\Http\Resources\ConsecutivoFEResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConsecutivoFEController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ConsecutivoFE::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        // Filtros
        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('tipo_documento_dgt')) {
            $query->where('tipo_documento_dgt', $request->tipo_documento_dgt);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ordenamiento
        $query->orderBy('tipo_documento_dgt')
            ->orderBy('prefijo');

        $consecutivos = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ConsecutivoFEResource::collection($consecutivos),
            'meta' => [
                'current_page' => $consecutivos->currentPage(),
                'last_page' => $consecutivos->lastPage(),
                'per_page' => $consecutivos->perPage(),
                'total' => $consecutivos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConsecutivoFERequest $request): JsonResponse
    {
        $consecutivo = ConsecutivoFE::create([
            'empresa_id' => auth()->user()->empresa_id,
            'sucursal_id' => $request->sucursal_id,
            'tipo_documento_dgt' => $request->tipo_documento_dgt,
            'prefijo' => $request->prefijo,
            'consecutivo_actual' => $request->consecutivo_actual ?? 1,
            'estado' => $request->estado ?? 'Activo',
            'fecha_autorizacion' => $request->fecha_autorizacion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE creado exitosamente',
            'data' => new ConsecutivoFEResource($consecutivo)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ConsecutivoFEResource($consecutivoFe)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConsecutivoFERequest $request, ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        // No permitir modificar el consecutivo_actual manualmente por seguridad
        $data = $request->except('consecutivo_actual');
        
        $consecutivoFe->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE actualizado exitosamente',
            'data' => new ConsecutivoFEResource($consecutivoFe)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        // Soft delete
        $consecutivoFe->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE eliminado exitosamente'
        ]);
    }

    /**
     * Obtiene el siguiente consecutivo disponible de forma thread-safe.
     */
    public function obtenerSiguiente(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_documento_dgt' => 'required|string|size:2',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'prefijo' => 'nullable|string|max:10',
        ]);

        DB::beginTransaction();
        try {
            $query = ConsecutivoFE::where('empresa_id', auth()->user()->empresa_id)
                ->where('tipo_documento_dgt', $request->tipo_documento_dgt)
                ->where('estado', 'Activo')
                ->where('eliminado', 0)
                ->lockForUpdate(); // Lock pesimista para thread-safety

            if ($request->filled('sucursal_id')) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            if ($request->filled('prefijo')) {
                $query->where('prefijo', $request->prefijo);
            }

            $consecutivo = $query->first();

            if (!$consecutivo) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No hay consecutivo activo disponible para este tipo de documento'
                ], 404);
            }

            $siguienteNumero = $consecutivo->consecutivo_actual;
            
            // Incrementar para el próximo uso
            $consecutivo->increment('consecutivo_actual');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'consecutivo_id' => $consecutivo->id,
                    'tipo_documento_dgt' => $consecutivo->tipo_documento_dgt,
                    'prefijo' => $consecutivo->prefijo,
                    'numero' => $siguienteNumero,
                    'consecutivo_completo' => $consecutivo->prefijo . str_pad($siguienteNumero, 10, '0', STR_PAD_LEFT),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el consecutivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resetear el consecutivo a un número específico (solo admin).
     */
    public function resetear(Request $request, ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'nuevo_consecutivo' => 'required|integer|min:1',
        ]);

        $consecutivoFe->update([
            'consecutivo_actual' => $request->nuevo_consecutivo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo reseteado exitosamente',
            'data' => new ConsecutivoFEResource($consecutivoFe)
        ]);
    }

    /**
     * Listar consecutivos por tipo de documento.
     */
    public function porTipoDocumento(Request $request, string $tipoDocumentoDgt): JsonResponse
    {
        $consecutivos = ConsecutivoFE::where('empresa_id', auth()->user()->empresa_id)
            ->where('tipo_documento_dgt', $tipoDocumentoDgt)
            ->where('eliminado', 0)
            ->orderBy('prefijo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ConsecutivoFEResource::collection($consecutivos)
        ]);
    }

    /**
     * Marcar un consecutivo como agotado.
     */
    public function marcarAgotado(ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivoFe->update(['estado' => 'Agotado']);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo marcado como agotado',
            'data' => new ConsecutivoFEResource($consecutivoFe)
        ]);
    }

    /**
     * Activar un consecutivo inactivo.
     */
    public function activar(ConsecutivoFE $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivoFe->update([
            'estado' => 'Activo',
            'activo' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo activado exitosamente',
            'data' => new ConsecutivoFEResource($consecutivoFe)
        ]);
    }

    /**
     * Resumen de consecutivos por estado.
     */
    public function resumenPorEstado(): JsonResponse
    {
        $resumen = ConsecutivoFE::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
