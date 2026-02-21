<?php

declare(strict_types=1);

namespace App\CQRS;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class CommandBus
 *
 * Bus central para despachar comandos a sus handlers correspondientes.
 * Implementa el patrón mediador para desacoplar commands de handlers.
 *
 * @package App\CQRS
 * @author Sistemas Ursol S.A.
 */
class CommandBus
{
    /**
     * Mapeo de comandos a sus handlers.
     *
     * @var array<class-string<Command>, class-string<CommandHandler>>
     */
    protected array $handlers = [];

    /**
     * @param Container $container Contenedor de dependencias
     */
    public function __construct(
        protected Container $container
    ) {}

    /**
     * Registra un handler para un comando específico.
     *
     * @param class-string<Command> $commandClass Clase del comando
     * @param class-string<CommandHandler> $handlerClass Clase del handler
     * @return self
     */
    public function register(string $commandClass, string $handlerClass): self
    {
        $this->handlers[$commandClass] = $handlerClass;
        return $this;
    }

    /**
     * Registra múltiples handlers de una vez.
     *
     * @param array<class-string<Command>, class-string<CommandHandler>> $map
     * @return self
     */
    public function registerMany(array $map): self
    {
        foreach ($map as $commandClass => $handlerClass) {
            $this->register($commandClass, $handlerClass);
        }
        return $this;
    }

    /**
     * Despacha un comando a su handler.
     *
     * @param Command $command El comando a ejecutar
     * @return CommandResult El resultado de la ejecución
     * @throws InvalidArgumentException Si no hay handler registrado
     */
    public function dispatch(Command $command): CommandResult
    {
        $commandClass = get_class($command);
        $startTime = microtime(true);

        Log::channel('audit')->info('Command dispatched', [
            'command' => $command->commandName(),
            'class' => $commandClass,
        ]);

        try {
            $handler = $this->resolveHandler($commandClass);
            $result = $handler->handle($command);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('audit')->info('Command completed', [
                'command' => $command->commandName(),
                'success' => $result->success,
                'duration_ms' => $duration,
                'id' => $result->id,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('audit')->error('Command failed', [
                'command' => $command->commandName(),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return CommandResult::failure(
                message: $e->getMessage(),
                metadata: ['exception' => get_class($e)]
            );
        }
    }

    /**
     * Resuelve el handler para un comando.
     *
     * @param class-string<Command> $commandClass
     * @return CommandHandler
     * @throws InvalidArgumentException
     */
    protected function resolveHandler(string $commandClass): CommandHandler
    {
        // Buscar handler registrado explícitamente
        if (isset($this->handlers[$commandClass])) {
            /** @var CommandHandler */
            return $this->container->make($this->handlers[$commandClass]);
        }

        // Convención: CommandClass -> CommandClassHandler
        $handlerClass = $commandClass . 'Handler';

        if (class_exists($handlerClass)) {
            /** @var CommandHandler */
            return $this->container->make($handlerClass);
        }

        throw new InvalidArgumentException(
            "No handler registered for command: {$commandClass}"
        );
    }

    /**
     * Verifica si hay un handler registrado para un comando.
     *
     * @param class-string<Command> $commandClass
     * @return bool
     */
    public function hasHandler(string $commandClass): bool
    {
        return isset($this->handlers[$commandClass])
            || class_exists($commandClass . 'Handler');
    }
}
