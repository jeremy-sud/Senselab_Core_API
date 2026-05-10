<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'servicio' => 'nullable|string|max:150',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'monto_total' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ];
    }
}
