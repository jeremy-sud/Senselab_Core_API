<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class ClassifyProductRequest extends FormRequest
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
            'description' => 'required|string|min:3|max:500',
            'category_hint' => 'nullable|string|max:100',
            'max_suggestions' => 'nullable|integer|min:1|max:10|default:5',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'La descripción del producto es requerida.',
            'description.min' => 'La descripción debe tener al menos 3 caracteres.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'category_hint.max' => 'La categoría no puede exceder 100 caracteres.',
            'max_suggestions.min' => 'El mínimo de sugerencias es 1.',
            'max_suggestions.max' => 'El máximo de sugerencias es 10.',
        ];
    }
}
