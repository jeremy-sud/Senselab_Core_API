<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZonaGeograficaRequest extends FormRequest
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
            'empresa_id' => ['nullable', 'exists:empresas,id'],
            'codigo' => ['required', 'string', 'max:10'],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:provincia,canton,distrito,zona_ventas,ruta'],
            'zona_padre_id' => ['nullable', 'exists:zonas_geograficas,id'],
            'provincias_incluidas' => ['nullable', 'array'],
            'vendedor_asignado_id' => ['nullable', 'exists:empleados,id'],
            'activa' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'nombre.required' => 'El nombre es obligatorio',
            'tipo.required' => 'El tipo de zona es obligatorio',
            'tipo.in' => 'Tipo válido: provincia, cantón, distrito, zona_ventas o ruta',
        ];
    }
}
