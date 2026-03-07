<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRetencionImpuestoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'compra_id' => ['nullable', 'integer'],
            'venta_id' => ['nullable', 'integer'],
            'tipo_retencion' => ['required', 'in:renta,iva,otras'],
            'porcentaje_retencion' => ['required', 'numeric', 'min:0', 'max:100'],
            'monto_base' => ['required', 'numeric', 'min:0'],
            'monto_retenido' => ['required', 'numeric', 'min:0'],
            'numero_comprobante' => ['nullable', 'string', 'max:50'],
            'fecha_retencion' => ['required', 'date'],
            'periodo_declaracion' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'declarado' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'tipo_retencion.required' => 'El tipo de retención es obligatorio',
            'tipo_retencion.in' => 'Tipo válido: renta, iva u otras',
            'porcentaje_retencion.required' => 'El porcentaje es obligatorio',
            'monto_base.required' => 'El monto base es obligatorio',
            'monto_retenido.required' => 'El monto retenido es obligatorio',
            'fecha_retencion.required' => 'La fecha de retención es obligatoria',
            'periodo_declaracion.required' => 'El período es obligatorio',
            'periodo_declaracion.regex' => 'Formato del período: YYYY-MM',
        ];
    }
}
