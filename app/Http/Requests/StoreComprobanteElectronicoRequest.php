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
    public function rules(): array
    {
        return [
            // Datos básicos del comprobante
            'tipo_documento' => 'required|in:01,02,03,04',
            'consecutivo' => 'required|string|max:20',
            'fecha_emision' => 'nullable|date',
            'condicion_venta' => 'required|in:01,02,03,04,99',
            'plazo_credito' => 'nullable|integer|min:0',
            'medio_pago' => 'required|in:01,02,03,04,05,99',
            'situacion' => 'nullable|in:1,2,3',
            
            // Información del receptor
            'receptor_nombre' => 'nullable|string|max:100',
            'receptor_tipo_identificacion' => 'nullable|in:01,02,03,04',
            'receptor_numero_identificacion' => 'nullable|string|max:12',
            'receptor_email' => 'nullable|email|max:160',
            'receptor_telefono' => 'nullable|string|max:20',
            'receptor_provincia' => 'nullable|string|max:2',
            'receptor_canton' => 'nullable|string|max:2',
            'receptor_distrito' => 'nullable|string|max:2',
            'receptor_barrio' => 'nullable|string|max:2',
            'receptor_otras_senas' => 'nullable|string|max:250',
            
            // Información de moneda
            'codigo_moneda' => 'nullable|string|size:3',
            'tipo_cambio' => 'nullable|numeric|min:0',
            
            // Observaciones
            'observaciones' => 'nullable|string|max:1000',
            
            // Información de referencia (para notas crédito/débito)
            'tipo_documento_referencia' => 'nullable|in:01,02,03,04',
            'numero_documento_referencia' => 'nullable|string|max:50',
            'fecha_emision_referencia' => 'nullable|date',
            'codigo_referencia' => 'nullable|in:01,02,03,04,05,99',
            'razon_referencia' => 'nullable|string|max:180',
            
            // Líneas de detalle
            'lineas' => 'required|array|min:1',
            'lineas.*.numero_linea' => 'required|integer|min:1',
            'lineas.*.codigo_tipo' => 'nullable|in:01,02,03,04,99',
            'lineas.*.codigo' => 'nullable|string|max:20',
            'lineas.*.cantidad' => 'required|numeric|min:0',
            'lineas.*.unidad_medida' => 'nullable|in:Sp,m,kg,s,I,Os,Spe,Alc,Cm,Otros',
            'lineas.*.detalle' => 'required|string|max:200',
            'lineas.*.precio_unitario' => 'required|numeric|min:0',
            'lineas.*.monto_total' => 'required|numeric|min:0',
            'lineas.*.monto_descuento' => 'nullable|numeric|min:0',
            'lineas.*.naturaleza_descuento' => 'nullable|string|max:80',
            'lineas.*.subtotal' => 'required|numeric|min:0',
            'lineas.*.base_imponible' => 'nullable|numeric|min:0',
            
            // Impuestos por línea
            'lineas.*.impuestos' => 'nullable|array',
            'lineas.*.impuestos.*.codigo' => 'required_with:lineas.*.impuestos|in:01,02,03,04,05,06,07,08,99',
            'lineas.*.impuestos.*.codigo_tarifa' => 'required_with:lineas.*.impuestos|in:01,02,03,04,05,06,07,08',
            'lineas.*.impuestos.*.tarifa' => 'required_with:lineas.*.impuestos|numeric|min:0|max:100',
            'lineas.*.impuestos.*.monto' => 'required_with:lineas.*.impuestos|numeric|min:0',
            'lineas.*.impuestos.*.exoneracion_tipo_documento' => 'nullable|in:01,02,03,04,05,99',
            'lineas.*.impuestos.*.exoneracion_numero_documento' => 'nullable|string|max:40',
            'lineas.*.impuestos.*.exoneracion_nombre_institucion' => 'nullable|string|max:160',
            'lineas.*.impuestos.*.exoneracion_fecha_emision' => 'nullable|date',
            'lineas.*.impuestos.*.exoneracion_porcentaje_compra' => 'nullable|integer|min:0|max:100',
            'lineas.*.impuestos.*.exoneracion_monto_impuesto' => 'nullable|numeric|min:0',
            
            // Monto total línea
            'lineas.*.monto_total_linea' => 'required|numeric|min:0',
            
            // Certificado digital a usar
            'certificado_id' => 'required|integer|exists:fe_certificados_digitales,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
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
}
