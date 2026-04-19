<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class EmpresaCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $num_identificacion_dgt,
        public readonly string $tipo_identificacion,
        public readonly ?string $nombre_comercial = null,
        public readonly ?string $razon_social = null,
        public readonly ?string $actividad_economica_principal = null,
        public readonly ?string $proveedor_sistemas = null,
        public readonly ?string $direccion = null,
        public readonly ?string $provincia = null,
        public readonly ?string $canton = null,
        public readonly ?string $distrito = null,
        public readonly ?string $barrio = null,
        public readonly ?string $registro_fiscal_8707 = null,
        public readonly ?string $telefono = null,
        public readonly ?string $email = null,
        public readonly ?string $subdominio = null,
        public readonly ?string $prefijo_orden_compra = null,
        public readonly string $moneda_defecto = 'CRC',
        public readonly ?int $regimen_tributario_id = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            num_identificacion_dgt: $request->string('num_identificacion_dgt')->trim()->toString(),
            tipo_identificacion: $request->string('tipo_identificacion')->trim()->toString(),
            nombre_comercial: $request->filled('nombre_comercial') ? $request->string('nombre_comercial')->trim()->toString() : null,
            razon_social: $request->filled('razon_social') ? $request->string('razon_social')->trim()->toString() : null,
            actividad_economica_principal: $request->filled('actividad_economica_principal') ? $request->string('actividad_economica_principal')->trim()->toString() : null,
            proveedor_sistemas: $request->filled('proveedor_sistemas') ? $request->string('proveedor_sistemas')->trim()->toString() : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            provincia: $request->filled('provincia') ? $request->string('provincia')->trim()->toString() : null,
            canton: $request->filled('canton') ? $request->string('canton')->trim()->toString() : null,
            distrito: $request->filled('distrito') ? $request->string('distrito')->trim()->toString() : null,
            barrio: $request->filled('barrio') ? $request->string('barrio')->trim()->toString() : null,
            registro_fiscal_8707: $request->filled('registro_fiscal_8707') ? $request->string('registro_fiscal_8707')->trim()->toString() : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            email: $request->filled('email') ? $request->string('email')->trim()->toString() : null,
            subdominio: $request->filled('subdominio') ? $request->string('subdominio')->trim()->toString() : null,
            prefijo_orden_compra: $request->filled('prefijo_orden_compra') ? $request->string('prefijo_orden_compra')->trim()->toString() : null,
            moneda_defecto: $request->string('moneda_defecto', 'CRC')->trim()->toString(),
            regimen_tributario_id: $request->filled('regimen_tributario_id') ? (int) $request->input('regimen_tributario_id') : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'num_identificacion_dgt' => $this->num_identificacion_dgt,
            'tipo_identificacion' => $this->tipo_identificacion,
            'nombre_comercial' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'actividad_economica_principal' => $this->actividad_economica_principal,
            'proveedor_sistemas' => $this->proveedor_sistemas,
            'direccion' => $this->direccion,
            'provincia' => $this->provincia,
            'canton' => $this->canton,
            'distrito' => $this->distrito,
            'barrio' => $this->barrio,
            'registro_fiscal_8707' => $this->registro_fiscal_8707,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'subdominio' => $this->subdominio,
            'prefijo_orden_compra' => $this->prefijo_orden_compra,
            'moneda_defecto' => $this->moneda_defecto,
            'regimen_tributario_id' => $this->regimen_tributario_id,
            'activo' => $this->activo,
        ];
    }
}
