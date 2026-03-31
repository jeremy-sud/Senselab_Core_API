<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class WebhookCreateDTO
{
    /**
     * @param string $nombre
     * @param string $url
     * @param array<int, string> $eventos
     * @param string|null $descripcion
     * @param int $timeout_segundos
     * @param int $max_reintentos
     * @param bool $activo
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $url,
        public readonly array $eventos,
        public readonly ?string $descripcion = null,
        public readonly int $timeout_segundos = 30,
        public readonly int $max_reintentos = 3,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim(),
            url: $request->string('url')->trim(),
            eventos: $request->input('eventos', []),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            timeout_segundos: $request->integer('timeout_segundos', 30),
            max_reintentos: $request->integer('max_reintentos', 3),
            activo: $request->boolean('activo', true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'url' => $this->url,
            'eventos' => $this->eventos,
            'descripcion' => $this->descripcion,
            'timeout_segundos' => $this->timeout_segundos,
            'max_reintentos' => $this->max_reintentos,
            'activo' => $this->activo,
        ];
    }
}
