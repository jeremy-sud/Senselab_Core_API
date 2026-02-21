<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
/** @use HasFactory<\Database\Factories\ConfiguracionFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuracion extends Model
{
    /** @use HasFactory<\Database\Factories\ConfiguracionFactory> */
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'configuraciones';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Tipos de datos soportados.
     */
    const TIPO_STRING = 'string';
    const TIPO_INTEGER = 'integer';
    const TIPO_FLOAT = 'float';
    const TIPO_BOOLEAN = 'boolean';
    const TIPO_JSON = 'json';
    const TIPO_ARRAY = 'array';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'clave',
        'valor',
        'tipo_dato',
        'descripcion',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Scope para obtener configuraciones activas.
     */
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por clave.
     */
    public function scopePorClave(Builder $query, mixed $clave): Builder{
        return $query->where('clave', $clave);
    }

    /**
     * Scope para buscar por tipo de dato.
     */
    public function scopePorTipoDato(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo_dato', $tipo);
    }

    /**
     * Obtiene el valor convertido al tipo de dato correspondiente.
     */
    public function getValorConvertidoAttribute(): mixed
    {
        return match($this->tipo_dato) {
            self::TIPO_STRING => (string) $this->valor,
            self::TIPO_INTEGER => (int) $this->valor,
            self::TIPO_FLOAT => (float) $this->valor,
            self::TIPO_BOOLEAN => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            self::TIPO_JSON => json_decode($this->valor, true),
            self::TIPO_ARRAY => explode(',', $this->valor),
            default => $this->valor
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($configuracion) {
            // Validar tipo de dato
            if (!in_array($configuracion->tipo_dato, [
                self::TIPO_STRING,
                self::TIPO_INTEGER,
                self::TIPO_FLOAT,
                self::TIPO_BOOLEAN,
                self::TIPO_JSON,
                self::TIPO_ARRAY
            ])) {
                throw new \Exception('Tipo de dato no válido.');
            }

            // Validar formato según tipo de dato
            switch ($configuracion->tipo_dato) {
                case self::TIPO_JSON:
                    if (!self::isValidJson($configuracion->valor)) {
                        throw new \Exception('El valor no es un JSON válido.');
                    }
                    break;
                case self::TIPO_INTEGER:
                    if (!is_numeric($configuracion->valor)) {
                        throw new \Exception('El valor debe ser numérico.');
                    }
                    break;
                case self::TIPO_FLOAT:
                    if (!is_numeric($configuracion->valor)) {
                        throw new \Exception('El valor debe ser numérico.');
                    }
                    break;
            }

            // Validar clave única por empresa
            $exists = static::where('id', '!=', $configuracion->id)
                ->where('empresa_id', $configuracion->empresa_id)
                ->where('clave', $configuracion->clave)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe una configuración con esta clave en la empresa.');
            }
        });
    }

    /**
     * Valida si una cadena es un JSON válido.
     */
    protected static function isValidJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Método estático para obtener una configuración por clave.
     */
    public static function obtenerPorClave(int $empresaId, string $clave, mixed $default = null): mixed
    {
        $config = static::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->where('activo', true)
            ->where('eliminado', false)
            ->first();

        return $config ? $config->valor_convertido : $default;
    }
}
