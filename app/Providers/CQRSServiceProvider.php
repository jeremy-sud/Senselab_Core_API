<?php

declare(strict_types=1);

namespace App\Providers;

use App\CQRS\CommandBus;
use App\CQRS\QueryBus;
use App\CQRS\Commands\Venta\CreateVentaCommand;
use App\CQRS\Commands\Venta\CreateVentaCommandHandler;
use App\CQRS\Commands\Venta\CancelVentaCommand;
use App\CQRS\Commands\Venta\CancelVentaCommandHandler;
use App\CQRS\Queries\Venta\GetVentaQuery;
use App\CQRS\Queries\Venta\GetVentaQueryHandler;
use App\CQRS\Queries\Venta\ListVentasQuery;
use App\CQRS\Queries\Venta\ListVentasQueryHandler;
use App\CQRS\Queries\Venta\VentasStatsQuery;
use App\CQRS\Queries\Venta\VentasStatsQueryHandler;
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

        // Productos (futuro)
        // CreateProductoCommand::class => CreateProductoHandler::class,

        // Inventario (futuro)
        // AjustarStockCommand::class => AjustarStockHandler::class,
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

        // Reportes (futuro)
        // VentasResumenQuery::class => VentasResumenHandler::class,
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
