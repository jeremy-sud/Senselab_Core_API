<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PorNaturalezaRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'naturaleza' => 'required|in:Deudora,Acreedora'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'naturaleza.required' => 'La naturaleza de la cuenta es requerida',
            'naturaleza.in' => 'La naturaleza debe ser Deudora o Acreedora',
        ];
    }
}
