<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntidadEtiquetaRequest extends FormRequest
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
            'etiqueta_id' => 'required|integer|exists:etiquetas,id',
            'entidad_tipo' => 'required|string|max:50|in:clientes,productos,ventas,empleados,proveedores,facturas,cotizaciones,pedidos,ordenes_compra,proyectos',
            'entidad_id' => 'required|integer|min:1',
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
            'etiqueta_id.required' => 'La etiqueta es requerida',
            'etiqueta_id.exists' => 'La etiqueta especificada no existe',
            'entidad_tipo.required' => 'El tipo de entidad es requerido',
            'entidad_tipo.max' => 'El tipo de entidad no puede superar los 50 caracteres',
            'entidad_tipo.in' => 'El tipo de entidad debe ser válido (clientes, productos, ventas, empleados, etc.)',
            'entidad_id.required' => 'El ID de la entidad es requerido',
            'entidad_id.min' => 'El ID de la entidad debe ser mayor a 0',
        ];
    }
}
