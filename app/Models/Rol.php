<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\RolFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use App\Traits\HasPermissionCache;

class Rol extends Model
{
    /** @use HasFactory<\Database\Factories\RolFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope, HasPermissionCache;
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos que se pueden asignar de manera masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
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
     * Boot del modelo.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Normalizar datos antes de guardar
        static::saving(function ($model) {
            // Capitalizar primera letra de cada palabra
            $model->nombre = ucwords(strtolower($model->nombre));
        });
    }

    /**
     * Scope para filtrar solo los registros activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo los registros no eliminados.
     */
    public function scopeNoEliminados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('eliminado', false);
    }

    /**
     * Relación muchos a muchos con Usuario.
     */
    public function usuarios(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario', 'rol_id', 'usuario_id')
                    ->wherePivot('activo', true)
                    ->wherePivot('eliminado', false)
                    ->withTimestamps();
    }

    /**
     * Relación muchos a muchos con Permiso.
     */
    public function permisos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
                    ->wherePivot('activo', true)
                    ->withTimestamps();
    }

    /**
     * Asignar permisos al rol.
     *
     * @param array<int> $permisoIds Array de IDs de permisos
     * @return void
     */
    public function assignPermissions(array $permisoIds): void
    {
        // Primero, desactivar todos los permisos actuales
        \Illuminate\Support\Facades\DB::table('roles_permisos')
            ->where('rol_id', $this->id)
            ->update(['activo' => false]);

        // Luego, asignar o reactivar los nuevos permisos
        foreach ($permisoIds as $permisoId) {
            \Illuminate\Support\Facades\DB::table('roles_permisos')
                ->updateOrInsert(
                    ['rol_id' => $this->id, 'permiso_id' => $permisoId],
                    ['activo' => true]
                );
        }
    }

    /**
     * Verificar si el rol tiene un permiso específico.
     *
     * @param string $permisoSlug Slug del permiso
     * @return bool
     */
    public function hasPermission(string $permisoSlug): bool
    {
        return $this->permisos()->where('slug', $permisoSlug)->exists();
    }

    /**
     * Verificar si el rol tiene alguno de los permisos especificados.
     *
     * @param array<string> $permisoSlugs Array de slugs de permisos
     * @return bool
     */
    public function hasAnyPermission(array $permisoSlugs): bool
    {
        return $this->permisos()->whereIn('slug', $permisoSlugs)->exists();
    }

    /**
     * Verificar si el rol tiene todos los permisos especificados.
     *
     * @param array<string> $permisoSlugs Array de slugs de permisos
     * @return bool
     */
    public function hasAllPermissions(array $permisoSlugs): bool
    {
        $count = $this->permisos()->whereIn('slug', $permisoSlugs)->count();
        return $count === count($permisoSlugs);
    }

    /**
     * Sincronizar permisos del rol.
     *
     * @param array<int> $permisoIds Array de IDs de permisos
     * @return void
     */
    public function syncPermissions(array $permisoIds): void
    {
        // Usar el método sync de Laravel con datos adicionales
        $syncData = [];
        foreach ($permisoIds as $permisoId) {
            $syncData[$permisoId] = ['activo' => true];
        }
        
        $this->permisos()->sync($syncData);
        
        // Limpiar cache después de sincronizar
        $this->clearPermissionCache();
    }

    /**
     * Cargar permisos desde la base de datos para cache.
     * Requerido por HasPermissionCache trait.
     *
     * @return array<int, string>
     */
    protected function loadPermissionsFromDatabase(): array
    {
        return $this->permisos()
            ->where('permisos.activo', true)
            ->where('permisos.eliminado', false)
            ->pluck('slug')
            ->toArray();
    }
}
