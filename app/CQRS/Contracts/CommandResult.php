<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

/**
 * Class CommandResult
 *
 * Encapsula el resultado de la ejecución de un comando.
 * Proporciona información sobre éxito/fallo y datos adicionales.
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
final readonly class CommandResult
{
    /**
     * @param bool $success Indica si el comando se ejecutó exitosamente
     * @param string|int|null $id ID del recurso creado/modificado (si aplica)
     * @param string|null $message Mensaje descriptivo del resultado
     * @param array<string, mixed> $metadata Datos adicionales del resultado
     * @param array<string, array<int, string>> $errors Errores de validación (si los hay)
     */
    public function __construct(
        public bool $success,
        public string|int|null $id = null,
        public ?string $message = null,
        public array $metadata = [],
        public array $errors = [],
    ) {}

    /**
     * Crea un resultado exitoso.
     *
     * @param string|int|null $id ID del recurso creado/modificado
     * @param string|null $message Mensaje opcional
     * @param array<string, mixed> $metadata Datos adicionales
     * @return self
     */
    public static function success(
        string|int|null $id = null,
        ?string $message = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            id: $id,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Crea un resultado fallido.
     *
     * @param string $message Mensaje de error
     * @param array<string, array<int, string>> $errors Errores de validación
     * @param array<string, mixed> $metadata Datos adicionales
     * @return self
     */
    public static function failure(
        string $message,
        array $errors = [],
        array $metadata = []
    ): self {
        return new self(
            success: false,
            id: null,
            message: $message,
            metadata: $metadata,
            errors: $errors,
        );
    }

    /**
     * Verifica si el comando falló.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return !$this->success;
    }

    /**
     * Convierte el resultado a array para respuestas JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
        ];

        if ($this->id !== null) {
            $result['id'] = $this->id;
        }

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        if (!empty($this->metadata)) {
            $result['data'] = $this->metadata;
        }

        if (!empty($this->errors)) {
            $result['errors'] = $this->errors;
        }

        return $result;
    }
}
