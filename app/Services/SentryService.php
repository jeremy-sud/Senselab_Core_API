<?php

declare(strict_types=1);

namespace App\Services;

use Sentry\Tracing\Transaction;
use Sentry\EventId;
use Sentry\State\Hub;

/**
 * Sentry Error Tracking Service
 *
 * FASE 1.4: Wrapper para Sentry API
 * Proporciona métodos para capturar eventos, errores, y cambios de contexto
 *
 * @see https://docs.sentry.io/platforms/php/
 */
class SentryService
{
    /**
     * Capturar una excepción en Sentry
     *
     * @param \Throwable $exception
     * @param array<string, mixed> $context
     * @return \Sentry\EventId|null
     */
    public static function captureException(\Throwable $exception, array $context = []): ?EventId
    {
        if (!config('sentry.enabled', true)) {
            return null;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();

        // Agregar contexto adicional
        if (!empty($context)) {
            $hub->withScope(function ($scope) use ($context) {
                foreach ($context as $key => $value) {
                    $scope->setContext($key, is_array($value) ? $value : [$key => $value]);
                }
            });
        }

        return $hub->captureException($exception);
    }

    /**
     * Capturar un mensaje en Sentry
     *
     * @param string $message
     * @param string $level debug, info, warning, error, fatal
     * @param array<string, mixed> $context
     * @return \Sentry\EventId|null
     */
    public static function captureMessage(string $message, string $level = 'info', array $context = []): ?EventId
    {
        if (!config('sentry.enabled', true)) {
            return null;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();

        return $hub->withScope(function ($scope) use ($hub, $message, $level, $context) {
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $scope->setContext($key, is_array($value) ? $value : [$key => $value]);
                }
            }

            return $hub->captureMessage($message, $level);
        });
    }

    /**
     * Establecer identificador de usuario para error tracking
     *
     * @param int|string|null $userId
     * @param string|null $email
     * @param string|null $username
     * @return void
     */
    public static function setUserContext($userId = null, ?string $email = null, ?string $username = null): void
    {
        if (!config('sentry.enabled', true)) {
            return;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();

        $hub->configureScope(function ($scope) use ($userId, $email, $username) {
            $scope->setUser([
                'id' => $userId,
                'email' => $email,
                'username' => $username,
            ]);
        });
    }

    /**
     * Limpiar contexto de usuario
     *
     * @return void
     */
    public static function clearUserContext(): void
    {
        if (!config('sentry.enabled', true)) {
            return;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();
        $hub->configureScope(function ($scope) {
            $scope->setUser(null);
        });
    }

    /**
     * Agregar contexto personalizado
     *
     * @param string $key
     * @param array<string, mixed> $data
     * @return void
     */
    public static function addContext(string $key, array $data): void
    {
        if (!config('sentry.enabled', true)) {
            return;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();
        $hub->configureScope(function ($scope) use ($key, $data) {
            $scope->setContext($key, $data);
        });
    }

    /**
     * Agregar breadcrumb (evento en la cadena de eventos)
     *
     * @param string $message
     * @param string $category
     * @param string $level debug, info, warning, error
     * @param array<string, mixed> $data
     * @return void
     */
    public static function addBreadcrumb(string $message, string $category = 'app', string $level = 'info', array $data = []): void
    {
        if (!config('sentry.enabled', true)) {
            return;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();
        $hub->addBreadcrumb(
            \Sentry\Breadcrumb::create()
                ->setMessage($message)
                ->setCategory($category)
                ->setLevel($level)
                ->setData($data)
        );
    }

    /**
     * Iniciar una transacción (para performance monitoring)
     *
     * @param string $name Nombre de la transacción
     * @param string $op Operación (http.request, db.query, etc)
     * @return Transaction|null
     */
    public static function captureTransaction(string $name, string $op = 'http.request'): ?Transaction
    {
        if (!config('sentry.enabled', true)) {
            return null;
        }

        $hub = \Sentry\SentrySdk::getCurrentHub();

        return $hub->startTransaction(
            \Sentry\Tracing\TransactionContext::make()
                ->setName($name)
                ->setOp($op)
        );
    }

    /**
     * Completar una transacción
     *
     * @param Transaction|null $transaction
     * @return void
     */
    public static function finishTransaction(?Transaction $transaction): void
    {
        if ($transaction !== null) {
            $transaction->finish();
        }
    }

    /**
     * Verificar si Sentry está habilitado
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return config('sentry.enabled', true) && !empty(config('sentry.dsn'));
    }
}
