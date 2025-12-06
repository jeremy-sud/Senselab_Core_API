<?php

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\OCRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controlador de OCR para Facturas
 *
 * Permite escanear facturas de proveedores y extraer datos automáticamente
 * utilizando inteligencia artificial (GPT-4 Vision).
 *
 * @package App\Http\Controllers\API\AI
 */
#[OA\Tag(name: 'IA - OCR', description: 'Reconocimiento óptico de caracteres para facturas')]
class OCRController extends Controller
{
    protected OCRService $ocrService;

    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Escanear una factura y extraer datos
     */
    #[OA\Post(
        path: '/api/ai/ocr/scan',
        summary: 'Escanear factura de proveedor',
        description: 'Analiza una imagen o PDF de factura y extrae automáticamente los datos estructurados',
        tags: ['IA - OCR'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'Archivo de factura (JPEG, PNG, WebP, PDF)'
                        ),
                        new OA\Property(
                            property: 'validate',
                            type: 'boolean',
                            description: 'Validar datos contra base de datos',
                            default: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Factura escaneada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'proveedor',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'nombre', type: 'string'),
                                        new OA\Property(property: 'identificacion', type: 'string'),
                                        new OA\Property(property: 'tipo_identificacion', type: 'string'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'factura',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'numero', type: 'string'),
                                        new OA\Property(property: 'fecha_emision', type: 'string', format: 'date'),
                                        new OA\Property(property: 'moneda', type: 'string'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'totales',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'subtotal', type: 'number'),
                                        new OA\Property(property: 'impuesto', type: 'number'),
                                        new OA\Property(property: 'total', type: 'number'),
                                    ]
                                ),
                                new OA\Property(property: 'lineas', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'confianza', type: 'integer', example: 95),
                            ]
                        ),
                        new OA\Property(property: 'validation', type: 'object', nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Archivo inválido'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 500, description: 'Error del servidor'),
        ]
    )]
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:20480', // 20MB
            ],
            'validate' => 'boolean',
        ]);

        $file = $request->file('file');
        $shouldValidate = $request->boolean('validate', true);

        // Escanear factura
        $result = $this->ocrService->scanInvoice($file);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Validar datos si se solicita
        if ($shouldValidate && !empty($result['data'])) {
            $validation = $this->ocrService->validateExtractedData($result['data']);
            $result['validation'] = $validation['validation'];
        }

        return response()->json($result);
    }

    /**
     * Escanear múltiples facturas
     */
    #[OA\Post(
        path: '/api/ai/ocr/scan-multiple',
        summary: 'Escanear múltiples facturas',
        description: 'Analiza múltiples archivos de facturas en una sola petición',
        tags: ['IA - OCR'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['files'],
                    properties: [
                        new OA\Property(
                            property: 'files[]',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Archivos de facturas (máximo 5)'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Facturas procesadas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'processed', type: 'integer'),
                        new OA\Property(property: 'results', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Archivos inválidos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function scanMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:5',
            'files.*' => [
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:20480',
            ],
        ]);

        $files = $request->file('files');
        $result = $this->ocrService->scanMultiple($files);

        return response()->json($result);
    }

    /**
     * Obtener el schema de datos que extrae el OCR
     */
    #[OA\Get(
        path: '/api/ai/ocr/schema',
        summary: 'Obtener schema de extracción',
        description: 'Retorna la estructura de datos que el OCR extrae de las facturas',
        tags: ['IA - OCR'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Schema de extracción',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'schema', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function schema(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'schema' => $this->ocrService->getSchema(),
        ]);
    }

    /**
     * Verificar estado del servicio OCR
     */
    #[OA\Get(
        path: '/api/ai/ocr/status',
        summary: 'Estado del servicio OCR',
        description: 'Verifica si el servicio de OCR está disponible y configurado',
        tags: ['IA - OCR'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado del servicio',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean'),
                        new OA\Property(property: 'api_configured', type: 'boolean'),
                        new OA\Property(property: 'max_file_size_mb', type: 'integer'),
                        new OA\Property(property: 'allowed_formats', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
        ]
    )]
    public function status(): JsonResponse
    {
        $apiKey = config('openai.api_key');

        return response()->json([
            'success' => true,
            'enabled' => config('openai.features.ocr.enabled', false),
            'api_configured' => !empty($apiKey),
            'max_file_size_mb' => config('openai.features.ocr.max_file_size_mb', 20),
            'allowed_formats' => ['jpeg', 'jpg', 'png', 'webp', 'pdf'],
        ]);
    }
}
