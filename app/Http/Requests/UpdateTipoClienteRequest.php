<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoClienteRequest extends FormRequest
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
        // El parámetro de ruta para apiResource se genera automáticamente como singular sin guiones
        $id = $this->route('tipos_cliente');
        
        return [
            'codigo' => ['sometimes', 'string', 'max:10', 'unique:tipos_clientes,codigo,' . $id],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'descuento_default' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dias_credito_default' => ['nullable', 'integer', 'min:0', 'max:365'],
            'activo' => ['boolean'],
        ];
    }
}
