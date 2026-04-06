<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReporteProgramadoRequest extends FormRequest
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
            'nombre' => 'sometimes|string|max:100',
            'tipo_reporte' => 'sometimes|string|in:estado_resultados,balance_general,flujo_caja',
            'frecuencia' => 'sometimes|string|in:diario,semanal,mensual',
            'formato' => 'sometimes|string|in:pdf,excel,csv',
            'filtros' => 'sometimes|array',
            'filtros.moneda' => 'sometimes|string|in:CRC,USD,EUR',
            'filtros.sucursal_id' => 'sometimes|integer|exists:sucursales,id',
            'destinatarios' => 'sometimes|array|min:1|max:10',
            'destinatarios.*' => 'required_with:destinatarios|email|max:255',
            'dia_semana' => 'nullable|string|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'dia_mes' => 'nullable|integer|min:1|max:28',
            'hora_envio' => 'sometimes|date_format:H:i',
            'activo' => 'sometimes|boolean',
        ];
    }
}
