<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComprobanteRecibidoElectronicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'clave_numerica' => 'required|string|size:50|unique:comprobantes_recibidos_electronicos,clave_numerica',
            'consecutivo_receptor' => 'nullable|string|max:20',
            'tipo_documento_dgt' => 'required|string|size:2|in:01,02,03,04,08,09',
            'fecha_emision_comprobante' => 'required|date',
            'moneda' => 'nullable|string|size:3|in:CRC,USD,EUR',
            'total_impuesto' => 'nullable|numeric|min:0',
            'total_comprobante' => 'required|numeric|min:0',
            'xml_contenido' => 'nullable|string',
            'entrada_inventario_id' => 'nullable|exists:entradas_inventario,id'
        ];
    }

    public function messages(): array
    {
        return [
            'clave_numerica.required' => 'La clave numérica es obligatoria',
            'clave_numerica.size' => 'La clave numérica debe tener exactamente 50 caracteres',
            'clave_numerica.unique' => 'Ya existe un comprobante con esta clave numérica',
            'tipo_documento_dgt.required' => 'El tipo de documento DGT es obligatorio',
            'tipo_documento_dgt.in' => 'El tipo de documento debe ser: 01, 02, 03, 04, 08 o 09',
            'fecha_emision_comprobante.required' => 'La fecha de emisión es obligatoria',
            'total_comprobante.required' => 'El total del comprobante es obligatorio',
            'total_comprobante.min' => 'El total del comprobante debe ser mayor o igual a 0'
        ];
    }
}
