<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMensajeHaciendaRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'comprobante_id' => ['nullable', 'exists:comprobantes_recibidos_electronicos,id'],
            'clave_numerica' => ['required', 'string', 'max:50'],
            'tipo_mensaje' => ['required', 'in:aceptacion,rechazo,aceptacion_parcial,consulta'],
            'codigo_respuesta' => ['nullable', 'string', 'max:10'],
            'detalle_mensaje' => ['nullable', 'string'],
            'xml_respuesta' => ['nullable', 'string'],
            'fecha_emision' => ['required', 'date'],
            'fecha_procesamiento' => ['nullable', 'date'],
            'estado' => ['nullable', 'in:pendiente,procesado,error'],
            'intentos_envio' => ['nullable', 'integer', 'min:0'],
            'ultimo_error' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'clave_numerica.required' => 'La clave numérica es obligatoria',
            'tipo_mensaje.required' => 'El tipo de mensaje es obligatorio',
            'tipo_mensaje.in' => 'El tipo de mensaje debe ser: aceptación, rechazo, aceptación parcial o consulta',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria',
        ];
    }
}
