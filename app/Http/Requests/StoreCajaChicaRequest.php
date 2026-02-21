<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCajaChicaRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'monto_inicial' => 'required|numeric|min:0|max:99999999.99',
            'responsable_id' => 'required|integer|exists:empleados,id',
            'fecha_apertura' => 'required|date',
            'estado' => 'nullable|string|in:Abierta,Cerrada,Liquidada',
            'observaciones' => 'nullable|string',
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
            'nombre.required' => 'El nombre del fondo es requerido',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'monto_inicial.required' => 'El monto inicial es requerido',
            'monto_inicial.numeric' => 'El monto inicial debe ser un valor numérico',
            'monto_inicial.min' => 'El monto inicial debe ser mayor o igual a 0',
            'monto_inicial.max' => 'El monto inicial no puede superar 99,999,999.99',
            'responsable_id.required' => 'El responsable es requerido',
            'responsable_id.exists' => 'El empleado responsable especificado no existe',
            'fecha_apertura.required' => 'La fecha de apertura es requerida',
            'fecha_apertura.date' => 'La fecha de apertura debe ser una fecha válida',
            'estado.in' => 'El estado debe ser Abierta, Cerrada o Liquidada',
        ];
    }
}
