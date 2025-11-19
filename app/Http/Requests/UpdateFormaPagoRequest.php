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

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una forma de pago con este nombre'
        ];
    }
}
