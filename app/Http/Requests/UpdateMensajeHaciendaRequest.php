<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMensajeHaciendaRequest extends FormRequest
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
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'comprobante_id' => ['nullable', 'exists:comprobantes_recibidos_electronicos,id'],
            'clave_numerica' => ['sometimes', 'string', 'max:50'],
            'tipo_mensaje' => ['sometimes', 'in:aceptacion,rechazo,aceptacion_parcial,consulta'],
            'codigo_respuesta' => ['nullable', 'string', 'max:10'],
            'detalle_mensaje' => ['nullable', 'string'],
            'xml_respuesta' => ['nullable', 'string'],
            'fecha_emision' => ['sometimes', 'date'],
            'fecha_procesamiento' => ['nullable', 'date'],
            'estado' => ['sometimes', 'in:pendiente,procesado,error'],
            'intentos_envio' => ['sometimes', 'integer', 'min:0'],
            'ultimo_error' => ['nullable', 'string'],
        ];
    }
}
