<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración API Hacienda Costa Rica - Facturación Electrónica v4.4
    |--------------------------------------------------------------------------
    |
    | Configuración para integración con el sistema de comprobantes electrónicos
    | del Ministerio de Hacienda de Costa Rica.
    |
    | Actualizado a versión 4.4 según DGT-R-000-2024
    |
    | Documentación oficial: https://www.hacienda.go.cr/docs/ComprobantesElectronicosAPI.html
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Versión del Esquema XML
    |--------------------------------------------------------------------------
    |
    | Versión actual del esquema de comprobantes electrónicos.
    | Actualizado a v4.4 según DGT-R-000-2024
    |
    */
    'version_esquema' => env('HACIENDA_VERSION_ESQUEMA', '4.4'),

    /*
    |--------------------------------------------------------------------------
    | Proveedor del Sistema
    |--------------------------------------------------------------------------
    |
    | Identificación del proveedor del sistema de facturación electrónica.
    | Este campo es OBLIGATORIO en la versión 4.4
    |
    */
    'proveedor_sistemas' => env('HACIENDA_PROVEEDOR_SISTEMAS', 'SISTEMA ERP'),

    /*
    |--------------------------------------------------------------------------
    | Configuración XAdES-EPES
    |--------------------------------------------------------------------------
    |
    | Configuración para la firma XAdES-EPES según Anexo 2 de DGT-R-000-2024
    |
    */
    'xades' => [
        // URL de la política de firma v4.4
        'policy_url' => env(
            'HACIENDA_XADES_POLICY_URL',
            'https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-000-2024.pdf'
        ),

        // Hash de la política de firma v4.4 (base64 de SHA256)
        'policy_hash' => env(
            'HACIENDA_XADES_POLICY_HASH',
            'NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ=='
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambiente de Ejecución
    |--------------------------------------------------------------------------
    |
    | Valores permitidos: 'sandbox' (ATV), 'production'
    |
    | - sandbox: Ambiente de pruebas (ATV - Ambiente de Validación y Test)
    | - production: Ambiente productivo
    |
    */
    'environment' => env('HACIENDA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | URLs de la API por Ambiente
    |--------------------------------------------------------------------------
    */
    'api_urls' => [
        'sandbox' => [
            'oauth' => env('HACIENDA_OAUTH_URL_SANDBOX', 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token'),
            'logout' => env('HACIENDA_LOGOUT_URL_SANDBOX', 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout'),
            'recepcion' => env('HACIENDA_API_URL_SANDBOX', 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1'),
        ],
        'production' => [
            'oauth' => env('HACIENDA_OAUTH_URL_PROD', 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token'),
            'logout' => env('HACIENDA_LOGOUT_URL_PROD', 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout'),
            'recepcion' => env('HACIENDA_API_URL_PROD', 'https://api.comprobanteselectronicos.go.cr/recepcion/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credenciales OAuth 2.0
    |--------------------------------------------------------------------------
    |
    | Credenciales para autenticación con el IdP de Hacienda.
    | Utiliza el flujo Resource Owner Password Credentials (grant_type=password).
    |
    | - client_id: 'api-stag' para Sandbox, 'api-prod' para Producción
    | - username: Usuario generado en la Oficina Virtual (OVi) > Tico Factura
    | - password: Contraseña generada en OVi (debe estar URL-encoded si contiene símbolos)
    | - client_secret y scope: No requeridos por Hacienda
    |
    */
    'oauth' => [
        'client_id' => env('HACIENDA_OAUTH_CLIENT_ID', 'api-stag'),
        'client_secret' => env('HACIENDA_OAUTH_CLIENT_SECRET', ''),
        'grant_type' => 'password',
        'username' => env('HACIENDA_OAUTH_USERNAME'),
        'password' => env('HACIENDA_OAUTH_PASSWORD'),
        'scope' => env('HACIENDA_OAUTH_SCOPE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificado Digital para Firma
    |--------------------------------------------------------------------------
    |
    | Configuración del certificado digital (.p12) para firmar los comprobantes.
    | El certificado debe estar en formato PKCS#12 (.p12 o .pfx)
    |
    */
    'certificate' => [
        // Ruta al archivo del certificado (storage/app/certificates/)
        'path' => env('HACIENDA_CERT_PATH', storage_path('app/certificates/certificate.p12')),
        
        // Contraseña del certificado (PIN)
        'password' => env('HACIENDA_CERT_PASSWORD'),
        
        // Validar que el certificado esté vigente
        'validate_expiry' => env('HACIENDA_CERT_VALIDATE_EXPIRY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Timeouts y Reintentos
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('HACIENDA_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('HACIENDA_HTTP_CONNECT_TIMEOUT', 10),
        'retry_times' => env('HACIENDA_HTTP_RETRY_TIMES', 3),
        'retry_delay' => env('HACIENDA_HTTP_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Límites de la API según documentación oficial:
    | - Burst: 20 req/seg durante 5 seg (max 100)
    | - Sostenido: 10 req/seg durante 120 seg (max 1200)
    |
    */
    'rate_limit' => [
        'enabled' => env('HACIENDA_RATE_LIMIT_ENABLED', true),
        'max_requests_per_second' => env('HACIENDA_RATE_LIMIT_MAX_PER_SEC', 8), // Margen de seguridad
        'max_requests_per_minute' => env('HACIENDA_RATE_LIMIT_MAX_PER_MIN', 480),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cache
    |--------------------------------------------------------------------------
    |
    | Cache para tokens OAuth y consultas repetitivas
    |
    */
    'cache' => [
        'enabled' => env('HACIENDA_CACHE_ENABLED', true),
        'token_ttl' => env('HACIENDA_CACHE_TOKEN_TTL', 3600), // 1 hora
        'response_ttl' => env('HACIENDA_CACHE_RESPONSE_TTL', 300), // 5 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | URL donde Hacienda enviará las respuestas de aceptación/rechazo
    |
    */
    'callback_url' => env('HACIENDA_CALLBACK_URL', config('app.url') . '/api/hacienda/callback'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('HACIENDA_LOG_ENABLED', true),
        'channel' => env('HACIENDA_LOG_CHANNEL', 'daily'),
        'level' => env('HACIENDA_LOG_LEVEL', 'info'),
        
        // Guardar XMLs enviados/recibidos (útil para debugging)
        'save_xml' => env('HACIENDA_LOG_SAVE_XML', true),
        'xml_path' => storage_path('logs/hacienda/xml'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados de Comprobantes
    |--------------------------------------------------------------------------
    |
    | Estados posibles según respuesta de Hacienda
    |
    */
    'estados' => [
        'pendiente' => 'pendiente',          // Generado pero no enviado
        'enviando' => 'enviando',            // En proceso de envío
        'recibido' => 'recibido',            // Recibido por Hacienda (201)
        'procesando' => 'procesando',        // Hacienda está procesando
        'aceptado' => 'aceptado',            // Aceptado por Hacienda
        'rechazado' => 'rechazado',          // Rechazado por Hacienda
        'error' => 'error',                  // Error en procesamiento
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Comprobante
    |--------------------------------------------------------------------------
    |
    | Códigos según Anexo 2 - Tipos de Documentos
    |
    */
    'tipos_comprobante' => [
        '01' => 'Factura Electrónica',
        '02' => 'Nota de Débito Electrónica',
        '03' => 'Nota de Crédito Electrónica',
        '04' => 'Tiquete Electrónico',
        '05' => 'Nota de Despacho',
        '06' => 'Contrato',
        '07' => 'Procedimiento',
        '08' => 'Comprobante de Compra',
        '09' => 'Factura de Exportación',
    ],

    /*
    |--------------------------------------------------------------------------
    | Situación del Comprobante
    |--------------------------------------------------------------------------
    |
    | Situación de emisión del comprobante
    |
    */
    'situaciones' => [
        '1' => 'Normal',
        '2' => 'Contingencia',
        '3' => 'Sin internet',
    ],

    /*
    |--------------------------------------------------------------------------
    | Medios de Pago
    |--------------------------------------------------------------------------
    */
    'medios_pago' => [
        '01' => 'Efectivo',
        '02' => 'Tarjeta',
        '03' => 'Cheque',
        '04' => 'Transferencia - depósito bancario',
        '05' => 'Recaudado por terceros',
        '99' => 'Otros',
    ],

    /*
    |--------------------------------------------------------------------------
    | Condiciones de Venta
    |--------------------------------------------------------------------------
    */
    'condiciones_venta' => [
        '01' => 'Contado',
        '02' => 'Crédito',
        '03' => 'Consignación',
        '04' => 'Apartado',
        '05' => 'Arrendamiento con opción de compra',
        '06' => 'Arrendamiento en función financiera',
        '99' => 'Otros',
    ],
];
