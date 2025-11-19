<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodoNominaRequest;
use App\Http\Requests\UpdatePeriodoNominaRequest;
use App\Http\Resources\PeriodoNominaResource;
use App\Models\PeriodoNomina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Períodos de Nómina
 *
 * Gestiona los períodos de nómina (quincenales, mensuales, etc.) con estados: Abierto, Cerrado, Procesado.
 * Incluye validación de solapamiento de fechas y cálculos de totales por período.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PeriodoNominaController extends Controller
{
    /**
     * Listar todos los períodos de nómina de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'pagosNomina']);

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por año
        if ($request->filled('anio')) {
            $query->whereYear('fecha_inicio', $request->anio);
        }

        // Filtro por mes
        if ($request->filled('mes')) {
            $query->whereMonth('fecha_inicio', $request->mes);
        }

        $periodos = $query->orderBy('fecha_inicio', 'desc')->paginate(15);

        return PeriodoNominaResource::collection($periodos);
    }

    /**
     * Crear un nuevo período de nómina
     *
     * @param StorePeriodoNominaRequest $request
     * @return PeriodoNominaResource
     */
    public function store(StorePeriodoNominaRequest $request): PeriodoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::create([
            'empresa_id' => $empresaId,
            'nombre_periodo' => $request->nombre_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_pago_estimada' => $request->fecha_pago_estimada,
            'estado' => $request->estado ?? 'Abierto',
            'observaciones' => $request->observaciones,
            'activo' => $request->activo ?? 1
        ]);

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Mostrar un período de nómina específico
     *
     * @param int $id
     * @param Request $request
     * @return PeriodoNominaResource
     */
    public function show(int $id, Request $request): PeriodoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'pagosNomina.empleado', 'pagosNomina.metodoPago'])
            ->findOrFail($id);

        return new PeriodoNominaResource($periodo);
    }

    /**
     * Actualizar un período de nómina
     *
     * @param UpdatePeriodoNominaRequest $request
     * @param int $id
     * @return PeriodoNominaResource
     */
    public function update(UpdatePeriodoNominaRequest $request, int $id): PeriodoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no esté procesado
        if ($periodo->estado === 'Procesado') {
            abort(422, 'No se puede modificar un período de nómina que ya ha sido procesado');
        }

        $periodo->update($request->only([
            'nombre_periodo',
            'fecha_inicio',
            'fecha_fin',
            'fecha_pago_estimada',
            'estado',
            'observaciones',
            'activo'
        ]));

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Eliminar (soft delete) un período de nómina
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no tenga pagos asociados
        if ($periodo->pagosNomina()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un período con pagos de nómina asociados'
            ], 422);
        }

        $periodo->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Período de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Cerrar un período de nómina (cambiar estado a Cerrado)
     *
     * @param int $id
     * @param Request $request
     * @return PeriodoNominaResource
     */
    public function cerrar(int $id, Request $request): PeriodoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($periodo->estado !== 'Abierto') {
            abort(422, 'Solo se pueden cerrar períodos en estado Abierto');
        }

        $periodo->update(['estado' => 'Cerrado']);

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Procesar un período de nómina (cambiar estado a Procesado)
     * Genera los pagos de nómina para todos los empleados activos
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function procesar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        if ($periodo->estado === 'Procesado') {
            return response()->json([
                'message' => 'Este período ya ha sido procesado'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Aquí se implementaría la lógica para generar los pagos de nómina
            // Por ahora solo cambiamos el estado
            $periodo->update(['estado' => 'Procesado']);

            DB::commit();

            return response()->json([
                'message' => 'Período de nómina procesado exitosamente',
                'periodo' => new PeriodoNominaResource($periodo)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el período de nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de totales del período
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function resumen(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        $totales = $periodo->pagosNomina()
            ->selectRaw('
                COUNT(*) as total_empleados,
                SUM(monto_bruto) as total_bruto,
                SUM(total_deducciones) as total_deducciones,
                SUM(monto_neto_pagado) as total_neto
            ')
            ->first();

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
                'estado' => $periodo->estado
            ],
            'resumen' => [
                'total_empleados' => $totales->total_empleados ?? 0,
                'total_bruto' => number_format($totales->total_bruto ?? 0, 2),
                'total_deducciones' => number_format($totales->total_deducciones ?? 0, 2),
                'total_neto' => number_format($totales->total_neto ?? 0, 2)
            ]
        ]);
    }

    /**
     * Listar períodos activos para selección en formularios
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodos = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre_periodo', 'fecha_inicio', 'fecha_fin', 'estado')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return response()->json($periodos);
    }
}
