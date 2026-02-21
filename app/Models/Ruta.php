<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Ruta extends Model
{
    use BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'rutas';

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos que se pueden asignar de manera masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'origen',
        'destino',
        'distancia_km',
        'duracion_estimada',
        'tarifa_base',
        'observaciones',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distancia_km' => 'float',
        'duracion_estimada' => 'integer',
        'tarifa_base' => 'float',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Indica si el modelo tiene marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Relación con el modelo Empresa.
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para filtrar solo los registros activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo los registros no eliminados.
     */
    public function scopeNoEliminados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('eliminado', false);
    }

    /**
     * Relación: horarios/viajes programados para esta ruta.
     */
    public function horariosRuta(): HasMany
    {
        return $this->hasMany(HorarioRuta::class, 'ruta_id');
    }
}