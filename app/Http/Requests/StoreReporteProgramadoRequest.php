<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReporteProgramadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'tipo_reporte' => 'required|string|in:estado_resultados,balance_general,flujo_caja',
            'frecuencia' => 'required|string|in:diario,semanal,mensual',
            'formato' => 'sometimes|string|in:pdf,excel,csv',
            'filtros' => 'sometimes|array',
            'filtros.moneda' => 'sometimes|string|in:CRC,USD,EUR',
            'filtros.sucursal_id' => 'sometimes|integer|exists:sucursales,id',
            'destinatarios' => 'required|array|min:1|max:10',
            'destinatarios.*' => 'required|email|max:255',
            'dia_semana' => 'required_if:frecuencia,semanal|nullable|string|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'dia_mes' => 'required_if:frecuencia,mensual|nullable|integer|min:1|max:28',
            'hora_envio' => 'sometimes|date_format:H:i',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_reporte.in' => 'Tipo de reporte inválido. Opciones: estado_resultados, balance_general, flujo_caja',
            'frecuencia.in' => 'Frecuencia inválida. Opciones: diario, semanal, mensual',
            'destinatarios.min' => 'Debe especificar al menos un destinatario',
            'destinatarios.max' => 'Máximo 10 destinatarios por reporte',
            'dia_mes.max' => 'Día del mes máximo 28 para compatibilidad con todos los meses',
        ];
    }
}
