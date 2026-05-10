<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar ventas
 *
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'estado_venta' => ['sometimes', 'required', 'in:pendiente,pagada,anulada'],
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado_venta.in' => 'El estado debe ser: pendiente, pagada o anulada',
        ];
    }
}
