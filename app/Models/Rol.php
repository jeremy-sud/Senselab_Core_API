<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
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
     * @var array
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
     * Boot del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Normalizar datos antes de guardar
        static::saving(function ($model) {
            $model->nombre = ucfirst(strtolower($model->nombre));
        });
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
     * Relación muchos a muchos con Usuario.
     */
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario', 'rol_id', 'usuario_id')
                    ->wherePivot('activo', true)
                    ->wherePivot('eliminado', false)
                    ->withTimestamps();
    }

    /**
     * Relación muchos a muchos con Permiso.
     */
    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
                    ->wherePivot('activo', true)
                    ->withTimestamps();
    }

    /**
     * Asignar permisos al rol.
     *
     * @param array $permisoIds Array de IDs de permisos
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
}
