<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreNotificacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'usuario_id' => 'required|exists:usuarios,id',
            'tipo' => 'required|in:info,success,warning,error,alert',
            'categoria' => 'required|in:sistema,facturacion,ventas,compras,inventario,nomina,contabilidad,usuarios,otros',
            'prioridad' => 'required|in:baja,normal,alta,urgente',
            'titulo' => 'required|string|max:255',
            'mensaje' => 'required|string|max:1000',
            'icono' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'datos' => 'nullable|array',
            'entidad_tipo' => 'nullable|string|max:255',
            'entidad_id' => 'nullable|integer',
            'accion_url' => 'nullable|url|max:500',
            'accion_texto' => 'nullable|string|max:100',
            'canal' => 'nullable|in:web,email,push,sms',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'usuario_id' => 'usuario',
            'tipo' => 'tipo de notificación',
            'categoria' => 'categoría',
            'prioridad' => 'prioridad',
            'titulo' => 'título',
            'mensaje' => 'mensaje',
            'accion_url' => 'URL de acción',
            'accion_texto' => 'texto de acción',
            'expires_at' => 'fecha de expiración',
        ];
    }
}
