<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Tipo de Cuenta Contable
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class TipoCuentaResource extends JsonResource
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'naturaleza' => $this->naturaleza,
            'activo' => (bool) $this->activo,
            'cuentas_contables_count' => $this->whenCounted('cuentasContables'),
            'cuentas_contables' => $this->when($request->has('include_cuentas'), function () {
                return $this->cuentasContables->map(function ($cuenta) {
                    return [
                        'id' => $cuenta->id,
                        'numero_cuenta' => $cuenta->numero_cuenta,
                        'nombre_cuenta' => $cuenta->nombre_cuenta
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
