<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Cabys extends Model
{

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'cabys';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'codigo',
        'descripcion',
        'impuesto_iva_predeterminado',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'impuesto_iva_predeterminado' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
    /**
     * Obtiene los productos asociados a este código CAByS.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(\App\Models\Producto::class, 'cabys_id');
    }
    /**
     * Scope para buscar por código CAByS.
     */
    public function scopePorCodigo(Builder $query, mixed $codigo): Builder{
        return $query->where('codigo', 'LIKE', "%{$codigo}%");
    }

    /**
     * Scope para buscar por descripción.
     */
    public function scopePorDescripcion(Builder $query, mixed $descripcion): Builder{
        return $query->where('descripcion', 'LIKE', "%{$descripcion}%");
    }

    /**
     * Scope para filtrar por tasa de IVA.
     */
    public function scopePorTasaIva(Builder $query, mixed $tasa): Builder{
        return $query->where('impuesto_iva_predeterminado', $tasa);
    }

    /**
     * Scope para obtener códigos activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Formatea el código CAByS con guiones para mejor legibilidad.
     * Ejemplo: 2811100000000 -> 2811-1000-0000-0
     */
    public function getCodigoFormateadoAttribute(): string
    {
        $codigo = $this->codigo;
        if (strlen($codigo) === 13) {
            return substr($codigo, 0, 4) . '-' . 
                   substr($codigo, 4, 4) . '-' . 
                   substr($codigo, 8, 4) . '-' . 
                   substr($codigo, 12);
        }
        return $codigo;
    }

    /**
     * Obtiene la tasa de IVA formateada como porcentaje.
     */
    public function getTasaIvaFormateadaAttribute(): string
    {
        return $this->impuesto_iva_predeterminado . '%';
    }

    /**
     * Valida si el código CAByS tiene el formato correcto (13 dígitos).
     */
    public static function validarCodigo(string $codigo): bool
    {
        return preg_match('/^\d{13}$/', $codigo) === 1;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($cabys) {
            // Asegura que el código tenga exactamente 13 dígitos
            if (!self::validarCodigo($cabys->codigo)) {
                throw new \InvalidArgumentException('El código CAByS debe tener exactamente 13 dígitos numéricos.');
            }
        });

        static::updating(function ($cabys) {
            if ($cabys->isDirty('codigo') && !self::validarCodigo($cabys->codigo)) {
                throw new \InvalidArgumentException('El código CAByS debe tener exactamente 13 dígitos numéricos.');
            }
        });
    }
}