<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoCuentaRequest;
use App\Http\Requests\UpdateTipoCuentaRequest;
use App\Http\Resources\TipoCuentaResource;
use App\Models\TipoCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Tipos de Cuentas Contables
 *
 * Gestiona los tipos de cuentas contables (Activo, Pasivo, Patrimonio, Ingresos, Costos, Gastos).
 * Tabla global sin empresa_id, con naturaleza Deudora/Acreedora.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TipoCuentaController extends Controller
{
    /**
     * Listar todos los tipos de cuentas contables
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TipoCuenta::where('eliminado', 0)->with('cuentasContables');

        // Filtro por naturaleza
        if ($request->filled('naturaleza')) {
            $query->where('naturaleza', $request->naturaleza);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda por nombre
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'nombre'), $request->get('sort_order', 'asc'));

        $tipos = $query->paginate($request->get('per_page', 15));

        return TipoCuentaResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de cuenta contable
     *
     * @param StoreTipoCuentaRequest $request
     * @return JsonResponse
     */
    public function store(StoreTipoCuentaRequest $request): JsonResponse
    {
        $tipo = TipoCuenta::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta creado exitosamente',
            'data' => new TipoCuentaResource($tipo)
        ], 201);
    }

    /**
     * Mostrar un tipo de cuenta específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->with(['cuentasContables' => function ($query) {
                $query->where('eliminado', 0);
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new TipoCuentaResource($tipo)
        ]);
    }

    /**
     * Actualizar un tipo de cuenta existente
     *
     * @param UpdateTipoCuentaRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTipoCuentaRequest $request, int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tipo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta actualizado exitosamente',
            'data' => new TipoCuentaResource($tipo)
        ]);
    }

    /**
     * Eliminar (soft delete) un tipo de cuenta
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no tenga cuentas contables asignadas
        $cuentasCount = $tipo->cuentasContables()->where('eliminado', 0)->count();
        if ($cuentasCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el tipo de cuenta. Tiene {$cuentasCount} cuenta(s) contable(s) asignada(s)"
            ], 422);
        }

        $tipo->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta eliminado exitosamente'
        ]);
    }

    /**
     * Obtener tipos de cuenta por naturaleza (Deudora/Acreedora)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function porNaturaleza(Request $request): JsonResponse
    {
        $request->validate([
            'naturaleza' => 'required|in:Deudora,Acreedora'
        ]);

        $tipos = TipoCuenta::where('eliminado', 0)
            ->where('activo', 1)
            ->where('naturaleza', $request->naturaleza)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCuentaResource::collection($tipos)
        ]);
    }

    /**
     * Obtener tipos activos para uso en formularios
     *
     * @return JsonResponse
     */
    public function activos(): JsonResponse
    {
        $tipos = TipoCuenta::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCuentaResource::collection($tipos)
        ]);
    }
}
