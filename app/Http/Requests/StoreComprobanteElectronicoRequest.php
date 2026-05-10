<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para crear comprobante electrónico
 */
class StoreComprobanteElectronicoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorización manejada por policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Datos básicos del comprobante
            'tipo_documento' => 'required|in:01,02,03,04',
            'consecutivo' => 'required|string|max:20',
            'fecha_emision' => 'nullable|date',
            // Brecha #10: condicion_venta soporta 01-15 y 99
            'condicion_venta' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,99',
            'condicion_venta_otros' => 'required_if:condicion_venta,99|nullable|string|max:100',
            'plazo_credito' => 'nullable|integer|min:0',
            'medio_pago' => 'nullable|in:01,02,03,04,05,06,07,99',
            'situacion' => 'nullable|in:1,2,3',

            // Brecha #9: Código de actividad económica del receptor
            'codigo_actividad_receptor' => 'nullable|string|max:6',

            // Información del receptor
            'receptor_nombre' => 'nullable|string|max:100',
            'receptor_nombre_comercial' => 'nullable|string|max:80',
            // Brecha #8/#11: receptor_tipo_identificacion soporta 05,06
            'receptor_tipo_identificacion' => 'nullable|in:01,02,03,04,05,06',
            // Brecha #8: receptor_numero_identificacion max 20
            'receptor_numero_identificacion' => 'nullable|string|max:20',
            'receptor_email' => 'nullable|email|max:160',
            'receptor_telefono_codigo_pais' => 'nullable|string|max:3',
            'receptor_telefono_numero' => 'nullable|string|max:20',
            'receptor_provincia' => 'nullable|string|max:2',
            'receptor_canton' => 'nullable|string|max:2',
            'receptor_distrito' => 'nullable|string|max:2',
            'receptor_barrio' => 'nullable|string|max:2',
            'receptor_otras_senas' => 'nullable|string|max:250',
            'receptor_otras_senas_extranjero' => 'nullable|string|max:400',

            // Información de moneda
            'codigo_moneda' => 'nullable|string|size:3',
            'tipo_cambio' => 'nullable|numeric|min:0',

            // Observaciones
            'observaciones' => 'nullable|string|max:1000',

            // Brecha #14: Información de referencia — soporta múltiples registros
            'informacion_referencia' => 'nullable|array|max:10',
            'informacion_referencia.*.tipo_doc' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,99',
            'informacion_referencia.*.tipo_doc_otro' => 'required_if:informacion_referencia.*.tipo_doc,99|nullable|string|max:100',
            'informacion_referencia.*.numero' => 'required|string|max:50',
            'informacion_referencia.*.fecha_emision' => 'required|date',
            'informacion_referencia.*.codigo' => 'required|in:01,02,03,04,05,99',
            'informacion_referencia.*.codigo_referencia_otro' => 'required_if:informacion_referencia.*.codigo,99|nullable|string|max:100',
            'informacion_referencia.*.razon' => 'required|string|max:180',

            // Campos legacy de referencia (compatibilidad)
            'tipo_documento_referencia' => 'nullable|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,99',
            'numero_documento_referencia' => 'nullable|string|max:50',
            'fecha_emision_referencia' => 'nullable|date',
            'codigo_referencia' => 'nullable|in:01,02,03,04,05,99',
            'razon_referencia' => 'nullable|string|max:180',

            // Brecha #6: Medios de pago — soporta 1-4 medios
            'medios_pago' => 'nullable|array|min:1|max:4',
            'medios_pago.*.tipo_medio_pago' => 'required|in:01,02,03,04,05,06,07,99',
            'medios_pago.*.medio_pago_otros' => 'required_if:medios_pago.*.tipo_medio_pago,99|nullable|string|max:100',
            'medios_pago.*.total_medio_pago' => 'required|numeric|min:0',

            // Brecha #13: Otros cargos
            'otros_cargos' => 'nullable|array|max:15',
            'otros_cargos.*.tipo_documento_oc' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12,99',
            'otros_cargos.*.tipo_documento_otros' => 'required_if:otros_cargos.*.tipo_documento_oc,99|nullable|string|min:5|max:100',
            'otros_cargos.*.tercero_tipo_identificacion' => 'nullable|in:01,02,03,04,05',
            'otros_cargos.*.tercero_numero_identificacion' => 'nullable|string|max:20',
            'otros_cargos.*.nombre_tercero' => 'nullable|string|max:100',
            'otros_cargos.*.detalle' => 'required|string|max:160',
            'otros_cargos.*.porcentaje_oc' => 'nullable|numeric|min:0|max:100',
            'otros_cargos.*.monto_cargo' => 'required|numeric|min:0',

            // Líneas de detalle
            'lineas' => 'required|array|min:1',
            'lineas.*.numero_linea' => 'required|integer|min:1',
            'lineas.*.codigo_tipo' => 'nullable|in:01,02,03,04,99',
            'lineas.*.codigo' => 'nullable|string|max:20',
            'lineas.*.codigo_cabys' => 'nullable|string|max:13',
            'lineas.*.partida_arancelaria' => 'nullable|string|max:12',
            'lineas.*.cantidad' => 'required|numeric|min:0',
            'lineas.*.unidad_medida' => 'nullable|in:Sp,m,kg,s,I,Os,Spe,Alc,Cm,Otros',
            'lineas.*.unidad_medida_comercial' => 'nullable|string|max:20',
            'lineas.*.detalle' => 'required|string|max:200',
            'lineas.*.precio_unitario' => 'required|numeric|min:0',
            'lineas.*.monto_total' => 'required|numeric|min:0',
            'lineas.*.tipo_transaccion' => 'nullable|in:V,X',

            // Descuentos por línea
            'lineas.*.monto_descuento' => 'nullable|numeric|min:0',
            'lineas.*.naturaleza_descuento' => 'nullable|string|max:80',
            // Brecha #2: codigo_descuento obligatorio cuando hay descuento
            'lineas.*.codigo_descuento' => 'required_with:lineas.*.monto_descuento|nullable|in:01,02,03,04,05,06,07,99',
            'lineas.*.codigo_descuento_otro' => 'nullable|string|max:100',

            // Brecha #15: Descuentos múltiples por línea
            'lineas.*.descuentos' => 'nullable|array|max:5',
            'lineas.*.descuentos.*.monto_descuento' => 'required|numeric|min:0',
            'lineas.*.descuentos.*.codigo_descuento' => 'required|in:01,02,03,04,05,06,07,99',
            'lineas.*.descuentos.*.codigo_descuento_otro' => 'nullable|string|max:100',
            'lineas.*.descuentos.*.naturaleza_descuento' => 'nullable|string|max:80',

            'lineas.*.subtotal' => 'required|numeric|min:0',
            'lineas.*.base_imponible' => 'nullable|numeric|min:0',

            // Campos adicionales v4.4 por línea
            'lineas.*.numero_vin_serie' => 'nullable|string|max:30',
            'lineas.*.registro_medicamento' => 'nullable|string|max:20',
            'lineas.*.forma_farmaceutica' => 'nullable|string|max:50',
            'lineas.*.iva_cobrado_fabrica' => 'nullable|numeric|min:0',
            'lineas.*.impuesto_asumido_emisor_fabrica' => 'nullable|numeric|min:0',
            'lineas.*.monto_exportacion' => 'nullable|numeric|min:0',

            // Impuestos por línea
            'lineas.*.impuestos' => 'nullable|array',
            'lineas.*.impuestos.*.codigo' => 'required_with:lineas.*.impuestos|in:01,02,03,04,05,06,07,08,99',
            'lineas.*.impuestos.*.codigo_impuesto_otro' => 'nullable|string|max:100',
            'lineas.*.impuestos.*.codigo_tarifa' => 'nullable|in:01,02,03,04,05,06,07,08',
            'lineas.*.impuestos.*.tarifa' => 'required_with:lineas.*.impuestos|numeric|min:0|max:100',
            'lineas.*.impuestos.*.factor_calculo_iva' => 'nullable|numeric|min:0',
            'lineas.*.impuestos.*.monto' => 'required_with:lineas.*.impuestos|numeric|min:0',
            'lineas.*.impuestos.*.impuesto_asumido_emisor_fabrica' => 'nullable|numeric|min:0',
            'lineas.*.impuestos.*.monto_exportacion' => 'nullable|numeric|min:0',
            // Datos impuesto específico
            'lineas.*.impuestos.*.dato_especifico_codigo' => 'nullable|string|max:3',
            'lineas.*.impuestos.*.dato_especifico_tipo_gravamen' => 'nullable|in:01,02',
            'lineas.*.impuestos.*.dato_especifico_unidad_medida' => 'nullable|string|max:10',
            'lineas.*.impuestos.*.dato_especifico_cantidad_base' => 'nullable|numeric|min:0',
            'lineas.*.impuestos.*.dato_especifico_monto_gravamen' => 'nullable|numeric|min:0',
            // Exoneración
            'lineas.*.impuestos.*.exoneracion_tipo_documento' => 'nullable|in:01,02,03,04,05,99',
            'lineas.*.impuestos.*.exoneracion_tipo_documento_otro' => 'nullable|string|max:100',
            'lineas.*.impuestos.*.exoneracion_numero_documento' => 'nullable|string|max:40',
            'lineas.*.impuestos.*.exoneracion_nombre_institucion' => 'nullable|string|max:160',
            'lineas.*.impuestos.*.exoneracion_nombre_institucion_otros' => 'nullable|string|max:160',
            'lineas.*.impuestos.*.exoneracion_fecha_emision' => 'nullable|date',
            'lineas.*.impuestos.*.exoneracion_articulo' => 'nullable|string|max:20',
            'lineas.*.impuestos.*.exoneracion_inciso' => 'nullable|string|max:20',
            'lineas.*.impuestos.*.exoneracion_porcentaje_compra' => 'nullable|integer|min:0|max:100',
            'lineas.*.impuestos.*.exoneracion_tarifa_exonerada' => 'nullable|numeric|min:0|max:100',
            'lineas.*.impuestos.*.exoneracion_monto_impuesto' => 'nullable|numeric|min:0',

            // Monto total línea
            'lineas.*.monto_total_linea' => 'required|numeric|min:0',

            // Brecha #33: Códigos comerciales múltiples {0,5}
            'lineas.*.codigos_comerciales' => 'nullable|array|max:5',
            'lineas.*.codigos_comerciales.*.tipo' => 'required|in:01,02,03,04,99',
            'lineas.*.codigos_comerciales.*.codigo' => 'required|string|max:20',

            // Brecha #31: Detalle surtido {0,20}
            'lineas.*.detalle_surtido' => 'nullable|array|max:20',
            'lineas.*.detalle_surtido.*.numero_linea_surtido' => 'required|integer|min:1|max:20',
            'lineas.*.detalle_surtido.*.codigo_cabys_surtido' => 'required|string|max:13',
            'lineas.*.detalle_surtido.*.cantidad_surtido' => 'required|numeric|min:0',
            'lineas.*.detalle_surtido.*.unidad_medida_surtido' => 'nullable|string|max:15',
            'lineas.*.detalle_surtido.*.detalle_surtido' => 'required|string|max:200',
            'lineas.*.detalle_surtido.*.precio_unitario_surtido' => 'required|numeric|min:0',
            'lineas.*.detalle_surtido.*.monto_total_surtido' => 'required|numeric|min:0',
            'lineas.*.detalle_surtido.*.monto_descuento_surtido' => 'nullable|numeric|min:0',
            'lineas.*.detalle_surtido.*.subtotal_surtido' => 'required|numeric|min:0',
            'lineas.*.detalle_surtido.*.impuestos' => 'nullable|array',
            'lineas.*.detalle_surtido.*.impuestos.*.codigo' => 'required|in:01,02,03,04,05,06,07,08,99',
            'lineas.*.detalle_surtido.*.impuestos.*.codigo_tarifa_iva' => 'nullable|in:01,02,03,04,05,06,07,08',
            'lineas.*.detalle_surtido.*.impuestos.*.tarifa' => 'nullable|numeric|min:0|max:100',
            'lineas.*.detalle_surtido.*.impuestos.*.monto' => 'required|numeric|min:0',

            // Certificado digital a usar
            'certificado_id' => 'required|integer|exists:fe_certificados_digitales,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es requerido',
            'tipo_documento.in' => 'Tipo de documento inválido. Use: 01=Factura, 02=Nota Débito, 03=Nota Crédito, 04=Tiquete',
            'consecutivo.required' => 'El consecutivo es requerido',
            'condicion_venta.required' => 'La condición de venta es requerida',
            'medio_pago.required' => 'El medio de pago es requerido',
            'lineas.required' => 'Debe incluir al menos una línea de detalle',
            'lineas.min' => 'Debe incluir al menos una línea de detalle',
            'certificado_id.required' => 'Debe especificar un certificado digital',
            'certificado_id.exists' => 'El certificado digital no existe',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'consecutivo' => 'consecutivo',
            'condicion_venta' => 'condición de venta',
            'medio_pago' => 'medio de pago',
            'receptor_email' => 'email del receptor',
            'lineas' => 'líneas de detalle',
            'certificado_id' => 'certificado digital',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lineas = $this->input('lineas', []);
            if (!is_array($lineas)) {
                return;
            }

            foreach ($lineas as $index => $linea) {
                // Ensure required fields exist for calculation
                if (!isset($linea['precio_unitario'], $linea['cantidad'])) {
                    continue;
                }

                $precioUnitario = (float) $linea['precio_unitario'];
                $cantidad = (float) $linea['cantidad'];
                
                $expectedMontoTotal = round($precioUnitario * $cantidad, 5);
                $actualMontoTotal = isset($linea['monto_total']) ? (float) $linea['monto_total'] : 0.0;

                // Hacienda allows minor rounding differences, but we'll check tight enough
                if (abs($expectedMontoTotal - $actualMontoTotal) > 0.5) {
                    $validator->errors()->add("lineas.{$index}.monto_total", "El monto total de la línea {$linea['numero_linea']} debe ser igual a precio_unitario * cantidad ({$expectedMontoTotal}).");
                }

                $montoDescuento = 0.0;
                if (isset($linea['descuentos']) && is_array($linea['descuentos'])) {
                    foreach ($linea['descuentos'] as $descuento) {
                        if (isset($descuento['monto_descuento'])) {
                            $montoDescuento += (float) $descuento['monto_descuento'];
                        }
                    }
                } elseif (isset($linea['monto_descuento'])) {
                    $montoDescuento = (float) $linea['monto_descuento'];
                }

                $expectedSubtotal = round($actualMontoTotal - $montoDescuento, 5);
                $actualSubtotal = isset($linea['subtotal']) ? (float) $linea['subtotal'] : 0.0;

                if (abs($expectedSubtotal - $actualSubtotal) > 0.5) {
                    $validator->errors()->add("lineas.{$index}.subtotal", "El subtotal de la línea {$linea['numero_linea']} debe ser igual a monto_total - descuentos ({$expectedSubtotal}).");
                }

                $montoImpuestos = 0.0;
                if (isset($linea['impuestos']) && is_array($linea['impuestos'])) {
                    foreach ($linea['impuestos'] as $impuesto) {
                        if (isset($impuesto['monto'])) {
                            $montoImpuestos += (float) $impuesto['monto'];
                        }
                    }
                }

                $expectedMontoTotalLinea = round($actualSubtotal + $montoImpuestos, 5);
                $actualMontoTotalLinea = isset($linea['monto_total_linea']) ? (float) $linea['monto_total_linea'] : 0.0;

                if (abs($expectedMontoTotalLinea - $actualMontoTotalLinea) > 0.5) {
                    $validator->errors()->add("lineas.{$index}.monto_total_linea", "El monto total de la línea {$linea['numero_linea']} debe ser igual a subtotal + impuestos ({$expectedMontoTotalLinea}).");
                }
            }
        });
    }
}
