<?php

namespace App\Http\Requests\Api\V1\Hacienda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para: POST /api/v1/hacienda/generar
 * Generar nuevo comprobante electrónico
 */
class StoreHaciendaComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comprobante_id' => ['required', 'integer', 'exists:comprobantes,id'],
            'tipo_comprobante' => ['required', 'string', 'in:01,03,04,05,07'],
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'comprobante_id.required' => 'El comprobante es obligatorio',
            'comprobante_id.exists' => 'El comprobante no existe',
            'tipo_comprobante.required' => 'El tipo de comprobante es obligatorio',
            'tipo_comprobante.in' => 'El tipo de comprobante debe ser 01, 03, 04, 05 o 07',
            'empresa_id.required' => 'La empresa es obligatoria',
            'empresa_id.exists' => 'La empresa no existe',
        ];
    }
}
