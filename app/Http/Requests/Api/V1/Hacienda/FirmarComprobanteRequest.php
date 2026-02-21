<?php

namespace App\Http\Requests\Api\V1\Hacienda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para: POST /api/v1/hacienda/{id}/firmar
 * Firmar comprobante con certificado digital
 */
class FirmarComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'certificado_ruta' => [
                'required',
                'string',
                'exists:App\Models\Certificado,ruta',
            ],
            'certificado_password' => [
                'required',
                'string',
                'min:6',
            ],
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
            'certificado_ruta.required' => 'La ruta del certificado es obligatoria',
            'certificado_ruta.exists' => 'El certificado no existe',
            'certificado_password.required' => 'La contraseña del certificado es obligatoria',
            'certificado_password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ];
    }
}
