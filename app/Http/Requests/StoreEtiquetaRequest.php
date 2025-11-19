<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtiquetaRequest extends FormRequest
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
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('etiquetas')->where(function ($query) {
                    return $query->where('empresa_id', auth()->user()->empresa_id)
                                 ->where('eliminado', 0);
                }),
            ],
            'color_hex' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la etiqueta es requerido',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres',
            'nombre.unique' => 'Ya existe una etiqueta con este nombre',
            'color_hex.regex' => 'El color debe estar en formato hexadecimal (ej: #FF5733)',
        ];
    }
}
