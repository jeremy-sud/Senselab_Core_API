<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RutaParada extends Model
{
    use HasFactory, BelongsToTenant;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'ruta_paradas';

    protected $fillable = [
        'empresa_id',
        'ruta_id',
        'nombre',
        'codigo',
        'orden',
        'ciudad',
        'direccion',
        'latitud',
        'longitud',
        'distancia_km_origen',
        'minutos_estimados_origen',
        'es_terminal',
        'permite_abordaje',
        'permite_bajada',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'orden' => 'integer',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'distancia_km_origen' => 'decimal:2',
        'minutos_estimados_origen' => 'integer',
        'es_terminal' => 'boolean',
        'permite_abordaje' => 'boolean',
        'permite_bajada' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function tarifasOrigen(): HasMany
    {
        return $this->hasMany(RutaTarifaTramo::class, 'parada_origen_id');
    }

    public function tarifasDestino(): HasMany
    {
        return $this->hasMany(RutaTarifaTramo::class, 'parada_destino_id');
    }
}
