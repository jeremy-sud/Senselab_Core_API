<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear proveedores
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'tipo_identificacion' => ['required', 'in:fisica,juridica,dimex,nite,extranjero'],
            'numero_identificacion' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'pais' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_telefono' => ['nullable', 'string', 'max:50'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio',
            'email.email' => 'El formato del email no es válido',
            'contacto_email.email' => 'El formato del email de contacto no es válido',
            'dias_credito.min' => 'Los días de crédito deben ser mayor o igual a 0',
            'limite_credito.min' => 'El límite de crédito debe ser mayor o igual a 0',
        ];
    }
}
