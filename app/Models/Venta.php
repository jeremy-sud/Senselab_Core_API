<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Venta extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasCustomSoftDeletes;
    use HasAuditFields;
    use HasActiveScope;
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'ventas';

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
        'sucursal_id',
        'cliente_id',
        'usuario_id',
        'fecha_venta',
        'fecha_vencimiento',
        'tipo_comprobante',
        'serie_comprobante',
        'numero_comprobante',
        'clave_numerica_hacienda',
        'consecutivo_hacienda',
        'moneda',
        'subtotal_bruto_total',
        'monto_descuento_total',
        'subtotal_neto_total',
        'monto_impuesto_total',
        'monto_total_venta',
        'estado_venta',
        'condicion_pago',
        'condicion_venta_dgt',
        'plazo_credito_dias',
        'observaciones',
        'xml_enviado',
        'xml_respuesta_hacienda',
        'estado_hacienda',
        'tipo_referencia_doc',
        'clave_referencia_doc',
        'forma_pago_id',
        'activo',
        'eliminado',
        'fecha_emision_hacienda',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_vencimiento' => 'date',
        'subtotal_bruto_total' => 'float',
        'monto_descuento_total' => 'float',
        'subtotal_neto_total' => 'float',
        'monto_impuesto_total' => 'float',
        'monto_total_venta' => 'float',
        'plazo_credito_dias' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'fecha_emision_hacienda' => 'datetime',
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
     * Relación con el modelo Sucursal.
     */
    public function sucursal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Relación con el modelo Cliente.
     */
    public function cliente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con el modelo Usuario.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con el modelo FormaPago.
     */
    public function formaPago(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    /**
     * Relación con los detalles de venta.
     */
    public function detalles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    /**
     * Relación con las cuentas por cobrar.
     */
    public function cuentasPorCobrar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaPorCobrar::class, 'venta_id');
    }

    /**
     * Relación con las salidas de inventario.
     */
    public function salidasInventario(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalidaInventario::class, 'venta_id');
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
