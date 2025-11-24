<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

/**
 * Trait HasCacheableQueries
 * 
 * Proporciona funcionalidad de cache estandarizada para controladores.
 * Implementa un patrón consistente para cachear consultas de índice
 * con invalidación automática en operaciones de escritura.
 * 
 * IMPORTANTE: Define estas propiedades en tu controlador:
 * - protected array $cacheTags = ['tag1', 'tag2'];
 * - protected int $cacheTTL = 3600;
 * 
 * @package App\Traits
 * @author Sistemas Ursol S.A.
 */
trait HasCacheableQueries
{
    /**
     * Prefijo para las claves de cache
     * Se genera automáticamente basado en el nombre de la clase
     * 
     * @return string
     */
    protected function getCachePrefix(): string
    {
        $className = class_basename($this);
        return strtolower(str_replace('Controller', '', $className));
    }

    /**
     * Genera una clave de cache única basada en los parámetros del request
     * 
     * @param Request $request
     * @param string|null $suffix Sufijo adicional para la clave
     * @return string
     */
    protected function getCacheKey(Request $request, ?string $suffix = null): string
    {
        $prefix = $this->getCachePrefix();
        $params = $request->all();
        
        // Agregar empresa_id del usuario autenticado si existe
        if (auth('sanctum')->check() && !isset($params['empresa_id'])) {
            $params['empresa_id'] = auth('sanctum')->user()->empresa_id ?? null;
        }
        
        $hash = md5(json_encode($params));
        
        return $suffix 
            ? "{$prefix}:{$suffix}:{$hash}"
            : "{$prefix}:index:{$hash}";
    }

    /**
     * Obtiene el TTL del cache
     * Por defecto: 1 hora
     * Puede ser sobrescrito definiendo $cacheTTL en el controlador
     * 
     * @return int Segundos de duración del cache
     */
    protected function getCacheTTL(): int
    {
        return $this->cacheTTL ?? 3600;
    }

    /**
     * Obtiene los tags del cache
     * Puede ser sobrescrito definiendo $cacheTags en el controlador
     * 
     * @return array
     */
    protected function getCacheTags(): array
    {
        return $this->cacheTags ?? [$this->getCachePrefix(), 'catalogos'];
    }

    /**
     * Ejecuta una consulta con cache
     * 
     * @param Request $request
     * @param callable $callback Función que retorna los datos a cachear
     * @param string|null $suffix Sufijo para la clave de cache
     * @return mixed
     */
    protected function cacheQuery(Request $request, callable $callback, ?string $suffix = null)
    {
        $cacheKey = $this->getCacheKey($request, $suffix);
        $cacheTags = $this->getCacheTags();
        $cacheTTL = $this->getCacheTTL();

        return Cache::tags($cacheTags)->remember($cacheKey, $cacheTTL, $callback);
    }

    /**
     * Invalida todo el cache relacionado a este controlador
     * Se debe llamar en operaciones de escritura (create, update, delete)
     * 
     * @return void
     */
    protected function flushCache(): void
    {
        Cache::tags($this->getCacheTags())->flush();
    }

    /**
     * Invalida una clave específica del cache
     * 
     * @param string $key
     * @return void
     */
    protected function forgetCacheKey(string $key): void
    {
        Cache::tags($this->getCacheTags())->forget($key);
    }

    /**
     * Verifica si el cache está habilitado
     * Útil para desactivar cache en testing o debugging
     * 
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return config('cache.enabled', true) 
            && config('cache.default') !== 'array'
            && !app()->environment('testing');
    }

    /**
     * Wrapper condicional para cache
     * Solo cachea si está habilitado, sino ejecuta directamente
     * 
     * @param Request $request
     * @param callable $callback
     * @param string|null $suffix
     * @return mixed
     */
    protected function cacheQueryIfEnabled(Request $request, callable $callback, ?string $suffix = null)
    {
        if ($this->isCacheEnabled()) {
            return $this->cacheQuery($request, $callback, $suffix);
        }
        
        return $callback();
    }
}
