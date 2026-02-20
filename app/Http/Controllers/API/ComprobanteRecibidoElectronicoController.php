<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\UpdateComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\ActualizarRespuestaHaciendaRequest;
use App\Http\Resources\ComprobanteRecibidoElectronicoResource;
use App\Models\ComprobanteRecibidoElectronico;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos',
        summary: 'Listar comprobantes electrónicos recibidos',
        description: 'Listado paginado de comprobantes electrónicos recibidos de proveedores',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ComprobanteRecibidoElectronico::class);

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())
            ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
            ->orderByDesc('fecha_recepcion_sistema')
            ->paginate(15);

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos',
        summary: 'Registrar comprobante electrónico recibido',
        description: 'Registra un nuevo comprobante de proveedor con XML y datos estructurados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function store(StoreComprobanteRecibidoElectronicoRequest $request): JsonResponse
    {
        $this->authorize('create', ComprobanteRecibidoElectronico::class);

        try {
            DB::beginTransaction();
            $comprobante = ComprobanteRecibidoElectronico::create([
                'empresa_id' => $this->getEmpresaId(),
                ...$request->validated(),
                'moneda' => $request->moneda ?? 'CRC',
                'estado_hacienda' => 'Procesando',
                'confirmado_usuario' => 0
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->load('proveedor'))->resolve(),
                'message' => 'Comprobante registrado exitosamente'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al registrar', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/{id}',
        summary: 'Obtener comprobante electrónico',
        description: 'Detalle completo de un comprobante con proveedor e inventario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function show(int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())
            ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
            ->findOrFail($id);

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
        tags: ['Facturación Electrónica']
    )]
    public function update(UpdateComprobanteRecibidoElectronicoRequest $request, int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())->findOrFail($id);
        $this->authorize('update', $comprobante);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json(['success' => false, 'message' => 'No se puede modificar un comprobante confirmado'], 422);
        }

        try {
            DB::beginTransaction();
            $comprobante->update($request->validated());
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->fresh('proveedor'))->resolve(),
                'message' => 'Comprobante actualizado exitosamente'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al actualizar', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/api/comprobantes-recibidos-electronicos/{id}',
        summary: 'Eliminar comprobante electrónico',
        description: 'Elimina comprobante. No permite eliminar comprobantes confirmados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function destroy(int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())->findOrFail($id);
        $this->authorize('delete', $comprobante);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar un comprobante confirmado'], 422);
        }

        $comprobante->delete();
        return response()->json(['success' => true, 'message' => 'Comprobante eliminado exitosamente']);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos/{id}/confirmar',
        summary: 'Confirmar comprobante',
        description: 'Marca el comprobante como confirmado/aceptado por el usuario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function confirmar(Request $request, int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())->findOrFail($id);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json(['success' => false, 'message' => 'El comprobante ya fue confirmado'], 422);
        }

        $comprobante->update([
            'confirmado_usuario' => 1,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))->resolve(),
            'message' => 'Comprobante confirmado exitosamente'
        ]);
    }

    #[OA\Post(
        path: '/api/comprobantes-recibidos-electronicos/{id}/rechazar',
        summary: 'Rechazar comprobante',
        description: 'Marca el comprobante como rechazado por el usuario',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())->findOrFail($id);

        $comprobante->update([
            'confirmado_usuario' => 2,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))->resolve(),
            'message' => 'Comprobante rechazado exitosamente'
        ]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/proveedor/{proveedorId}',
        summary: 'Comprobantes por proveedor',
        description: 'Listado de comprobantes de un proveedor específico',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function porProveedor(int $proveedorId): AnonymousResourceCollection
    {
        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())
            ->where('proveedor_id', $proveedorId)
            ->with(['proveedor', 'entradaInventario'])
            ->orderByDesc('fecha_emision_comprobante')
            ->paginate(15);

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/pendientes/list',
        summary: 'Comprobantes pendientes',
        description: 'Comprobantes que no han sido confirmados ni rechazados',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function pendientes(): AnonymousResourceCollection
    {
        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())
            ->where('confirmado_usuario', 0)
            ->with(['proveedor'])
            ->orderBy('fecha_recepcion_sistema')
            ->get();

        return ComprobanteRecibidoElectronicoResource::collection($comprobantes)
            ->additional(['success' => true]);
    }

    #[OA\Get(
        path: '/api/comprobantes-recibidos-electronicos/resumen/por-estado',
        summary: 'Resumen por estado de Hacienda',
        description: 'Estadísticas agrupadas por estado de respuesta de Hacienda',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function resumenPorProveedor(): JsonResponse
    {
        $resumen = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())
            ->selectRaw('estado_hacienda, COUNT(*) as total_comprobantes, SUM(total_comprobante) as monto_total')
            ->groupBy('estado_hacienda')
            ->get();

        return response()->json(['success' => true, 'data' => $resumen]);
    }

    #[OA\Put(
        path: '/api/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda',
        summary: 'Actualizar respuesta de Hacienda',
        description: 'Actualiza con la respuesta XML recibida de Hacienda (DGT)',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica']
    )]
    public function actualizarRespuestaHacienda(ActualizarRespuestaHaciendaRequest $request, int $id): JsonResponse
    {
        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $this->getEmpresaId())->findOrFail($id);

        $comprobante->update([
            'xml_respuesta_hacienda' => $request->xml_respuesta_hacienda,
            'estado_hacienda' => $request->estado_hacienda,
            'mensaje_hacienda' => $request->mensaje_hacienda,
            'fecha_respuesta_hacienda' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::make($comprobante)->resolve(),
            'message' => 'Respuesta de Hacienda actualizada exitosamente'
        ]);
    }
}
