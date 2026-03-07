<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoComprobanteFeRequest extends FormRequest
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo_dgt' => ['required', 'string', 'size:2', 'unique:tipos_comprobantes_fe,codigo_dgt'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'requiere_referencia' => ['boolean'],
            'permite_exportacion' => ['boolean'],
            'activo' => ['boolean'],
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
            'codigo_dgt.required' => 'El código DGT es obligatorio',
            'codigo_dgt.size' => 'El código DGT debe tener 2 caracteres',
            'codigo_dgt.unique' => 'Este código DGT ya existe',
            'nombre.required' => 'El nombre es obligatorio',
        ];
    }
}
