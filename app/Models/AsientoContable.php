<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\AsientoContableFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

class AsientoContable extends Model
{
    /** @use HasFactory<\Database\Factories\AsientoContableFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'asientos_contables';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'numero_asiento',
        'fecha_asiento',
        'tipo_asiento',
        'origen',
        'documento_origen_id',
        'concepto',
        'total_debe',
        'total_haber',
        'estado',
        'usuario_id',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha_asiento' => 'datetime',
        'total_debe' => 'decimal:5',
        'total_haber' => 'decimal:5',
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
     * Scope para filtrar por fecha de asiento.
     */
    public function scopeFechaAsientoBetween(Builder $query, mixed $start, mixed $end): Builder{
        return $query->whereBetween('fecha_asiento', [$start, $end]);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado(Builder $query, mixed $estado): Builder{
        return $query->where('estado', $estado);
    }

    /**
     * Scope para obtener asientos activos (no eliminados).
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para obtener asientos descuadrados.
     */
    public function scopeDescuadrados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereRaw('total_debe != total_haber');
    }

    /**
     * Determina si el asiento está cuadrado (debe = haber).
     */
    public function estaCuadrado(): bool
    {
        return $this->total_debe == $this->total_haber;
    }

    /**
     * Relación: detalles del asiento contable.
     * Cada asiento posee múltiples movimientos (detalle_asientos).
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\DetalleAsiento, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleAsiento::class, 'asiento_contable_id');
    }
}