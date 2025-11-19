<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCabyRequest;
use App\Http\Requests\UpdateCabyRequest;
use App\Http\Resources\CabyResource;
use App\Models\Caby;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para CAByS
 *
 * Gestiona el Catálogo de Bienes y Servicios (CAByS) de Costa Rica.
 * Tabla global sin empresa_id, utilizada para clasificación fiscal de productos.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CabyController extends Controller
{
    /**
     * Listar todos los códigos CAByS
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Caby::where('eliminado', 0);

        // Búsqueda por código o descripción
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        // Filtro por código específico
        if ($request->filled('codigo')) {
            $query->where('codigo', $request->codigo);
        }

        // Filtro por tasa IVA
        if ($request->filled('impuesto_iva')) {
            $query->where('impuesto_iva_predeterminado', $request->impuesto_iva);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'codigo'), $request->get('sort_order', 'asc'));

        $cabys = $query->paginate($request->get('per_page', 15));

        return CabyResource::collection($cabys);
    }

    /**
     * Crear un nuevo código CAByS
     *
     * @param StoreCabyRequest $request
     * @return JsonResponse
     */
    public function store(StoreCabyRequest $request): JsonResponse
    {
        $caby = Caby::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Código CAByS creado exitosamente',
            'data' => new CabyResource($caby)
        ], 201);
    }

    /**
     * Mostrar un código CAByS específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $caby = Caby::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CabyResource($caby)
        ]);
    }

    /**
     * Actualizar un código CAByS existente
     *
     * @param UpdateCabyRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCabyRequest $request, int $id): JsonResponse
    {
        $caby = Caby::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $caby->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Código CAByS actualizado exitosamente',
            'data' => new CabyResource($caby)
        ]);
    }

    /**
     * Eliminar (soft delete) un código CAByS
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $caby = Caby::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no esté asignado a productos
        $productosCount = $caby->productos()->count();
        if ($productosCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el código CAByS. Está asignado a {$productosCount} producto(s)"
            ], 422);
        }

        $caby->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Código CAByS eliminado exitosamente'
        ]);
    }

    /**
     * Buscar códigos CAByS por término de búsqueda
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:3'
        ]);

        $termino = $request->termino;

        $resultados = Caby::where('eliminado', 0)
            ->where('activo', 1)
            ->where(function ($q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                  ->orWhere('descripcion', 'like', "%{$termino}%");
            })
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => CabyResource::collection($resultados),
            'total' => $resultados->count()
        ]);
    }
}
