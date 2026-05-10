<?php

declare(strict_types=1);

/**
 * Distributed Tracing Configuration (OpenTelemetry)
 *
 * FASE 22 - Escalabilidad
 *
 * Configuración para distributed tracing con OpenTelemetry.
 * Permite rastrear requests a través de múltiples servicios,
 * identificar cuellos de botella y debuggear problemas de performance.
 *
 * Exporters soportados:
 * - jaeger: Jaeger (recomendado para desarrollo)
 * - zipkin: Zipkin
 * - otlp: OpenTelemetry Protocol (Grafana Tempo, Honeycomb, etc.)
 * - null: Deshabilitado
 *
 * Instalación (si se usa el SDK de OpenTelemetry):
 *   composer require open-telemetry/sdk open-telemetry/exporter-otlp
 *
 * Headers de correlación:
 * - X-Trace-Id: ID único de la traza
 * - X-Span-Id: ID del span actual
 * - X-Parent-Span-Id: ID del span padre
 *
 * @see https://opentelemetry.io/docs/instrumentation/php/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Tracing Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable distributed tracing.
    |
    */

    'enabled' => env('TRACING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | The name of this service in traces. Appears in Jaeger/Zipkin UI.
    |
    */

    'service_name' => env('TRACING_SERVICE_NAME', env('APP_NAME', 'senselab-core-api')),

    /*
    |--------------------------------------------------------------------------
    | Service Version
    |--------------------------------------------------------------------------
    |
    | Version of the service for trace metadata.
    |
    */

    'service_version' => env('APP_VERSION', '5.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Environment tag for filtering traces.
    |
    */

    'environment' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Exporter Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the trace exporter (where traces are sent).
    |
    */

    'exporter' => env('TRACING_EXPORTER', 'null'),

    'exporters' => [
        'jaeger' => [
            'endpoint' => env('JAEGER_ENDPOINT', 'http://localhost:14268/api/traces'),
            'agent_host' => env('JAEGER_AGENT_HOST', 'localhost'),
            'agent_port' => env('JAEGER_AGENT_PORT', 6831),
        ],
        'zipkin' => [
            'endpoint' => env('ZIPKIN_ENDPOINT', 'http://localhost:9411/api/v2/spans'),
        ],
        'otlp' => [
            'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
            'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'), // http/protobuf, grpc
            'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling Configuration
    |--------------------------------------------------------------------------
    |
    | Controls what percentage of traces are recorded.
    | - always_on: Record all traces (development)
    | - always_off: Record no traces
    | - ratio: Record a percentage (production, e.g., 0.1 = 10%)
    |
    */

    'sampler' => env('TRACING_SAMPLER', 'always_off'),

    'sampling_ratio' => env('TRACING_SAMPLING_RATIO', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Auto-Instrumentation
    |--------------------------------------------------------------------------
    |
    | Enable/disable auto-instrumentation for various components.
    |
    */

    'auto_instrument' => [
        'http' => env('TRACING_INSTRUMENT_HTTP', true),
        'database' => env('TRACING_INSTRUMENT_DATABASE', true),
        'cache' => env('TRACING_INSTRUMENT_CACHE', true),
        'queue' => env('TRACING_INSTRUMENT_QUEUE', true),
        'redis' => env('TRACING_INSTRUMENT_REDIS', true),
        'guzzle' => env('TRACING_INSTRUMENT_GUZZLE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Propagation
    |--------------------------------------------------------------------------
    |
    | Headers used for context propagation between services.
    |
    */

    'propagation' => [
        'headers' => [
            'trace_id' => 'X-Trace-Id',
            'span_id' => 'X-Span-Id',
            'parent_span_id' => 'X-Parent-Span-Id',
            'sampled' => 'X-Trace-Sampled',
        ],
        'formats' => ['w3c', 'b3'], // W3C Trace Context, B3 (Zipkin)
    ],

    /*
    |--------------------------------------------------------------------------
    | Span Attributes
    |--------------------------------------------------------------------------
    |
    | Default attributes added to all spans.
    |
    */

    'default_attributes' => [
        'service.namespace' => 'senselab',
        'deployment.environment' => env('APP_ENV', 'production'),
        'host.name' => gethostname() ?: 'unknown',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Filtering
    |--------------------------------------------------------------------------
    |
    | Fields to redact from span attributes (security).
    |
    */

    'redact_fields' => [
        'password',
        'password_hash',
        'token',
        'api_key',
        'secret',
        'authorization',
        'cookie',
        'credit_card',
        'cedula',
        'identificacion',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for batching spans before export.
    |
    */

    'batch' => [
        'max_queue_size' => env('TRACING_BATCH_MAX_QUEUE_SIZE', 2048),
        'scheduled_delay_millis' => env('TRACING_BATCH_SCHEDULED_DELAY', 5000),
        'export_timeout_millis' => env('TRACING_BATCH_EXPORT_TIMEOUT', 30000),
        'max_export_batch_size' => env('TRACING_BATCH_MAX_EXPORT_SIZE', 512),
    ],
];
