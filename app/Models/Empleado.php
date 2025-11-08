<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Empleado extends Model
{
    use BelongsToTenant;

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
        'genero',
        'fecha_contratacion',
        'cargo_id',
        'salario',
        'usuario_id',
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
        'fecha_contratacion' => 'date',
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
        'genero' => 'nullable|string|max:20',
        'fecha_contratacion' => 'required|date',
        'cargo_id' => 'nullable|exists:cargos,id',
        'salario' => 'required|numeric|min:0',
        'usuario_id' => 'nullable|exists:usuarios,id',
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

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
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

            // Si se asigna usuario_id, asegurarse que no exista otro empleado con el mismo usuario (clave única en BD)
            if (!empty($model->usuario_id)) {
                $uExists = self::where('usuario_id', $model->usuario_id)
                    ->where('id', '<>', $model->id ?? 0)
                    ->exists();

                if ($uExists) {
                    throw new \Exception('El usuario ya está asignado a otro empleado.');
                }
            }
        });
    }
}
