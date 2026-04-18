<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Services\PagoService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * PagoController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de pagos.
 * Actualiza automáticamente saldos de cuentas por cobrar/pagar.
 * Reducción: 627 → ~180 líneas (-71%)
 */
class PagoController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private readonly PagoService $service)
    {
    }

    #[OA\Get(
        path: '/api/pagos',
        summary: 'Listar todos los pagos',
        description: 'Listado paginado con filtros por estado, forma de pago, proveedor, cliente y fechas',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Pago::class);

        $filtros = array_merge(
            $request->only(['estado', 'forma_pago_id', 'proveedor_id', 'cliente_id', 'desde', 'hasta', 'sort_by', 'sort_order']),
            ['empresa_id' => $this->getEmpresaId()]
        );

        $pagos = $this->service->listar($filtros, (int) $request->get('per_page', 15));

        return PagoResource::collection($pagos);
    }

    #[OA\Post(
        path: '/api/pagos',
        summary: 'Crear un nuevo pago',
        description: 'Registra un pago y actualiza automáticamente saldos de cuentas relacionadas',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StorePagoRequest $request): JsonResponse
    {
        $this->authorize('create', Pago::class);

        $pago = $this->service->crear([
            'empresa_id' => $this->getEmpresaId(),
            ...$request->validated()
        ]);

        return response()->json([
            'data' => PagoResource::make($pago)->resolve(),
            'message' => 'Pago registrado exitosamente'
        ], 201);
    }

    #[OA\Get(
        path: '/api/pagos/{id}',
        summary: 'Obtener un pago específico',
        description: 'Detalle completo de un pago con todas sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): PagoResource
    {
        $pago = $this->service->obtener($id);

        $this->authorize('view', $pago);
        return new PagoResource($pago);
    }

    #[OA\Put(
        path: '/api/pagos/{id}',
        summary: 'Actualizar un pago existente',
        description: 'Actualiza pago y ajusta saldos si cambia el monto. No permite modificar pagos procesados',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdatePagoRequest $request, int $id): JsonResponse
    {
        $pago = $this->service->obtener($id);

        $this->authorize('update', $pago);

        $pago = $this->service->actualizar($pago, $request->validated());

        return response()->json([
            'data' => PagoResource::make($pago)->resolve(),
            'message' => 'Pago actualizado exitosamente'
        ]);
    }

    #[OA\Delete(
        path: '/api/pagos/{id}',
        summary: 'Eliminar un pago',
        description: 'Soft delete del pago. Revierte saldos de cuentas. No permite eliminar pagos procesados',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $pago = $this->service->obtener($id);

        $this->authorize('delete', $pago);

        $this->service->eliminar($pago);

        return response()->json(['success' => true, 'message' => 'Pago eliminado exitosamente']);
    }

    #[OA\Get(
        path: '/api/pagos/resumen-por-forma-pago',
        summary: 'Resumen de pagos por forma de pago',
        description: 'Estadísticas de pagos agrupados por forma de pago (solo pagos procesados)',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function resumenPorFormaPago(): JsonResponse
    {
        $resumen = $this->service->resumenPorFormaPago($this->getEmpresaId());

        return response()->json(['success' => true, 'data' => $resumen]);
    }
}
