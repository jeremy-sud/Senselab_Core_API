<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\ProveedorFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `proveedores`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Proveedor extends Model
{
    /** @use HasFactory<\Database\Factories\ProveedorFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'proveedores';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'tipo_identificacion',
        'numero_identificacion',
        'nombre',
        'nombre_comercial',
        'email',
        'telefono',
        'direccion',
        'provincia',
        'canton',
        'distrito',
        'limite_credito',
        'plazo_credito_dias',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'plazo_credito_dias' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string',
        'numero_identificacion' => 'required|string',
        'email' => 'nullable|email',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function productos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Producto::class, 'proveedor_id');
    }

    /**
     * Relación con órdenes de compra.
     */
    public function ordenesCompra(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    /**
     * Relación con cuentas por pagar.
     */
    public function cuentasPorPagar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaPorPagar::class, 'proveedor_id');
    }

    /**
     * Relación con entradas de inventario.
     */
    public function entradasInventario(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EntradaInventario::class, 'proveedor_id');
    }

    /**
     * Relación con comprobantes recibidos electrónicos.
     */
    public function comprobantesRecibidos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ComprobanteRecibidoElectronico::class, 'proveedor_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa(mixed $q, mixed $empresaId): Builder{
        return $q->where('empresa_id', $empresaId);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Proveedor $p) {
            if (isset($p->nombre)) {
                $p->nombre = trim($p->nombre);
            }
            if (isset($p->nombre_comercial)) {
                $p->nombre_comercial = trim($p->nombre_comercial);
            }
            if (isset($p->numero_identificacion)) {
                $p->numero_identificacion = Str::upper(trim($p->numero_identificacion));
            }
            if (isset($p->email)) {
                $p->email = Str::lower(trim($p->email));
            }
            if (isset($p->telefono)) {
                $p->telefono = trim($p->telefono);
            }
        });
    }
}
