<?php

namespace App\Services\AI;

use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * Servicio de integración con OpenAI API
 *
 * Proporciona acceso a GPT-4, Vision, y Embeddings para:
 * - OCR de facturas de proveedores
 * - Chatbot asistente ERP
 * - Predicciones de demanda
 * - Generación de contenido
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class OpenAIService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected ?string $organization = null;
    /** @var array<string, mixed> */
    protected array $config = [];
    
    /** @var array<string, mixed> */
    protected array $usageStats = [
        'requests' => 0,
        'tokens_used' => 0,
        'estimated_cost' => 0.0,
    ];

    public function __construct()
    {
        $this->apiKey = config('openai.api_key') ?? '';
        $this->organization = config('openai.organization');
        $this->config = config('openai') ?? [];
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
        $model = $options['model'] ?? config('openai.models.chat', 'gpt-4o');
        $temperature = $options['temperature'] ?? config('openai.features.chatbot.temperature', 0.7);
        $maxTokens = $options['max_tokens'] ?? config('openai.features.chatbot.max_tokens', 2048);

        $messages = [];

        // System prompt si existe
        if (!empty($context['system'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $context['system'],
            ];
        }

        // Historial de conversación
        if (!empty($context['history']) && is_array($context['history'])) {
            foreach ($context['history'] as $msg) {
                $messages[] = $msg;
            }
        }

        // Mensaje actual del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        try {
            $response = $this->makeRequest('chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            $this->trackUsage($response);

            return [
                'success' => true,
                'message' => $response['choices'][0]['message']['content'] ?? '',
                'model' => $model,
                'usage' => $response['usage'] ?? null,
                'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI chat error', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);

            return [
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Error en la comunicación con el servicio de IA',
                'model' => $model,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeImage(string $imagePath, string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? config('openai.models.vision', 'gpt-4o');
        $detail = $options['detail'] ?? config('openai.features.ocr.detail', 'high');

        // Preparar contenido de imagen
        $imageContent = $this->prepareImageContent($imagePath, $detail);

        if (!$imageContent) {
            return [
                'success' => false,
                'error' => 'No se pudo procesar la imagen',
            ];
        }

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt,
                    ],
                    $imageContent,
                ],
            ],
        ];

        try {
            $response = $this->makeRequest('chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? 4096,
            ]);

            $this->trackUsage($response);

            return [
                'success' => true,
                'content' => $response['choices'][0]['message']['content'] ?? '',
                'model' => $model,
                'usage' => $response['usage'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI vision error', [
                'message' => $e->getMessage(),
                'image' => $imagePath,
            ]);

            return [
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Error analizando imagen',
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function embeddings(string|array $text, array $options = []): array
    {
        $model = $options['model'] ?? config('openai.models.embeddings', 'text-embedding-3-small');
        $input = is_array($text) ? $text : [$text];

        try {
            $response = $this->makeRequest('embeddings', [
                'model' => $model,
                'input' => $input,
            ]);

            $this->trackUsage($response);

            $embeddings = array_map(
                fn($item) => $item['embedding'],
                $response['data'] ?? []
            );

            return [
                'success' => true,
                'embeddings' => is_array($text) ? $embeddings : ($embeddings[0] ?? []),
                'model' => $model,
                'usage' => $response['usage'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI embeddings error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Error generando embeddings',
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Verificar en cache si ya validamos recientemente
        $cacheKey = 'openai_available';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->get($this->baseUrl . '/models');

            $available = $response->successful();
            Cache::put($cacheKey, $available, now()->addMinutes(5));

            return $available;
        } catch (\Exception $e) {
            Cache::put($cacheKey, false, now()->addMinute());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUsageStats(): array
    {
        return $this->usageStats;
    }

    /**
     * Realizar request a OpenAI API
     *
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function makeRequest(string $endpoint, array $data): array
    {
        $timeout = config('openai.request.timeout', 60);
        $maxRetries = config('openai.request.max_retries', 3);
        $retryDelay = config('openai.request.retry_delay', 1);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->organization) {
            $headers['OpenAI-Organization'] = $this->organization;
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->withHeaders($headers)
                    ->timeout($timeout)
                    ->post($this->baseUrl . '/' . $endpoint, $data);

                if ($response->successful()) {
                    $this->usageStats['requests']++;

                    if (config('openai.logging.enabled')) {
                        Log::channel(config('openai.logging.channel', 'stack'))->info('OpenAI request', [
                            'endpoint' => $endpoint,
                            'model' => $data['model'] ?? 'unknown',
                            'status' => $response->status(),
                        ]);
                    }

                    return $response->json();
                }

                // Error de rate limit - esperar y reintentar
                if ($response->status() === 429) {
                    $attempt++;
                    sleep($retryDelay * $attempt);
                    continue;
                }

                throw AIServiceException::apiError('OpenAI', $response->json()['error']['message'] ?? $response->body());
            } catch (RequestException $e) {
                $lastException = $e;
                $attempt++;
                sleep($retryDelay * $attempt);
            }
        }

        throw $lastException ?? AIServiceException::apiError('OpenAI', 'Max retries exceeded');
    }

    /**
     * Preparar contenido de imagen para Vision API
     *
     * @param string $imagePath
     * @param string $detail
     * @return array<string, mixed>|null
     */
    protected function prepareImageContent(string $imagePath, string $detail = 'auto'): ?array
    {
        // Si es URL, usar directamente
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imagePath,
                    'detail' => $detail,
                ],
            ];
        }

        // Si es archivo local, convertir a base64
        if (file_exists($imagePath)) {
            $mimeType = mime_content_type($imagePath);
            $base64 = base64_encode(file_get_contents($imagePath));

            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$base64}",
                    'detail' => $detail,
                ],
            ];
        }

        // Si ya es base64, usar directamente
        if (str_starts_with($imagePath, 'data:image')) {
            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imagePath,
                    'detail' => $detail,
                ],
            ];
        }

        return null;
    }

    /**
     * Trackear uso para estadísticas
     *
     * @param array<string, mixed> $response
     */
    protected function trackUsage(array $response): void
    {
        if (isset($response['usage'])) {
            $usage = $response['usage'];
            $model = $response['model'] ?? 'gpt-4o';

            $this->usageStats['tokens_used'] += ($usage['total_tokens'] ?? 0);

            // Calcular costo estimado
            $costs = config('openai.costs.' . $model, [
                'input' => 0.005,
                'output' => 0.015,
            ]);

            $inputCost = (($usage['prompt_tokens'] ?? 0) / 1000) * ($costs['input'] ?? 0);
            $outputCost = (($usage['completion_tokens'] ?? 0) / 1000) * ($costs['output'] ?? 0);

            $this->usageStats['estimated_cost'] += ($inputCost + $outputCost);
        }
    }

    /**
     * Generar JSON estructurado a partir de texto
     *
     * @param string $text
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function parseToJson(string $text, array $schema, array $options = []): array
    {
        $model = $options['model'] ?? config('openai.models.mini', 'gpt-4o-mini');

        $systemPrompt = "Eres un asistente que extrae información estructurada.
        Responde ÚNICAMENTE con JSON válido siguiendo exactamente este schema:
        " . json_encode($schema, JSON_PRETTY_PRINT);

        return $this->chat($text, ['system' => $systemPrompt], [
            'model' => $model,
            'temperature' => 0.1, // Más determinístico
        ]);
    }

    /**
     * Clasificar texto en categorías predefinidas
     *
     * @param string $text
     * @param array<int, string> $categories
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function classify(string $text, array $categories, array $options = []): array
    {
        $model = $options['model'] ?? config('openai.models.mini', 'gpt-4o-mini');

        $prompt = "Clasifica el siguiente texto en una de estas categorías: " .
            implode(', ', $categories) . "\n\nTexto: " . $text .
            "\n\nResponde ÚNICAMENTE con el nombre de la categoría.";

        $result = $this->chat($prompt, [], [
            'model' => $model,
            'temperature' => 0,
            'max_tokens' => 50,
        ]);

        if ($result['success']) {
            $category = trim($result['message']);
            return [
                'success' => true,
                'category' => $category,
                'confidence' => in_array($category, $categories) ? 1.0 : 0.5,
            ];
        }

        return $result;
    }
}

