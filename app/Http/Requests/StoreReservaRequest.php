<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'servicio' => 'required|string|max:150',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'monto_total' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ];
    }
}
