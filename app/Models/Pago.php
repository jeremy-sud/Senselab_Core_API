<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `pagos`.
 * Se crea a partir del CREATE TABLE obtenido de la base de datos.
 */
class Pago extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'pagos';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'orden_compra_id',
        'cuenta_por_pagar_id',
        'proveedor_id',
        'cliente_id',
        'cuenta_por_cobrar_id',
        'forma_pago_id',
        'fecha_pago',
        'monto',
        'moneda',
        'descripcion',
        'referencia',
        'estado',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'forma_pago_id' => 'required|exists:formas_pago,id',
        'fecha_pago' => 'required|date',
        'monto' => 'required|numeric|min:0.01',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function cuentaPorPagar()
    {
        return $this->belongsTo(CuentaPorPagar::class, 'cuenta_por_pagar_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cuentaPorCobrar()
    {
        return $this->belongsTo(CuentaPorCobrar::class, 'cuenta_por_cobrar_id');
    }

    public function formaPago()
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos($q)
    {
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa($q, $empresaId)
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopeRecientes($q, $limit = 10)
    {
        return $q->orderBy('fecha_pago', 'desc')->limit($limit);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Pago $pago) {
            // Normalizaciones
            if (isset($pago->referencia)) {
                $pago->referencia = trim($pago->referencia);
            }

            if (isset($pago->estado)) {
                $pago->estado = Str::lower(trim($pago->estado));
            }

            // Validación simple de coherencia: si existe orden_compra_id, marcar proveedor si está vacío
            if (isset($pago->orden_compra_id) && empty($pago->proveedor_id) && $pago->relationLoaded('ordenCompra')) {
                $oc = $pago->ordenCompra;
                if ($oc && isset($oc->proveedor_id)) {
                    $pago->proveedor_id = $oc->proveedor_id;
                }
            }
        });
    }

    /* --------------------- Helpers --------------------- */
    public function esAnulable()
    {
        return in_array($this->estado, ['pendiente', 'confirmado']);
    }
}
