<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Generación Automática de Contenido
 *
 * Genera automáticamente:
 * - Emails de recordatorio de cobro
 * - Notificaciones personalizadas
 * - Mensajes de WhatsApp
 * - Cartas de cobro formal
 *
 * Usa Gemini (GRATUITO) por defecto
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class ContentGeneratorService
{
    protected AIServiceInterface $aiService;
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $empresaInfo = null;

    /**
     * Plantillas base por tipo de contenido
     * @var array<string, array<string, mixed>>
     */
    protected array $templates = [
        'recordatorio_amigable' => [
            'tono' => 'amigable y cordial',
            'urgencia' => 'baja',
            'dias_mora' => [1, 15],
        ],
        'recordatorio_formal' => [
            'tono' => 'profesional y formal',
            'urgencia' => 'media',
            'dias_mora' => [16, 30],
        ],
        'cobro_urgente' => [
            'tono' => 'serio pero respetuoso',
            'urgencia' => 'alta',
            'dias_mora' => [31, 60],
        ],
        'cobro_legal' => [
            'tono' => 'formal y legal',
            'urgencia' => 'critica',
            'dias_mora' => [61, 999],
        ],
    ];

    public function __construct(?AIServiceInterface $aiService = null)
    {
        if ($aiService) {
            $this->aiService = $aiService;
        } elseif (!empty(config('gemini.api_key'))) {
            $this->aiService = app(GeminiService::class);
        } else {
            $this->aiService = app(OpenAIService::class);
        }
    }

    /**
     * @param array<string, mixed> $empresaInfo
     */
    public function setEmpresa(array $empresaInfo): self
    {
        $this->empresaInfo = $empresaInfo;
        return $this;
    }

    /**
     * Generar email de recordatorio de cobro
     *
     * @param array<string, mixed> $cuentaCobrar
     * @return array<string, mixed>
     */
    public function generatePaymentReminder(array $cuentaCobrar, string $tipo = 'auto'): array
    {
        // Determinar tipo automáticamente si es 'auto'
        if ($tipo === 'auto') {
            $diasMora = $cuentaCobrar['dias_mora'] ?? 0;
            $tipo = $this->determineReminderType($diasMora);
        }

        $template = $this->templates[$tipo] ?? $this->templates['recordatorio_amigable'];
        $empresa = $this->empresaInfo ?? ['nombre' => 'La Empresa'];

        // Preparar variables para el heredoc (no soporta operadores)
        $empresaNombre = $empresa['nombre'] ?? 'La Empresa';
        $empresaTelefono = $empresa['telefono'] ?? 'No disponible';
        $empresaEmail = $empresa['email'] ?? 'No disponible';
        $clienteNombre = $cuentaCobrar['cliente_nombre'] ?? 'Cliente';
        $clienteEmail = $cuentaCobrar['cliente_email'] ?? 'N/A';
        $numeroFactura = $cuentaCobrar['numero_factura'] ?? 'N/A';
        $saldoPendiente = $cuentaCobrar['saldo_pendiente'] ?? 0;
        $fechaVencimiento = $cuentaCobrar['fecha_vencimiento'] ?? 'N/A';
        $diasMoraVal = $cuentaCobrar['dias_mora'] ?? 0;
        $templateTono = $template['tono'] ?? 'amigable';
        $templateUrgencia = $template['urgencia'] ?? 'baja';

        $prompt = <<<PROMPT
Genera un email profesional de recordatorio de cobro para Costa Rica.

DATOS DE LA EMPRESA:
- Nombre: {$empresaNombre}
- Teléfono: {$empresaTelefono}
- Email: {$empresaEmail}

DATOS DEL CLIENTE:
- Nombre: {$clienteNombre}
- Email: {$clienteEmail}

DATOS DE LA FACTURA:
- Número: {$numeroFactura}
- Monto pendiente: ₡{$saldoPendiente}
- Fecha vencimiento: {$fechaVencimiento}
- Días en mora: {$diasMoraVal}

INSTRUCCIONES:
- Tono: {$templateTono}
- Urgencia: {$templateUrgencia}
- Incluir: saludo, referencia a factura, monto, opciones de pago, despedida
- NO incluir amenazas legales a menos que sea tipo 'cobro_legal'
- Usar colones costarricenses (₡)
- Ser conciso pero completo

Genera el email con:
1. Asunto del email
2. Cuerpo del email (HTML simple)
3. Versión texto plano

Formato de respuesta JSON:
{
    "asunto": "...",
    "cuerpo_html": "...",
    "cuerpo_texto": "..."
}
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ]);

        if (!$result['success']) {
            return $result;
        }

        // Parsear respuesta JSON
        $content = $result['content'] ?? '';
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $parsed = json_decode(trim($content), true);

        if (!$parsed) {
            // Fallback: usar contenido como texto
            return [
                'success' => true,
                'tipo' => $tipo,
                'asunto' => "Recordatorio: Factura {$cuentaCobrar['numero_factura']} pendiente",
                'cuerpo_html' => nl2br($content),
                'cuerpo_texto' => strip_tags($content),
                'generado_por' => 'ia_fallback',
            ];
        }

        return [
            'success' => true,
            'tipo' => $tipo,
            'asunto' => $parsed['asunto'] ?? '',
            'cuerpo_html' => $parsed['cuerpo_html'] ?? '',
            'cuerpo_texto' => $parsed['cuerpo_texto'] ?? '',
            'generado_por' => 'ia',
            'provider' => $result['provider'] ?? 'unknown',
        ];
    }

    /**
     * Generar mensaje de WhatsApp para cobro
     *
     * @param array<string, mixed> $cuentaCobrar
     * @return array<string, mixed>
     */
    public function generateWhatsAppMessage(array $cuentaCobrar, string $tipo = 'auto'): array
    {
        if ($tipo === 'auto') {
            $diasMora = $cuentaCobrar['dias_mora'] ?? 0;
            $tipo = $this->determineReminderType($diasMora);
        }

        $template = $this->templates[$tipo] ?? $this->templates['recordatorio_amigable'];
        $empresa = $this->empresaInfo ?? ['nombre' => 'La Empresa'];

        $prompt = <<<PROMPT
Genera un mensaje de WhatsApp corto y profesional para recordatorio de cobro.

Cliente: {$cuentaCobrar['cliente_nombre']}
Factura: {$cuentaCobrar['numero_factura']}
Monto: ₡{$cuentaCobrar['saldo_pendiente']}
Días mora: {$cuentaCobrar['dias_mora']}
Empresa: {$empresa['nombre']}
Tono: {$template['tono']}

REGLAS:
- Máximo 500 caracteres
- Usar emojis moderadamente (1-2)
- Incluir saludo, monto y forma de contacto
- Ser directo pero respetuoso
- Usar formato WhatsApp (negritas con *)

Responde SOLO con el mensaje, sin explicaciones.
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.7,
            'max_tokens' => 300,
        ]);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'tipo' => $tipo,
            'mensaje' => trim($result['content'] ?? ''),
            'caracteres' => strlen($result['content'] ?? ''),
            'generado_por' => 'ia',
        ];
    }

    /**
     * Generar carta formal de cobro (PDF)
     *
     * @param array<string, mixed> $cuentaCobrar
     * @return array<string, mixed>
     */
    public function generateFormalLetter(array $cuentaCobrar): array
    {
        $empresa = $this->empresaInfo ?? ['nombre' => 'La Empresa', 'cedula' => ''];

        // Preparar variables para heredoc
        $empresaNombre = $empresa['nombre'] ?? 'La Empresa';
        $empresaCedula = $empresa['cedula'] ?? '';
        $empresaDireccion = $empresa['direccion'] ?? 'San José, Costa Rica';
        $empresaTelefono = $empresa['telefono'] ?? '';
        $clienteNombre = $cuentaCobrar['cliente_nombre'] ?? 'Cliente';
        $clienteCedula = $cuentaCobrar['cliente_cedula'] ?? 'No especificada';
        $numeroFactura = $cuentaCobrar['numero_factura'] ?? 'N/A';
        $fechaEmision = $cuentaCobrar['fecha_emision'] ?? 'N/A';
        $fechaVencimiento = $cuentaCobrar['fecha_vencimiento'] ?? 'N/A';
        $montoOriginal = $cuentaCobrar['monto_original'] ?? 0;
        $saldoPendiente = $cuentaCobrar['saldo_pendiente'] ?? 0;
        $diasMora = $cuentaCobrar['dias_mora'] ?? 0;
        $fechaActual = $this->getCurrentDate();

        $prompt = <<<PROMPT
Genera una carta formal de cobro para imprimir, siguiendo el formato legal de Costa Rica.

DATOS EMPRESA EMISORA:
- Razón Social: {$empresaNombre}
- Cédula Jurídica: {$empresaCedula}
- Dirección: {$empresaDireccion}
- Teléfono: {$empresaTelefono}

DATOS DEL DEUDOR:
- Nombre/Razón Social: {$clienteNombre}
- Identificación: {$clienteCedula}

DATOS DE LA DEUDA:
- Número de Factura: {$numeroFactura}
- Fecha Emisión: {$fechaEmision}
- Fecha Vencimiento: {$fechaVencimiento}
- Monto Original: ₡{$montoOriginal}
- Saldo Pendiente: ₡{$saldoPendiente}
- Días en Mora: {$diasMora}

INSTRUCCIONES:
1. Formato de carta formal con membrete
2. Fecha actual: {$fechaActual}
3. Incluir referencia a las condiciones de venta originales
4. Mencionar posibles intereses por mora si aplica
5. Dar plazo de 8 días hábiles para regularizar
6. Mencionar posibles acciones legales de manera profesional
7. Incluir datos bancarios para pago (placeholder)

Responde en formato JSON:
{
    "fecha": "...",
    "referencia": "...",
    "destinatario": "...",
    "asunto": "...",
    "cuerpo": "...",
    "firma": "...",
    "pie_pagina": "..."
}
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]);

        if (!$result['success']) {
            return $result;
        }

        $content = $result['content'] ?? '';
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $parsed = json_decode(trim($content), true);

        return [
            'success' => true,
            'tipo' => 'carta_formal',
            'contenido' => $parsed ?? ['cuerpo' => $content],
            'generado_por' => 'ia',
        ];
    }

    /**
     * Generar notificación push/in-app
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function generateNotification(string $tipo, array $datos): array
    {
        $prompts = [
            'factura_vencida' => "Genera una notificación corta (máx 100 chars) para alertar que la factura {$datos['numero']} está vencida. Monto: ₡{$datos['monto']}. Tono urgente pero profesional.",
            'pago_recibido' => "Genera una notificación corta (máx 100 chars) confirmando recepción de pago de ₡{$datos['monto']} del cliente {$datos['cliente']}. Tono positivo.",
            'stock_bajo' => "Genera una notificación corta (máx 100 chars) alertando stock bajo del producto {$datos['producto']}. Stock actual: {$datos['stock']} unidades. Tono informativo.",
            'meta_alcanzada' => "Genera una notificación corta (máx 100 chars) celebrando que se alcanzó la meta de ventas. Monto: ₡{$datos['monto']}. Usar emoji celebratorio.",
        ];

        $prompt = $prompts[$tipo] ?? "Genera una notificación corta sobre: " . json_encode($datos);
        $prompt .= "\n\nResponde SOLO con el texto de la notificación, sin explicaciones.";

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.8,
            'max_tokens' => 150,
        ]);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'tipo' => $tipo,
            'titulo' => $this->generateNotificationTitle($tipo),
            'mensaje' => trim($result['content'] ?? ''),
            'generado_por' => 'ia',
        ];
    }

    /**
     * Generar descripción de producto para catálogo
     *
     * @param array<string, mixed> $producto
     * @return array<string, mixed>
     */
    public function generateProductDescription(array $producto): array
    {
        // Preparar variables para heredoc
        $productoNombre = $producto['nombre'] ?? 'Producto';
        $productoCategoria = $producto['categoria'] ?? 'General';
        $productoCaracteristicas = $producto['caracteristicas'] ?? 'No especificadas';
        $productoPrecio = $producto['precio'] ?? 'No especificado';

        $prompt = <<<PROMPT
Genera una descripción comercial atractiva para el siguiente producto:

Nombre: {$productoNombre}
Categoría: {$productoCategoria}
Características: {$productoCaracteristicas}
Precio: ₡{$productoPrecio}

INSTRUCCIONES:
- Descripción de 2-3 oraciones (máximo 200 caracteres)
- Destacar beneficios, no solo características
- Tono profesional pero atractivo
- Apto para e-commerce o catálogo

Responde SOLO con la descripción, sin explicaciones.
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.8,
            'max_tokens' => 200,
        ]);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'descripcion' => trim($result['content'] ?? ''),
            'producto_id' => $producto['id'] ?? null,
            'generado_por' => 'ia',
        ];
    }

    /**
     * Generar múltiples recordatorios en lote
     *
     * @param array<int, array<string, mixed>> $cuentasCobrar
     * @return array<string, mixed>
     */
    public function generateBatchReminders(array $cuentasCobrar, string $canal = 'email'): array
    {
        $results = [];
        $errors = [];

        foreach ($cuentasCobrar as $cuenta) {
            try {
                if ($canal === 'email') {
                    $result = $this->generatePaymentReminder($cuenta);
                } elseif ($canal === 'whatsapp') {
                    $result = $this->generateWhatsAppMessage($cuenta);
                } else {
                    $result = $this->generatePaymentReminder($cuenta);
                }

                if ($result['success']) {
                    $results[] = array_merge($result, [
                        'cuenta_id' => $cuenta['id'] ?? null,
                        'cliente_nombre' => $cuenta['cliente_nombre'],
                    ]);
                } else {
                    $errors[] = [
                        'cuenta_id' => $cuenta['id'] ?? null,
                        'error' => $result['error'] ?? 'Error desconocido',
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'cuenta_id' => $cuenta['id'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total_procesados' => count($cuentasCobrar),
            'exitosos' => count($results),
            'fallidos' => count($errors),
            'resultados' => $results,
            'errores' => $errors,
        ];
    }

    // ========== MÉTODOS AUXILIARES ==========

    protected function determineReminderType(int $diasMora): string
    {
        foreach ($this->templates as $tipo => $config) {
            if ($diasMora >= $config['dias_mora'][0] && $diasMora <= $config['dias_mora'][1]) {
                return $tipo;
            }
        }
        return 'recordatorio_amigable';
    }

    protected function generateNotificationTitle(string $tipo): string
    {
        return match ($tipo) {
            'factura_vencida' => '⚠️ Factura Vencida',
            'pago_recibido' => '✅ Pago Recibido',
            'stock_bajo' => '📦 Stock Bajo',
            'meta_alcanzada' => '🎉 ¡Meta Alcanzada!',
            default => '📢 Notificación',
        };
    }

    protected function getCurrentDate(): string
    {
        $date = Carbon::now();
        $date->setLocale('es');
        return $date->isoFormat('D [de] MMMM [de] YYYY');
    }
}

