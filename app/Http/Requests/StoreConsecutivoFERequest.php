<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsecutivoFERequest extends FormRequest
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
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'tipo_documento_dgt' => 'required|string|size:2|in:01,02,03,04,08,09',
            'prefijo' => 'required|string|max:10',
            'consecutivo_actual' => 'nullable|integer|min:1',
            'estado' => 'nullable|string|in:Activo,Agotado,Inactivo',
            'fecha_autorizacion' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tipo_documento_dgt.required' => 'El tipo de documento DGT es requerido',
            'tipo_documento_dgt.size' => 'El tipo de documento debe tener exactamente 2 caracteres',
            'tipo_documento_dgt.in' => 'El tipo de documento debe ser un código válido (01, 02, 03, 04, 08, 09)',
            'prefijo.required' => 'El prefijo es requerido',
            'prefijo.max' => 'El prefijo no puede superar los 10 caracteres',
            'consecutivo_actual.min' => 'El consecutivo debe ser mayor a 0',
            'estado.in' => 'El estado debe ser Activo, Agotado o Inactivo',
            'sucursal_id.exists' => 'La sucursal especificada no existe',
            'fecha_autorizacion.date' => 'La fecha de autorización debe ser una fecha válida',
        ];
    }
}
