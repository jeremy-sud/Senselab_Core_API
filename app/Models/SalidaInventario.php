<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalidaInventario extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'salidas_inventario';

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
        'empresa_id',
        'almacen_id',
        'fecha_salida',
        'tipo_salida',
        'venta_id',
        'cliente_id',
        'proveedor_id',
        'documento_referencia',
        'estado',
        'monto_total',
        'observaciones',
        'descripcion',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha_salida' => 'datetime',
        'monto_total' => 'float',
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
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con el modelo Almacen.
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    /**
     * Relación con el modelo Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con el modelo Proveedor.
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Scope para filtrar solo los registros activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo los registros no eliminados.
     */
    public function scopeNoEliminados($query)
    {
        return $query->where('eliminado', false);
    }
}
