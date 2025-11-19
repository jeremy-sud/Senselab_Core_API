<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear sucursales
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'canton' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'es_principal' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'nombre.required' => 'El nombre de la sucursal es obligatorio',
            'email.email' => 'El formato del email no es válido',
        ];
    }

    /**
     * Validación adicional: solo una sucursal principal por empresa
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->es_principal) {
                $existe = \App\Models\Sucursal::where('empresa_id', $this->empresa_id)
                    ->where('es_principal', true)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'es_principal',
                        'Ya existe una sucursal principal para esta empresa'
                    );
                }
            }
        });
    }
}
