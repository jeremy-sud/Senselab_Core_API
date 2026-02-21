<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormaPagoRequest extends FormRequest
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
        $formaPagoId = $this->route('forma_pago') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('formas_pago', 'nombre')->ignore($formaPagoId)
            ],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'requiere_referencia' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean']
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
            'nombre.unique' => 'Ya existe una forma de pago con este nombre'
        ];
    }
}
