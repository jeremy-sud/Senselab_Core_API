<?php

/**
 * Rate Limiting Configuration - FASE 1.5
 *
 * Define límites de throttling granulares para diferentes tipos de
 * usuarios (autenticados/guests) y endpoints (API/reportes/importaciones)
 */

return [
    /**
     * Límites por tipo de usuario
     */
    'users' => [
        'authenticated' => [
            'api' => 60,           // 60 requests/minuto para usuarios autenticados
            'reports' => 15,       // 15 reportes/minuto
            'imports' => 5,        // 5 importaciones/minuto
            'exports' => 10,       // 10 exportaciones/minuto
            'hacienda' => 30,      // 30 requests a Hacienda/minuto
            'payment_process' => 5, // 5 pagos/minuto (operación crítica)
            'login' => 5,          // 5 intentos login/minuto
        ],
        'guest' => [
            'api' => 30,           // 30 requests/minuto para guests
            'reports' => 5,        // 5 reportes/minuto (muy limitado)
            'imports' => 1,        // 1 importación/minuto
            'exports' => 2,        // 2 exportaciones/minuto
            'hacienda' => 10,      // 10 requests a Hacienda/minuto
            'payment_process' => 0, // No permitir sin autenticación
            'login' => 5,          // 5 intentos login/minuto
        ],
    ],

    /**
     * Ventanas de tiempo (en minutos)
     */
    'windows' => [
        'per_minute' => 1,
        'per_5minutes' => 5,
        'per_hour' => 60,
        'per_day' => 1440,
    ],

    /**
     * Endpoints específicos con overrides
     */
    'endpoints' => [
        // Endpoints de login (más restrictivo)
        '/api/*/auth/login' => [
            'limit' => 5,
            'window' => 'per_minute',
            'by' => 'ip', // Por IP, no por usuario
        ],
        '/api/*/auth/register' => [
            'limit' => 3,
            'window' => 'per_minute',
            'by' => 'ip',
        ],
        '/api/*/auth/forgot-password' => [
            'limit' => 3,
            'window' => 'per_5minutes',
            'by' => 'ip',
        ],

        // Endpoints de pagos (crítico)
        '/api/*/pagos' => [
            'authenticated_limit' => 5,
            'window' => 'per_minute',
            'by' => 'user',
        ],

        // Endpoints de reportes (pesados)
        '/api/*/reportes/*' => [
            'authenticated_limit' => 15,
            'guest_limit' => 5,
            'window' => 'per_minute',
            'by' => 'user',
        ],

        // Endpoints de importación (muy pesados)
        '/api/*/importar' => [
            'authenticated_limit' => 5,
            'guest_limit' => 1,
            'window' => 'per_hour', // Por hora, no por minuto
            'by' => 'user',
        ],

        // Hacienda API (servicio externo)
        '/api/*/hacienda/*' => [
            'authenticated_limit' => 30,
            'guest_limit' => 10,
            'window' => 'per_minute',
            'by' => 'user',
        ],
    ],

    /**
     * Mensajes de respuesta personalizados
     */
    'messages' => [
        'limit_exceeded' => 'Se ha excedido el límite de solicitudes. Intenta más tarde.',
        'retry_after' => 'Demasiadas solicitudes. Inténtalo en :seconds segundos.',
        'blocked_ip' => 'Tu dirección IP ha sido bloqueada temporalmente por demasiadas solicitudes.',
    ],

    /**
     * Configuración de bloqueo por IP
     */
    'ip_blocking' => [
        'enabled' => true,
        'threshold' => 10, // 10 violaciones antes de bloqueo
        'block_duration' => 3600, // Bloqueo por 1 hora
        'cleanup_interval' => 86400, // Limpiar registros cada 24 horas
    ],

    /**
     * Excepciones (IPs/usuarios que siempre pasan)
     */
    'exceptions' => [
        'ips' => [
            // Añadir IPs confiables aquí
            // '127.0.0.1',
            // '::1',
        ],
        'users' => [
            // Usuarios administrativos que no tienen límites
            // Puede ser un array de user IDs o roles
        ],
    ],

    /**
     * Monitoreo y alertas
     */
    'monitoring' => [
        'enabled' => true,
        'log_channel' => 'security', // Usar canal de seguridad (FASE 1.3)
        'alert_threshold' => 100, // Alertar si hay >100 violaciones en 1 minuto
        'track_by_endpoint' => true, // Rastrear límites por endpoint específico
    ],

    /**
     * Response headers personalizados
     */
    'headers' => [
        'remaining' => 'X-RateLimit-Remaining',
        'limit' => 'X-RateLimit-Limit',
        'reset' => 'X-RateLimit-Reset',
        'retry_after' => 'Retry-After',
    ],
];
