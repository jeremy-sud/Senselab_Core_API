<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseSeatRequest extends FormRequest
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
            'user_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seat_number.required' => 'El número de asiento es obligatorio para liberarlo.',
        ];
    }
}
