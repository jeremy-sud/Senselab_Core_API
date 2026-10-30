<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldSeatRequest extends FormRequest
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
            'seat_number' => ['required', 'string', 'max:10'],
            'passenger_session_id' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seat_number.required' => 'El número de asiento es obligatorio para retenerlo.',
            'seat_number.string' => 'El número de asiento debe ser una cadena válida.',
            'user_id.exists' => 'El usuario especificado no existe en el sistema.',
        ];
    }
}
