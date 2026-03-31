<?php

namespace App\Http\Requests;

use App\Models\Webhook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'url' => ['sometimes', 'url:https', 'max:2048'],
            'eventos' => ['sometimes', 'array', 'min:1'],
            'eventos.*' => ['required_with:eventos', 'string', Rule::in(Webhook::EVENTOS_DISPONIBLES)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'timeout_segundos' => ['sometimes', 'integer', 'min:5', 'max:60'],
            'max_reintentos' => ['sometimes', 'integer', 'min:0', 'max:5'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'La URL debe ser válida y usar HTTPS',
            'eventos.min' => 'Debe seleccionar al menos un evento',
            'eventos.*.in' => 'El evento seleccionado no es válido. Eventos disponibles: ' . implode(', ', Webhook::EVENTOS_DISPONIBLES),
            'timeout_segundos.min' => 'El timeout mínimo es 5 segundos',
            'timeout_segundos.max' => 'El timeout máximo es 60 segundos',
            'max_reintentos.max' => 'El máximo de reintentos es 5',
        ];
    }
}
