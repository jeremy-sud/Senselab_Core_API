<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de comprobante electrónico
 *
 * Valida y transforma datos de entrada para la creación de comprobantes electrónicos.
 * Actualizado para cumplimiento Hacienda v4.4 (DGT-R-000-2024).
 * Fecha de creación: 12 de febrero de 2026
 */
final class ComprobanteElectronicoCreateDTO
{
    /**
     * @param array<mixed> $detalles_json
     * @param array<mixed> $medios_pago
     * @param array<mixed> $informacion_referencia
     * @param array<mixed> $otros_cargos
     */
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $venta_id,
        public readonly string $tipo_comprobante,
        public readonly string $clave_numerica,
        public readonly \DateTime $fecha_emision,
        public readonly string $estado,
        public readonly ?string $numero_telefax = null,
        public readonly ?string $correo_receptor = null,
        public readonly ?array $detalles_json = null,
        // Campos v4.4
        public readonly ?string $codigo_actividad_receptor = null,
        public readonly ?string $condicion_venta_otros = null,
        public readonly ?string $receptor_nombre_comercial = null,
        public readonly ?string $receptor_provincia = null,
        public readonly ?string $receptor_canton = null,
        public readonly ?string $receptor_distrito = null,
        public readonly ?string $receptor_barrio = null,
        public readonly ?string $receptor_otras_senas = null,
        public readonly ?string $receptor_otras_senas_extranjero = null,
        public readonly ?string $receptor_telefono_codigo_pais = null,
        public readonly ?string $receptor_telefono_numero = null,
        public readonly ?array $medios_pago = null,
        public readonly ?array $informacion_referencia = null,
        public readonly ?array $otros_cargos = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            venta_id: $request->integer('venta_id'),
            tipo_comprobante: $request->string('tipo_comprobante'),
            clave_numerica: $request->string('clave_numerica')->trim(),
            fecha_emision: new \DateTime($request->string('fecha_emision')),
            estado: $request->string('estado'),
            numero_telefax: $request->filled('numero_telefax') ? $request->string('numero_telefax')->trim()->toString() : null,
            correo_receptor: $request->filled('correo_receptor') ? $request->string('correo_receptor')->trim()->toString() : null,
            detalles_json: $request->array('detalles_json'),
            codigo_actividad_receptor: $request->filled('codigo_actividad_receptor') ? $request->string('codigo_actividad_receptor')->trim()->toString() : null,
            condicion_venta_otros: $request->filled('condicion_venta_otros') ? $request->string('condicion_venta_otros')->trim()->toString() : null,
            receptor_nombre_comercial: $request->filled('receptor_nombre_comercial') ? $request->string('receptor_nombre_comercial')->trim()->toString() : null,
            receptor_provincia: $request->filled('receptor_provincia') ? $request->string('receptor_provincia')->trim()->toString() : null,
            receptor_canton: $request->filled('receptor_canton') ? $request->string('receptor_canton')->trim()->toString() : null,
            receptor_distrito: $request->filled('receptor_distrito') ? $request->string('receptor_distrito')->trim()->toString() : null,
            receptor_barrio: $request->filled('receptor_barrio') ? $request->string('receptor_barrio')->trim()->toString() : null,
            receptor_otras_senas: $request->filled('receptor_otras_senas') ? $request->string('receptor_otras_senas')->trim()->toString() : null,
            receptor_otras_senas_extranjero: $request->filled('receptor_otras_senas_extranjero') ? $request->string('receptor_otras_senas_extranjero')->trim()->toString() : null,
            receptor_telefono_codigo_pais: $request->filled('receptor_telefono_codigo_pais') ? $request->string('receptor_telefono_codigo_pais')->trim()->toString() : null,
            receptor_telefono_numero: $request->filled('receptor_telefono_numero') ? $request->string('receptor_telefono_numero')->trim()->toString() : null,
            medios_pago: $request->filled('medios_pago') ? $request->array('medios_pago') : null,
            informacion_referencia: $request->filled('informacion_referencia') ? $request->array('informacion_referencia') : null,
            otros_cargos: $request->filled('otros_cargos') ? $request->array('otros_cargos') : null,
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'empresa_id' => $this->empresa_id,
            'venta_id' => $this->venta_id,
            'tipo_comprobante' => $this->tipo_comprobante,
            'clave_numerica' => $this->clave_numerica,
            'fecha_emision' => $this->fecha_emision->format('Y-m-d H:i:s'),
            'estado' => $this->estado,
            'numero_telefax' => $this->numero_telefax,
            'correo_receptor' => $this->correo_receptor,
            'detalles_json' => $this->detalles_json ? json_encode($this->detalles_json) : null,
            'codigo_actividad_receptor' => $this->codigo_actividad_receptor,
            'condicion_venta_otros' => $this->condicion_venta_otros,
            'receptor_nombre_comercial' => $this->receptor_nombre_comercial,
            'receptor_provincia' => $this->receptor_provincia,
            'receptor_canton' => $this->receptor_canton,
            'receptor_distrito' => $this->receptor_distrito,
            'receptor_barrio' => $this->receptor_barrio,
            'receptor_otras_senas' => $this->receptor_otras_senas,
            'receptor_otras_senas_extranjero' => $this->receptor_otras_senas_extranjero,
            'receptor_telefono_codigo_pais' => $this->receptor_telefono_codigo_pais,
            'receptor_telefono_numero' => $this->receptor_telefono_numero,
        ], fn ($v) => $v !== null);
    }
}
