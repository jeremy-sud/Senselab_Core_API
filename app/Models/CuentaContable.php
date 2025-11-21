<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaContable extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'cuentas_contables';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'codigo',
        'tipo_cuenta_id',
        'cuenta_padre_id',
        'permite_movimientos',
        'saldo_actual',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'permite_movimientos' => 'boolean',
        'saldo_actual' => 'decimal:2',
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
     * Relación con el tipo de cuenta.
     */
    public function tipoCuenta(): BelongsTo
    {
        return $this->belongsTo(TipoCuenta::class, 'tipo_cuenta_id');
    }

    /**
     * Relación con la cuenta padre.
     */
    public function cuentaPadre(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_padre_id');
    }

    /**
     * Relación con las cuentas hijas.
     */
    public function cuentasHijas(): HasMany
    {
        return $this->hasMany(CuentaContable::class, 'cuenta_padre_id');
    }

    /**
     * Scope para obtener cuentas activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por código.
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', 'LIKE', "%{$codigo}%");
    }

    /**
     * Scope para buscar por nombre.
     */
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Scope para filtrar por tipo de cuenta.
     */
    public function scopePorTipoCuenta($query, $tipoId)
    {
        return $query->where('tipo_cuenta_id', $tipoId);
    }

    /**
     * Scope para obtener solo cuentas que permiten movimientos.
     */
    public function scopeConMovimientos($query)
    {
        return $query->where('permite_movimientos', true);
    }

    /**
     * Scope para obtener cuentas raíz (sin padre).
     */
    public function scopeCuentasRaiz($query)
    {
        return $query->whereNull('cuenta_padre_id');
    }

    /**
     * Determina si la cuenta tiene movimientos.
     */
    public function tieneMovimientos(): bool
    {
        return $this->permite_movimientos;
    }

    /**
     * Determina si la cuenta es una cuenta padre.
     */
    public function esCuentaPadre(): bool
    {
        return $this->cuentasHijas()->exists();
    }

    /**
     * Obtiene el nivel de profundidad de la cuenta en el árbol.
     */
    public function getNivelAttribute(): int
    {
        $nivel = 1;
        $cuenta = $this;

        while ($cuenta->cuenta_padre_id) {
            $nivel++;
            $cuenta = $cuenta->cuentaPadre;
        }

        return $nivel;
    }

    /**
     * Obtiene el código completo incluyendo los códigos de las cuentas padre.
     */
    public function getCodigoCompletoAttribute(): string
    {
        $codigos = [$this->codigo];
        $cuenta = $this;

        while ($cuenta->cuenta_padre_id) {
            $cuenta = $cuenta->cuentaPadre;
            array_unshift($codigos, $cuenta->codigo);
        }

        return implode('.', $codigos);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cuenta) {
            // Validar código único por empresa
            $exists = static::where('id', '!=', $cuenta->id)
                ->where('empresa_id', $cuenta->empresa_id)
                ->where('codigo', $cuenta->codigo)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe una cuenta con este código en la empresa.');
            }

            // Validar que no se asigne como padre una cuenta que no permite movimientos
            if ($cuenta->cuenta_padre_id) {
                $padre = static::find($cuenta->cuenta_padre_id);
                if (!$padre->permite_movimientos) {
                    throw new \Exception('No se puede asignar como padre una cuenta que no permite movimientos.');
                }
            }
        });
    }

    /**
     * Actualiza el saldo de la cuenta.
     */
    public function actualizarSaldo(float $monto)
    {
        if (!$this->permite_movimientos) {
            throw new \Exception('Esta cuenta no permite movimientos.');
        }

        $this->increment('saldo_actual', $monto);
    }
}