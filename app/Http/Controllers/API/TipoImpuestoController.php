<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoImpuestoRequest;
use App\Http\Requests\UpdateTipoImpuestoRequest;
use App\Http\Resources\TipoImpuestoResource;
use App\Models\TipoImpuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Tipos de Impuesto
 *
 * Gestiona el catálogo de tipos de impuestos utilizados en la facturación.
 * Tabla global sin empresa_id, incluye códigos de Hacienda Costa Rica.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TipoImpuestoController extends Controller
{
    /**
     * Listar todos los tipos de impuesto
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TipoImpuesto::where('eliminado', 0);

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda por nombre o código
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo_hacienda', 'like', "%{$buscar}%");
            });
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'nombre'), $request->get('sort_order', 'asc'));

        $tipos = $query->paginate($request->get('per_page', 15));

        return TipoImpuestoResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de impuesto
     *
     * @param StoreTipoImpuestoRequest $request
     * @return JsonResponse
     */
    public function store(StoreTipoImpuestoRequest $request): JsonResponse
    {
        $tipo = TipoImpuesto::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto creado exitosamente',
            'data' => new TipoImpuestoResource($tipo)
        ], 201);
    }

    /**
     * Mostrar un tipo de impuesto específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new TipoImpuestoResource($tipo)
        ]);
    }

    /**
     * Actualizar un tipo de impuesto existente
     *
     * @param UpdateTipoImpuestoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTipoImpuestoRequest $request, int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tipo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto actualizado exitosamente',
            'data' => new TipoImpuestoResource($tipo)
        ]);
    }

    /**
     * Eliminar (soft delete) un tipo de impuesto
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $tipo = TipoImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no sea el IVA (código 01) que no debe borrarse
        if ($tipo->codigo_hacienda === '01') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de impuesto IVA (01). Es requerido por Hacienda.'
            ], 422);
        }

        $tipo->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de impuesto eliminado exitosamente'
        ]);
    }

    /**
     * Obtener tipos de impuesto activos para uso en facturación
     *
     * @return JsonResponse
     */
    public function activos(): JsonResponse
    {
        $tipos = TipoImpuesto::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoImpuestoResource::collection($tipos)
        ]);
    }
}
