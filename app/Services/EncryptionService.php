<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Servicio de Encriptación Granular de Datos Sensibles
 *
 * Proporciona métodos estáticos para cifrar/descifrar campos específicos
 * en modelos Eloquent. Soporta múltiples algoritmos, auditoría y rotación de claves.
 *
 * @package App\Services
 * @version 1.0.0
 */
class EncryptionService
{
    /**
     * Encrypter instance
     */
    protected static ?Encrypter $encrypter = null;

    /**
     * Obtener la instancia de encriptador
     */
    protected static function getEncrypter(): Encrypter
    {
        if (! static::$encrypter) {
            // Obtener la instancia global de encrypter suministrada por Laravel.
            // Esto utiliza la clave definida en `config/app.php`/`.env` y el
            // cifrado configurado (por ejemplo AES-256-CBC). Reuse la misma
            // instancia para evitar recrear objetos en llamadas repetidas.
            static::$encrypter = app('encrypter');
        }

        return static::$encrypter;
    }

    /**
     * Encriptar un valor
     *
     * @param mixed $value Valor a encriptar
     * @return string|null Valor encriptado en base64 o null si no hay valor
     */
    public static function encrypt($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $encrypter = static::getEncrypter();
            $encrypted = $encrypter->encrypt($value);

            // Log si está habilitado
            if (config('encryption.options.log_decryption_access')) {
                // Registrar el acceso al cifrado para auditoría mínima. No
                // registramos el valor real, sólo un marcador `***` para
                // evitar filtrar datos sensibles en los logs.
                static::logEncryptionAccess('encryption', $value === '' ? null : '***');
            }

            return $encrypted;
        } catch (\Exception $e) {
            Log::error('Encryption failed', [
                'error' => $e->getMessage(),
                'value_length' => strlen((string)$value),
            ]);

            return null;
        }
    }

    /**
     * Desencriptar un valor
     *
     * @param string|null $encryptedValue Valor encriptado
     * @return string|null Valor desencriptado o null si falla
     */
    public static function decrypt(?string $encryptedValue): ?string
    {
        if ($encryptedValue === null || $encryptedValue === '') {
            return null;
        }

        try {
            // Verificar permisos de desencriptación
            // Antes de intentar desencriptar, asegurarse de que el actor
            // (user/IP/roles) esté autorizado a ver datos sensibles. Si no lo
            // está, devolvemos un marcador en lugar de lanzar una excepción
            // que podría filtrar información.
            if (! static::canDecrypt()) {
                Log::warning('Decryption denied - insufficient permissions', [
                    'user_id' => Auth::id(),
                    'ip' => request()->ip(),
                ]);

                return '[ENCRYPTED]';
            }

            $encrypter = static::getEncrypter();
            $decrypted = $encrypter->decrypt($encryptedValue);

            // Log si está habilitado
            if (config('encryption.options.log_decryption_access')) {
                static::logEncryptionAccess('decryption', '***');
            }

            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Decryption failed', [
                'error' => $e->getMessage(),
                'encrypted_length' => strlen($encryptedValue),
            ]);

            return null;
        }
    }

    /**
     * Verificar si el usuario actual puede desencriptar datos
     *
     * @return bool
     */
    public static function canDecrypt(): bool
    {
        // Si encriptación está deshabilitada, permitir todo
        if (! config('encryption.enabled')) {
            return true;
        }

        // Verificar IP whitelist
        $ipWhitelist = config('encryption.trusted_decryptors.ip_whitelist', []);
        // Si la petición proviene de una IP de confianza, permitir desencriptar
        if (in_array(request()->ip(), $ipWhitelist)) {
            return true;
        }

        // Verificar roles
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $trustedRoles = config('encryption.trusted_decryptors.roles', []);
        foreach ($trustedRoles as $role) {
            // Revisar roles asignados al usuario (ej. admin, security_officer)
            if ($user->hasRole($role)) {
                return true;
            }
        }

        // Verificar permisos específicos
        $trustedPermissions = config('encryption.trusted_decryptors.permissions', []);
        foreach ($trustedPermissions as $permission) {
            // Permisos finos (ej. 'view_sensitive_data') permiten acceso
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encriptar múltiples campos de un array
     *
     * @param array<string, mixed> $data Array con datos a encriptar
     * @param string $modelClass Clase del modelo (para saber qué campos encriptar)
     * @return array<string, mixed>
     */
    public static function encryptFields(array $data, string $modelClass): array
    {
        $fields = config("encryption.models.{$modelClass}", []);

        foreach ($fields as $fieldName => $config) {
            if ($config['enabled'] ?? false && isset($data[$fieldName])) {
                $data[$fieldName] = static::encrypt($data[$fieldName]);

                // Generar hash para búsqueda rápida
                if (config('encryption.options.use_hashed_lookup')) {
                    $hashColumn = $fieldName . config('encryption.options.hash_column_suffix', '_hash');
                    // Para facilitar búsquedas sin desencriptar, almacenamos
                    // un hash del valor encriptado. Importante: la búsqueda se
                    // realiza contra este hash, por lo que para buscar se
                    // debe hashear el valor de entrada igual que aquí.
                    $data[$hashColumn] = hash(
                        config('encryption.options.hash_algorithm', 'sha256'),
                        $data[$fieldName] ?? ''
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Desencriptar múltiples campos de un array
     *
     * @param array<string, mixed> $data Array con datos encriptados
     * @param string $modelClass Clase del modelo
     * @return array<string, mixed>
     */
    public static function decryptFields(array $data, string $modelClass): array
    {
        $fields = config("encryption.models.{$modelClass}", []);

        foreach ($fields as $fieldName => $config) {
            if ($config['enabled'] ?? false && isset($data[$fieldName])) {
                $data[$fieldName] = static::decrypt($data[$fieldName]);
            }
        }

        return $data;
    }

    /**
     * Buscar por campo encriptado usando hash
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Campo a buscar
     * @param string $searchValue Valor a buscar (se hashea antes)
     * @return string Hash para consulta
     */
    public static function getHashForSearch(string $modelClass, string $fieldName, string $searchValue): string
    {
        if (! config('encryption.options.use_hashed_lookup')) {
            throw new \RuntimeException('Hash lookup is disabled in encryption config');
        }

        // Para buscar por un campo encriptado, primero se encripta el valor
        // de búsqueda con la misma lógica que al guardar y luego se aplica
        // el hash configurado. Esto evita la necesidad de desencriptar
        // filas durante búsquedas.
        return hash(
            config('encryption.options.hash_algorithm', 'sha256'),
            static::encrypt($searchValue) ?? ''
        );
    }

    /**
     * Generar clave de caché para valor descifrado
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Campo
     * @param mixed $recordId ID del registro
     * @return string
     */
    protected static function getCacheKey(string $modelClass, string $fieldName, $recordId): string
    {
        return "encryption.decrypted.{$modelClass}.{$fieldName}.{$recordId}";
    }

    /**
     * Obtener valor descifrado del caché (si está habilitado)
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Campo
     * @param mixed $recordId ID del registro
     * @return string|null
     */
    public static function getFromCache(string $modelClass, string $fieldName, $recordId): ?string
    {
        if (! config('encryption.performance.cache_decrypted')) {
            return null;
        }

        $key = static::getCacheKey($modelClass, $fieldName, $recordId);
        $ttl = config('encryption.performance.cache_ttl', 0);

        // Devolver el valor descifrado en caché si existe. Atención: el uso
        // de caché para datos sensibles requiere políticas de expiración y
        // borrado cuidadoso cuando se actualizan permisos o el propio dato.
        return Cache::get($key);
    }

    /**
     * Guardar valor descifrado en caché
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Campo
     * @param mixed $recordId ID del registro
     * @param string $value Valor descifrado
     * @return void
     */
    public static function putInCache(string $modelClass, string $fieldName, $recordId, string $value): void
    {
        if (! config('encryption.performance.cache_decrypted')) {
            return;
        }

        $key = static::getCacheKey($modelClass, $fieldName, $recordId);
        $ttl = config('encryption.performance.cache_ttl', 0);

        if ($ttl > 0) {
            // Guardar el valor descifrado temporalmente. Asegurarse de que
            // el `cache_ttl` sea corto y que exista lógica para invalidar
            // entradas cuando cambie el dato o los permisos.
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Loguear acceso a datos encriptados para auditoría
     *
     * @param string $action 'encryption' o 'decryption'
     * @param string $value Valor (mostrado como ***)
     * @return void
     */
    protected static function logEncryptionAccess(string $action, ?string $value = null): void
    {
        $channel = config('encryption.options.log_channel', 'security');
        $user = Auth::user();

        Log::channel($channel)->info("Data encryption access: {$action}", [
            'action' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip' => request()->ip(),
            'timestamp' => now(),
            'value_summary' => $value,
        ]);

        // Guardar en tabla de auditoría si está habilitada
        if (config('encryption.audit.enabled') && config('encryption.audit.log_access')) {
            // Además del log por canal, grabar un registro estructurado en
            // la tabla de auditoría para informes forenses si está
            // configurado. Aquí se guarda un resumen (no el valor real).
            static::recordAuditLog($action, $user?->id, $value);
        }
    }

    /**
     * Grabar log de auditoría
     *
     * @param string $action Acción realizada
     * @param int|null $userId ID del usuario
     * @param string|null $valueSummary Resumen del valor (mostrado como ***)
     * @return void
     */
    protected static function recordAuditLog(string $action, ?int $userId, ?string $valueSummary): void
    {
        $auditTable = config('encryption.audit.audit_table');

        if (! $auditTable || ! \Schema::hasTable($auditTable)) {
            return;
        }

        try {
            \DB::table($auditTable)->insert([
                'action' => $action,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'value_summary' => $valueSummary,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record encryption audit log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener campos encriptados para un modelo
     *
     * @param string $modelClass Clase del modelo
     * @return array<string, mixed>
     */
    public static function getEncryptedFields(string $modelClass): array
    {
        $config = config("encryption.models.{$modelClass}", []);

        return array_filter($config, fn($field) => $field['enabled'] ?? false);
    }

    /**
     * Verificar si un campo está encriptado
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Nombre del campo
     * @return bool
     */
    public static function isFieldEncrypted(string $modelClass, string $fieldName): bool
    {
        $config = config("encryption.models.{$modelClass}.{$fieldName}", []);

        return $config['enabled'] ?? false;
    }

    /**
     * Reencriptar datos con nueva clave (para rotación de claves)
     *
     * @param string $modelClass Clase del modelo
     * @param string $fieldName Campo a reencriptar
     * @param Encrypter $oldEncrypter Instancia anterior del encryptador (con clave vieja)
     * @return int Número de registros reencriptados
     */
    public static function rotateKey(string $modelClass, string $fieldName, Encrypter $oldEncrypter): int
    {
        if (! config('encryption.options.support_key_rotation')) {
            throw new \RuntimeException('Key rotation is not enabled in encryption config');
        }

        // Obtener modelo
        $model = new $modelClass();
        // Nota: este método realiza una rotación sencilla recorriendo todos
        // los registros. Para datasets grandes o producción, se recomienda
        // paginar, usar transacciones parciales, bloquear filas y/o ejecutar
        // el proceso fuera de línea para evitar inconsistencias.
        $records = $model->all();
        $count = 0;

        foreach ($records as $record) {
            try {
                // Desencriptar con clave vieja
                $oldValue = $oldEncrypter->decrypt($record->{$fieldName});

                // Encriptar con clave nueva
                $newValue = static::encrypt($oldValue);

                // Guardar
                $record->update([$fieldName => $newValue]);
                $count++;

                Log::info("Key rotated for {$modelClass}.{$fieldName}", [
                    'record_id' => $record->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Key rotation failed for {$modelClass}.{$fieldName}", [
                    'record_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Obtener estadísticas de encriptación
     *
     * @return array<string, mixed>
     */
    public static function getStatistics(): array
    {
        $modelsConfig = config('encryption.models', []);
        $totalFields = 0;
        $totalEncryptedFields = 0;

        foreach ($modelsConfig as $modelClass => $fields) {
            $totalFields += count($fields);
            $totalEncryptedFields += count(array_filter($fields, fn($f) => $f['enabled'] ?? false));
        }

        return [
            'encryption_enabled' => config('encryption.enabled'),
            'cipher' => config('encryption.cipher'),
            'total_models' => count($modelsConfig),
            'total_fields' => $totalFields,
            'encrypted_fields' => $totalEncryptedFields,
            'cache_enabled' => config('encryption.performance.cache_decrypted'),
            'audit_enabled' => config('encryption.audit.enabled'),
            'timestamp' => now(),
        ];
    }
}
