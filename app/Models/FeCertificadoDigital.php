<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
/** @use HasFactory<\Database\Factories\FeCertificadoDigitalFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Traits\BelongsToTenant;

/**
 * Modelo para Certificados Digitales de Firma Electrónica
 * 
 * Gestiona los certificados .p12 utilizados para firmar comprobantes electrónicos.
 */
class FeCertificadoDigital extends Model
{
    /** @use HasFactory<\Database\Factories\FeCertificadoDigitalFactory> */
    use HasFactory, SoftDeletes, BelongsToTenant;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'fe_certificados_digitales';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'tipo',
        'numero_serie',
        'emisor',
        'sujeto',
        'ruta_archivo',
        'password_encrypted',
        'fecha_emision',
        'fecha_vencimiento',
        'activo',
        'valido',
        'ambiente',
        'metadata',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'activo' => 'boolean',
        'valido' => 'boolean',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Atributos ocultos para serialización.
     */
    protected $hidden = [
        'password_encrypted',
    ];

    /**
     * Relación: Pertenece a una Empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scope: Certificados activos.
     */
    public function scopeActivos(Builder $query): Builder{
        return $query->where('activo', true);
    }

    /**
     * Scope: Certificados válidos (no expirados).
     */
    public function scopeValidos(Builder $query): Builder{
        return $query->where('valido', true)
            ->where('fecha_vencimiento', '>', Carbon::now());
    }

    /**
     * Scope: Filtrar por ambiente.
     */
    public function scopeAmbiente(Builder $query, string $ambiente): Builder{
        return $query->where('ambiente', $ambiente);
    }

    /**
     * Accessor: Verificar si el certificado está próximo a vencer (30 días).
     */
    public function getProximoVencerAttribute(): bool
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        return Carbon::now()->diffInDays($this->fecha_vencimiento, false) <= 30
            && Carbon::now()->diffInDays($this->fecha_vencimiento, false) > 0;
    }

    /**
     * Accessor: Verificar si el certificado está vencido.
     */
    public function getVencidoAttribute(): bool
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        return Carbon::now()->greaterThan($this->fecha_vencimiento);
    }

    /**
     * Accessor: Días restantes hasta el vencimiento.
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->fecha_vencimiento, false);
    }
}
