<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use App\Traits\HasPermissionCache;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasCustomSoftDeletes, HasAuditFields, HasActiveScope, HasPermissionCache;

    /**
     * Campo de password personalizado
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

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
                    ->wherePivot('activo', true)
                    ->wherePivot('eliminado', false)
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

    /**
     * Verificar si el usuario tiene un permiso específico.
     *
     * @param string $permissionSlug Slug del permiso
     * @return bool
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Usar cache de permisos para mejor performance
        return $this->hasCachedPermission($permissionSlug);
    }

    /**
     * Verificar si el usuario tiene un rol específico.
     *
     * @param string $roleName Nombre del rol
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()
            ->where('nombre', $roleName)
            ->where('roles.activo', true)
            ->where('roles.eliminado', false)
            ->exists();
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados.
     *
     * @param array $roleNames Array de nombres de roles
     * @return bool
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()
            ->whereIn('nombre', $roleNames)
            ->where('roles.activo', true)
            ->where('roles.eliminado', false)
            ->exists();
    }

    /**
     * Obtener todos los permisos del usuario a través de sus roles.
     *
     * @return array Array de slugs de permisos
     */
    public function getAllPermissions()
    {
        return \App\Models\Permiso::whereHas('roles', function ($query) {
            $query->whereHas('usuarios', function ($q) {
                $q->where('usuarios.id', $this->id)
                  ->where('rol_usuario.activo', true);
            })
            ->where('roles.activo', true)
            ->where('roles.eliminado', false);
        })
        ->where('permisos.activo', true)
        ->where('permisos.eliminado', false)
        ->pluck('slug')
        ->toArray();
    }

    /**
     * Asignar roles al usuario.
     *
     * @param array $roleIds Array de IDs de roles
     * @return void
     */
    public function assignRoles(array $roleIds): void
    {
        // Primero, desactivar todos los roles actuales
        \Illuminate\Support\Facades\DB::table('rol_usuario')
            ->where('usuario_id', $this->id)
            ->update(['activo' => false]);

        // Luego, asignar o reactivar los nuevos roles
        foreach ($roleIds as $roleId) {
            \Illuminate\Support\Facades\DB::table('rol_usuario')
                ->updateOrInsert(
                    ['usuario_id' => $this->id, 'rol_id' => $roleId],
                    ['activo' => true, 'eliminado' => false]
                );
        }
    }
}
