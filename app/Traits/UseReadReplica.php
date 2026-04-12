<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait para usar Read Replicas en consultas pesadas
 *
 * FASE 22 - Escalabilidad
 *
 * Use este trait en servicios que ejecutan consultas pesadas (reportes, dashboards)
 * para derivar las lecturas a réplicas de base de datos.
 *
 * Requisitos:
 * - DB_READ_WRITE_SPLIT=true o mysql_read configurado en database.php
 * - Réplica MySQL configurada con replication lag < 1s
 *
 * Uso:
 *   use UseReadReplica;
 *
 *   public function getReport() {
 *       return $this->queryOnReplica(function () {
 *           return Venta::whereBetween('fecha_venta', [...])->get();
 *       });
 *   }
 *
 * @package App\Traits
 * @version 5.0.0
 */
trait UseReadReplica
{
    /**
     * Nombre de la conexión de réplica.
     */
    protected string $readReplicaConnection = 'mysql_read';

    /**
     * Ejecuta una query en la réplica de lectura.
     *
     * Automáticamente hace fallback a la conexión default si la réplica no está configurada.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function queryOnReplica(callable $callback): mixed
    {
        // Verificar si read replicas están habilitadas
        if (!$this->isReadReplicaEnabled()) {
            return $callback();
        }

        // Cambiar temporalmente a la conexión de réplica
        $previousConnection = DB::getDefaultConnection();

        try {
            DB::setDefaultConnection($this->readReplicaConnection);
            return $callback();
        } finally {
            // Restaurar conexión original
            DB::setDefaultConnection($previousConnection);
        }
    }

    /**
     * Retorna un query builder conectado a la réplica.
     *
     * @param string $table
     * @return Builder
     */
    protected function tableOnReplica(string $table): Builder
    {
        $connection = $this->isReadReplicaEnabled()
            ? $this->readReplicaConnection
            : config('database.default');

        return DB::connection($connection)->table($table);
    }

    /**
     * Verifica si las read replicas están configuradas y habilitadas.
     */
    protected function isReadReplicaEnabled(): bool
    {
        // Verificar si la conexión de réplica existe y está configurada
        $replicaConfig = config("database.connections.{$this->readReplicaConnection}");

        if (empty($replicaConfig)) {
            return false;
        }

        // Verificar si el host de réplica está configurado
        $replicaHost = $replicaConfig['host'] ?? null;
        $defaultHost = config('database.connections.mysql.host');

        // Si es el mismo host, no hay réplica real (env de desarrollo)
        if ($replicaHost === $defaultHost) {
            return false;
        }

        return !empty($replicaHost);
    }

    /**
     * Ejecuta múltiples queries en réplica con una sola conexión.
     *
     * Útil para dashboards que necesitan múltiples consultas.
     *
     * @template T
     * @param array<string, callable(): mixed> $queries Array asociativo de nombre => callback
     * @return array<string, mixed>
     */
    protected function batchQueryOnReplica(array $queries): array
    {
        if (!$this->isReadReplicaEnabled()) {
            $results = [];
            foreach ($queries as $name => $callback) {
                $results[$name] = $callback();
            }
            return $results;
        }

        $previousConnection = DB::getDefaultConnection();

        try {
            DB::setDefaultConnection($this->readReplicaConnection);

            $results = [];
            foreach ($queries as $name => $callback) {
                $results[$name] = $callback();
            }

            return $results;
        } finally {
            DB::setDefaultConnection($previousConnection);
        }
    }
}
