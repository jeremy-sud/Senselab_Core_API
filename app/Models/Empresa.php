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
        'subdominio',
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
    public function almacenes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Almacen::class);
    }

    public function sucursales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function productos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function clientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function usuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function configuraciones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Configuracion::class);
    }

    // Relaciones de documentos
    public function ventas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function comprobantesRecibidos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ComprobanteRecibidoElectronico::class);
    }

    // Relaciones de catálogos
    public function categoriasProductos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CategoriaProducto::class);
    }

    // Relación con régimen tributario
    public function regimenTributario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RegimenTributario::class);
    }

    // Relaciones adicionales
    public function proveedores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Proveedor::class);
    }

    public function empleados(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Empleado::class);
    }

    public function ordenesCompra(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function cuentasContables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaContable::class);
    }

    public function asientosContables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AsientoContable::class);
    }

    public function presupuestos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }

    public function cajaChica(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CajaChica::class);
    }

    public function cuentasPorCobrar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaPorCobrar::class);
    }

    public function cuentasPorPagar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CuentaPorPagar::class);
    }

    public function periodosNomina(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PeriodoNomina::class);
    }

    public function etiquetas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Etiqueta::class);
    }

    public function rutas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ruta::class);
    }

    public function consecutivosFe(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsecutivoFe::class);
    }

    // Scopes
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }
}
