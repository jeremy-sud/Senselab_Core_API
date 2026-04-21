<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Empleado extends Model
{
    /** @use HasFactory<\Database\Factories\EmpleadoFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada.
     *
     * @var string
     */
    protected $table = 'empleados';

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Atributos asignables.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'fecha_ingreso',
        'cargo_id',
        'departamento_id',
        'salario',
        'direccion',
        'telefono',
        'email',
        'activo',
        'eliminado',
    ];

    /**
     * Tipos de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'salario' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Campos ocultos.
     *
     * @var list<string>
     */
    protected $hidden = [
        'eliminado',
    ];

    /**
     * Atributos añadidos.
     *
     * @var list<string>
     */
    protected $appends = [
        'nombre_completo',
    ];

    /**
     * Reglas de validación (uso referencial en controladores/servicios).
     *
     * @var array<string, string>
     */
    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string|max:255',
        'primer_apellido' => 'required|string|max:255',
        'segundo_apellido' => 'nullable|string|max:255',
        'tipo_documento' => 'required|string|max:50',
        'numero_documento' => 'required|string|max:50',
        'fecha_nacimiento' => 'nullable|date',
        'fecha_ingreso' => 'required|date',
        'cargo_id' => 'nullable|exists:cargos,id',
        'salario' => 'required|numeric|min:0',
        'direccion' => 'nullable|string',
        'telefono' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Relaciones
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cargo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    /**
     * Relación con el usuario del sistema asociado al empleado.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Relación con el departamento del empleado.
     */
    public function departamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Nombre completo del empleado.
     *
     * @return string
     */
    public function getNombreCompletoAttribute(): mixed
    {
        $parts = array_filter([$this->nombre, $this->primer_apellido, $this->segundo_apellido]);
        return trim(implode(' ', $parts));
    }

    /**
     * Scopes útiles.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCargo(Builder $query, mixed $cargoId): Builder{
        return $query->where('cargo_id', $cargoId);
    }

    public function scopePorEmpresa(Builder $query, mixed $empresaId): Builder{
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeBuscarDocumento(Builder $query, mixed $tipo, mixed $numero): Builder{
        return $query->where('tipo_documento', $tipo)->where('numero_documento', $numero);
    }

    /**
     * Boot model: validaciones/normalizaciones antes de guardar.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Normalizar espacios en nombres
            $model->nombre = $model->nombre ? trim(preg_replace('/\s+/', ' ', $model->nombre)) : $model->nombre;
            $model->primer_apellido = $model->primer_apellido ? trim(preg_replace('/\s+/', ' ', $model->primer_apellido)) : $model->primer_apellido;
            $model->segundo_apellido = $model->segundo_apellido ? trim(preg_replace('/\s+/', ' ', $model->segundo_apellido)) : $model->segundo_apellido;

            // Validaciones básicas
            if (($model->salario ?? 0) < 0) {
                throw new \Exception('El salario no puede ser negativo.');
            }

            if (empty($model->tipo_documento) || empty($model->numero_documento)) {
                throw new \Exception('Tipo y número de documento son requeridos.');
            }

            // Evitar duplicados de documento dentro de la misma empresa (clave única en BD)
            $exists = self::where('empresa_id', $model->empresa_id)
                ->where('tipo_documento', $model->tipo_documento)
                ->where('numero_documento', $model->numero_documento)
                ->where('id', '<>', $model->id ?? 0)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe un empleado con ese documento en la empresa.');
            }
        });
    }
}
