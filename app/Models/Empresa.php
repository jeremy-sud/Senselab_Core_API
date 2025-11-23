<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Tenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Empresa extends Tenant
{
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'empresas';
    
    protected $fillable = [
        'nombre',
        'nombre_comercial',
        'razon_social',
        'num_identificacion_dgt',
        'tipo_identificacion',
        'actividad_economica_principal',
        'direccion',
        'provincia',
        'canton',
        'distrito',
        'telefono',
        'email',
        'certificado_llave_fe',
        'pin_llave_fe_hash',
        'prefijo_orden_compra',
        'moneda_defecto',
        'regimen_tributario_id',
        'activo',
    ];

    protected $hidden = [
        'certificado_llave_fe',
        'pin_llave_fe_hash'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    // Relaciones principales
    public function almacenes()
    {
        return $this->hasMany(Almacen::class);
    }

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    public function configuraciones()
    {
        return $this->hasMany(Configuracion::class);
    }

    // Relaciones de documentos
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function comprobantesRecibidos()
    {
        return $this->hasMany(ComprobanteRecibidoElectronico::class);
    }

    // Relaciones de catálogos
    public function categoriasProductos()
    {
        return $this->hasMany(CategoriaProducto::class);
    }

    // Relación con régimen tributario
    public function regimenTributario()
    {
        return $this->belongsTo(RegimenTributario::class);
    }

    // Relaciones adicionales
    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class);
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function cuentasContables()
    {
        return $this->hasMany(CuentaContable::class);
    }

    public function asientosContables()
    {
        return $this->hasMany(AsientoContable::class);
    }

    public function presupuestos()
    {
        return $this->hasMany(Presupuesto::class);
    }

    public function cajaChica()
    {
        return $this->hasMany(CajaChica::class);
    }

    public function cuentasPorCobrar()
    {
        return $this->hasMany(CuentaPorCobrar::class);
    }

    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class);
    }

    public function periodosNomina()
    {
        return $this->hasMany(PeriodoNomina::class);
    }

    public function etiquetas()
    {
        return $this->hasMany(Etiqueta::class);
    }

    public function rutas()
    {
        return $this->hasMany(Ruta::class);
    }

    public function consecutivosFe()
    {
        return $this->hasMany(ConsecutivoFe::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }
}
