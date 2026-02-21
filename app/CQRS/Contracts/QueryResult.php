<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class QueryResult
 *
 * Encapsula el resultado de la ejecución de una query.
 * Soporta resultados únicos, colecciones y paginación.
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
final readonly class QueryResult
{
    /**
     * @param bool $success Indica si la query se ejecutó exitosamente
     * @param mixed $data Los datos retornados
     * @param array<string, mixed> $meta Metadatos adicionales (paginación, totales, etc.)
     * @param string|null $message Mensaje de error (si falló)
     */
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public array $meta = [],
        public ?string $message = null,
    ) {}

    /**
     * Crea un resultado exitoso con un único registro.
     *
     * @param mixed $data El registro encontrado
     * @return self
     */
    public static function found(mixed $data): self
    {
        return new self(
            success: true,
            data: $data,
        );
    }

    /**
     * Crea un resultado cuando no se encontró el registro.
     *
     * @param string $message Mensaje descriptivo
     * @return self
     */
    public static function notFound(string $message = 'Registro no encontrado'): self
    {
        return new self(
            success: false,
            data: null,
            message: $message,
        );
    }

    /**
     * Crea un resultado exitoso con una colección de registros.
     *
     * @param Collection<int|string, mixed>|array<int, mixed> $data La colección de registros
     * @param array<string, mixed> $meta Metadatos adicionales
     * @return self
     */
    public static function collection(Collection|array $data, array $meta = []): self
    {
        return new self(
            success: true,
            data: $data instanceof Collection ? $data : collect($data),
            meta: array_merge(['count' => is_countable($data) ? count($data) : 0], $meta),
        );
    }

    /**
     * Crea un resultado exitoso con datos paginados.
     *
     * @param LengthAwarePaginator<int, mixed> $paginator El paginador de Laravel
     * @return self
     */
    public static function paginated(LengthAwarePaginator $paginator): self
    {
        return new self(
            success: true,
            data: $paginator->items(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        );
    }

    /**
     * Crea un resultado fallido.
     *
     * @param string $message Mensaje de error
     * @return self
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            data: null,
            message: $message,
        );
    }

    /**
     * Verifica si la query no encontró datos.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (!$this->success) {
            return true;
        }

        if ($this->data === null) {
            return true;
        }

        if ($this->data instanceof Collection) {
            return $this->data->isEmpty();
        }

        if (is_array($this->data)) {
            return empty($this->data);
        }

        return false;
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

        if ($this->success) {
            $result['data'] = $this->data instanceof Collection
                ? $this->data->toArray()
                : $this->data;

            if (!empty($this->meta)) {
                $result['meta'] = $this->meta;
            }
        } else {
            $result['message'] = $this->message;
        }

        return $result;
    }
}
