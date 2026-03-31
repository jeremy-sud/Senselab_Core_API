<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class WebhookUpdateDTO
{
    /**
     * @param string|null $nombre
     * @param string|null $url
     * @param array<int, string>|null $eventos
     * @param string|null $descripcion
     * @param int|null $timeout_segundos
     * @param int|null $max_reintentos
     * @param bool|null $activo
     */
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $url = null,
        public readonly ?array $eventos = null,
        public readonly ?string $descripcion = null,
        public readonly ?int $timeout_segundos = null,
        public readonly ?int $max_reintentos = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            url: $request->filled('url') ? $request->string('url')->trim()->toString() : null,
            eventos: $request->has('eventos') ? $request->input('eventos') : null,
            descripcion: $request->has('descripcion') ? ($request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null) : null,
            timeout_segundos: $request->filled('timeout_segundos') ? $request->integer('timeout_segundos') : null,
            max_reintentos: $request->filled('max_reintentos') ? $request->integer('max_reintentos') : null,
            activo: $request->has('activo') ? $request->boolean('activo') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'url' => $this->url,
            'eventos' => $this->eventos,
            'descripcion' => $this->descripcion,
            'timeout_segundos' => $this->timeout_segundos,
            'max_reintentos' => $this->max_reintentos,
            'activo' => $this->activo,
        ], fn ($v) => $v !== null);
    }
}
