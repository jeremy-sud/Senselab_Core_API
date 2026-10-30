<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutaTarifaTramo extends Model
{
    use HasFactory, BelongsToTenant;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'ruta_tarifas_tramos';

    protected $fillable = [
        'empresa_id',
        'ruta_id',
        'parada_origen_id',
        'parada_destino_id',
        'precio_general',
        'precio_adulto_mayor',
        'precio_estudiante',
        'precio_menor',
        'codigo_resolucion_aresep',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'precio_general' => 'decimal:2',
        'precio_adulto_mayor' => 'decimal:2',
        'precio_estudiante' => 'decimal:2',
        'precio_menor' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function paradaOrigen(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_origen_id');
    }

    public function paradaDestino(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_destino_id');
    }
}
