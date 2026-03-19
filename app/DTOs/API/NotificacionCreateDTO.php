<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class NotificacionCreateDTO
{
    /**
     * @param array<mixed>|null $datos
     */
    public function __construct(
        public readonly int $usuario_id,
        public readonly string $tipo,
        public readonly string $categoria,
        public readonly string $prioridad,
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly ?string $icono = null,
        public readonly ?string $color = null,
        public readonly ?array $datos = null,
        public readonly ?string $entidad_tipo = null,
        public readonly ?int $entidad_id = null,
        public readonly ?string $accion_url = null,
        public readonly ?string $accion_texto = null,
        public readonly ?string $canal = null,
        public readonly ?string $expires_at = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            usuario_id: (int) $request->input('usuario_id'),
            tipo: (string) $request->input('tipo'),
            categoria: (string) $request->input('categoria'),
            prioridad: (string) $request->input('prioridad'),
            titulo: $request->string('titulo')->trim()->toString(),
            mensaje: $request->string('mensaje')->trim()->toString(),
            icono: $request->filled('icono') ? $request->string('icono')->trim()->toString() : null,
            color: $request->filled('color') ? $request->string('color')->trim()->toString() : null,
            datos: $request->input('datos'),
            entidad_tipo: $request->filled('entidad_tipo') ? (string) $request->input('entidad_tipo') : null,
            entidad_id: $request->filled('entidad_id') ? (int) $request->input('entidad_id') : null,
            accion_url: $request->filled('accion_url') ? (string) $request->input('accion_url') : null,
            accion_texto: $request->filled('accion_texto') ? $request->string('accion_texto')->trim()->toString() : null,
            canal: $request->filled('canal') ? (string) $request->input('canal') : null,
            expires_at: $request->input('expires_at'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'prioridad' => $this->prioridad,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'icono' => $this->icono,
            'color' => $this->color,
            'datos' => $this->datos,
            'entidad_tipo' => $this->entidad_tipo,
            'entidad_id' => $this->entidad_id,
            'accion_url' => $this->accion_url,
            'accion_texto' => $this->accion_texto,
            'canal' => $this->canal,
            'expires_at' => $this->expires_at,
        ];
    }
}
