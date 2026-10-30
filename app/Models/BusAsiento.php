<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusAsiento extends Model
{
    use HasFactory, BelongsToTenant;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'bus_asientos';

    protected $fillable = [
        'empresa_id',
        'plantilla_id',
        'bus_id',
        'piso',
        'fila',
        'columna',
        'tipo_elemento',
        'numero_asiento',
        'clase',
        'ubicacion',
        'es_asiento',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'piso' => 'integer',
        'fila' => 'integer',
        'columna' => 'integer',
        'es_asiento' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(BusPlantillaAsiento::class, 'plantilla_id');
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(BusUnidad::class, 'bus_id');
    }
}
