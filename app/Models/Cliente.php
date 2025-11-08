<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'clientes';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Tipos de identificación DGT.
     */
    const TIPO_DGT = [
        '01' => 'Cédula física',
        '02' => 'Cédula jurídica',
        '03' => 'DIMEX',
        '04' => 'NITE',
        '05' => 'Extranjero',
        '06' => 'Identificación específica sin país',
        '07' => 'Pasaporte'
    ];

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'apellido',
        'tipo_documento_interno',
        'num_identificacion_dgt',
        'tipo_identificacion_dgt',
        'telefono',
        'email',
        'actividad_economica_dgt',
        'direccion'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con las ventas.
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Scope para obtener clientes activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por nombre o apellido.
     */
    public function scopePorNombre($query, $nombre)
    {
        return $query->where(function($q) use ($nombre) {
            $q->where('nombre', 'LIKE', "%{$nombre}%")
              ->orWhere('apellido', 'LIKE', "%{$nombre}%");
        });
    }

    /**
     * Scope para buscar por identificación.
     */
    public function scopePorIdentificacion($query, $identificacion)
    {
        return $query->where('num_identificacion_dgt', 'LIKE', "%{$identificacion}%");
    }

    /**
     * Scope para buscar por tipo de identificación DGT.
     */
    public function scopePorTipoIdentificacion($query, $tipo)
    {
        return $query->where('tipo_identificacion_dgt', $tipo);
    }

    /**
     * Scope para buscar por email.
     */
    public function scopePorEmail($query, $email)
    {
        return $query->where('email', 'LIKE', "%{$email}%");
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cliente) {
            // Validar tipo de identificación DGT
            if ($cliente->tipo_identificacion_dgt && !array_key_exists($cliente->tipo_identificacion_dgt, self::TIPO_DGT)) {
                throw new \Exception('Tipo de identificación DGT inválido.');
            }

            // Validar que la identificación sea única por empresa y tipo
            if ($cliente->tipo_documento_interno && $cliente->num_identificacion_dgt) {
                $exists = static::where('id', '!=', $cliente->id)
                    ->where('empresa_id', $cliente->empresa_id)
                    ->where('tipo_documento_interno', $cliente->tipo_documento_interno)
                    ->where('num_identificacion_dgt', $cliente->num_identificacion_dgt)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Ya existe un cliente con esta identificación en la empresa.');
                }
            }
        });
    }

    /**
     * Obtiene el nombre completo del cliente.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    /**
     * Obtiene la descripción del tipo de identificación DGT.
     */
    public function getTipoIdentificacionDescripcionAttribute(): ?string
    {
        return self::TIPO_DGT[$this->tipo_identificacion_dgt] ?? null;
    }

    /**
     * Verifica si el cliente tiene identificación válida para facturación electrónica.
     */
    public function tieneIdentificacionValida(): bool
    {
        return !empty($this->tipo_identificacion_dgt) && 
               !empty($this->num_identificacion_dgt);
    }
}
