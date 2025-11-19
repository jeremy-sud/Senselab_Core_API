<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCambioHistorial extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tipos_cambio_historial';

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
        'fecha',
        'moneda_origen',
        'moneda_destino',
        'tasa_compra',
        'tasa_venta',
        'fuente',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha' => 'date',
        'tasa_compra' => 'float',
        'tasa_venta' => 'float',
        'creado_en' => 'datetime',
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
     * Scope para filtrar por moneda origen y destino.
     */
    public function scopePorMonedas($query, $origen, $destino)
    {
        return $query->where('moneda_origen', $origen)->where('moneda_destino', $destino);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopePorRangoFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}