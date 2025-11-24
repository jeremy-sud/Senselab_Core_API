<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class TipoCuenta extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tipos_cuentas';

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos que se pueden asignar de manera masiva.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'naturaleza',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
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
}