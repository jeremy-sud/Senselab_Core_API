<?php

declare(strict_types=1);

namespace App\Providers;

use App\CQRS\CommandBus;
use App\CQRS\QueryBus;
use App\CQRS\Commands\Venta\CreateVentaCommand;
use App\CQRS\Commands\Venta\CreateVentaCommandHandler;
use App\CQRS\Commands\Venta\CancelVentaCommand;
use App\CQRS\Commands\Venta\CancelVentaCommandHandler;
use App\CQRS\Commands\Contabilidad\CreateAsientoCommand;
use App\CQRS\Commands\Contabilidad\CreateAsientoCommandHandler;
use App\CQRS\Commands\Contabilidad\AnularAsientoCommand;
use App\CQRS\Commands\Contabilidad\AnularAsientoCommandHandler;
use App\CQRS\Commands\Compra\CreateOrdenCompraCommand;
use App\CQRS\Commands\Compra\CreateOrdenCompraCommandHandler;
use App\CQRS\Commands\Compra\CancelOrdenCompraCommand;
use App\CQRS\Commands\Compra\CancelOrdenCompraCommandHandler;
use App\CQRS\Queries\Venta\GetVentaQuery;
use App\CQRS\Queries\Venta\GetVentaQueryHandler;
use App\CQRS\Queries\Venta\ListVentasQuery;
use App\CQRS\Queries\Venta\ListVentasQueryHandler;
use App\CQRS\Queries\Venta\VentasStatsQuery;
use App\CQRS\Queries\Venta\VentasStatsQueryHandler;
use App\CQRS\Queries\Contabilidad\GetAsientoQuery;
use App\CQRS\Queries\Contabilidad\GetAsientoQueryHandler;
use App\CQRS\Queries\Contabilidad\ListAsientosQuery;
use App\CQRS\Queries\Contabilidad\ListAsientosQueryHandler;
use App\CQRS\Queries\Compra\GetOrdenCompraQuery;
use App\CQRS\Queries\Compra\GetOrdenCompraQueryHandler;
use App\CQRS\Queries\Compra\ListOrdenesCompraQuery;
use App\CQRS\Queries\Compra\ListOrdenesCompraQueryHandler;
use Illuminate\Support\ServiceProvider;

/**
 * Class CQRSServiceProvider
 *
 * Registra los buses CQRS y los handlers de comandos/queries.
 *
 * @package App\Providers
 * @author Sistemas Ursol S.A.
 */
class CQRSServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de Commands a Handlers.
     *
     * @var array<class-string, class-string>
     */
    protected array $commands = [
        // Ventas
        CreateVentaCommand::class => CreateVentaCommandHandler::class,
        CancelVentaCommand::class => CancelVentaCommandHandler::class,

        // Contabilidad
        CreateAsientoCommand::class => CreateAsientoCommandHandler::class,
        AnularAsientoCommand::class => AnularAsientoCommandHandler::class,

        // Compras
        CreateOrdenCompraCommand::class => CreateOrdenCompraCommandHandler::class,
        CancelOrdenCompraCommand::class => CancelOrdenCompraCommandHandler::class,
    ];

    /**
     * Mapeo de Queries a Handlers.
     *
     * @var array<class-string, class-string>
     */
    protected array $queries = [
        // Ventas
        GetVentaQuery::class => GetVentaQueryHandler::class,
        ListVentasQuery::class => ListVentasQueryHandler::class,
        VentasStatsQuery::class => VentasStatsQueryHandler::class,

        // Contabilidad
        GetAsientoQuery::class => GetAsientoQueryHandler::class,
        ListAsientosQuery::class => ListAsientosQueryHandler::class,

        // Compras
        GetOrdenCompraQuery::class => GetOrdenCompraQueryHandler::class,
        ListOrdenesCompraQuery::class => ListOrdenesCompraQueryHandler::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar CommandBus como singleton
        $this->app->singleton(CommandBus::class, function ($app) {
            $bus = new CommandBus($app);
            $bus->registerMany($this->commands);
            return $bus;
        });

        // Registrar QueryBus como singleton
        $this->app->singleton(QueryBus::class, function ($app) {
            $bus = new QueryBus($app);
            $bus->registerMany($this->queries);
            return $bus;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
