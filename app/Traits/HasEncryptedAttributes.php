<?php

namespace App\Traits;

use App\Services\EncryptionService;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Trait HasEncryptedAttributes
 *
 * Proporciona encriptación/desencriptación automática de campos en modelos Eloquent.
 * Los campos configurados en config/encryption.php se cifran automáticamente al guardar
 * y se descifran al acceder.
 *
 * @package App\Traits
 * @version 1.0.0
 *
 * Use en modelos:
 * ```php
 * class Usuario extends Model {
 *     use HasEncryptedAttributes;
 *     
 *     protected $encryptedAttributes = ['document_number', 'phone', 'personal_email'];
 * }
 * ```
 */
trait HasEncryptedAttributes
{
    /**
     * Boot the trait
     */
    public static function bootHasEncryptedAttributes(): void
    {
        // Encriptar datos antes de guardar
        static::saving(function ($model) {
            $model->encryptAttributesBeforeSave();
        });

        // Desencriptar datos después de obtener
        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    /**
     * Encriptar atributos antes de guardar
     *
     * @return void
     */
    protected function encryptAttributesBeforeSave(): void
    {
        if (! config('encryption.enabled')) {
            return;
        }

        $encryptedFields = EncryptionService::getEncryptedFields(static::class);

        foreach ($encryptedFields as $fieldName => $config) {
            if ($this->isDirty($fieldName) && isset($this->attributes[$fieldName])) {
                // Encriptar valor
                $this->attributes[$fieldName] = EncryptionService::encrypt(
                    $this->attributes[$fieldName]
                );

                // Generar hash para búsqueda si está habilitado
                if (config('encryption.options.use_hashed_lookup')) {
                    $hashColumn = $fieldName . config('encryption.options.hash_column_suffix', '_hash');
                    
                    if ($this->getTable() && \Schema::hasColumn($this->getTable(), $hashColumn)) {
                        $this->attributes[$hashColumn] = hash(
                            config('encryption.options.hash_algorithm', 'sha256'),
                            $this->attributes[$fieldName] ?? ''
                        );
                    }
                }
            }
        }
    }

    /**
     * Desencriptar atributos después de obtener del BD
     *
     * @return void
     */
    protected function decryptAttributes(): void
    {
        if (! config('encryption.enabled')) {
            return;
        }

        $encryptedFields = EncryptionService::getEncryptedFields(static::class);

        foreach ($encryptedFields as $fieldName => $config) {
            if (isset($this->attributes[$fieldName])) {
                // Intentar obtener del caché primero
                $cached = EncryptionService::getFromCache(
                    static::class,
                    $fieldName,
                    $this->getKey()
                );

                if ($cached !== null) {
                    $this->attributes[$fieldName] = $cached;
                    continue;
                }

                // Desencriptar
                $decrypted = EncryptionService::decrypt($this->attributes[$fieldName]);

                if ($decrypted !== null) {
                    $this->attributes[$fieldName] = $decrypted;

                    // Guardar en caché
                    EncryptionService::putInCache(
                        static::class,
                        $fieldName,
                        $this->getKey(),
                        $decrypted
                    );
                }
            }
        }
    }

    /**
     * Obtener lista de campos encriptados de este modelo
     *
     * @return array
     */
    public function getEncryptedAttributes(): array
    {
        return array_keys(EncryptionService::getEncryptedFields(static::class));
    }

    /**
     * Verificar si un atributo está encriptado
     *
     * @param string $attribute Nombre del atributo
     * @return bool
     */
    public function isAttributeEncrypted(string $attribute): bool
    {
        return EncryptionService::isFieldEncrypted(static::class, $attribute);
    }

    /**
     * Encriptar un atributo manualmente
     *
     * @param string $attribute Nombre del atributo
     * @return string|null Valor encriptado
     */
    public function encryptAttribute(string $attribute): ?string
    {
        if (! isset($this->attributes[$attribute])) {
            return null;
        }

        return EncryptionService::encrypt($this->attributes[$attribute]);
    }

    /**
     * Desencriptar un atributo manualmente
     *
     * @param string $attribute Nombre del atributo
     * @return string|null Valor desencriptado
     */
    public function decryptAttribute(string $attribute): ?string
    {
        if (! isset($this->attributes[$attribute])) {
            return null;
        }

        return EncryptionService::decrypt($this->attributes[$attribute]);
    }

    /**
     * Hacer una consulta por campo encriptado usando hash
     * 
     * Ejemplo:
     * Usuario::whereEncrypted('document_number', '12345678')->first();
     *
     * @param string $field Campo a buscar
     * @param string $value Valor a buscar (se encripta y se hashea automáticamente)
     * @return bool|string
     */
    public static function getEncryptedSearchHash(string $field, string $value): string
    {
        if (! config('encryption.options.use_hashed_lookup')) {
            throw new \RuntimeException('Hash lookup is disabled in encryption config');
        }

        return EncryptionService::getHashForSearch(static::class, $field, $value);
    }

    /**
     * Scope para buscar por campo encriptado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field Campo encriptado
     * @param string $value Valor a buscar
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereEncrypted($query, string $field, string $value)
    {
        if (! EncryptionService::isFieldEncrypted(static::class, $field)) {
            throw new \RuntimeException("Field {$field} is not encrypted");
        }

        if (config('encryption.options.use_hashed_lookup')) {
            $hashColumn = $field . config('encryption.options.hash_column_suffix', '_hash');
            $hash = EncryptionService::getHashForSearch(static::class, $field, $value);

            return $query->where($hashColumn, $hash);
        }

        // Fallback: búsqueda desencriptada (más lenta)
        return $query->where($field, EncryptionService::encrypt($value));
    }

    /**
     * Reencriptar todos los registros con nueva clave
     *
     * Se usa cuando cambia APP_KEY y hay datos antiguos encriptados
     *
     * @param string $field Campo a reencriptar ('all' para todos)
     * @param object $oldEncrypter Instancia anterior del encryptador
     * @return int Número de registros procesados
     */
    public static function rotateEncryptionKey(string $field = 'all', $oldEncrypter = null): int
    {
        if (! config('encryption.options.support_key_rotation')) {
            throw new \RuntimeException('Key rotation is not enabled in encryption config');
        }

        $count = 0;

        if ($field === 'all') {
            foreach (EncryptionService::getEncryptedFields(static::class) as $fieldName => $config) {
                $count += EncryptionService::rotateKey(
                    static::class,
                    $fieldName,
                    $oldEncrypter ?? app('encrypter.old')
                );
            }
        } else {
            $count = EncryptionService::rotateKey(
                static::class,
                $field,
                $oldEncrypter ?? app('encrypter.old')
            );
        }

        return $count;
    }

    /**
     * Obtener un atributo (respeta el caché de decriptación)
     *
     * @param string $key Nombre del atributo
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Si está encriptado pero aún no se descifró, descifrarlo ahora
        if ($this->isAttributeEncrypted($key) && config('encryption.enabled')) {
            $decrypted = EncryptionService::decrypt($value);
            if ($decrypted !== null && $decrypted !== '[ENCRYPTED]') {
                return $decrypted;
            }
        }

        return $value;
    }
}
