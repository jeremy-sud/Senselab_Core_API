<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

class AsientoContable extends Model
{
    use HasFactory, BelongsToTenant;

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
    public function scopeFechaAsientoBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha_asiento', [$start, $end]);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para obtener asientos activos (no eliminados).
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para obtener asientos descuadrados.
     */
    public function scopeDescuadrados($query)
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
}