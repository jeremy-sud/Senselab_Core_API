<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModeloBus;

class BusUnidad extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'buses_unidades';

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
        'placa',
        'modelo_id',
        'capacidad_asientos',
        'identificador_interno',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'capacidad_asientos' => 'integer',
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
     * Relación con el modelo del bus.
     */
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloBus::class, 'modelo_id');
    }

    /**
     * Scope para obtener unidades activas (no eliminadas).
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por placa.
     */
    public function scopePorPlaca($query, $placa)
    {
        return $query->where('placa', 'LIKE', "%{$placa}%");
    }

    /**
     * Scope para buscar por identificador interno.
     */
    public function scopePorIdentificador($query, $identificador)
    {
        return $query->where('identificador_interno', 'LIKE', "%{$identificador}%");
    }

    /**
     * Scope para filtrar por capacidad de asientos.
     */
    public function scopePorCapacidad($query, $minimo, $maximo = null)
    {
        $query = $query->where('capacidad_asientos', '>=', $minimo);
        
        if ($maximo) {
            $query->where('capacidad_asientos', '<=', $maximo);
        }

        return $query;
    }

    /**
     * Obtiene el número de placa formateado.
     */
    public function getPrettyPlacaAttribute(): string
    {
        return strtoupper($this->placa);
    }

    /**
     * Obtiene la identificación completa del bus (placa + identificador interno).
     */
    public function getIdentificacionCompletaAttribute(): string
    {
        return $this->identificador_interno 
            ? "{$this->placa} ({$this->identificador_interno})"
            : $this->placa;
    }
}