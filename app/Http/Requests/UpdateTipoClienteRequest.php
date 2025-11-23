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
    public function rules(): array
    {
        $id = $this->route('tipo_cliente');
        
        return [
            'codigo' => ['sometimes', 'string', 'max:10', 'unique:tipos_clientes,codigo,' . $id],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'descuento_default' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dias_credito_default' => ['nullable', 'integer', 'min:0', 'max:365'],
            'activo' => ['boolean'],
        ];
    }
}
