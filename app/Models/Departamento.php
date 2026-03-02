<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

/**
 * Modelo Departamento — Catálogo de departamentos organizacionales.
 *
 * Tabla global compartida entre empresas (no usa BelongsToTenant).
 * Referenciado por Empleado::departamento().
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $codigo
 * @property bool $activo
 * @property bool $eliminado
 */
class Departamento extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'departamentos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'codigo',
        'activo',
        'eliminado',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Empleados asignados a este departamento.
     */
    public function empleados(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Empleado::class, 'departamento_id');
    }

    /**
     * Scope: departamentos activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }
}
