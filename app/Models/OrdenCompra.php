<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

/**
 * Modelo para `ordenes_compra`.
 *
 * Notas:
 * - He inferido campos comunes (empresa_id, proveedor_id, numero, fechas, totales, estado).
 * - Dependencias que pueden faltar: `Proveedor`, `DetalleOrdenCompra`, `Pago`.
 *   Si esos modelos no existen, crea versiones mínimas para evitar warnings del linter.
 */
class OrdenCompra extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ordenes_compra';

    // Timestamps personalizados
    public $timestamps = true;
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'numero_orden',
        'proveedor_id',
        'almacen_destino_id',
        'moneda',
        'subtotal',
        'impuesto_total',
        'total_orden',
        'fecha_orden',
        'fecha_entrega_esperada',
        'estado',
        'observaciones',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_entrega_esperada' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto_total' => 'decimal:2',
        'total_orden' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    // Reglas de validación básicas; ajustar si el esquema real difiere
    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'proveedor_id' => 'required|exists:proveedores,id',
        'numero_orden' => 'nullable|string',
        'fecha_orden' => 'required|date',
    ];

    /* ------------------------- Relaciones ------------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        // Relación con detalle de orden de compra
        return $this->hasMany(DetalleOrdenCompra::class, 'orden_compra_id');
    }

    public function pagos()
    {
        // Aquí asumimos una relación simple hasMany; si en el proyecto hay modelos especializados
        // para pagos a cuentas por pagar, ajustar a la relación concreta (p.ej. PagoCuentaPorPagar)
        return $this->hasMany(Pago::class, 'orden_compra_id');
    }

    /**
     * Relación con usuario que creó la orden.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con cuentas por pagar generadas.
     */
    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class, 'orden_compra_id');
    }

    /**
     * Relación con entradas de inventario.
     */
    public function entradasInventario()
    {
        return $this->hasMany(EntradaInventario::class, 'orden_compra_id');
    }

    /* ------------------------- Scopes útiles ------------------------- */
    public function scopeActivas($q)
    {
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorProveedor($q, $proveedorId)
    {
        return $q->where('proveedor_id', $proveedorId);
    }

    public function scopePorEmpresa($q, $empresaId)
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopePendientes($q)
    {
        return $q->whereIn('estado', ['pendiente', 'parcial']);
    }

    /* ------------------------- Boot / eventos ------------------------- */
    protected static function boot()
    {
        parent::boot();

        // Antes de guardar, intentar calcular totales si los detalles están cargados
        static::saving(function (OrdenCompra $model) {
            if ($model->relationLoaded('detalles') && $model->detalles->isNotEmpty()) {
                $subtotal = 0;
                $impuestos = 0;

                foreach ($model->detalles as $d) {
                    // El detalle debe exponer subtotal e impuestos; usar valores nulos como 0
                    $subtotal += (float) ($d->subtotal ?? 0);
                    $impuestos += (float) ($d->impuestos ?? 0);
                }

                $model->subtotal = round($subtotal, 2);
                $model->impuesto_total = round($impuestos, 2);
                $model->total_orden = round($subtotal + $impuestos, 2);
            }

            // Normalizar número si es necesario
            if (isset($model->numero_orden)) {
                $model->numero_orden = (string) Str::upper(trim($model->numero_orden));
            }
        });
    }

    /* ------------------------- Helpers ------------------------- */
    public function calcularSaldoPendiente()
    {
        // Método auxiliar para calcular saldo pendiente (total_orden - suma(pagos))
        $pagado = 0;
        if ($this->relationLoaded('pagos')) {
            $pagado = $this->pagos->sum(function ($p) {
                return (float) ($p->monto ?? 0);
            });
        } else {
            // si no están cargados, intentar sumar con relación (más lento)
            $pagado = $this->pagos()->sum('monto');
        }

        return round(((float) ($this->total_orden ?? 0)) - $pagado, 2);
    }
}
