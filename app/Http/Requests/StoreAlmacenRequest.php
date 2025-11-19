<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear almacenes
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreAlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string'],
            'es_principal' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'sucursal_id.required' => 'La sucursal es obligatoria',
            'nombre.required' => 'El nombre del almacén es obligatorio',
        ];
    }

    /**
     * Validación adicional: solo un almacén principal por sucursal
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->es_principal) {
                $existe = \App\Models\Almacen::where('sucursal_id', $this->sucursal_id)
                    ->where('es_principal', true)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'es_principal',
                        'Ya existe un almacén principal para esta sucursal'
                    );
                }
            }
        });
    }
}
