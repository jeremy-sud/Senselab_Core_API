<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Tenant;

class Empresa extends Tenant
{
    use HasFactory;

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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }
}
