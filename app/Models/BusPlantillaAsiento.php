<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusPlantillaAsiento extends Model
{
    use HasFactory, BelongsToTenant;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'bus_plantillas_asientos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'pisos',
        'total_asientos',
        'filas_piso_1',
        'columnas_piso_1',
        'filas_piso_2',
        'columnas_piso_2',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'pisos' => 'integer',
        'total_asientos' => 'integer',
        'filas_piso_1' => 'integer',
        'columnas_piso_1' => 'integer',
        'filas_piso_2' => 'integer',
        'columnas_piso_2' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function asientos(): HasMany
    {
        return $this->hasMany(BusAsiento::class, 'plantilla_id');
    }

    public function buses(): HasMany
    {
        return $this->hasMany(BusUnidad::class, 'plantilla_id');
    }
}
