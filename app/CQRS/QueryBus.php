<?php

declare(strict_types=1);

namespace App\CQRS;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class QueryBus
 *
 * Bus central para despachar queries a sus handlers correspondientes.
 * Soporta caching automático de resultados.
 *
 * @package App\CQRS
 * @author Sistemas Ursol S.A.
 */
class QueryBus
{
    /**
     * Mapeo de queries a sus handlers.
     *
     * @var array<class-string<Query>, class-string<QueryHandler>>
     */
    protected array $handlers = [];

    /**
     * TTL por defecto para cache de queries (10 minutos).
     */
    protected const DEFAULT_CACHE_TTL = 600;

    /**
     * @param Container $container Contenedor de dependencias
     */
    public function __construct(
        protected Container $container
    ) {}

    /**
     * Registra un handler para una query específica.
     *
     * @param class-string<Query> $queryClass Clase de la query
     * @param class-string<QueryHandler> $handlerClass Clase del handler
     * @return self
     */
    public function register(string $queryClass, string $handlerClass): self
    {
        $this->handlers[$queryClass] = $handlerClass;
        return $this;
    }

    /**
     * Registra múltiples handlers de una vez.
     *
     * @param array<class-string<Query>, class-string<QueryHandler>> $map
     * @return self
     */
    public function registerMany(array $map): self
    {
        foreach ($map as $queryClass => $handlerClass) {
            $this->register($queryClass, $handlerClass);
        }
        return $this;
    }

    /**
     * Despacha una query a su handler.
     *
     * @param Query $query La query a ejecutar
     * @param bool $useCache Si se debe usar cache (default: true)
     * @param int|null $ttl TTL personalizado para cache
     * @return QueryResult El resultado de la consulta
     * @throws InvalidArgumentException Si no hay handler registrado
     */
    public function dispatch(Query $query, bool $useCache = true, ?int $ttl = null): QueryResult
    {
        $queryClass = get_class($query);
        $startTime = microtime(true);
        $cacheKey = '';

        // Intentar obtener de cache
        if ($useCache) {
            $cacheKey = $this->getCacheKey($query);

            $cached = Cache::tags(['queries', $this->getQueryTag($queryClass)])->get($cacheKey);

            if ($cached instanceof QueryResult) {
                Log::channel('audit')->debug('Query served from cache', [
                    'query' => $query->queryName(),
                    'cache_key' => $cacheKey,
                ]);

                return $cached;
            }
        }

        Log::channel('audit')->debug('Query dispatched', [
            'query' => $query->queryName(),
            'class' => $queryClass,
        ]);

        try {
            $handler = $this->resolveHandler($queryClass);
            $result = $handler->handle($query);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Guardar en cache si fue exitoso
            if ($useCache && $result->success) {
                $cacheTtl = $ttl ?? self::DEFAULT_CACHE_TTL;
                if ($cacheKey === '') {
                    $cacheKey = $this->getCacheKey($query);
                }
                Cache::tags(['queries', $this->getQueryTag($queryClass)])
                    ->put($cacheKey, $result, $cacheTtl);
            }

            Log::channel('audit')->debug('Query completed', [
                'query' => $query->queryName(),
                'success' => $result->success,
                'duration_ms' => $duration,
                'cached' => $useCache,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('audit')->error('Query failed', [
                'query' => $query->queryName(),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return QueryResult::failure($e->getMessage());
        }
    }

    /**
     * Invalida el cache para un tipo de query.
     *
     * @param class-string<Query>|null $queryClass Clase específica o null para todas
     * @return void
     */
    public function invalidateCache(?string $queryClass = null): void
    {
        if ($queryClass === null) {
            Cache::tags(['queries'])->flush();
        } else {
            Cache::tags([$this->getQueryTag($queryClass)])->flush();
        }
    }

    /**
     * Resuelve el handler para una query.
     *
     * @param class-string<Query> $queryClass
     * @return QueryHandler
     * @throws InvalidArgumentException
     */
    protected function resolveHandler(string $queryClass): QueryHandler
    {
        // Buscar handler registrado explícitamente
        if (isset($this->handlers[$queryClass])) {
            /** @var QueryHandler */
            return $this->container->make($this->handlers[$queryClass]);
        }

        // Convención: QueryClass -> QueryClassHandler
        $handlerClass = $queryClass . 'Handler';

        if (class_exists($handlerClass)) {
            /** @var QueryHandler */
            return $this->container->make($handlerClass);
        }

        throw new InvalidArgumentException(
            "No handler registered for query: {$queryClass}"
        );
    }

    /**
     * Genera una clave de cache única para una query.
     *
     * @param Query $query
     * @return string
     */
    protected function getCacheKey(Query $query): string
    {
        $hash = md5(serialize($query));
        return "query:{$query->queryName()}:{$hash}";
    }

    /**
     * Obtiene el tag de cache para una clase de query.
     *
     * @param class-string<Query> $queryClass
     * @return string
     */
    protected function getQueryTag(string $queryClass): string
    {
        return 'query:' . class_basename($queryClass);
    }

    /**
     * Verifica si hay un handler registrado para una query.
     *
     * @param class-string<Query> $queryClass
     * @return bool
     */
    public function hasHandler(string $queryClass): bool
    {
        return isset($this->handlers[$queryClass])
            || class_exists($queryClass . 'Handler');
    }
}
