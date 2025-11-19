<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaContableRequest;
use App\Http\Requests\UpdateCuentaContableRequest;
use App\Http\Resources\CuentaContableResource;
use App\Models\CuentaContable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Cuentas Contables
 *
 * Gestiona el plan de cuentas contables (PUC) de la empresa.
 * Estructura jerárquica para registrar asientos contables.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaContableController extends Controller
{
    /**
     * Listar todas las cuentas contables de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        // Filtro por tipo de cuenta
        if ($request->filled('tipo_cuenta_id')) {
            $query->where('tipo_cuenta_id', $request->tipo_cuenta_id);
        }

        // Filtro por cuenta padre
        if ($request->filled('cuenta_padre_id')) {
            $query->where('cuenta_padre_id', $request->cuenta_padre_id);
        }

        // Filtro solo cuentas principales (sin padre)
        if ($request->filled('principales') && $request->principales == 1) {
            $query->whereNull('cuenta_padre_id');
        }

        // Filtro por código
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', "%{$request->codigo}%");
        }

        // Filtro que permiten movimientos
        if ($request->filled('permite_movimientos')) {
            $query->where('permite_movimientos', $request->permite_movimientos);
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'codigo'), $request->get('sort_order', 'asc'));

        $cuentas = $query->paginate($request->get('per_page', 15));

        return CuentaContableResource::collection($cuentas);
    }

    /**
     * Crear una nueva cuenta contable
     *
     * @param StoreCuentaContableRequest $request
     * @return JsonResponse
     */
    public function store(StoreCuentaContableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = $request->user()->empresa_id;

        $cuenta = CuentaContable::create($validated);
        $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable creada exitosamente',
            'data' => new CuentaContableResource($cuenta)
        ], 201);
    }

    /**
     * Mostrar una cuenta contable específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas', 'asientos'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CuentaContableResource($cuenta)
        ]);
    }

    /**
     * Actualizar una cuenta contable existente
     *
     * @param UpdateCuentaContableRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCuentaContableRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $cuenta->update($request->validated());
        $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable actualizada exitosamente',
            'data' => new CuentaContableResource($cuenta)
        ]);
    }

    /**
     * Eliminar (soft delete) una cuenta contable
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no tenga subcuentas
        if ($cuenta->subcuentas()->where('eliminado', 0)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta contable que tiene subcuentas asociadas'
            ], 422);
        }

        // Validar que no tenga asientos contables
        if ($cuenta->asientos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta contable que tiene asientos contables registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable eliminada exitosamente'
        ]);
    }

    /**
     * Obtener el árbol jerárquico de cuentas contables
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function arbol(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        // Obtener solo las cuentas principales (sin padre)
        $cuentasPrincipales = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->whereNull('cuenta_padre_id')
            ->with(['subcuentas' => function ($query) {
                $query->where('eliminado', 0)->with('subcuentas');
            }])
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CuentaContableResource::collection($cuentasPrincipales)
        ]);
    }

    /**
     * Obtener cuentas que permiten movimientos (para asientos)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function paraMovimientos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('permite_movimientos', 1)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CuentaContableResource::collection($cuentas)
        ]);
    }
}
