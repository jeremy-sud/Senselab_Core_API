<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiqueteDetalle extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tiquetes_detalle';

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
        'detalle_venta_id',
        'horario_ruta_id',
        'asiento_numero',
        'nombre_pasajero',
        'identificacion_pasajero',
        'precio_final_tiquete',
        'estado',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'precio_final_tiquete' => 'float',
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
     * Relación con el modelo DetalleVenta.
     */
    public function detalleVenta(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'detalle_venta_id');
    }

    /**
     * Relación con el modelo HorarioRuta.
     */
    public function horarioRuta(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(HorarioRuta::class, 'horario_ruta_id');
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