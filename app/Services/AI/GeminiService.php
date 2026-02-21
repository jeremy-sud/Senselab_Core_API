<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Servicio de integración con Google Gemini API (GRATUITO)
 *
 * Proporciona acceso a modelos Gemini para:
 * - OCR de facturas de proveedores (Vision)
 * - Chatbot asistente ERP
 * - Análisis de texto
 *
 * API GRATUITA - Sin costos de uso
 * Obtener API Key: https://aistudio.google.com/
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class GeminiService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key', '');
        $this->baseUrl = config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->config = config('gemini') ?? [];
    }

    /**
     * Verificar si el servicio está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function chat(string $message, array $context = [], array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Gemini API key no configurada. Obtener gratis en: https://aistudio.google.com/',
            ];
        }

        $model = $options['model'] ?? config('gemini.chatbot.model', 'gemini-2.0-flash');
        
        try {
            // Construir historial de conversación
            $contents = [];
            
            // Agregar contexto del sistema como primer mensaje
            if (!empty($context['system_prompt'])) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => "Instrucciones del sistema: " . $context['system_prompt']]],
                ];
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => 'Entendido. Seguiré estas instrucciones.']],
                ];
            }

            // Agregar historial de conversación
            if (!empty($context['history'])) {
                foreach ($context['history'] as $msg) {
                    $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $msg['content']]],
                    ];
                }
            }

            // Agregar mensaje actual
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]],
            ];

            $response = $this->makeRequest($model, [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? config('gemini.generation.temperature', 0.7),
                    'topP' => config('gemini.generation.top_p', 0.95),
                    'topK' => config('gemini.generation.top_k', 40),
                    'maxOutputTokens' => $options['max_tokens'] ?? config('gemini.generation.max_output_tokens', 2048),
                ],
                'safetySettings' => config('gemini.safety_settings', []),
            ]);

            if (!$response->successful()) {
                return $this->handleError($response);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'content' => $text,
                'model' => $model,
                'provider' => 'gemini',
                'usage' => [
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                ],
                'cost' => 0.00, // ¡GRATIS!
            ];

        } catch (\Exception $e) {
            Log::error('Gemini chat error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Error en la comunicación con Gemini: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     * Analizar imagen usando Gemini Vision
     */
    public function analyzeImage(string $imagePath, string $prompt, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Gemini API key no configurada',
            ];
        }

        $model = $options['model'] ?? config('gemini.ocr.model', 'gemini-1.5-flash');

        try {
            // Leer y codificar imagen
            $imageData = $this->prepareImageData($imagePath);
            if (!$imageData) {
                return [
                    'success' => false,
                    'error' => 'No se pudo leer la imagen',
                ];
            }

            $response = $this->makeRequest($model, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $imageData['mime_type'],
                                    'data' => $imageData['base64'],
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2, // Bajo para OCR
                    'maxOutputTokens' => 4096,
                ],
            ]);

            if (!$response->successful()) {
                return $this->handleError($response);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'content' => $text,
                'model' => $model,
                'provider' => 'gemini',
                'cost' => 0.00,
            ];

        } catch (\Exception $e) {
            Log::error('Gemini vision error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Error analizando imagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     * Nota: Gemini no tiene embeddings nativos, usamos respuesta estructurada
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateEmbedding(string $text, array $options = []): array
    {
        // Gemini no tiene API de embeddings gratuita comparable
        // Retornamos hash simple para búsquedas básicas
        return [
            'success' => true,
            'embedding' => [], // No disponible en tier gratuito
            'method' => 'not_available',
            'note' => 'Embeddings no disponibles en Gemini free tier. Usar búsqueda por texto.',
        ];
    }

    /**
     * {@inheritdoc}
     * Extraer datos estructurados de contenido
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function extractData(string $content, string $schema, array $options = []): array
    {
        $prompt = <<<PROMPT
Extrae la información del siguiente contenido y devuélvela en formato JSON válido.

Esquema esperado:
{$schema}

Contenido a analizar:
{$content}

IMPORTANTE: Responde ÚNICAMENTE con el JSON, sin markdown ni explicaciones.
PROMPT;

        $response = $this->chat($prompt, [], [
            'temperature' => 0.1, // Muy bajo para extracción precisa
            'max_tokens' => 4096,
        ]);

        if (!$response['success']) {
            return $response;
        }

        // Intentar parsear JSON
        $jsonText = $response['content'];
        
        // Limpiar posibles marcadores de código
        $jsonText = preg_replace('/^```json\s*/i', '', $jsonText);
        $jsonText = preg_replace('/\s*```$/i', '', $jsonText);
        $jsonText = trim($jsonText);

        $parsed = json_decode($jsonText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'No se pudo parsear la respuesta como JSON',
                'raw_response' => $response['content'],
            ];
        }

        return [
            'success' => true,
            'data' => $parsed,
            'model' => $response['model'],
            'cost' => 0.00,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function estimateCost(string $model, int $tokens): float
    {
        // ¡Gemini es GRATIS!
        return 0.00;
    }

    /**
     * Verificar si la API está disponible
     */
    public function isAvailable(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/models?key={$this->apiKey}");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Listar modelos disponibles
     *
     * @return array<int, mixed>
     */
    public function listModels(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/models?key={$this->apiKey}");

            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return array_map(fn($m) => [
                    'name' => $m['name'] ?? '',
                    'displayName' => $m['displayName'] ?? '',
                    'description' => $m['description'] ?? '',
                    'inputTokenLimit' => $m['inputTokenLimit'] ?? 0,
                    'outputTokenLimit' => $m['outputTokenLimit'] ?? 0,
                ], $models);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error listing Gemini models', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ========== MÉTODOS PRIVADOS ==========

    /**
     * Realizar petición a la API de Gemini
     *
     * @param array<string, mixed> $payload
     */
    protected function makeRequest(string $model, array $payload): \Illuminate\Http\Client\Response
    {
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        return Http::timeout(60)
            ->retry(2, 1000)
            ->post($url, $payload);
    }

    /**
     * Preparar datos de imagen para la API
     *
     * @return array<string, mixed>|null
     */
    protected function prepareImageData(string $imagePath): ?array
    {
        // Si es una URL
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(30)->get($imagePath);
                if ($response->successful()) {
                    $content = $response->body();
                    $mimeType = $response->header('Content-Type') ?: 'image/jpeg';
                    return [
                        'base64' => base64_encode($content),
                        'mime_type' => explode(';', $mimeType)[0],
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error downloading image', ['url' => $imagePath, 'error' => $e->getMessage()]);
            }
            return null;
        }

        // Si es un archivo local
        if (file_exists($imagePath)) {
            $content = file_get_contents($imagePath);
            $mimeType = mime_content_type($imagePath);

            // Convertir PDF a imagen si es necesario
            if ($mimeType === 'application/pdf') {
                return $this->convertPdfToImage($imagePath);
            }

            return [
                'base64' => base64_encode($content),
                'mime_type' => $mimeType,
            ];
        }

        // Si ya es base64
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $imagePath, $matches)) {
            return [
                'base64' => $matches[2],
                'mime_type' => $matches[1],
            ];
        }

        return null;
    }

    /**
     * Convertir PDF a imagen (primera página)
     *
     * @return array<string, mixed>|null
     */
    protected function convertPdfToImage(string $pdfPath): ?array
    {
        // Verificar si Imagick está disponible
        if (!extension_loaded('imagick')) {
            Log::warning('Imagick extension not available for PDF conversion');
            
            // Fallback: leer PDF como texto si es posible
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]'); // Primera página
            $imagick->setImageFormat('png');
            
            $imageData = $imagick->getImageBlob();
            $imagick->destroy();

            return [
                'base64' => base64_encode($imageData),
                'mime_type' => 'image/png',
            ];
        } catch (\Exception $e) {
            Log::error('Error converting PDF to image', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Manejar errores de la API
     *
     * @return array<string, mixed>
     */
    protected function handleError(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->json();
        $errorMessage = $body['error']['message'] ?? $response->body();
        $errorCode = $body['error']['code'] ?? $response->status();

        // Mensajes amigables para errores comunes
        $friendlyMessages = [
            400 => 'Solicitud inválida. Verifica los parámetros.',
            401 => 'API key inválida. Verifica tu GEMINI_API_KEY.',
            403 => 'Acceso denegado. La API key no tiene permisos.',
            429 => 'Límite de rate excedido. Espera un momento e intenta de nuevo.',
            500 => 'Error interno de Gemini. Intenta más tarde.',
            503 => 'Servicio no disponible temporalmente.',
        ];

        $message = $friendlyMessages[$errorCode] ?? $errorMessage;

        Log::warning('Gemini API error', [
            'code' => $errorCode,
            'message' => $errorMessage,
        ]);

        return [
            'success' => false,
            'error' => $message,
            'error_code' => $errorCode,
        ];
    }

    /**
     * {@inheritdoc}
     * Generar embeddings para texto (requerido por AIServiceInterface)
     */
    public function embeddings(string|array $text, array $options = []): array
    {
        // Gemini free tier no tiene embeddings nativos
        // Usamos generateEmbedding internamente
        if (is_array($text)) {
            $results = [];
            foreach ($text as $t) {
                $results[] = $this->generateEmbedding($t, $options);
            }
            return [
                'success' => true,
                'embeddings' => $results,
                'note' => 'Embeddings no disponibles en Gemini free tier',
            ];
        }

        return $this->generateEmbedding($text, $options);
    }

    /**
     * {@inheritdoc}
     * Obtener estadísticas de uso
     */
    public function getUsageStats(): array
    {
        // Gemini free tier tiene límites fijos
        return [
            'requests_per_minute' => 15,
            'requests_per_day' => 1500,
            'tokens_per_minute' => 32000,
            'model' => $this->config['model'] ?? 'gemini-2.0-flash',
            'tier' => 'free',
            'note' => 'Estadísticas de límites del tier gratuito de Gemini',
            'documentation' => 'https://ai.google.dev/pricing',
        ];
    }
}

