<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'tipo' => 'sometimes|string|in:estado_resultados,balance_general,flujo_caja',
            'fecha_inicio' => 'sometimes|date|before_or_equal:fecha_fin',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
            'sucursal_id' => 'sometimes|integer|exists:sucursales,id',
            'moneda' => 'sometimes|string|in:CRC,USD,EUR',
            'periodo_comparacion' => 'sometimes|nullable|string|in:mes,trimestre,anio',
            'formato' => 'sometimes|string|in:json,pdf,excel,csv',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.in' => 'Tipo de reporte inválido. Opciones: estado_resultados, balance_general, flujo_caja',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha fin',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha de inicio',
            'moneda.in' => 'Moneda no soportada. Opciones: CRC, USD, EUR',
            'periodo_comparacion.in' => 'Período de comparación inválido. Opciones: mes, trimestre, anio',
            'formato.in' => 'Formato no soportado. Opciones: json, pdf, excel, csv',
        ];
    }
}
