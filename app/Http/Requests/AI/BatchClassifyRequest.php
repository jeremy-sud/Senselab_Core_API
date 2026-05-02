<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class BatchClassifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('use-ai-classification') ?? true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1|max:50',
            'products.*.description' => 'required|string|min:3|max:500',
            'products.*.id' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'El arreglo de productos es requerido.',
            'products.min' => 'Debe clasificar al menos 1 producto.',
            'products.max' => 'No puede clasificar más de 50 productos por lote.',
            'products.*.description.required' => 'Cada producto debe tener una descripción.',
            'products.*.description.min' => 'La descripción debe tener al menos 3 caracteres.',
            'products.*.description.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
