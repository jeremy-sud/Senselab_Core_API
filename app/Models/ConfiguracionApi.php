<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ConfiguracionApi extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'configuraciones_api';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'clave',
        'valor',
        'tipo',
        'categoria',
        'descripcion',
        'encriptado',
        'activo',
    ];

    protected $casts = [
        'encriptado' => 'boolean',
        'activo' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Relación con la empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para configuraciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener el valor desencriptado si está encriptado
     */
    public function getValorDesencriptadoAttribute()
    {
        if ($this->encriptado) {
            try {
                return Crypt::decryptString($this->valor);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->valor;
    }

    /**
     * Obtener el valor parseado según su tipo
     */
    public function getValorParseadoAttribute()
    {
        $valor = $this->valor_desencriptado;

        switch ($this->tipo) {
            case 'json':
                return json_decode($valor, true);
            case 'int':
                return (int) $valor;
            case 'bool':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            default:
                return $valor;
        }
    }

    /**
     * Establecer el valor encriptándolo si es necesario
     */
    public function setValorAttribute($value)
    {
        if ($this->encriptado) {
            $this->attributes['valor'] = Crypt::encryptString($value);
        } else {
            $this->attributes['valor'] = $value;
        }
    }

    /**
     * Obtener una configuración específica
     */
    public static function obtener($clave, $empresaId = null, $default = null)
    {
        $empresaId = $empresaId ?? auth()->user()->empresa_id ?? null;

        $config = self::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->where('activo', true)
            ->first();

        return $config ? $config->valor_parseado : $default;
    }

    /**
     * Establecer una configuración
     */
    public static function establecer($clave, $valor, $empresaId = null, $tipo = 'string', $categoria = 'general', $encriptado = false)
    {
        $empresaId = $empresaId ?? auth()->user()->empresa_id ?? null;

        return self::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'clave' => $clave,
            ],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'categoria' => $categoria,
                'encriptado' => $encriptado,
                'activo' => true,
            ]
        );
    }
}
