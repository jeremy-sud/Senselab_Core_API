<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRetencionImpuestoRequest extends FormRequest
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
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'compra_id' => ['nullable', 'integer'],
            'venta_id' => ['nullable', 'integer'],
            'tipo_retencion' => ['sometimes', 'in:renta,iva,otras'],
            'porcentaje_retencion' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'monto_base' => ['sometimes', 'numeric', 'min:0'],
            'monto_retenido' => ['sometimes', 'numeric', 'min:0'],
            'numero_comprobante' => ['nullable', 'string', 'max:50'],
            'fecha_retencion' => ['sometimes', 'date'],
            'periodo_declaracion' => ['sometimes', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'declarado' => ['boolean'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
