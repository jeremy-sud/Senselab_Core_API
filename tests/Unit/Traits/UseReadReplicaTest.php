<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Traits\UseReadReplica;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para UseReadReplica trait
 *
 * FASE 22 - Escalabilidad (v5.0.1)
 *
 * Verifica que el trait maneja correctamente:
 * - Fallback a conexión default cuando no hay réplica configurada
 * - Detección de réplica habilitada/deshabilitada
 * - Ejecución de queries en réplica vs default
 * - Batch queries en réplica
 */
#[CoversClass(UseReadReplica::class)]
class UseReadReplicaTest extends TestCase
{
    private object $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear una clase anónima que use el trait
        $this->service = new class {
            use UseReadReplica;

            /**
             * Expone queryOnReplica para testing.
             *
             * @template T
             * @param callable(): T $callback
             * @return T
             */
            public function testQueryOnReplica(callable $callback): mixed
            {
                return $this->queryOnReplica($callback);
            }

            /**
             * Expone tableOnReplica para testing.
             */
            public function testTableOnReplica(string $table): Builder
            {
                return $this->tableOnReplica($table);
            }

            /**
             * Expone isReadReplicaEnabled para testing.
             */
            public function testIsReadReplicaEnabled(): bool
            {
                return $this->isReadReplicaEnabled();
            }

            /**
             * Expone batchQueryOnReplica para testing.
             *
             * @param array<string, callable(): mixed> $queries
             * @return array<string, mixed>
             */
            public function testBatchQueryOnReplica(array $queries): array
            {
                return $this->batchQueryOnReplica($queries);
            }
        };
    }

    #[Test]
    public function replica_deshabilitada_cuando_no_hay_configuracion(): void
    {
        // Sin configuración de mysql_read, debe estar deshabilitada
        Config::set('database.connections.mysql_read', null);

        $this->assertFalse($this->service->testIsReadReplicaEnabled());
    }

    #[Test]
    public function replica_deshabilitada_cuando_host_es_igual_a_default(): void
    {
        $defaultHost = config('database.connections.mysql.host', '127.0.0.1');

        Config::set('database.connections.mysql_read', [
            'driver' => 'mysql',
            'host' => $defaultHost,
            'database' => 'test_db',
        ]);

        $this->assertFalse($this->service->testIsReadReplicaEnabled());
    }

    #[Test]
    public function replica_deshabilitada_cuando_host_esta_vacio(): void
    {
        Config::set('database.connections.mysql_read', [
            'driver' => 'mysql',
            'host' => '',
            'database' => 'test_db',
        ]);

        $this->assertFalse($this->service->testIsReadReplicaEnabled());
    }

    #[Test]
    public function query_on_replica_usa_fallback_cuando_replica_deshabilitada(): void
    {
        Config::set('database.connections.mysql_read', null);

        $result = $this->service->testQueryOnReplica(fn () => 'fallback_value');

        $this->assertEquals('fallback_value', $result);
    }

    #[Test]
    public function batch_query_ejecuta_todos_los_callbacks_sin_replica(): void
    {
        Config::set('database.connections.mysql_read', null);

        $results = $this->service->testBatchQueryOnReplica([
            'ventas' => fn () => 100,
            'clientes' => fn () => 50,
            'inventario' => fn () => 200,
        ]);

        $this->assertEquals([
            'ventas' => 100,
            'clientes' => 50,
            'inventario' => 200,
        ], $results);
    }

    #[Test]
    public function batch_query_retorna_array_vacio_con_queries_vacias(): void
    {
        Config::set('database.connections.mysql_read', null);

        $results = $this->service->testBatchQueryOnReplica([]);

        $this->assertEquals([], $results);
    }

    #[Test]
    public function table_on_replica_retorna_builder_con_conexion_default(): void
    {
        Config::set('database.connections.mysql_read', null);

        $builder = $this->service->testTableOnReplica('ventas');

        $this->assertInstanceOf(Builder::class, $builder);
    }

    #[Test]
    public function query_on_replica_preserva_conexion_original_tras_ejecucion(): void
    {
        Config::set('database.connections.mysql_read', null);
        $originalConnection = DB::getDefaultConnection();

        $this->service->testQueryOnReplica(fn () => 'test');

        $this->assertEquals($originalConnection, DB::getDefaultConnection());
    }

    #[Test]
    public function query_on_replica_retorna_tipos_diversos(): void
    {
        Config::set('database.connections.mysql_read', null);

        // Array
        $this->assertEquals([1, 2, 3], $this->service->testQueryOnReplica(fn () => [1, 2, 3]));

        // Null
        $this->assertNull($this->service->testQueryOnReplica(fn () => null));

        // Integer
        $this->assertEquals(42, $this->service->testQueryOnReplica(fn () => 42));

        // Float
        $this->assertEquals(3.14, $this->service->testQueryOnReplica(fn () => 3.14));
    }
}
