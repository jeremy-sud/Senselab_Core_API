<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLogAccesoSistemaRequest extends FormRequest
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
        return [
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'email' => ['nullable', 'email', 'max:191'],
            'tipo_evento' => ['sometimes', 'in:login_exitoso,login_fallido,logout,cambio_password,reset_password,bloqueo_cuenta,desbloqueo_cuenta'],
            'ip_address' => ['sometimes', 'ip', 'max:45'],
            'user_agent' => ['nullable', 'string', 'max:255'],
            'metodo_autenticacion' => ['nullable', 'string', 'max:50'],
            'razon_fallo' => ['nullable', 'string', 'max:255'],
            'sesion_id' => ['nullable', 'string', 'max:191'],
            'duracion_sesion' => ['nullable', 'integer', 'min:0'],
            'pais' => ['nullable', 'string', 'size:2'],
            'ciudad' => ['nullable', 'string', 'max:100'],
        ];
    }
}
