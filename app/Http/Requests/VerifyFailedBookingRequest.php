<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFailedBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'referencia_pasarela' => ['required', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'in:audit,reissue,refund'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'horario_ruta_id' => ['nullable', 'integer', 'exists:horarios_ruta,id'],
            'asiento_numero' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'referencia_pasarela.required' => 'La referencia externa de pasarela o transacción bancaria es obligatoria.',
            'action.in' => 'La acción solicitada debe ser: audit, reissue o refund.',
        ];
    }
}
