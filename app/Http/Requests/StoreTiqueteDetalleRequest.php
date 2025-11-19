<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Tiquete
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreTiqueteDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detalle_venta_id' => ['required', 'integer', 'exists:detalle_ventas,id'],
            'horario_ruta_id' => ['required', 'integer', 'exists:horarios_ruta,id'],
            'asiento_numero' => ['required', 'string', 'max:10'],
            'nombre_pasajero' => ['nullable', 'string', 'max:255'],
            'identificacion_pasajero' => ['nullable', 'string', 'max:50'],
            'precio_final_tiquete' => ['required', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Vendido', 'Usado', 'Cancelado'])],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que el asiento no esté ocupado
            if ($this->has('horario_ruta_id') && $this->has('asiento_numero')) {
                $ocupado = \App\Models\TiqueteDetalle::where('horario_ruta_id', $this->horario_ruta_id)
                    ->where('asiento_numero', $this->asiento_numero)
                    ->where('estado', '!=', 'Cancelado')
                    ->where('eliminado', 0)
                    ->exists();

                if ($ocupado) {
                    $validator->errors()->add('asiento_numero', 'Este asiento ya está ocupado');
                }
            }

            // Validar que haya asientos disponibles
            if ($this->has('horario_ruta_id')) {
                $horario = \App\Models\HorarioRuta::find($this->horario_ruta_id);
                if ($horario && $horario->asientos_disponibles <= 0) {
                    $validator->errors()->add('horario_ruta_id', 'No hay asientos disponibles para este viaje');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'detalle_venta_id.required' => 'El detalle de venta es obligatorio',
            'detalle_venta_id.exists' => 'El detalle de venta seleccionado no existe',
            'horario_ruta_id.required' => 'El horario de ruta es obligatorio',
            'horario_ruta_id.exists' => 'El horario de ruta seleccionado no existe',
            'asiento_numero.required' => 'El número de asiento es obligatorio',
            'precio_final_tiquete.required' => 'El precio del tiquete es obligatorio',
            'precio_final_tiquete.min' => 'El precio debe ser mayor o igual a 0'
        ];
    }

    public function attributes(): array
    {
        return [
            'detalle_venta_id' => 'detalle de venta',
            'horario_ruta_id' => 'horario de ruta',
            'asiento_numero' => 'número de asiento',
            'nombre_pasajero' => 'nombre del pasajero',
            'identificacion_pasajero' => 'identificación del pasajero',
            'precio_final_tiquete' => 'precio del tiquete',
            'estado' => 'estado'
        ];
    }
}
