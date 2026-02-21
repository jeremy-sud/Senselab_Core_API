<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\OrdenCompraFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
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
    /** @use HasFactory<\Database\Factories\OrdenCompraFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'ordenes_compra';

    // Timestamps personalizados
    public $timestamps = true;
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'usuario_id',
        'numero_orden',
        'fecha_orden',
        'fecha_entrega_esperada',
        'moneda',
        'subtotal',
        'impuesto_total',
        'total_orden',
        'estado',
        'observaciones',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_entrega_esperada' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto_total' => 'decimal:2',
        'total_orden' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    // Reglas de validación básicas; ajustar si el esquema real difiere
    /**

     * @var array<string, mixed>

     */
    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'proveedor_id' => 'required|exists:proveedores,id',
        'numero_orden' => 'nullable|string',
        'fecha_orden' => 'required|date',
    ];

    /* ------------------------- Relaciones ------------------------- */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proveedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): mixed
    {
        // Relación con detalle de orden de compra
        return $this->hasMany(DetalleOrdenCompra::class, 'orden_compra_id');
    }

    public function pagos(): mixed
    {
        // Aquí asumimos una relación simple hasMany; si en el proyecto hay modelos especializados
        // para pagos a cuentas por pagar, ajustar a la relación concreta (p.ej. PagoCuentaPorPagar)
        return $this->hasMany(Pago::class, 'orden_compra_id');
    }

    /**
     * Relación con usuario que creó la orden.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con cuentas por pagar generadas.
     */
    public function cuentasPorPagar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaPorPagar::class, 'orden_compra_id');
    }

    /**
     * Relación con entradas de inventario.
     */
    public function entradasInventario(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EntradaInventario::class, 'orden_compra_id');
    }

    /* ------------------------- Scopes útiles ------------------------- */
    public function scopeActivas(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorProveedor(mixed $q, mixed $proveedorId): Builder{
        return $q->where('proveedor_id', $proveedorId);
    }

    public function scopePorEmpresa(mixed $q, mixed $empresaId): Builder{
        return $q->where('empresa_id', $empresaId);
    }

    public function scopePendientes(mixed $q): Builder{
        return $q->whereIn('estado', ['pendiente', 'parcial']);
    }

    /* ------------------------- Boot / eventos ------------------------- */
    protected static function boot(): void
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
    public function calcularSaldoPendiente(): mixed
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
