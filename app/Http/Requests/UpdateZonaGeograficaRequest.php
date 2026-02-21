<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZonaGeograficaRequest extends FormRequest
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
     * @return array<string, mixed>
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
            'codigo' => ['sometimes', 'string', 'max:10'],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'tipo' => ['sometimes', 'in:provincia,canton,distrito,zona_ventas,ruta'],
            'zona_padre_id' => ['nullable', 'exists:zonas_geograficas,id'],
            'provincias_incluidas' => ['nullable', 'array'],
            'vendedor_asignado_id' => ['nullable', 'exists:empleados,id'],
            'activa' => ['boolean'],
        ];
    }
}
