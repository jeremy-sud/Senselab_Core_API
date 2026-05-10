<?php

/**
 * Configuración Granular de Auditoría Completa
 *
 * Define qué modelos auditar, qué eventos registrar y con qué nivel de detalle.
 * Soporta exclusión de campos sensibles y auditoría selectiva por roles.
 *
 * @package Senselab\Configuration
 * @version 1.0.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Activar/desactivar auditoría global de cambios.
    | Cambia a false para ambiente de desarrollo sin auditoría.
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Events to Audit
    |--------------------------------------------------------------------------
    |
    | Qué eventos registrar: created, updated, deleted, restored, etc.
    |
    */
    'events' => [
        'created' => true,     // Registrar creación de registros
        'updated' => true,     // Registrar actualización de registros
        'deleted' => true,     // Registrar eliminación de registros
        'restored' => true,    // Registrar restauración de registros (soft delete)
        'viewed' => false,     // Registrar visualización (muy verbose)
        'exported' => true,    // Registrar exportaciones de datos
        'imported' => true,    // Registrar importaciones de datos
    ],

    /*
    |--------------------------------------------------------------------------
    | Audited Models
    |--------------------------------------------------------------------------
    |
    | Modelos a auditar. array vacío = auditar todos modelos, false = no auditar este modelo
    | false = no auditar este modelo
    |
    */
    'models' => [
        // Modelos financieros críticos
        'App\Models\ComprobanteElectronicoFe' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\Pago' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\Venta' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],

        // Modelos de configuración crítica
        'App\Models\Usuario' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted', 'restored'],
            'exclude_fields' => ['password'], // Nunca auditar passwords
        ],
        'App\Models\Empresa' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\Rol' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\Permiso' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],

        // Modelos de integración externa
        'App\Models\HaciendaComprobante' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],

        // Otros modelos importantes
        'App\Models\Proveedor' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\Cliente' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\EntradaInventario' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
        'App\Models\SalidaInventario' => [
            'enabled' => true,
            'events' => ['created', 'updated', 'deleted'],
            'exclude_fields' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Campos nunca auditados (globalmente, además de por modelo)
    |
    */
    'globally_excluded_fields' => [
        'password',              // Passwords nunca en auditoría
        'remember_token',        // Tokens de sesión
        'api_token',             // API tokens
        'two_factor_secret',     // Secretos 2FA
        'backup_codes',          // Códigos de respaldo
        'updated_at',            // Cambios de timestamp no son significativos
        'remember_token_created_at',
        '_token',                // CSRF tokens
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Context
    |--------------------------------------------------------------------------
    |
    | Qué información de contexto capturar con cada auditoría
    |
    */
    'context' => [
        'capture_user_info' => true,      // ID, email, nombre del usuario
        'capture_ip_address' => true,     // IP del cliente
        'capture_user_agent' => true,     // User agent del navegador
        'capture_url' => true,            // URL/ruta de la solicitud
        'capture_http_method' => true,    // GET, POST, PUT, DELETE, PATCH
        'capture_route_action' => true,   // Controller@method
        'capture_empresa_context' => true, // empresa_id si aplica
        'capture_execution_time' => true, // Tiempo para ejecutar
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Changes Tracking
    |--------------------------------------------------------------------------
    |
    | Cómo registrar cambios de campos
    |
    */
    'changes' => [
        // Registrar valores anteriores y nuevos
        'track_changes' => true,

        // Registrar solo campos que cambiaron (no todos)
        'only_changed_fields' => true,

        // Ocultar valores sensibles de auditoría (mostrar solo *** u hash)
        'mask_sensitive_values' => true,
        'sensitive_patterns' => [
            'password',
            'token',
            'secret',
            'api_key',
            'credit_card',
            'ssn',
            'license',
            'bank_account',
        ],

        // Formato de valores sensibles enmascarados
        'masked_value' => '***MASKED***',

        // Tamaño máximo de campo en auditoría (para fields muy grandes)
        'max_field_size' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Levels
    |--------------------------------------------------------------------------
    |
    | Niveles de auditoría por rol/permiso
    | - 'full': auditar todo (cambios completos con valores anteriores y nuevos)
    | - 'sensitive': auditar solo cambios en campos sensibles
    | - 'none': no auditar
    |
    */
    'audit_levels' => [
        'super_admin' => 'full',              // Super admin: auditar todo con detalles completos
        'admin' => 'full',                    // Admin: auditar todo con detalles completos
        'finance_manager' => 'full',          // Finance: auditar todos cambios con detalles completos
        'employee' => 'sensitive',            // Empleado: auditar solo sensibles con valores enmascarados
        'guest' => 'none',                    // Invitado: no auditar ningún cambio
    ],

    /*
    |--------------------------------------------------------------------------
    | Deletion Handling
    |--------------------------------------------------------------------------
    |
    | Cómo manejar eliminaciones de registros
    |
    */
    'deletion' => [
        // Registrar soft deletes como 'deleted'
        'audit_soft_deletes' => true,

        // Registrar hard deletes como 'deleted' antes de eliminar
        'audit_hard_deletes' => true,

        // Permitir restauración trazando el evento 'restored'
        'audit_restores' => true,

        // Tiempo de retención de logs de auditoría eliminados (días)
        'deleted_logs_retention_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    |
    | Optimizaciones para auditoría a gran escala
    |
    */
    'performance' => [
        // Usar cola (queue) para grabar auditoría en background
        'use_queue' => false,
        'queue_name' => 'audit',

        // Batch inserts de auditoría para mejor rendimiento
        'batch_size' => 100,

        // TTL de caché de configuración (segundos)
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | Cuánto tiempo mantener logs de auditoría
    |
    */
    'retention' => [
        // Retener logs por N días
        'days' => env('AUDIT_RETENTION_DAYS', 365),

        // Archivar logs más antiguos a tabla separada (null = no archivar)
        'archive_after_days' => 90,
        'archive_table' => 'audit_logs_archived',

        // Purgar logs más antiguos de N días (null = no purgar)
        'purge_after_days' => 730,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Notifications
    |--------------------------------------------------------------------------
    |
    | Dónde registrar eventos de auditoría
    |
    */
    'logging' => [
        // Canal de log para eventos importantes de auditoría
        'channel' => 'audit',

        // Loguear auditoría de auditoría (cambios en tabla audit_logs)
        'log_audit_changes' => true,

        // Loguear accesos a datos auditados
        'log_access' => false,

        // Notificar admins de cambios críticos
        'notify_critical_changes' => true,
        'notification_channel' => 'slack',

        // Cambios críticos que requiero notificación
        'critical_models' => [
            'App\Models\Usuario',
            'App\Models\Permiso',
            'App\Models\HaciendaComprobante',
            'App\Models\Pago',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search & Reporting
    |--------------------------------------------------------------------------
    |
    | Capacidades de búsqueda y reportes
    |
    */
    'search' => [
        // Usar índice full-text para búsqueda rápida
        'enable_fulltext_search' => true,

        // Campos donde habilitar búsqueda full-text
        'fulltext_fields' => ['description', 'model_type'],

        // Cantidad máxima de resultados por búsqueda
        'max_results' => 1000,

        // Permitir exportación de auditoría
        'allow_export' => true,
        'export_formats' => ['csv', 'json', 'excel'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance & Legal
    |--------------------------------------------------------------------------
    |
    | Configuración para cumplimiento normativo
    |
    */
    'compliance' => [
        // GDPR: Permitir eliminación de datos personales del audit
        'support_gdpr_erasure' => true,

        // Auditoría de auditor: Quién accede a logs de auditoría
        'audit_audit_access' => true,

        // Country de regulación (para compliance local)
        'jurisdiction' => env('AUDIT_JURISDICTION', 'CR'),

        // Datos a enmascarar por privacidad
        'privacy_fields' => [
            'email',
            'phone',
            'document_number',
            'identification_number',
        ],
    ],
];
