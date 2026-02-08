<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /**
         * CORS Logging Channel (FASE 1.2)
         *
         * Canal separado para logging de CORS requests
         * Útil para auditoría, debugging y detección de ataques
         */
        'cors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cors.log'),
            'level' => env('LOG_CORS_LEVEL', 'debug'),
            'days' => env('LOG_CORS_DAYS', 30),
            'replace_placeholders' => true,
        ],

        /**
         * Security Logging Channel (FASE 1.3)
         *
         * Canal para logging estructurado de seguridad, requests HTTP y eventos críticos
         * Formato JSON para análisis automático
         * Retención: 30 días
         */
        'security' => [
            'driver' => 'monolog',
            'level' => env('LOG_SECURITY_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/security.log'),
                'maxFiles' => 30,
            ],
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'processors' => [
                \Monolog\Processor\UidProcessor::class,
                \Monolog\Processor\InvocationProcessor::class,
            ],
        ],

        /**
         * Audit Logging Channel (FASE 1.3)
         *
         * Canal para auditoría de cambios en datos (CRUD operations)
         * Retención extendida: 90 días
         */
        'audit' => [
            'driver' => 'monolog',
            'level' => 'info',
            'handler' => \Monolog\Handler\RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/audit.log'),
                'maxFiles' => 90,
            ],
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'processors' => [
                \Monolog\Processor\UidProcessor::class,
            ],
        ],

        /**
         * Performance Logging Channel (FASE 1.3)
         *
         * Canal para métricas de rendimiento (queries lentas, requests lentos)
         */
        'performance' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => \Monolog\Handler\RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/performance.log'),
                'maxFiles' => 30,
            ],
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

    ],

];
