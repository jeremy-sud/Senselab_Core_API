<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCajaRequest extends FormRequest
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cajas')->where(function ($query) {
                    if ($this->sucursal_id) {
                        return $query->where('sucursal_id', $this->sucursal_id)
                                     ->where('eliminado', 0);
                    }
                    return $query->whereNull('sucursal_id')
                                 ->where('eliminado', 0);
                }),
            ],
            'descripcion' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la caja es requerido',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres',
            'nombre.unique' => 'Ya existe una caja con este nombre en la sucursal',
            'sucursal_id.exists' => 'La sucursal especificada no existe',
        ];
    }
}
