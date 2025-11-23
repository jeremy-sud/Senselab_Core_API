<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

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
        'tipo_identificacion',
        'numero_identificacion',
        'nombre',
        'apellidos',
        'nombre_comercial',
        'email',
        'telefono',
        'direccion',
        'provincia',
        'canton',
        'distrito',
        'limite_credito',
        'plazo_credito_dias',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'limite_credito' => 'decimal:2',
        'plazo_credito_dias' => 'integer',
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
     * Relación con cuentas por cobrar.
     */
    public function cuentasPorCobrar(): HasMany
    {
        return $this->hasMany(CuentaPorCobrar::class, 'cliente_id');
    }

    /**
     * Relación con salidas de inventario.
     */
    public function salidasInventario(): HasMany
    {
        return $this->hasMany(SalidaInventario::class, 'cliente_id');
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
              ->orWhere('apellidos', 'LIKE', "%{$nombre}%");
        });
    }

    /**
     * Scope para buscar por identificación.
     */
    public function scopePorIdentificacion($query, $identificacion)
    {
        return $query->where('numero_identificacion', 'LIKE', "%{$identificacion}%");
    }

    /**
     * Scope para buscar por tipo de identificación.
     */
    public function scopePorTipoIdentificacion($query, $tipo)
    {
        return $query->where('tipo_identificacion', $tipo);
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
            // Validar que la identificación sea única por empresa
            if ($cliente->numero_identificacion) {
                $exists = static::where('id', '!=', $cliente->id)
                    ->where('empresa_id', $cliente->empresa_id)
                    ->where('numero_identificacion', $cliente->numero_identificacion)
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
        return trim("{$this->nombre} {$this->apellidos}");
    }

    /**
     * Obtiene la descripción del tipo de identificación DGT.
     */
    public function getTipoIdentificacionDescripcionAttribute(): ?string
    {
        return self::TIPO_DGT[$this->tipo_identificacion] ?? null;
    }

    /**
     * Verifica si el cliente tiene identificación válida para facturación electrónica.
     */
    public function tieneIdentificacionValida(): bool
    {
        return !empty($this->tipo_identificacion) && 
               !empty($this->numero_identificacion);
    }
}
