<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ObtenerSiguienteConsecutivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_documento_dgt' => 'required|string|size:2',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'prefijo' => 'nullable|string|max:10',
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
            'tipo_documento_dgt.required' => 'El tipo de documento DGT es obligatorio.',
            'tipo_documento_dgt.size' => 'El tipo de documento DGT debe tener exactamente 2 caracteres.',
            'sucursal_id.exists' => 'La sucursal especificada no existe.',
            'prefijo.max' => 'El prefijo no puede tener más de 10 caracteres.',
        ];
    }
}
