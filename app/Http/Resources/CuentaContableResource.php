<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Cuenta Contable
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaContableResource extends JsonResource
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'codigo' => $this->codigo,
            'tipo_cuenta_id' => $this->tipo_cuenta_id,
            'cuenta_padre_id' => $this->cuenta_padre_id,
            'permite_movimientos' => (bool) $this->permite_movimientos,
            'saldo_actual' => $this->saldo_actual ? (float) $this->saldo_actual : 0.00,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Información adicional
            'nivel' => $this->cuenta_padre_id ? $this->getNivel() : 1,
            'es_cuenta_padre' => $this->subcuentas()->where('eliminado', 0)->count() > 0,
            'tiene_movimientos' => $this->whenLoaded('asientos', function () {
                return $this->asientos->count() > 0;
            }),
            
            // Contadores
            'subcuentas_count' => $this->whenCounted('subcuentas'),
            'asientos_count' => $this->whenCounted('asientos'),
            
            // Relaciones
            'tipo_cuenta' => $this->whenLoaded('tipoCuenta', function () {
                return [
                    'id' => $this->tipoCuenta->id,
                    'nombre' => $this->tipoCuenta->nombre,
                    'naturaleza' => $this->tipoCuenta->naturaleza
                ];
            }),
            'cuenta_padre' => $this->whenLoaded('cuentaPadre', function () {
                return [
                    'id' => $this->cuentaPadre->id,
                    'nombre' => $this->cuentaPadre->nombre,
                    'codigo' => $this->cuentaPadre->codigo
                ];
            }),
            'subcuentas' => $this->whenLoaded('subcuentas', function () {
                return CuentaContableResource::collection($this->subcuentas()->where('eliminado', 0)->get());
            }),
        ];
    }

    /**
     * Calcular el nivel jerárquico de la cuenta
     *
     * @return int
     */
    private function getNivel(): int
    {
        $nivel = 1;
        $cuenta = $this->resource;
        
        while ($cuenta->cuenta_padre_id) {
            $nivel++;
            $cuenta = $cuenta->cuentaPadre;
            
            // Prevenir bucles infinitos
            if ($nivel > 10) break;
        }
        
        return $nivel;
    }
}
