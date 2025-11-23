<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Empleado extends Model
{
    use BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada.
     *
     * @var string
     */
    protected $table = 'empleados';

    /**
     * Atributos asignables.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'fecha_ingreso',
        'cargo_id',
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
     * @var array<string,string>
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
     * @var array<int,string>
     */
    protected $hidden = [
        'eliminado',
    ];

    /**
     * Atributos añadidos.
     *
     * @var array<int,string>
     */
    protected $appends = [
        'nombre_completo',
    ];

    /**
     * Reglas de validación (uso referencial en controladores/servicios).
     *
     * @var array<string,string>
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
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    /**
     * Nombre completo del empleado.
     *
     * @return string
     */
    public function getNombreCompletoAttribute()
    {
        $parts = array_filter([$this->nombre, $this->primer_apellido, $this->segundo_apellido]);
        return trim(implode(' ', $parts));
    }

    /**
     * Scopes útiles.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCargo($query, $cargoId)
    {
        return $query->where('cargo_id', $cargoId);
    }

    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeBuscarDocumento($query, $tipo, $numero)
    {
        return $query->where('tipo_documento', $tipo)->where('numero_documento', $numero);
    }

    /**
     * Boot model: validaciones/normalizaciones antes de guardar.
     */
    protected static function boot()
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
