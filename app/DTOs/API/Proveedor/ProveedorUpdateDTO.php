<?php

namespace App\DTOs\API\Proveedor;

use Illuminate\Http\Request;

/**
 * ProveedorUpdateDTO - Data Transfer Object para actualizar proveedor
 *
 * Maneja actualizaciones parciales de proveedores con campos opcionales.
 * Cada campo null es ignorado en la actualización (no sobrescribe valores existentes).
 */
final class ProveedorUpdateDTO
{
    private function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $numero_identificacion = null,
        public readonly ?float $saldo_acreedor = null,
        public readonly ?bool $activo = null,
        public readonly ?array $contactos = null,
    ) {}

    /**
     * Factory method desde Request HTTP
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->input('nombre'),
            email: $request->input('email'),
            telefono: $request->input('telefono'),
            direccion: $request->input('direccion'),
            numero_identificacion: $request->input('numero_identificacion'),
            saldo_acreedor: $request->input('saldo_acreedor') !== null
                ? (float) $request->input('saldo_acreedor')
                : null,
            activo: $request->input('activo') !== null
                ? (bool) $request->input('activo')
                : null,
            contactos: $request->input('contactos'),
        );
    }

    /**
     * Convierte DTO a array para actualizar modelo
     * Solo incluye campos no-null
     */
    public function toModelData(): array
    {
        $data = [];

        if ($this->nombre !== null) {
            $data['nombre'] = trim($this->nombre);
        }
        if ($this->email !== null) {
            $data['email'] = strtolower(trim($this->email));
        }
        if ($this->telefono !== null) {
            $data['telefono'] = trim($this->telefono);
        }
        if ($this->direccion !== null) {
            $data['direccion'] = trim($this->direccion);
        }
        if ($this->numero_identificacion !== null) {
            $data['numero_identificacion'] = trim($this->numero_identificacion);
        }
        if ($this->saldo_acreedor !== null) {
            $data['saldo_acreedor'] = $this->saldo_acreedor;
        }
        if ($this->activo !== null) {
            $data['activo'] = $this->activo;
        }
        if ($this->contactos !== null) {
            $data['contactos'] = json_encode($this->contactos);
        }

        return $data;
    }

    /**
     * Reglas de validación para actualizar proveedor
     *
     * Usa 'sometimes' para campos opcionales
     * Actualiza solo si están presentes en request
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:proveedores,email',
            'telefono' => 'sometimes|required|string|max:20',
            'direccion' => 'sometimes|required|string|max:500',
            'numero_identificacion' => 'sometimes|required|string|unique:proveedores,numero_identificacion',
            'saldo_acreedor' => 'sometimes|nullable|numeric|min:0',
            'activo' => 'sometimes|boolean',
            'contactos' => 'sometimes|nullable|array',
            'contactos.*.nombre' => 'required_with:contactos|string|max:255',
            'contactos.*.numero' => 'required_with:contactos|string|max:20',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public static function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es requerido',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'email.email' => 'El email debe ser una dirección válida',
            'email.unique' => 'El email ya está registrado para otro proveedor',
            'numero_identificacion.unique' => 'El número de identificación ya existe',
            'saldo_acreedor.numeric' => 'El saldo debe ser un número',
            'saldo_acreedor.min' => 'El saldo no puede ser negativo',
        ];
    }
}
