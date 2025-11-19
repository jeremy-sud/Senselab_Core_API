<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'nombre_completo' => trim("{$this->nombre} {$this->primer_apellido} {$this->segundo_apellido}"),
            'nombre' => $this->nombre,
            'primer_apellido' => $this->primer_apellido,
            'segundo_apellido' => $this->segundo_apellido,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'edad' => $this->fecha_nacimiento ? \Carbon\Carbon::parse($this->fecha_nacimiento)->age : null,
            'genero' => $this->genero,
            'fecha_contratacion' => $this->fecha_contratacion,
            'antiguedad_anos' => $this->fecha_contratacion ? \Carbon\Carbon::parse($this->fecha_contratacion)->diffInYears(now()) : null,
            'cargo' => $this->whenLoaded('cargo', function () {
                return [
                    'id' => $this->cargo->id,
                    'nombre' => $this->cargo->nombre
                ];
            }),
            'cargo_id' => $this->cargo_id,
            'salario' => (float) $this->salario,
            'salario_formateado' => number_format($this->salario, 2, '.', ','),
            'usuario' => $this->whenLoaded('usuario', function () {
                return $this->usuario ? [
                    'id' => $this->usuario->id,
                    'nombre_usuario' => $this->usuario->nombre_usuario,
                    'email' => $this->usuario->email
                ] : null;
            }),
            'usuario_id' => $this->usuario_id,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
