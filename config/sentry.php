<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send the events to. If this value is not
    | provided, the SDK will try to read it from the SENTRY_LARAVEL_DSN
    | environment variable. If that variable also does not exist, the SDK
    | will just not send any events.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Sentry Release
    |--------------------------------------------------------------------------
    |
    | Set the release version for Sentry. This allows you to track which
    | version of your application produced an error.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sentry Environment
    |--------------------------------------------------------------------------
    |
    | Set the environment where your application is running. This allows you
    | to separate errors from different environments like production,
    | staging, development, etc.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for error events. 1.0 means 100% of errors are sent.
    | Set to 0.0 to disable error tracking entirely.
    |
    */

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for performance monitoring (traces). 1.0 means 100%
    | of traces are sent. For production, use a lower value like 0.2 (20%).
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Profiles Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for profiling. This requires traces_sample_rate to be
    | greater than 0. For production, use a value like 0.1 (10%).
    |
    */

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this flag is enabled, certain personally identifiable information
    | is added by active integrations. Without this flag they are never
    | added to the event, to begin with.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Traces Propagation Targets
    |--------------------------------------------------------------------------
    |
    | Control which outgoing HTTP requests to attach tracing headers to.
    | For production, limit this to your own domains only.
    |
    */

    'traces_propagation_targets' => env('SENTRY_TRACES_PROPAGATION_TARGETS')
        ? explode(',', env('SENTRY_TRACES_PROPAGATION_TARGETS'))
        : null,

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure which breadcrumbs are captured. Breadcrumbs are used to
    | create a trail of events that happened before an error.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL', true),

        // Capture SQL bindings (can contain sensitive data)
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS', false),

        // Capture queue job information
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE', true),

        // Capture command information
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure which Sentry integrations should be loaded. Some are enabled
    | by default, some need to be explicitly enabled.
    |
    */

    'integrations' => [
        // Enable tracing integration
        'tracing' => env('SENTRY_TRACING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Exceptions
    |--------------------------------------------------------------------------
    |
    | List of exception classes that should not be reported to Sentry.
    |
    */

    'ignore_exceptions' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Transactions
    |--------------------------------------------------------------------------
    |
    | List of transaction patterns that should not be reported to Sentry.
    | Use glob patterns like asterisk-slash-health or slash-api-slash-internal-slash-asterisk
    |
    */

    'ignore_transactions' => [
        // '/health',
        // '/ping',
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before an event is sent to Sentry.
    | You can use this to modify the event or return null to prevent sending.
    |
    */

    'before_send' => null,

    /*
    |--------------------------------------------------------------------------
    | Before Send Transaction Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before a transaction is sent to Sentry.
    | You can use this to modify the transaction or return null to prevent
    | sending.
    |
    */

    'before_send_transaction' => null,

];
