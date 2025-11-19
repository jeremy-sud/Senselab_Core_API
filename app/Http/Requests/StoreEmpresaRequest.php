<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear empresas
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreEmpresaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta request.
     */
    public function authorize(): bool
    {
        return true; // Cambia según tu lógica de autorización
    }

    /**
     * Reglas de validación
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'nit_ruc' => ['required', 'string', 'max:50', 'unique:empresas,nit_ruc'],
            'regimen_tributario_id' => ['required', 'exists:regimen_tributario,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'pais' => ['required', 'string', 'max:100'],
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

    /**
     * Nombres de atributos personalizados
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la empresa',
            'razon_social' => 'razón social',
            'nit_ruc' => 'NIT/RUC',
            'regimen_tributario_id' => 'régimen tributario',
            'sitio_web' => 'sitio web',
            'codigo_postal' => 'código postal',
        ];
    }
}
