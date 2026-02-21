<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

/**
 * Interface QueryHandler
 *
 * Define un handler que procesa una query específica.
 * Cada handler encapsula la lógica para recuperar datos del sistema.
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
interface QueryHandler
{
    /**
     * Ejecuta la query y retorna los datos solicitados.
     *
     * @param Query $query La query a ejecutar
     * @return QueryResult El resultado con los datos
     */
    public function handle(Query $query): QueryResult;
}
