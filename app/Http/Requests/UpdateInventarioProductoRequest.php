<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventarioProductoRequest extends FormRequest
{
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
            'almacen_id' => 'sometimes|integer|exists:almacenes,id',
            'producto_id' => 'sometimes|integer|exists:productos,id',
            'stock_actual' => 'sometimes|numeric|min:0',
            'costo_promedio' => 'sometimes|numeric|min:0',
            'stock_minimo' => 'sometimes|numeric|min:0',
            'stock_maximo' => 'sometimes|numeric|min:0',
            'ubicacion' => 'nullable|string|max:100|regex:/^[A-Z0-9\-\/]+$/i',
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
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'producto_id.exists' => 'El producto seleccionado no existe',
            'stock_actual.min' => 'El stock actual no puede ser negativo',
            'costo_promedio.min' => 'El costo promedio no puede ser negativo',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo',
            'stock_maximo.min' => 'El stock máximo no puede ser negativo',
            'ubicacion.max' => 'La ubicación no puede exceder 100 caracteres',
            'ubicacion.regex' => 'La ubicación solo puede contener letras, números, guiones y barras (ej: A1-B2/C3)',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'almacen_id' => 'almacén',
            'producto_id' => 'producto',
            'stock_actual' => 'stock actual',
            'costo_promedio' => 'costo promedio',
            'stock_minimo' => 'stock mínimo',
            'stock_maximo' => 'stock máximo',
            'ubicacion' => 'ubicación',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que stock_maximo >= stock_minimo
            if ($this->has('stock_maximo') && $this->has('stock_minimo')) {
                if ((float)$this->stock_maximo < (float)$this->stock_minimo) {
                    $validator->errors()->add(
                        'stock_maximo',
                        'El stock máximo debe ser mayor o igual al stock mínimo'
                    );
                }
            }

            // Validar si el stock actual es bajo (advertencia, no error)
            if ($this->has('stock_actual') && $this->has('stock_minimo')) {
                if ((float)$this->stock_actual < (float)$this->stock_minimo) {
                    // Esto es solo informativo, no bloquea la actualización
                    // Podría logearse o enviarse como warning en la respuesta
                }
            }
        });
    }
}
