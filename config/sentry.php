<?php

declare(strict_types=1);

return [
    // The DSN (Data Source Name) of the Sentry project
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Set the environment name
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    // Enable distributed tracing
    'enable_tracing' => env('SENTRY_ENABLE_TRACING', false),

    // Ignore certain exceptions from being reported
    'ignore_exceptions' => [
        \Illuminate\Session\TokenMismatchException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Auth\AuthenticationException::class,
    ],

    // Performance monitoring sample rate (0.0 - 1.0)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Capture release information
    'release' => env('SENTRY_RELEASE'),

    // Maximum breadcrumbs
    'max_breadcrumbs' => 100,

    // Sample rate for errors
    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),

    // Send personally identifiable information (PII)
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    // Profiling sample rate
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    // Default tags
    'tags' => [
        'php_version' => PHP_VERSION,
        'laravel_version' => \Illuminate\Foundation\Application::VERSION,
    ],

    // HTTP timeouts
    'http_timeout' => 30,
    'http_ssl_verify_peer' => true,
    'http_connect_timeout' => 3,
];
