<?php

declare(strict_types=1);

namespace App\CQRS\Contracts;

/**
 * Interface CommandHandler
 *
 * Define un handler que procesa un comando específico.
 * Cada handler encapsula la lógica de negocio para ejecutar una operación de escritura.
 *
 * @package App\CQRS\Contracts
 * @author Sistemas Ursol S.A.
 */
interface CommandHandler
{
    /**
     * Ejecuta el comando y realiza la operación de escritura.
     *
     * @param Command $command El comando a ejecutar
     * @return CommandResult El resultado de la operación
     */
    public function handle(Command $command): CommandResult;
}
