<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegimenTributarioRequest extends FormRequest
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
            'codigo' => 'required|string|max:10|unique:regimenes_tributarios,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es requerido',
            'codigo.max' => 'El código no puede superar los 10 caracteres',
            'codigo.unique' => 'Ya existe un régimen tributario con este código',
            'nombre.required' => 'El nombre es requerido',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres',
        ];
    }
}
