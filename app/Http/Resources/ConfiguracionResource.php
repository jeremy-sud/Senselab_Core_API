<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Configuración del Sistema
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ConfiguracionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'clave' => $this->clave,
            'valor' => $this->valor,
            'valor_formateado' => $this->obtenerValorFormateado(),
            'tipo_dato' => $this->tipo_dato,
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }

    private function obtenerValorFormateado()
    {
        switch ($this->tipo_dato) {
            case 'numero':
                return is_numeric($this->valor) ? (float) $this->valor : $this->valor;
            case 'booleano':
                return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($this->valor, true);
            default:
                return $this->valor;
        }
    }
}
