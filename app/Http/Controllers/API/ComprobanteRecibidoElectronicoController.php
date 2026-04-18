<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\UpdateComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\ActualizarRespuestaHaciendaRequest;
use App\Http\Resources\ComprobanteRecibidoElectronicoResource;
use App\Models\ComprobanteRecibidoElectronico;
use App\Services\ComprobanteRecibidoElectronicoService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * ComprobanteRecibidoElectronicoController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de comprobantes electrónicos recibidos.
 * Reducción: 631 → ~200 líneas (-68%)
 */
class ComprobanteRecibidoElectronicoController extends Controller
{
    use HasEmpresaContext;

    public function __construct(
        private readonly ComprobanteRecibidoElectronicoService $service
    ) {}

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos',
        summary: 'Listar comprobantes electrónicos recibidos',
        description: 'Listado paginado de comprobantes electrónicos recibidos de proveedores',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ComprobanteRecibidoElectronico::class);

        $comprobantes = $this->service->listar(['empresa_id' => $this->getEmpresaId()]);

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos',
        summary: 'Registrar comprobante electrónico recibido',
        description: 'Registra un nuevo comprobante de proveedor con XML y datos estructurados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreComprobanteRecibidoElectronicoRequest $request): JsonResponse
    {
        $this->authorize('create', ComprobanteRecibidoElectronico::class);

        $data = [
            'empresa_id' => $this->getEmpresaId(),
            ...$request->validated(),
        ];

        $comprobante = $this->service->crear($data);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->load('proveedor'))->resolve(),
            'message' => 'Comprobante registrado exitosamente'
        ], 201);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/{id}',
        summary: 'Obtener comprobante electrónico',
        description: 'Detalle completo de un comprobante con proveedor e inventario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);

        $this->authorize('view', $comprobante);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante)->resolve()
        ]);
    }

    #[OA\Put(
        path: '/api/comprobantes-recibidos-electronicos/{id}',
        summary: 'Actualizar comprobante electrónico',
        description: 'Actualiza datos del comprobante. No permite modificar comprobantes confirmados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateComprobanteRecibidoElectronicoRequest $request, int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);
        $this->authorize('update', $comprobante);

        try {
            $this->service->actualizar($comprobante, $request->validated());
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->fresh('proveedor'))->resolve(),
            'message' => 'Comprobante actualizado exitosamente'
        ]);
    }

    #[OA\Delete(
        path: '/api/comprobantes-recibidos-electronicos/{id}',
        summary: 'Eliminar comprobante electrónico',
        description: 'Elimina comprobante. No permite eliminar comprobantes confirmados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);
        $this->authorize('delete', $comprobante);

        try {
            $this->service->eliminar($comprobante);
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Comprobante eliminado exitosamente']);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos/{id}/confirmar',
        summary: 'Confirmar comprobante',
        description: 'Marca el comprobante como confirmado/aceptado por el usuario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function confirmar(Request $request, int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);

        try {
            $comprobante = $this->service->confirmar($comprobante, $request->user()->id);
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante)->resolve(),
            'message' => 'Comprobante confirmado exitosamente'
        ]);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos/{id}/rechazar',
        summary: 'Rechazar comprobante',
        description: 'Marca el comprobante como rechazado por el usuario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);

        $comprobante = $this->service->rechazar($comprobante, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante)->resolve(),
            'message' => 'Comprobante rechazado exitosamente'
        ]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/proveedor/{proveedorId}',
        summary: 'Comprobantes por proveedor',
        description: 'Listado de comprobantes de un proveedor específico',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function porProveedor(int $proveedorId): AnonymousResourceCollection
    {
        $comprobantes = $this->service->porProveedor($this->getEmpresaId(), $proveedorId);

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/pendientes/list',
        summary: 'Comprobantes pendientes',
        description: 'Comprobantes que no han sido confirmados ni rechazados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function pendientes(): AnonymousResourceCollection
    {
        $comprobantes = $this->service->pendientes($this->getEmpresaId());

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/resumen/por-estado',
        summary: 'Resumen por estado de Hacienda',
        description: 'Estadísticas agrupadas por estado de respuesta de Hacienda',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function resumenPorProveedor(): JsonResponse
    {
        $resumen = $this->service->resumenPorEstadoHacienda($this->getEmpresaId());

        return response()->json(['success' => true, 'data' => $resumen]);
    }

    #[OA\Put(
        path: '/api/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda',
        summary: 'Actualizar respuesta de Hacienda',
        description: 'Actualiza con la respuesta XML recibida de Hacienda (DGT)',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function actualizarRespuestaHacienda(ActualizarRespuestaHaciendaRequest $request, int $id): JsonResponse
    {
        $comprobante = $this->service->obtenerPorEmpresa($this->getEmpresaId(), $id);

        $comprobante = $this->service->actualizarRespuestaHacienda($comprobante, $request->validated());

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante)->resolve(),
            'message' => 'Respuesta de Hacienda actualizada exitosamente'
        ]);
    }
}
