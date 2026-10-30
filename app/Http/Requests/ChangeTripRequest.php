<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo_boleto' => ['required', 'string', 'max:60'],
            'identificacion_pasajero' => ['required', 'string', 'max:50'],
            'nuevo_horario_ruta_id' => ['required', 'integer', 'exists:horarios_ruta,id'],
            'nuevo_asiento_numero' => ['required', 'string', 'max:10'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'horas_minimas_anticipacion' => ['nullable', 'integer', 'min:1', 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo_boleto.required' => 'El código de boleto actual es obligatorio.',
            'identificacion_pasajero.required' => 'El documento de identificación del pasajero es obligatorio por seguridad.',
            'nuevo_horario_ruta_id.required' => 'Debe seleccionar el nuevo viaje de destino.',
            'nuevo_horario_ruta_id.exists' => 'El nuevo viaje seleccionado no existe en el sistema.',
            'nuevo_asiento_numero.required' => 'Debe indicar el nuevo asiento a ocupar.',
        ];
    }
}
