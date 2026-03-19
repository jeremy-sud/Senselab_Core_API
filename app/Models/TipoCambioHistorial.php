<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * @var list<string>
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
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'tasa_compra' => 'decimal:5',
        'tasa_venta' => 'decimal:5',
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
    public function scopePorMonedas(Builder $query, mixed $origen, mixed $destino): Builder{
        return $query->where('moneda_origen', $origen)->where('moneda_destino', $destino);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopePorRangoFechas(Builder $query, mixed $inicio, mixed $fin): Builder{
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}