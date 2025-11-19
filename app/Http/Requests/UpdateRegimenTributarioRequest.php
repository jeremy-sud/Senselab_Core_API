<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegimenTributarioRequest extends FormRequest
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
            'codigo' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('regimenes_tributarios', 'codigo')->ignore($this->route('regimenTributario')),
            ],
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'codigo.max' => 'El código no puede superar los 10 caracteres',
            'codigo.unique' => 'Ya existe un régimen tributario con este código',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres',
        ];
    }
}
