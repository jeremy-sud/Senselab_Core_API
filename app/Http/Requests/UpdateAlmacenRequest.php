<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar almacenes
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateAlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string'],
            'es_principal' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del almacén es obligatorio',
        ];
    }

    /**
     * Validación adicional: solo un almacén principal por sucursal
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $almacenId = $this->route('almacene');
            $almacen = \App\Models\Almacen::find($almacenId);

            if ($this->has('es_principal') && $this->es_principal) {
                $existe = \App\Models\Almacen::where('sucursal_id', $almacen->sucursal_id)
                    ->where('es_principal', true)
                    ->where('id', '!=', $almacenId)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'es_principal',
                        'Ya existe otro almacén principal para esta sucursal'
                    );
                }
            }
        });
    }
}
