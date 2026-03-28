<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ModeloBusCreateDTO
{
    public function __construct(
        public readonly string $nombre,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
        ];
    }
}
