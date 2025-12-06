<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\ContentGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para generación automática de contenido mediante IA
 *
 * @group AI - Generación de Contenido
 */
class ContentController extends Controller
{
    public function __construct(
        private ContentGeneratorService $contentService
    ) {}

    /**
     * Generar recordatorio de pago
     */
    public function generatePaymentReminder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'factura_ids' => 'nullable|array',
            'factura_ids.*' => 'integer|exists:facturas,id',
            'tone' => 'nullable|in:friendly,formal,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;

            // Obtener información de la empresa
            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $this->contentService->setEmpresa([
                'nombre' => $empresa->nombre ?? 'Empresa',
                'telefono' => $empresa->telefono ?? '',
                'email' => $empresa->email ?? '',
            ]);

            // Obtener datos de cuentas por cobrar del cliente
            $clienteId = $request->input('cliente_id');
            $cliente = DB::table('clientes')->where('id', $clienteId)->first();

            // Buscar facturas pendientes
            $query = DB::table('facturas')
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $clienteId)
                ->where('estado', 'pendiente');

            if ($request->has('factura_ids') && !empty($request->input('factura_ids'))) {
                $query->whereIn('id', $request->input('factura_ids'));
            }

            $factura = $query->first();

            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron facturas pendientes para este cliente',
                ], 404);
            }

            // Preparar datos para el servicio
            $cuentaCobrar = [
                'cliente_nombre' => $cliente->nombre ?? 'Cliente',
                'cliente_email' => $cliente->email ?? '',
                'numero_factura' => $factura->numero ?? 'N/A',
                'saldo_pendiente' => $factura->total ?? 0,
                'fecha_vencimiento' => $factura->fecha_vencimiento ?? now()->format('Y-m-d'),
                'dias_mora' => 0,
            ];

            // Mapear tono a tipo
            $tone = $request->input('tone', 'friendly');
            $tipo = match ($tone) {
                'urgent' => 'cobro_urgente',
                'formal' => 'recordatorio_formal',
                default => 'recordatorio_amigable',
            };

            $result = $this->contentService->generatePaymentReminder($cuentaCobrar, $tipo);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar recordatorio de pago',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Generar mensaje de WhatsApp
     */
    public function generateThankYouEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'context' => 'nullable|in:payment,purchase,loyalty,referral',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;

            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $this->contentService->setEmpresa([
                'nombre' => $empresa->nombre ?? 'Empresa',
                'telefono' => $empresa->telefono ?? '',
            ]);

            $clienteId = $request->input('cliente_id');
            $cliente = DB::table('clientes')->where('id', $clienteId)->first();

            // Usar WhatsApp message como agradecimiento
            $cuentaCobrar = [
                'cliente_nombre' => $cliente->nombre ?? 'Cliente',
                'numero_factura' => 'Agradecimiento',
                'saldo_pendiente' => 0,
                'dias_mora' => 0,
            ];

            $result = $this->contentService->generateWhatsAppMessage($cuentaCobrar, 'agradecimiento');

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar email de agradecimiento',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Generar email de factura
     */
    public function generateInvoiceEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'factura_id' => 'required|integer|exists:facturas,id',
            'include_details' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $facturaId = $request->input('factura_id');

            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $this->contentService->setEmpresa([
                'nombre' => $empresa->nombre ?? 'Empresa',
                'cedula' => $empresa->cedula ?? '',
                'telefono' => $empresa->telefono ?? '',
                'email' => $empresa->email ?? '',
                'direccion' => $empresa->direccion ?? '',
            ]);

            $factura = DB::table('facturas')
                ->where('id', $facturaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada',
                ], 404);
            }

            $cliente = DB::table('clientes')->where('id', $factura->cliente_id)->first();

            $cuentaCobrar = [
                'cliente_nombre' => $cliente->nombre ?? 'Cliente',
                'cliente_cedula' => $cliente->cedula ?? '',
                'numero_factura' => $factura->numero ?? 'N/A',
                'fecha_emision' => $factura->fecha ?? now()->format('Y-m-d'),
                'fecha_vencimiento' => $factura->fecha_vencimiento ?? now()->format('Y-m-d'),
                'monto_original' => $factura->total ?? 0,
                'saldo_pendiente' => $factura->total ?? 0,
                'dias_mora' => 0,
            ];

            $result = $this->contentService->generateFormalLetter($cuentaCobrar);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar email de factura',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Generar reporte/notificación
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'nullable|in:sales,financial,inventory,general',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $reportType = $request->input('report_type', 'general');

            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $this->contentService->setEmpresa([
                'nombre' => $empresa->nombre ?? 'Empresa',
            ]);

            // Generar notificación de tipo reporte
            $datos = [
                'tipo_reporte' => $reportType,
                'fecha_inicio' => $request->input('start_date'),
                'fecha_fin' => $request->input('end_date'),
                'monto' => 0,
            ];

            $result = $this->contentService->generateNotification('meta_alcanzada', $datos);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'period' => [
                        'start' => $request->input('start_date'),
                        'end' => $request->input('end_date'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Generar descripción de producto
     */
    public function generateCustomContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:1000',
            'producto_id' => 'nullable|integer|exists:productos,id',
            'type' => 'nullable|in:email,sms,social,notification,product',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $type = $request->input('type', 'product');

            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $this->contentService->setEmpresa([
                'nombre' => $empresa->nombre ?? 'Empresa',
            ]);

            if ($type === 'product' && $request->has('producto_id')) {
                $producto = DB::table('productos')
                    ->where('id', $request->input('producto_id'))
                    ->where('empresa_id', $empresaId)
                    ->first();

                if ($producto) {
                    $result = $this->contentService->generateProductDescription([
                        'nombre' => $producto->nombre ?? 'Producto',
                        'categoria' => $producto->categoria ?? 'General',
                        'caracteristicas' => $producto->descripcion ?? '',
                        'precio' => $producto->precio ?? 0,
                        'id' => $producto->id,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $result,
                    ]);
                }
            }

            // Fallback: generar notificación genérica
            $result = $this->contentService->generateNotification('meta_alcanzada', [
                'prompt' => $request->input('prompt'),
                'monto' => 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar contenido personalizado',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
