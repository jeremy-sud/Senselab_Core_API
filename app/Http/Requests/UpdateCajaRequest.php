<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCajaRequest extends FormRequest
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
            'sucursal_id' => 'sometimes|nullable|exists:sucursales,id',
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('cajas')->where(function ($query) {
                    if ($this->sucursal_id) {
                        return $query->where('sucursal_id', $this->sucursal_id)
                                     ->where('eliminado', 0);
                    }
                    return $query->whereNull('sucursal_id')
                                 ->where('eliminado', 0);
                })->ignore($this->route('caja')),
            ],
            'descripcion' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.max' => 'El nombre no puede superar los 255 caracteres',
            'nombre.unique' => 'Ya existe una caja con este nombre en la sucursal',
            'sucursal_id.exists' => 'La sucursal especificada no existe',
        ];
    }
}
