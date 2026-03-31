<?php

namespace App\Http\Requests;

use App\Models\Webhook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url:https', 'max:2048'],
            'eventos' => ['required', 'array', 'min:1'],
            'eventos.*' => ['required', 'string', Rule::in(Webhook::EVENTOS_DISPONIBLES)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'timeout_segundos' => ['nullable', 'integer', 'min:5', 'max:60'],
            'max_reintentos' => ['nullable', 'integer', 'min:0', 'max:5'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del webhook es obligatorio',
            'url.required' => 'La URL del webhook es obligatoria',
            'url.url' => 'La URL debe ser válida y usar HTTPS',
            'eventos.required' => 'Debe seleccionar al menos un evento',
            'eventos.min' => 'Debe seleccionar al menos un evento',
            'eventos.*.in' => 'El evento seleccionado no es válido. Eventos disponibles: ' . implode(', ', Webhook::EVENTOS_DISPONIBLES),
            'timeout_segundos.min' => 'El timeout mínimo es 5 segundos',
            'timeout_segundos.max' => 'El timeout máximo es 60 segundos',
            'max_reintentos.max' => 'El máximo de reintentos es 5',
        ];
    }
}
