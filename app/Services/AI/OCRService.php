<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de OCR para Facturas de Proveedores
 *
 * Utiliza IA (Gemini gratuito o OpenAI) para extraer datos automáticamente de:
 * - Facturas de proveedores (PDF, imágenes)
 * - Comprobantes electrónicos
 * - Notas de crédito/débito
 *
 * Por defecto usa Gemini (GRATUITO)
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class OCRService
{
    protected AIServiceInterface $aiService;

    /**
     * Schema de datos a extraer de facturas
     * @var array<string, mixed>
     */
    protected array $invoiceSchema = [
        'proveedor' => [
            'nombre' => 'string',
            'identificacion' => 'string',
            'tipo_identificacion' => 'string (01=física, 02=jurídica, 03=DIMEX, 04=NITE)',
            'email' => 'string|null',
            'telefono' => 'string|null',
            'direccion' => 'string|null',
        ],
        'factura' => [
            'numero' => 'string',
            'fecha_emision' => 'YYYY-MM-DD',
            'fecha_vencimiento' => 'YYYY-MM-DD|null',
            'moneda' => 'CRC|USD',
            'tipo_cambio' => 'number|null',
            'condicion_venta' => 'Contado|Crédito',
            'plazo_credito' => 'number (días)|null',
        ],
        'totales' => [
            'subtotal' => 'number',
            'descuento' => 'number',
            'impuesto' => 'number',
            'total' => 'number',
        ],
        'lineas' => [
            [
                'cantidad' => 'number',
                'descripcion' => 'string',
                'precio_unitario' => 'number',
                'descuento' => 'number',
                'impuesto' => 'number',
                'subtotal' => 'number',
                'codigo_producto' => 'string|null',
                'unidad_medida' => 'string|null',
            ],
        ],
        'confianza' => 'number (0-100, porcentaje de confianza en la extracción)',
        'notas' => 'string|null (observaciones o advertencias)',
    ];

    public function __construct(?AIServiceInterface $aiService = null)
    {
        // Usar Gemini por defecto (gratuito), fallback a OpenAI
        if ($aiService) {
            $this->aiService = $aiService;
        } elseif (!empty(config('gemini.api_key'))) {
            $this->aiService = app(GeminiService::class);
        } else {
            $this->aiService = app(OpenAIService::class);
        }
    }

    /**
     * Escanear factura y extraer datos
     *
     * @param UploadedFile|string $file Archivo subido o ruta
     * @return array<string, mixed> Datos extraídos estructurados
     */
    public function scanInvoice(UploadedFile|string $file): array
    {
        // Validar si OCR está habilitado
        if (!config('openai.features.ocr.enabled', true)) {
            return [
                'success' => false,
                'error' => 'El servicio de OCR está deshabilitado',
            ];
        }

        // Preparar imagen
        $imagePath = $this->prepareFile($file);

        if (!$imagePath) {
            return [
                'success' => false,
                'error' => 'No se pudo procesar el archivo',
            ];
        }

        // Prompt optimizado para facturas costarricenses
        $prompt = $this->buildInvoicePrompt();

        try {
            $result = $this->aiService->analyzeImage($imagePath, $prompt, [
                'detail' => 'high',
                'max_tokens' => 4096,
            ]);

            if (!$result['success']) {
                return $result;
            }

            // Parsear respuesta JSON
            $extractedData = $this->parseResponse($result['content']);

            // Limpiar archivo temporal si existe
            $this->cleanupTempFile($imagePath);

            return [
                'success' => true,
                'data' => $extractedData,
                'raw_response' => $result['content'],
                'usage' => $result['usage'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OCR scan error', [
                'error' => $e->getMessage(),
                'file' => is_string($file) ? $file : $file->getClientOriginalName(),
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar la factura: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Escanear múltiples facturas
     *
     * @param array<int|string, UploadedFile|string> $files Array de archivos
     * @return array<string, mixed> Resultados por archivo
     */
    public function scanMultiple(array $files): array
    {
        $results = [];

        foreach ($files as $index => $file) {
            $results[$index] = $this->scanInvoice($file);
        }

        return [
            'success' => true,
            'total' => count($files),
            'processed' => count(array_filter($results, fn($r) => $r['success'])),
            'results' => $results,
        ];
    }

    /**
     * Validar datos extraídos contra base de datos
     *
     * @param array<string, mixed> $data Datos extraídos
     * @return array<string, mixed> Datos validados con sugerencias
     */
    public function validateExtractedData(array $data): array
    {
        $validation = [
            'proveedor' => null,
            'warnings' => [],
            'suggestions' => [],
        ];

        // Buscar proveedor existente por identificación
        if (!empty($data['proveedor']['identificacion'])) {
            $proveedor = \App\Models\Proveedor::where(
                'num_identificacion',
                $data['proveedor']['identificacion']
            )->first();

            if ($proveedor) {
                $validation['proveedor'] = [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'match' => 'identificacion',
                ];
            } else {
                $validation['suggestions'][] = [
                    'type' => 'new_proveedor',
                    'message' => 'Proveedor no encontrado. ¿Desea crearlo?',
                    'data' => $data['proveedor'],
                ];
            }
        }

        // Validar moneda
        if (!empty($data['factura']['moneda'])) {
            if (!in_array($data['factura']['moneda'], ['CRC', 'USD'])) {
                $validation['warnings'][] = 'Moneda no reconocida: ' . $data['factura']['moneda'];
            }
        }

        // Validar totales
        if (!empty($data['lineas']) && !empty($data['totales'])) {
            $sumLineas = array_sum(array_column($data['lineas'], 'subtotal'));
            $totalFactura = $data['totales']['subtotal'] ?? 0;

            if (abs($sumLineas - $totalFactura) > 1) {
                $validation['warnings'][] = sprintf(
                    'Diferencia en totales: suma líneas (%.2f) vs subtotal (%.2f)',
                    $sumLineas,
                    $totalFactura
                );
            }
        }

        return [
            'success' => true,
            'validation' => $validation,
            'data' => $data,
        ];
    }

    /**
     * Construir prompt optimizado para facturas CR
     */
    protected function buildInvoicePrompt(): string
    {
        return <<<PROMPT
Eres un experto en procesamiento de facturas de Costa Rica. Analiza esta imagen de factura y extrae TODOS los datos en formato JSON.

IMPORTANTE:
- Las cédulas jurídicas de Costa Rica tienen formato: X-XXX-XXXXXX
- Las cédulas físicas tienen 9 dígitos
- IVA estándar en Costa Rica es 13%
- Monedas comunes: CRC (colones), USD (dólares)
- Busca el número de factura, que puede aparecer como "Factura", "No.", "Número", "Consecutivo"

Responde ÚNICAMENTE con JSON válido (sin markdown, sin explicaciones) con esta estructura exacta:

{
  "proveedor": {
    "nombre": "string",
    "identificacion": "string (cédula sin guiones)",
    "tipo_identificacion": "01|02|03|04",
    "email": "string|null",
    "telefono": "string|null",
    "direccion": "string|null"
  },
  "factura": {
    "numero": "string",
    "fecha_emision": "YYYY-MM-DD",
    "fecha_vencimiento": "YYYY-MM-DD|null",
    "moneda": "CRC|USD",
    "tipo_cambio": number|null,
    "condicion_venta": "Contado|Crédito",
    "plazo_credito": number|null
  },
  "totales": {
    "subtotal": number,
    "descuento": number,
    "impuesto": number,
    "total": number
  },
  "lineas": [
    {
      "cantidad": number,
      "descripcion": "string",
      "precio_unitario": number,
      "descuento": number,
      "impuesto": number,
      "subtotal": number,
      "codigo_producto": "string|null",
      "unidad_medida": "string|null"
    }
  ],
  "confianza": number,
  "notas": "string|null"
}

Si no puedes leer algún campo, usa null. El campo "confianza" debe ser un número del 0 al 100 indicando qué tan seguro estás de la extracción.
PROMPT;
    }

    /**
     * Preparar archivo para análisis
     */
    protected function prepareFile(UploadedFile|string $file): ?string
    {
        // Si ya es una ruta o URL
        if (is_string($file)) {
            return $file;
        }

        // Validar tipo de archivo
        $allowedMimes = config('openai.features.ocr.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ]);

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            Log::warning('OCR: Tipo de archivo no permitido', [
                'mime' => $file->getMimeType(),
            ]);
            return null;
        }

        // Validar tamaño
        $maxSize = config('openai.features.ocr.max_file_size_mb', 20) * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            Log::warning('OCR: Archivo muy grande', [
                'size' => $file->getSize(),
                'max' => $maxSize,
            ]);
            return null;
        }

        // Si es PDF, convertir primera página a imagen
        if ($file->getMimeType() === 'application/pdf') {
            return $this->convertPdfToImage($file);
        }

        // Guardar temporalmente y retornar base64
        $path = $file->store('temp/ocr', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $mimeType = $file->getMimeType();
        $base64 = base64_encode(file_get_contents($fullPath));

        // Limpiar archivo temporal
        Storage::disk('local')->delete($path);

        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Convertir PDF a imagen
     */
    protected function convertPdfToImage(UploadedFile $file): ?string
    {
        // Verificar si Imagick está disponible
        if (!extension_loaded('imagick')) {
            Log::warning('OCR: Imagick no disponible para convertir PDF');

            // Alternativa: intentar enviar el PDF directamente como base64
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            return "data:application/pdf;base64,{$base64}";
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(200, 200);
            $imagick->readImage($file->getRealPath() . '[0]'); // Primera página
            $imagick->setImageFormat('png');

            $base64 = base64_encode($imagick->getImageBlob());
            $imagick->destroy();

            return "data:image/png;base64,{$base64}";
        } catch (\Exception $e) {
            Log::error('OCR: Error convirtiendo PDF', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parsear respuesta JSON de OpenAI
     *
     * @param string $content
     * @return array<string, mixed>
     */
    protected function parseResponse(string $content): array
    {
        // Limpiar posibles artefactos de markdown
        $content = trim($content);
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('OCR: Error parseando JSON', [
                'error' => json_last_error_msg(),
                'content' => substr($content, 0, 500),
            ]);

            // Intentar extraer JSON del contenido
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        return $data ?? [];
    }

    /**
     * Limpiar archivo temporal
     */
    protected function cleanupTempFile(string $path): void
    {
        // Solo limpiar si es un archivo local temporal
        if (str_starts_with($path, 'temp/') && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Obtener schema esperado
     *
     * @return array<string, mixed>
     */
    public function getSchema(): array
    {
        return $this->invoiceSchema;
    }
}

