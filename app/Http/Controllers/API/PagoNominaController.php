<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoNominaRequest;
use App\Http\Requests\UpdatePagoNominaRequest;
use App\Http\Resources\PagoNominaResource;
use App\Models\PagoNomina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Pagos de Nómina
 *
 * Gestiona los pagos individuales de nómina por empleado y período.
 * Incluye cálculo de monto bruto, deducciones y monto neto.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PagoNominaController extends Controller
{
    /**
     * Listar todos los pagos de nómina de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'empleado', 'periodoNomina', 'metodoPago']);

        // Filtro por empleado
        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }

        // Filtro por período
        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
        }

        $pagos = $query->orderBy('fecha_pago', 'desc')->paginate(15);

        return PagoNominaResource::collection($pagos);
    }

    /**
     * Crear un nuevo pago de nómina
     *
     * @param StorePagoNominaRequest $request
     * @return PagoNominaResource
     */
    public function store(StorePagoNominaRequest $request): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();

        try {
            $pago = PagoNomina::create([
                'empresa_id' => $empresaId,
                'empleado_id' => $request->empleado_id,
                'periodo_nomina_id' => $request->periodo_nomina_id,
                'fecha_pago' => $request->fecha_pago,
                'monto_bruto' => $request->monto_bruto,
                'total_deducciones' => $request->total_deducciones,
                'monto_neto_pagado' => $request->monto_neto_pagado,
                'metodo_pago_id' => $request->metodo_pago_id,
                'referencia_pago' => $request->referencia_pago,
                'estado' => $request->estado ?? 'pendiente',
                'observaciones' => $request->observaciones,
                'activo' => $request->activo ?? 1
            ]);

            DB::commit();

            return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al crear el pago de nómina: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un pago de nómina específico
     *
     * @param int $id
     * @param Request $request
     * @return PagoNominaResource
     */
    public function show(int $id, Request $request): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'empleado', 'periodoNomina', 'metodoPago'])
            ->findOrFail($id);

        return new PagoNominaResource($pago);
    }

    /**
     * Actualizar un pago de nómina
     *
     * @param UpdatePagoNominaRequest $request
     * @param int $id
     * @return PagoNominaResource
     */
    public function update(UpdatePagoNominaRequest $request, int $id): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no esté pagado
        if ($pago->estado === 'pagado') {
            abort(422, 'No se puede modificar un pago de nómina que ya ha sido marcado como pagado');
        }

        DB::beginTransaction();

        try {
            $pago->update($request->only([
                'empleado_id',
                'periodo_nomina_id',
                'fecha_pago',
                'monto_bruto',
                'total_deducciones',
                'monto_neto_pagado',
                'metodo_pago_id',
                'referencia_pago',
                'estado',
                'observaciones',
                'activo'
            ]));

            DB::commit();

            return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al actualizar el pago de nómina: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar (soft delete) un pago de nómina
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no esté pagado
        if ($pago->estado === 'pagado') {
            return response()->json([
                'message' => 'No se puede eliminar un pago de nómina que ya ha sido pagado'
            ], 422);
        }

        $pago->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Pago de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Marcar un pago como pagado
     *
     * @param int $id
     * @param Request $request
     * @return PagoNominaResource
     */
    public function marcarPagado(int $id, Request $request): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($pago->estado === 'pagado') {
            abort(422, 'Este pago ya ha sido marcado como pagado');
        }

        $pago->update([
            'estado' => 'pagado',
            'fecha_pago' => $request->fecha_pago ?? now()
        ]);

        return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));
    }

    /**
     * Obtener pagos por empleado
     *
     * @param int $empleadoId
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function porEmpleado(int $empleadoId, Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;

        $pagos = PagoNomina::where('empresa_id', $empresaId)
            ->where('empleado_id', $empleadoId)
            ->where('eliminado', 0)
            ->with(['periodoNomina', 'metodoPago'])
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15);

        return PagoNominaResource::collection($pagos);
    }

    /**
     * Obtener resumen de pagos por método de pago
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resumenPorMetodoPago(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0);

        // Filtro opcional por período
        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        $resumen = $query->join('formas_pago', 'pagos_nomina.metodo_pago_id', '=', 'formas_pago.id')
            ->selectRaw('
                formas_pago.id,
                formas_pago.nombre as metodo_pago,
                COUNT(pagos_nomina.id) as total_pagos,
                SUM(pagos_nomina.monto_neto_pagado) as total_monto
            ')
            ->groupBy('formas_pago.id', 'formas_pago.nombre')
            ->get();

        return response()->json($resumen);
    }

    /**
     * Calcular totales de nómina por período
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function totalesPorPeriodo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0);

        // Filtro opcional por año
        if ($request->filled('anio')) {
            $query->whereYear('fecha_pago', $request->anio);
        }

        $totales = $query->join('periodos_nomina', 'pagos_nomina.periodo_nomina_id', '=', 'periodos_nomina.id')
            ->selectRaw('
                periodos_nomina.id as periodo_id,
                periodos_nomina.nombre_periodo,
                periodos_nomina.fecha_inicio,
                periodos_nomina.fecha_fin,
                COUNT(pagos_nomina.id) as total_empleados,
                SUM(pagos_nomina.monto_bruto) as total_bruto,
                SUM(pagos_nomina.total_deducciones) as total_deducciones,
                SUM(pagos_nomina.monto_neto_pagado) as total_neto
            ')
            ->groupBy('periodos_nomina.id', 'periodos_nomina.nombre_periodo', 'periodos_nomina.fecha_inicio', 'periodos_nomina.fecha_fin')
            ->orderBy('periodos_nomina.fecha_inicio', 'desc')
            ->get();

        return response()->json($totales);
    }
}
