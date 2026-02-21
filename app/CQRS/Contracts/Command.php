<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

/**
 * Interface Command
 *
 * Define un comando que representa una intención de cambiar el estado del sistema.
 * Los comandos son objetos de valor inmutables que encapsulan todos los datos
 * necesarios para ejecutar una operación de escritura.
 *
 * Principios:
 * - Un comando NO devuelve datos del dominio (solo indicadores de éxito/fallo)
 * - Un comando es inmutable una vez creado
 * - Un comando tiene un único handler
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
interface Command
{
    /**
     * Obtiene el nombre único del comando para el registro/logging.
     *
     * @return string
     */
    public function commandName(): string;
}
