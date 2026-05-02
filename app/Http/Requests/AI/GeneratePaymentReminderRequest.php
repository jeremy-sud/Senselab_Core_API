<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePaymentReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('use-ai-generation') ?? true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => 'required|integer|min:1',
            'factura_ids' => 'nullable|array',
            'factura_ids.*' => 'integer|min:1',
            'tone' => 'nullable|in:friendly,formal,urgent|default:friendly',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El ID del cliente es requerido.',
            'cliente_id.integer' => 'El ID del cliente debe ser un número entero.',
            'factura_ids.*.integer' => 'Cada ID de factura debe ser un número entero.',
            'tone.in' => 'El tono debe ser: friendly, formal o urgent.',
        ];
    }
}
