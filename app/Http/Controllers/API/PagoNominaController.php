<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoNominaRequest;
use App\Http\Requests\UpdatePagoNominaRequest;
use App\Http\Resources\PagoNominaResource;
use App\Models\PagoNomina;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * PagoNominaController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de pagos de nómina.
 * Reducción: 657 → ~200 líneas (-70%)
 */
class PagoNominaController extends Controller
{
    use HasEmpresaContext;

    #[OA\Get(
        path: '/api/pagos-nomina',
        summary: 'Listar pagos de nómina',
        description: 'Obtiene listado paginado con filtros por empleado, período, estado y fechas',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PagoNomina::class);

        $query = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)
            ->with(['empleado', 'periodoNomina', 'metodoPago']);

        match (true) {
            $request->filled('empleado_id') => $query->where('empleado_id', $request->empleado_id),
            default => null
        };

        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled(['desde', 'hasta'])) {
            $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
        }

        return PagoNominaResource::collection($query->orderByDesc('id')->paginate(15));
    }

    #[OA\Post(
        path: '/api/pagos-nomina',
        summary: 'Crear pago de nómina',
        description: 'Registra un nuevo pago con cálculo de monto bruto, deducciones y neto',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StorePagoNominaRequest $request): JsonResponse
    {
        $this->authorize('create', PagoNomina::class);

        DB::beginTransaction();
        $pago = PagoNomina::create([
            'empresa_id' => $this->getEmpresaId(),
            ...$request->validated(),
            'estado' => $request->estado ?? 'pendiente',
            'activo' => 1
        ]);
        DB::commit();

        return $this->createdResponse(
            PagoNominaResource::make($pago->load(['empleado', 'periodoNomina', 'metodoPago']))->resolve(),
            'Pago creado exitosamente'
        );
    }

    #[OA\Get(
        path: '/api/pagos-nomina/{id}',
        summary: 'Obtener pago de nómina',
        description: 'Detalle completo de un pago incluyendo empleado, período y método de pago',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): PagoNominaResource
    {
        $pago = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)
            ->with(['empleado', 'periodoNomina', 'metodoPago'])
            ->findOrFail($id);

        $this->authorize('view', $pago);
        return new PagoNominaResource($pago);
    }

    #[OA\Put(
        path: '/api/pagos-nomina/{id}',
        summary: 'Actualizar pago de nómina',
        description: 'Actualiza pago de nómina. No permite modificar pagos ya pagados',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdatePagoNominaRequest $request, int $id): JsonResponse
    {
        $pago = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)->findOrFail($id);

        $this->authorize('update', $pago);

        if ($pago->estado === 'pagado') {
            return $this->errorResponse('No se puede modificar un pago ya pagado', 422);
        }

        DB::beginTransaction();
        $pago->update($request->validated());
        DB::commit();

        return $this->successResponse(
            PagoNominaResource::make($pago->fresh(['empleado', 'periodoNomina', 'metodoPago']))->resolve(),
            'Pago actualizado exitosamente'
        );
    }

    #[OA\Delete(
        path: '/api/pagos-nomina/{id}',
        summary: 'Eliminar pago de nómina',
        description: 'Soft delete de un pago. No permite eliminar pagos ya pagados',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $pago = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)->findOrFail($id);

        $this->authorize('delete', $pago);

        if ($pago->estado === 'pagado') {
            return $this->errorResponse('No se puede eliminar un pago ya pagado', 422);
        }

        $pago->update(['eliminado' => 1, 'activo' => 0]);
        return $this->deletedResponse('Pago eliminado exitosamente');
    }

    #[OA\Post(
        path: '/api/pagos-nomina/{id}/marcar-pagado',
        summary: 'Marcar pago como pagado',
        description: 'Cambia el estado a pagado y registra la fecha',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function marcarPagado(int $id, Request $request): JsonResponse
    {
        $pago = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)->findOrFail($id);

        if ($pago->estado === 'pagado') {
            return response()->json(['message' => 'Este pago ya está marcado como pagado'], 422);
        }

        $pago->update(['estado' => 'pagado', 'fecha_pago' => $request->fecha_pago ?? now()]);

        return response()->json([
            'data' => PagoNominaResource::make($pago->fresh(['empleado', 'periodoNomina', 'metodoPago']))->resolve(),
            'message' => 'Pago marcado como pagado'
        ]);
    }

    #[OA\Get(
        path: '/api/pagos-nomina/empleado/{empleadoId}',
        summary: 'Pagos por empleado',
        description: 'Historial de pagos de nómina de un empleado específico',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function porEmpleado(int $empleadoId): AnonymousResourceCollection
    {
        $pagos = PagoNomina::where('empresa_id', $this->getEmpresaId())
            ->where('empleado_id', $empleadoId)
            ->where('eliminado', 0)
            ->with(['periodoNomina', 'metodoPago'])
            ->orderByDesc('fecha_pago')
            ->paginate(15);

        return PagoNominaResource::collection($pagos);
    }

    #[OA\Get(
        path: '/api/pagos-nomina/resumen-metodo-pago',
        summary: 'Resumen por método de pago',
        description: 'Estadísticas agregadas por método de pago',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function resumenPorMetodoPago(Request $request): JsonResponse
    {
        $query = PagoNomina::where('empresa_id', $this->getEmpresaId())->where('eliminado', 0);

        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        $resumen = $query->join('formas_pago', 'pagos_nomina.metodo_pago_id', '=', 'formas_pago.id')
            ->selectRaw('formas_pago.id, formas_pago.nombre as metodo_pago, COUNT(*) as total_pagos, SUM(monto_neto_pagado) as total_monto')
            ->groupBy('formas_pago.id', 'formas_pago.nombre')
            ->get();

        return response()->json($resumen);
    }

    #[OA\Get(
        path: '/api/pagos-nomina/totales-por-periodo',
        summary: 'Totales por período',
        description: 'Totales agregados de nómina por período',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function totalesPorPeriodo(Request $request): JsonResponse
    {
        $query = PagoNomina::where('empresa_id', $this->getEmpresaId())->where('eliminado', 0);

        if ($request->filled('anio')) {
            $query->whereYear('fecha_pago', $request->anio);
        }

        $totales = $query->join('periodos_nomina', 'pagos_nomina.periodo_nomina_id', '=', 'periodos_nomina.id')
            ->selectRaw('periodos_nomina.id as periodo_id, periodos_nomina.nombre_periodo, periodos_nomina.fecha_inicio, periodos_nomina.fecha_fin, COUNT(*) as total_empleados, SUM(monto_bruto) as total_bruto, SUM(total_deducciones) as total_deducciones, SUM(monto_neto_pagado) as total_neto')
            ->groupBy('periodos_nomina.id', 'periodos_nomina.nombre_periodo', 'periodos_nomina.fecha_inicio', 'periodos_nomina.fecha_fin')
            ->orderByDesc('periodos_nomina.fecha_inicio')
            ->get();

        return response()->json($totales);
    }
}
