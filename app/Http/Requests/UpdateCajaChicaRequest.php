<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCajaChicaRequest extends FormRequest
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
            'nombre' => 'sometimes|string|max:255',
            'monto_inicial' => 'sometimes|numeric|min:0|max:99999999.99',
            'saldo_actual' => 'sometimes|numeric|max:99999999.99',
            'responsable_id' => 'sometimes|integer|exists:empleados,id',
            'fecha_apertura' => 'sometimes|date',
            'fecha_cierre' => 'nullable|date',
            'estado' => 'sometimes|string|in:Abierta,Cerrada,Liquidada',
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
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'monto_inicial.numeric' => 'El monto inicial debe ser un valor numérico',
            'monto_inicial.min' => 'El monto inicial debe ser mayor o igual a 0',
            'monto_inicial.max' => 'El monto inicial no puede superar 99,999,999.99',
            'saldo_actual.numeric' => 'El saldo actual debe ser un valor numérico',
            'saldo_actual.max' => 'El saldo actual no puede superar 99,999,999.99',
            'responsable_id.exists' => 'El empleado responsable especificado no existe',
            'fecha_apertura.date' => 'La fecha de apertura debe ser una fecha válida',
            'fecha_cierre.date' => 'La fecha de cierre debe ser una fecha válida',
            'estado.in' => 'El estado debe ser Abierta, Cerrada o Liquidada',
        ];
    }
}
