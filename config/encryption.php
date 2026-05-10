<?php

/**
 * Configuración Granular de Encriptación de Datos
 *
 * Define campos sensibles por modelo que deben encriptarse automáticamente
 * en la base de datos. Usa mutadores transparentes para cifrar/descifrar.
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
    | Activar/desactivar encriptación global de datos sensibles.
    | Cambia a false para ambiente de desarrollo sin encriptación.
    |
    */
    'enabled' => env('DATA_ENCRYPTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cipher
    |--------------------------------------------------------------------------
    |
    | Algoritmo de encriptación. Usa el mismo que APP_CIPHER de Laravel.
    | Valores soportados: 'AES-128-CBC', 'AES-256-CBC'
    |
    */
    'cipher' => env('APP_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Encrypted Fields by Model
    |--------------------------------------------------------------------------
    |
    | Define qué campos de cada modelo deben encriptarse automáticamente.
    | Los campos listados se cifran antes de guardar y descifran al leer.
    |
    */
    'models' => [
        // Modelo Usuario - documentos y contactos
        'App\Models\Usuario' => [
            'document_number' => [
                'enabled' => true,
                'description' => 'Número de cédula/RUC - Información sensible',
                'algorithm' => 'AES-256-GCM', // Puede sobrescribir cipher global
            ],
            'phone' => [
                'enabled' => true,
                'description' => 'Número de teléfono personal',
            ],
            'personal_email' => [
                'enabled' => true,
                'description' => 'Email personal (distinto del corporativo)',
            ],
        ],

        // Modelo Empresa - datos legales
        'App\Models\Empresa' => [
            'identification_number' => [
                'enabled' => true,
                'description' => 'RUC/Cédula jurídica de la empresa',
            ],
            'phone' => [
                'enabled' => true,
                'description' => 'Teléfono principal de la empresa',
            ],
            'fax' => [
                'enabled' => true,
                'description' => 'Fax de la empresa',
            ],
            'bank_account_number' => [
                'enabled' => true,
                'description' => 'Número de cuenta bancaria de la empresa',
            ],
        ],

        // Modelo Proveedor - información bancaria y datos personales
        'App\Models\Proveedor' => [
            'identification_number' => [
                'enabled' => true,
                'description' => 'Identificación del proveedor',
            ],
            'phone' => [
                'enabled' => true,
                'description' => 'Telefono del proveedor',
            ],
            'bank_account_number' => [
                'enabled' => true,
                'description' => 'Cuenta bancaria para pagos',
            ],
            'contact_email' => [
                'enabled' => true,
                'description' => 'Email de contacto principal',
            ],
        ],

        // Modelo Cliente - información de contacto y pagos
        'App\Models\Cliente' => [
            'identification_number' => [
                'enabled' => true,
                'description' => 'Identificación del cliente',
            ],
            'phone' => [
                'enabled' => true,
                'description' => 'Teléfono del cliente',
            ],
            'billing_email' => [
                'enabled' => true,
                'description' => 'Email para facturación',
            ],
            'bank_account_number' => [
                'enabled' => false, // Clientes típicamente no almacenan sus cuentas
                'description' => 'Número de cuenta del cliente (si aplica)',
            ],
        ],

        // Modelo ComprobanteElectronicoFe - referencias de transacciones
        'App\Models\ComprobanteElectronicoFe' => [
            'reference_number' => [
                'enabled' => true,
                'description' => 'Número de referencia de pago/transacción',
            ],
            'internal_notes' => [
                'enabled' => true,
                'description' => 'Notas internas con información sensible',
            ],
        ],

        // Modelo Pago - información bancaria de transacciones
        'App\Models\Pago' => [
            'transaction_id' => [
                'enabled' => true,
                'description' => 'ID de transacción en gateway de pago',
            ],
            'source_account' => [
                'enabled' => true,
                'description' => 'Cuenta de origen del pago',
            ],
            'authorization_code' => [
                'enabled' => true,
                'description' => 'Código de autorización del pago',
            ],
        ],

        // Modelo EntradaInventario - seriales de productos
        'App\Models\EntradaInventario' => [
            'serial_number' => [
                'enabled' => true,
                'description' => 'Número serial del producto',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Options
    |--------------------------------------------------------------------------
    |
    | Opciones avanzadas de encriptación
    |
    */
    'options' => [
        // Hacer hash del campo antes de cifrar para búsquedas rápidas
        'use_hashed_lookup' => true,

        // Almacenar hash en columna separada (e.g., document_number_hash)
        'hash_column_suffix' => '_hash',

        // Algoritmo para el hash de búsqueda (no es seguridad, es búsqueda)
        'hash_algorithm' => 'sha256',

        // Reencriptar datos con nueva clave si APP_KEY cambia
        'support_key_rotation' => true,

        // Clave adicional para encriptación externa (nil = usar APP_KEY)
        'external_key' => env('DATA_ENCRYPTION_KEY', null),

        // Almacenar IV o nonce en cada campo encriptado
        'store_iv' => true,

        // Loguear accesos a campos encriptados (seguridad)
        'log_decryption_access' => true,
        'log_channel' => 'security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Decryptors
    |--------------------------------------------------------------------------
    |
    | Usuarios/roles que pueden descifrar automáticamente campos encriptados.
    | Otros usuarios/apis verán [ENCRYPTED] al intentar acceder.
    |
    */
    'trusted_decryptors' => [
        'roles' => ['super_admin', 'admin', 'finance_manager'],
        'permissions' => ['view_encrypted_data', 'export_financial_reports'],
        'ip_whitelist' => [
            '127.0.0.1',
            '::1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Audit
    |--------------------------------------------------------------------------
    |
    | Rastrear todas las operaciones de encriptación/desencriptación
    |
    */
    'audit' => [
        'enabled' => true,

        // Loguear cada acceso a campos encriptados
        'log_access' => true,

        // Loguear cada modificación de campos encriptados
        'log_modifications' => true,

        // Tabla para auditoria detallada (null = solo logs)
        'audit_table' => 'encryption_audit_logs',

        // Retener logs por N días
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    |
    | Optimizaciones de performance para encriptación a gran escala
    |
    */
    'performance' => [
        // Caché de campos descifrados en memoria (por request)
        'cache_decrypted' => true,

        // Tamaño máximo de caché (número de registros)
        'cache_max_size' => 1000,

        // TTL del caché en segundos (0 = por request)
        'cache_ttl' => 0,

        // Usar lazy evaluation para descifrar solo campos accedidos
        'lazy_decryption' => true,
    ],
];
