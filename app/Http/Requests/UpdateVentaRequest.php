<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar ventas
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observaciones' => ['nullable', 'string'],
            'estado_venta' => ['sometimes', 'required', 'in:pendiente,pagada,anulada'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado_venta.in' => 'El estado debe ser: pendiente, pagada o anulada',
        ];
    }
}
