<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

/**
 * Interface Query
 *
 * Define una consulta que representa una solicitud de datos sin modificar el estado.
 * Las queries son objetos de valor inmutables que encapsulan los criterios de búsqueda.
 *
 * Principios:
 * - Una query NO modifica el estado del sistema
 * - Una query es inmutable una vez creada
 * - Una query puede tener múltiples handlers (diferentes fuentes de datos)
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
interface Query
{
    /**
     * Obtiene el nombre único de la query para el registro/logging.
     *
     * @return string
     */
    public function queryName(): string;
}
