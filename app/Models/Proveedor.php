<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `proveedores`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Proveedor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'proveedores';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'razon_social',
        'nit_ruc',
        'direccion',
        'telefono',
        'email',
        'dias_credito',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'dias_credito' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string',
        'nit_ruc' => 'required|string',
        'email' => 'nullable|email',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'proveedor_id_predeterminado');
    }

    /**
     * Relación con órdenes de compra.
     */
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    /**
     * Relación con cuentas por pagar.
     */
    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class, 'proveedor_id');
    }

    /**
     * Relación con entradas de inventario.
     */
    public function entradasInventario()
    {
        return $this->hasMany(EntradaInventario::class, 'proveedor_id');
    }

    /**
     * Relación con comprobantes recibidos electrónicos.
     */
    public function comprobantesRecibidos()
    {
        return $this->hasMany(ComprobanteRecibidoElectronico::class, 'proveedor_id');
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

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Proveedor $p) {
            if (isset($p->nombre)) {
                $p->nombre = trim($p->nombre);
            }
            if (isset($p->razon_social)) {
                $p->razon_social = trim($p->razon_social);
            }
            if (isset($p->nit_ruc)) {
                $p->nit_ruc = Str::upper(trim($p->nit_ruc));
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
