<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Sucursal extends Model
{
    use BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sucursales';

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
        'direccion',
        'telefono',
        'email',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
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
     * Relación con el modelo Empresa.
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con almacenes de la sucursal.
     */
    public function almacenes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Almacen::class, 'sucursal_id');
    }

    /**
     * Relación con ventas de la sucursal.
     */
    public function ventas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Venta::class, 'sucursal_id');
    }

    /**
     * Relación con cajas de la sucursal.
     */
    public function cajas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Caja::class, 'sucursal_id');
    }

    /**
     * Relación con consecutivos FE de la sucursal.
     */
    public function consecutivosFe(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsecutivoFe::class, 'sucursal_id');
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
}
