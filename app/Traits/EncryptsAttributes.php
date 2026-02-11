<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * Trait EncryptsAttributes - Encriptación Automática de Atributos FASE 3
 *
 * Proporciona métodos para encriptar/desencriptar automáticamente atributos
 * usando el cipher de Laravel (AES-128-CBC o AES-256-CBC).
 *
 * Uso en el modelo:
 *     protected $encrypted = ['password', 'ssn', 'phone'];
 *
 * @package App\Traits
 * @version 1.0.0
 */
trait EncryptsAttributes
{
    /**
     * Atributos que deben ser encriptados automáticamente
     * Sobrescribir en el modelo hijo
     *
     * @var array
     */
    protected $encrypted = [];

    /**
     * Boot del trait
     */
    public static function bootEncryptsAttributes()
    {
        // Desencriptar al recuperar
        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });

        // Encriptar antes de guardar
        static::saving(function ($model) {
            $model->encryptAttributes();
        });
    }

    /**
     * Encriptar atributos configurados
     */
    public function encryptAttributes(): void
    {
        foreach ($this->encrypted as $attribute) {
            if ($this->hasAttribute($attribute) && !is_null($this->attributes[$attribute] ?? null)) {
                try {
                    // Verificar si ya está encriptado
                    if (!$this->isEncrypted($this->attributes[$attribute])) {
                        $this->attributes[$attribute] = Crypt::encryptString($this->attributes[$attribute]);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error encriptando atributo {$attribute}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Desencriptar atributos configurados
     */
    public function decryptAttributes(): void
    {
        foreach ($this->encrypted as $attribute) {
            if ($this->hasAttribute($attribute) && !is_null($this->attributes[$attribute] ?? null)) {
                try {
                    // Intentar desencriptar si está encriptado
                    if ($this->isEncrypted($this->attributes[$attribute])) {
                        $this->attributes[$attribute] = Crypt::decryptString($this->attributes[$attribute]);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error desencriptando atributo {$attribute}: " . $e->getMessage());
                    // Mantener el valor original si no se puede desencriptar
                }
            }
        }
    }

    /**
     * Verificar si un valor está encriptado
     */
    protected function isEncrypted($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Las cadenas encriptadas por Laravel comienzan con "eyJ"
        // (base64 de {"iv":"...", "value":"...", etc})
        return strpos($value, 'eyJ') === 0 || strpos($value, 'base64:') === 0;
    }

    /**
     * Obtener el valor desencriptado de un atributo
     */
    public function getDecrypted(string $attribute): ?string
    {
        $value = $this->getAttribute($attribute);

        if (is_null($value) || !in_array($attribute, $this->encrypted)) {
            return $value;
        }

        try {
            return $this->isEncrypted($value) ? Crypt::decryptString($value) : $value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Establecer atributo encriptado
     */
    public function setEncrypted(string $attribute, ?string $value): self
    {
        if (is_null($value)) {
            $this->setAttribute($attribute, null);
            return $this;
        }

        if (!in_array($attribute, $this->encrypted)) {
            $this->encrypted[] = $attribute;
        }

        try {
            if (!$this->isEncrypted($value)) {
                $this->setAttribute($attribute, Crypt::encryptString($value));
            } else {
                $this->setAttribute($attribute, $value);
            }
        } catch (\Exception $e) {
            \Log::error("Error estableciendo atributo encriptado {$attribute}: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * Obtener atributos encriptados en la respuesta JSON
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // No incluir valores encriptados en arrays/JSON por defecto
        foreach ($this->encrypted as $attribute) {
            if (isset($array[$attribute])) {
                $array[$attribute] = '***ENCRYPTED***';
            }
        }

        return $array;
    }

    /**
     * Crear modelo con atributo encriptado
     */
    public static function createWithEncrypted(array $attributes): self
    {
        $instance = new static();

        foreach ($attributes as $key => $value) {
            if (in_array($key, $instance->encrypted)) {
                $instance->setEncrypted($key, $value);
            } else {
                $instance->setAttribute($key, $value);
            }
        }

        $instance->save();

        return $instance;
    }

    /**
     * Actualizar atributos encriptados
     */
    public function updateEncrypted(array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->encrypted)) {
                $this->setEncrypted($key, $value);
            } else {
                $this->setAttribute($key, $value);
            }
        }

        return $this->save();
    }
}
