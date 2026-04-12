<?php

declare(strict_types=1);

/**
 * Laravel Horizon Configuration
 *
 * FASE 22 - Escalabilidad
 *
 * Horizon proporciona un dashboard visual para monitoreo de queues Redis.
 * Incluye métricas de jobs procesados, fallidos, tiempos de espera y throughput.
 *
 * Instalación:
 *   composer require laravel/horizon
 *   php artisan horizon:install
 *   php artisan horizon
 *
 * Dashboard: /horizon (protegido por HorizonServiceProvider)
 *
 * @see https://laravel.com/docs/horizon
 */

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | These options define the wait time thresholds for queue lengths.
    | These values control when Horizon will notify about long wait times.
    |
    */

    'waits' => [
        'redis:default' => 60,
        'redis:high' => 30,
        'redis:webhooks' => 120,
        'redis:reports' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Jobs listed here will not appear in the completed jobs list in Horizon.
    | This is useful for jobs that run frequently and don't need monitoring.
    |
    */

    'silenced' => [
        // App\Jobs\ProcessHeartbeat::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Different metrics are captured by Horizon and visualized in the dashboard.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When using Supervisor (recommended), enabling fast termination will
    | signal workers to exit immediately after handling the current job.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | Memory limit for Horizon workers. Increase if processing large jobs.
    |
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application.
    | These settings determine how many workers each queue should have and
    | how long they should be allowed to work before timing out.
    |
    | Colas configuradas para Ursol CAST API:
    | - default: Operaciones generales
    | - high: Operaciones prioritarias (pagos, facturación)
    | - webhooks: Entrega de webhooks con retry
    | - reports: Generación de reportes pesados
    | - hacienda: Comunicación con Hacienda de Costa Rica
    | - emails: Envío de correos electrónicos
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            // Workers principales (alta prioridad + default)
            'supervisor-1' => [
                'maxProcesses' => 20,
                'balanceMaxShift' => 5,
                'balanceCooldown' => 3,
                'queue' => ['high', 'default'],
            ],
            // Workers para webhooks (requiere reintentos)
            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'simple',
                'maxProcesses' => 5,
                'maxTime' => 0,
                'maxJobs' => 500,
                'memory' => 128,
                'tries' => 10,
                'timeout' => 30,
                'backoff' => [30, 120, 480],
            ],
            // Workers para reportes (procesos largos)
            'supervisor-reports' => [
                'connection' => 'redis',
                'queue' => ['reports'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'maxTime' => 0,
                'maxJobs' => 50,
                'memory' => 512,
                'tries' => 1,
                'timeout' => 600,
            ],
            // Workers para Hacienda (rate limited)
            'supervisor-hacienda' => [
                'connection' => 'redis',
                'queue' => ['hacienda'],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'maxTime' => 0,
                'maxJobs' => 100,
                'memory' => 256,
                'tries' => 5,
                'timeout' => 120,
                'backoff' => [60, 300, 900],
            ],
            // Workers para emails
            'supervisor-emails' => [
                'connection' => 'redis',
                'queue' => ['emails'],
                'balance' => 'auto',
                'maxProcesses' => 5,
                'maxTime' => 0,
                'maxJobs' => 1000,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 30,
            ],
        ],

        'staging' => [
            'supervisor-1' => [
                'maxProcesses' => 5,
                'queue' => ['high', 'default', 'webhooks', 'reports', 'hacienda', 'emails'],
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
                'queue' => ['high', 'default', 'webhooks', 'reports', 'hacienda', 'emails'],
            ],
        ],
    ],
];
