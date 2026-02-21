<?php

namespace App\DTOs\API\Cliente;

use Illuminate\Http\Request;

/**
 * ClienteUpdateDTO - DTO para actualizar cliente
 *
 * Maneja actualizaciones parciales de datos del cliente.
 */
final class ClienteUpdateDTO
{
    private function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $identificacion = null,
        public readonly ?string $provincia = null,
        public readonly ?string $canton = null,
        public readonly ?string $distrito = null,
        public readonly ?float $saldo_deudor = null,
        public readonly ?bool $es_empresa = null,
        public readonly ?bool $activo = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->input('nombre'),
            email: $request->input('email'),
            telefono: $request->input('telefono'),
            direccion: $request->input('direccion'),
            identificacion: $request->input('identificacion'),
            provincia: $request->input('provincia'),
            canton: $request->input('canton'),
            distrito: $request->input('distrito'),
            saldo_deudor: $request->input('saldo_deudor') !== null
                ? (float) $request->input('saldo_deudor')
                : null,
            es_empresa: $request->input('es_empresa') !== null
                ? (bool) $request->input('es_empresa')
                : null,
            activo: $request->input('activo') !== null
                ? (bool) $request->input('activo')
                : null,
        );
    }

    /**
     * Convierte a array para actualizar modelo
     */
    /**
     * Convert the DTO to model data array.
     *
     * @return array<string, mixed>
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
        if ($this->identificacion !== null) {
            $data['identificacion'] = trim($this->identificacion);
        }
        if ($this->provincia !== null) {
            $data['provincia'] = $this->provincia;
        }
        if ($this->canton !== null) {
            $data['canton'] = $this->canton;
        }
        if ($this->distrito !== null) {
            $data['distrito'] = $this->distrito;
        }
        if ($this->saldo_deudor !== null) {
            $data['saldo_deudor'] = $this->saldo_deudor;
        }
        if ($this->es_empresa !== null) {
            $data['es_empresa'] = $this->es_empresa;
        }
        if ($this->activo !== null) {
            $data['activo'] = $this->activo;
        }

        return $data;
    }

    /**
     * Reglas de validación
     */
    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:clientes,email',
            'telefono' => 'sometimes|string|max:20',
            'direccion' => 'sometimes|string|max:500',
            'identificacion' => 'sometimes|string|unique:clientes,identificacion',
            'provincia' => 'sometimes|string|max:100',
            'canton' => 'sometimes|string|max:100',
            'distrito' => 'sometimes|string|max:100',
            'saldo_deudor' => 'sometimes|numeric|min:0',
            'es_empresa' => 'sometimes|boolean',
            'activo' => 'sometimes|boolean',
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'email.unique' => 'El email ya está registrado',
            'identificacion.unique' => 'La identificación ya está registrada',
        ];
    }
}
