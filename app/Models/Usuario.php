<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios';

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
        'nombre',
        'apellidos',
        'cargo_id',
        'email',
        'password_hash',
        'empresa_id',
        'telefono',
        'direccion',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
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
     * Relación con el modelo Cargo.
     */
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    /**
     * Relación muchos a muchos con Rol.
     */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id')
                    ->withTimestamps();
    }

    /**
     * Relación con Ventas creadas por el usuario.
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    /**
     * Relación con Órdenes de Compra creadas por el usuario.
     */
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class, 'usuario_id');
    }

    /**
     * Relación con Asientos Contables creados por el usuario.
     */
    public function asientosContables()
    {
        return $this->hasMany(AsientoContable::class, 'usuario_id');
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
