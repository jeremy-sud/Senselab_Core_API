<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar empresas
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateEmpresaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        $empresaId = $this->route('empresa');

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'num_identificacion_dgt' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('empresas', 'num_identificacion_dgt')->ignore($empresaId)
            ],
            'tipo_identificacion' => ['nullable', 'string', 'max:2'],
            'actividad_economica_principal' => ['nullable', 'string', 'max:6'],
            'regimen_tributario_id' => ['nullable', 'exists:regimenes_tributarios,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string', 'max:2'],
            'canton' => ['nullable', 'string', 'max:2'],
            'distrito' => ['nullable', 'string', 'max:2'],
            'certificado_llave_fe' => ['nullable', 'string'],
            'pin_llave_fe_hash' => ['nullable', 'string', 'max:255'],
            'prefijo_orden_compra' => ['nullable', 'string', 'max:20'],
            'moneda_defecto' => ['nullable', 'string', 'max:3'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio',
            'num_identificacion_dgt.required' => 'El número de identificación es obligatorio',
            'num_identificacion_dgt.unique' => 'Ya existe una empresa con este número de identificación',
            'regimen_tributario_id.exists' => 'El régimen tributario seleccionado no existe',
            'email.email' => 'El formato del email no es válido',
        ];
    }
}
