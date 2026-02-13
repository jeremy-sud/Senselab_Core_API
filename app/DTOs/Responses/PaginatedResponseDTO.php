<?php

namespace App\DTOs\Responses;

/**
 * DTO para respuestas paginadas
 *
 * Encapsula datos de respuesta paginada estandarizada
 * Fecha de creación: 12 de febrero de 2026
 */
final class PaginatedResponseDTO
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly int $per_page,
        public readonly int $current_page,
        public readonly int $last_page,
        public readonly string $path,
    ) {}

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'total' => $this->total,
                'per_page' => $this->per_page,
                'current_page' => $this->current_page,
                'last_page' => $this->last_page,
                'path' => $this->path,
            ],
        ];
    }
}
