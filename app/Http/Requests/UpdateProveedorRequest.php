<?php

namespace App\Http\Requests;

use App\Rules\CrIdentificacion;
use App\Rules\CrTelefono;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar proveedores
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateProveedorRequest extends FormRequest
{
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
            'tipo_identificacion' => ['sometimes', 'required', 'in:fisica,juridica,dimex,nite,extranjero'],
            'numero_identificacion' => ['sometimes', 'required', 'string', 'max:50', new CrIdentificacion()],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50', new CrTelefono()],
            'celular' => ['nullable', 'string', 'max:50', new CrTelefono()],
            'direccion' => ['nullable', 'string', 'max:500'],
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio',
            'email.email' => 'El formato del email no es válido',
            'contacto_email.email' => 'El formato del email de contacto no es válido',
        ];
    }
}
