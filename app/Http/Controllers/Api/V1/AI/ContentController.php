<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\ContentGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * Controller para generación automática de contenido mediante IA
 *
 * @OA\Tag(
 *     name="AI - Generación de Contenido",
 *     description="Endpoints para generación automática de emails, recordatorios, descripciones y reportes usando IA"
 * )
 */
class ContentController extends Controller
{
    public function __construct(
        private ContentGeneratorService $contentService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/ai/content/payment-reminder",
     *     summary="Generar recordatorio de pago",
     *     description="Genera un recordatorio de pago personalizado para un cliente con facturas pendientes",
     *     operationId="generatePaymentReminder",
     *     tags={"AI - Generación de Contenido"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cliente_id"},
     *             @OA\Property(property="cliente_id", type="integer", example=1),
     *             @OA\Property(property="factura_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="tone", type="string", enum={"friendly", "formal", "urgent"}, example="friendly")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recordatorio generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="body", type="string"),
     *                 @OA\Property(property="type", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No se encontraron facturas pendientes"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/ai/content/thank-you",
     *     summary="Generar mensaje de agradecimiento",
     *     description="Genera un mensaje de agradecimiento personalizado (WhatsApp/email) para un cliente",
     *     operationId="generateThankYouEmail",
     *     tags={"AI - Generación de Contenido"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cliente_id"},
     *             @OA\Property(property="cliente_id", type="integer", example=1),
     *             @OA\Property(property="context", type="string", enum={"payment", "purchase", "loyalty", "referral"}, example="purchase")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="type", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/ai/content/invoice-email",
     *     summary="Generar email de factura",
     *     description="Genera un email formal con carta de cobro para acompañar el envío de una factura",
     *     operationId="generateInvoiceEmail",
     *     tags={"AI - Generación de Contenido"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"factura_id"},
     *             @OA\Property(property="factura_id", type="integer", example=1),
     *             @OA\Property(property="include_details", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="body", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Factura no encontrada"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/ai/content/report",
     *     summary="Generar reporte con IA",
     *     description="Genera un reporte narrativo sobre un período específico usando IA",
     *     operationId="generateAIReport",
     *     tags={"AI - Generación de Contenido"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-03-31"),
     *             @OA\Property(property="report_type", type="string", enum={"sales", "financial", "inventory", "general"}, example="general")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="generated_at", type="string", format="date-time"),
     *                 @OA\Property(property="period", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/ai/content/custom",
     *     summary="Generar contenido personalizado",
     *     description="Genera contenido personalizado a partir de un prompt libre, opcionalmente vinculado a un producto",
     *     operationId="generateCustomContent",
     *     tags={"AI - Generación de Contenido"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prompt"},
     *             @OA\Property(property="prompt", type="string", maxLength=1000, example="Genera descripción de producto"),
     *             @OA\Property(property="producto_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", enum={"email", "sms", "social", "notification", "product"}, example="product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contenido generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
