<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaChica extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'caja_chica';

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
        'fecha',
        'descripcion',
        'monto',
        'tipo',
        'responsable_id'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha' => 'datetime',
        'monto' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Tipos de movimientos permitidos en caja chica.
     */
    const TIPO_INGRESO = 'Ingreso';
    const TIPO_EGRESO = 'Egreso';

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con el usuario responsable.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Scope para obtener movimientos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por tipo de movimiento.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeFechaBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha', [$start, $end]);
    }

    /**
     * Scope para filtrar por responsable.
     */
    public function scopePorResponsable($query, $responsableId)
    {
        return $query->where('responsable_id', $responsableId);
    }

    /**
     * Scope para filtrar por rango de monto.
     */
    public function scopePorMonto($query, $minimo, $maximo = null)
    {
        $query = $query->where('monto', '>=', $minimo);
        
        if ($maximo) {
            $query->where('monto', '<=', $maximo);
        }

        return $query;
    }

    /**
     * Determina si el movimiento es un ingreso.
     */
    public function esIngreso(): bool
    {
        return $this->tipo === self::TIPO_INGRESO;
    }

    /**
     * Determina si el movimiento es un egreso.
     */
    public function esEgreso(): bool
    {
        return $this->tipo === self::TIPO_EGRESO;
    }

    /**
     * Obtiene el monto con signo según el tipo de movimiento.
     */
    public function getMontoSignedAttribute(): float
    {
        return $this->esEgreso() ? -$this->monto : $this->monto;
    }
}