<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar productos
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'categoria_id' => ['sometimes', 'required', 'exists:categorias_productos,id'],
            'unidad_medida_id' => ['sometimes', 'required', 'exists:unidades_medida,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'codigo_barras' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['sometimes', 'required', 'in:producto,servicio'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'stock_maximo' => ['nullable', 'integer', 'min:0'],
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'proveedor_predeterminado_id' => ['nullable', 'exists:proveedores,id'],
            'tipo_impuesto_id' => ['nullable', 'exists:tipos_impuesto,id'],
            'cabys_id' => ['nullable', 'exists:cabys,id'],
            'imagen_url' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
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
            'nombre.required' => 'El nombre del producto es obligatorio',
            'tipo.required' => 'El tipo de producto es obligatorio',
            'tipo.in' => 'El tipo debe ser "producto" o "servicio"',
            'precio_venta.required' => 'El precio de venta es obligatorio',
            'precio_venta.min' => 'El precio de venta debe ser mayor o igual a 0',
        ];
    }
}
