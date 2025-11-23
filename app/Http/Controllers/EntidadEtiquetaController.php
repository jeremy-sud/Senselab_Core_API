<?php

namespace App\Http\Controllers;

use App\Models\EntidadEtiqueta;
use App\Http\Requests\StoreEntidadEtiquetaRequest;
use App\Http\Requests\UpdateEntidadEtiquetaRequest;
use App\Http\Resources\EntidadEtiquetaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EntidadEtiquetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Multi-tenancy: Filtrar por empresa del usuario autenticado
        $empresaId = $request->user()->empresa_id;
        
        $query = EntidadEtiqueta::where('eliminado', 0)
            ->with('etiqueta')
            ->whereHas('etiqueta', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // Filtros
        if ($request->filled('etiqueta_id')) {
            $query->where('etiqueta_id', $request->etiqueta_id);
        }

        if ($request->filled('entidad_tipo')) {
            $query->where('entidad_tipo', $request->entidad_tipo);
        }

        if ($request->filled('entidad_id')) {
            $query->where('entidad_id', $request->entidad_id);
        }

        $entidadEtiquetas = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => EntidadEtiquetaResource::collection($entidadEtiquetas),
            'meta' => [
                'current_page' => $entidadEtiquetas->currentPage(),
                'last_page' => $entidadEtiquetas->lastPage(),
                'per_page' => $entidadEtiquetas->perPage(),
                'total' => $entidadEtiquetas->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEntidadEtiquetaRequest $request): JsonResponse
    {
        // Verificar que no exista ya la relación
        $existente = EntidadEtiqueta::where('etiqueta_id', $request->etiqueta_id)
            ->where('entidad_tipo', $request->entidad_tipo)
            ->where('entidad_id', $request->entidad_id)
            ->where('eliminado', 0)
            ->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Esta etiqueta ya está asignada a esta entidad'
            ], 422);
        }

        $entidadEtiqueta = EntidadEtiqueta::create([
            'etiqueta_id' => $request->etiqueta_id,
            'entidad_tipo' => $request->entidad_tipo,
            'entidad_id' => $request->entidad_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta asignada exitosamente',
            'data' => new EntidadEtiquetaResource($entidadEtiqueta)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EntidadEtiqueta $entidadEtiqueta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new EntidadEtiquetaResource($entidadEtiqueta)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEntidadEtiquetaRequest $request, EntidadEtiqueta $entidadEtiqueta): JsonResponse
    {
        $entidadEtiqueta->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relación actualizada exitosamente',
            'data' => new EntidadEtiquetaResource($entidadEtiqueta)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntidadEtiqueta $entidadEtiqueta): JsonResponse
    {
        // Soft delete
        $entidadEtiqueta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta removida de la entidad exitosamente'
        ]);
    }

    /**
     * Asignar múltiples etiquetas a una entidad.
     */
    public function asignarMultiples(Request $request): JsonResponse
    {
        $request->validate([
            'etiqueta_ids' => 'required|array|min:1',
            'etiqueta_ids.*' => 'required|integer|exists:etiquetas,id',
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
        ]);

        $asignadas = [];
        $yaExistentes = [];

        foreach ($request->etiqueta_ids as $etiquetaId) {
            $existente = EntidadEtiqueta::where('etiqueta_id', $etiquetaId)
                ->where('entidad_tipo', $request->entidad_tipo)
                ->where('entidad_id', $request->entidad_id)
                ->where('eliminado', 0)
                ->first();

            if ($existente) {
                $yaExistentes[] = $etiquetaId;
            } else {
                $entidadEtiqueta = EntidadEtiqueta::create([
                    'etiqueta_id' => $etiquetaId,
                    'entidad_tipo' => $request->entidad_tipo,
                    'entidad_id' => $request->entidad_id,
                ]);
                $asignadas[] = new EntidadEtiquetaResource($entidadEtiqueta);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($asignadas) . ' etiqueta(s) asignada(s) exitosamente',
            'data' => [
                'asignadas' => $asignadas,
                'ya_existentes' => $yaExistentes,
            ]
        ], 201);
    }

    /**
     * Remover múltiples etiquetas de una entidad.
     */
    public function removerMultiples(Request $request): JsonResponse
    {
        $request->validate([
            'etiqueta_ids' => 'required|array|min:1',
            'etiqueta_ids.*' => 'required|integer',
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
        ]);

        $removidas = EntidadEtiqueta::whereIn('etiqueta_id', $request->etiqueta_ids)
            ->where('entidad_tipo', $request->entidad_tipo)
            ->where('entidad_id', $request->entidad_id)
            ->where('eliminado', 0)
            ->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => "{$removidas} etiqueta(s) removida(s) exitosamente"
        ]);
    }

    /**
     * Obtener todas las etiquetas de una entidad.
     */
    public function porEntidad(Request $request): JsonResponse
    {
        $request->validate([
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
        ]);

        $etiquetas = EntidadEtiqueta::where('entidad_tipo', $request->entidad_tipo)
            ->where('entidad_id', $request->entidad_id)
            ->where('eliminado', 0)
            ->with('etiqueta')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EntidadEtiquetaResource::collection($etiquetas)
        ]);
    }

    /**
     * Obtener todas las entidades de una etiqueta.
     */
    public function porEtiqueta(Request $request, int $etiquetaId): JsonResponse
    {
        $request->validate([
            'entidad_tipo' => 'nullable|string|max:50',
        ]);

        $query = EntidadEtiqueta::where('etiqueta_id', $etiquetaId)
            ->where('eliminado', 0);

        if ($request->filled('entidad_tipo')) {
            $query->where('entidad_tipo', $request->entidad_tipo);
        }

        $entidades = $query->get();

        return response()->json([
            'success' => true,
            'data' => EntidadEtiquetaResource::collection($entidades)
        ]);
    }

    /**
     * Sincronizar etiquetas de una entidad (reemplaza todas las existentes).
     */
    public function sincronizar(Request $request): JsonResponse
    {
        $request->validate([
            'etiqueta_ids' => 'required|array',
            'etiqueta_ids.*' => 'required|integer|exists:etiquetas,id',
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
        ]);

        // Remover todas las etiquetas actuales
        EntidadEtiqueta::where('entidad_tipo', $request->entidad_tipo)
            ->where('entidad_id', $request->entidad_id)
            ->where('eliminado', 0)
            ->update(['eliminado' => 1, 'activo' => 0]);

        // Asignar las nuevas etiquetas
        $nuevasEtiquetas = [];
        foreach ($request->etiqueta_ids as $etiquetaId) {
            $entidadEtiqueta = EntidadEtiqueta::create([
                'etiqueta_id' => $etiquetaId,
                'entidad_tipo' => $request->entidad_tipo,
                'entidad_id' => $request->entidad_id,
            ]);
            $nuevasEtiquetas[] = new EntidadEtiquetaResource($entidadEtiqueta);
        }

        return response()->json([
            'success' => true,
            'message' => 'Etiquetas sincronizadas exitosamente',
            'data' => $nuevasEtiquetas
        ]);
    }
}
