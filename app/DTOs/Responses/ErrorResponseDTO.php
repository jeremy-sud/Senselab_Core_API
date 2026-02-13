<?php

namespace App\DTOs\Responses;

/**
 * DTO para respuestas de error
 * 
 * Encapsula error de respuesta estandarizada
 * Fecha de creación: 12 de febrero de 2026
 */
final class ErrorResponseDTO
{
    public function __construct(
        public readonly string $message,
        public readonly int $status,
        public readonly ?string $code = null,
        public readonly array $errors = [],
        public readonly ?string $trace = null,
    ) {}

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return array_filter([
            'message' => $this->message,
            'status' => $this->status,
            'code' => $this->code,
            'errors' => $this->errors ?: null,
            'trace' => $this->trace,
        ], function ($value) {
            return $value !== null && $value !== [];
        });
    }
}
