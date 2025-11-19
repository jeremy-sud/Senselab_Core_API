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
            'razon_social' => ['sometimes', 'required', 'string', 'max:255'],
            'nit_ruc' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('empresas', 'nit_ruc')->ignore($empresaId)
            ],
            'regimen_tributario_id' => ['sometimes', 'required', 'exists:regimen_tributario,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'pais' => ['sometimes', 'required', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
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
            'razon_social.required' => 'La razón social es obligatoria',
            'nit_ruc.required' => 'El NIT/RUC es obligatorio',
            'nit_ruc.unique' => 'Ya existe una empresa con este NIT/RUC',
            'regimen_tributario_id.required' => 'El régimen tributario es obligatorio',
            'regimen_tributario_id.exists' => 'El régimen tributario seleccionado no existe',
            'email.email' => 'El formato del email no es válido',
            'pais.required' => 'El país es obligatorio',
            'sitio_web.url' => 'El formato del sitio web no es válido',
        ];
    }
}
