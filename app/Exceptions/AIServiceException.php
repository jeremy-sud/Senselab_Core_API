<?php

namespace App\Exceptions;

/**
 * Excepción para servicios de inteligencia artificial.
 *
 * FASE 15: Excepciones tipadas para módulo AI (Gemini, OpenAI, OCR).
 */
class AIServiceException extends DomainException
{
    public static function apiError(string $provider, string $detail, ?\Throwable $previous = null): self
    {
        return new self("{$provider} API error: {$detail}", 502, 0, $previous);
    }

    public static function modeloNoDisponible(string $provider, string $modelo): self
    {
        return new self("El modelo '{$modelo}' no está disponible en {$provider}", 503);
    }

    public static function cuotaExcedida(string $provider): self
    {
        return new self("Cuota de API excedida para {$provider}", 429);
    }

    public static function configurationError(string $provider, string $detail): self
    {
        return new self("Error de configuración {$provider}: {$detail}", 500);
    }
}
