# Glosario Completo — Ursol CAST API

**Fecha de creación:** 1 de abril de 2026  
**Proyecto:** Ursol CAST API v4.1.0  
**Desarrollado por:** Sistemas Ursol S.A.

> Este glosario recopila y define **toda** la terminología utilizada en el sistema ERP Ursol CAST API: conceptos de dominio, nombres de clases, patrones de arquitectura, convenciones de base de datos, abreviaturas técnicas, y vocabulario específico de Costa Rica y facturación electrónica. Está pensado como referencia rápida y material de onboarding para cualquier persona que trabaje con el codebase.

---

## Índice

- [A — Términos generales y arquitectura](#a--términos-generales-y-arquitectura)
- [B — Base de datos y modelos](#b--base-de-datos-y-modelos)
- [C — Controladores, configuración y Costa Rica](#c--controladores-configuración-y-costa-rica)
- [D — DTOs, Docker y despliegue](#d--dtos-docker-y-despliegue)
- [E — Eventos, excepciones y encriptación](#e--eventos-excepciones-y-encriptación)
- [F — Facturación electrónica y Hacienda](#f--facturación-electrónica-y-hacienda)
- [G — GDPR, Gemini y glosario de IA](#g--gdpr-gemini-y-glosario-de-ia)
- [H — Hacienda, HTTP y helpers](#h--hacienda-http-y-helpers)
- [I — Inventario, IA e infraestructura](#i--inventario-ia-e-infraestructura)
- [J — Jobs y JSON](#j--jobs-y-json)
- [K — Kubernetes y k6](#k--kubernetes-y-k6)
- [L — Laravel, logging y listeners](#l--laravel-logging-y-listeners)
- [M — Modelos, migraciones y multi-tenancy](#m--modelos-migraciones-y-multi-tenancy)
- [N — Nómina, notificaciones y naming](#n--nómina-notificaciones-y-naming)
- [O — Observers, OAuth y OpenAPI](#o--observers-oauth-y-openapi)
- [P — Policies, permisos y patrones](#p--policies-permisos-y-patrones)
- [Q — Queue y quality assurance](#q--queue-y-quality-assurance)
- [R — RBAC, Redis, rutas y rate limiting](#r--rbac-redis-rutas-y-rate-limiting)
- [S — Servicios, seguridad y Sanctum](#s--servicios-seguridad-y-sanctum)
- [T — Testing, traits y transporte](#t--testing-traits-y-transporte)
- [U — Usuarios, unidades y utilidades](#u--usuarios-unidades-y-utilidades)
- [V — Ventas, versionado y validaciones](#v--ventas-versionado-y-validaciones)
- [W — Webhooks y workers](#w--webhooks-y-workers)
- [X — XML, XAdES y XDebug](#x--xml-xades-y-xdebug)

---

## A — Términos generales y arquitectura

### Activo (`activo`)
Campo booleano presente en la mayoría de los modelos. Indica si un registro está activo (`true`) o inactivo (`false`). Es diferente de la eliminación lógica (`eliminado`). Se filtra con el scope `HasActiveScope`.

### AES-256
Algoritmo de encriptación simétrica utilizado para proteger datos sensibles en la base de datos (certificados digitales, PINs, campos financieros críticos). Implementado a través de `EncryptionService`.

### AI (Inteligencia Artificial)
El módulo de IA de la API que contiene 10 servicios y 32+ endpoints. Usa Google Gemini (gratis) como proveedor principal con fallback a OpenAI. Incluye OCR, chatbot, predicciones, detección de anomalías, clasificación CABYS, credit scoring, generación de contenido, análisis financiero, optimización de rutas y recomendaciones.

### AIServiceInterface
Contrato (interfaz PHP) que define el API mínimo que debe cumplir cualquier servicio de IA. Facilita intercambiar proveedores (Gemini → OpenAI) sin cambiar la lógica de negocio.

### Almacén (`Almacen`)
Modelo Eloquent que representa una bodega o almacén físico dentro de una sucursal. Campos clave: `nombre`, `codigo`, `descripcion`, `ubicacion`, `responsable_id`, `es_principal`, `activo`. Relaciones: `belongsTo(Empresa, Sucursal)`, `hasMany(EntradaInventario, SalidaInventario, InventarioProducto)`.

### AnomalyDetectionService
Servicio de IA que detecta transacciones fraudulentas, errores contables y patrones sospechosos. Reporta una precisión del ~95%. Utiliza Gemini para analizar patrones en datos financieros.

### ApiResponse (Trait)
Trait que estandariza todas las respuestas JSON de la API. Formato envelope: `{success: bool, data?: mixed, message?: string, meta?: object, errors?: object, trace_id?: string}`. Métodos: `successResponse()`, `createdResponse()`, `paginatedResponse()`, `errorResponse()`, `deletedResponse()`.

### AppServiceProvider
Proveedor de servicios principal de Laravel. Configura rate limiting granular y registra event listeners de webhooks. Las policies y observers se registran en `AuthServiceProvider` y `ObserverServiceProvider` respectivamente.

### Artisan
CLI de Laravel. El proyecto define comandos custom como `make:erp-module` para generar módulos completos (modelo, controlador, servicio, DTO, factory, test, migración, policy) automáticamente.

### AsientoContable
Modelo Eloquent para asientos contables (journal entries). Campos clave: `numero_asiento`, `fecha_asiento`, `tipo_asiento`, `origen`, `documento_origen_id`, `concepto`, `total_debe`, `total_haber`, `estado`. Relación `hasMany(DetalleAsiento)`.

### AuditLog
Modelo que almacena el registro de auditoría del sistema. Cada acción (CRUD) queda registrada con: usuario, IP, user agent, datos anteriores y posteriores, timestamp.

### AuditoriaActividad
Modelo para el seguimiento detallado de actividades. Registra cambios antes/después, el usuario que los realizó, y metadatos de la petición HTTP.

### AuthController
Controlador de autenticación (`app/Http/Controllers/API/AuthController.php`). Endpoints: `POST /login`, `POST /logout`, `GET /user`. Usa Laravel Sanctum para tokens.

### AuthServiceProvider
Proveedor que registra las 80+ policies RBAC y el mapeo `Modelo → Policy` con `Gate::policy()`.

---

## B — Base de datos y modelos

### BasePolicy
Clase abstracta (`app/Policies/BasePolicy.php`) de la que extienden las 80+ policies. Implementa lógica compartida: `ownsResource()` (verifica empresa del modelo vs. usuario), `hasPermission()` (verifica permiso RBAC en BD). Métodos estándar: `viewAny`, `view`, `create`, `update`, `delete`.

### BaseService
Clase abstracta (`app/Services/BaseService.php`) que sirve como fundación para todos los servicios de negocio. Provee patrones comunes de CRUD, manejo de transacciones, y logging.

### BelongsToTenant (Trait)
Trait que aplica multi-tenancy automático a modelos Eloquent. Filtra automáticamente por `empresa_id`, auto-asigna el tenant en creación, y provee el `empresa()` relationship. Funciona leyendo tanto el usuario autenticado como el tenant actual.

### BusUnidad
Modelo del módulo de transporte que representa una unidad de bus. Campos: placa, número económico, capacidad, modelo, estado, etc.

### Boolean Columns
Las columnas booleanas en el proyecto siguen la convención de usar tipos `boolean` de MySQL. Las más comunes son: `activo` (registro activo), `eliminado` (soft delete custom), `es_principal` (marca registro principal), `controla_inventario` (si el producto maneja stock).

---

## C — Controladores, configuración y Costa Rica

### CABYS
*Catálogo de Bienes y Servicios*. Sistema de clasificación de la Dirección General Tributación (DGT) de Costa Rica. Cada producto/servicio debe clasificarse con un código CABYS para facturación electrónica. El `CabysClassifierService` automatiza esta clasificación con IA (98% precisión).

### Cache Tags
Estrategia de invalidación de cache usando Redis. Cada entrada se etiqueta con `empresa_{id}` (tenant) y un tag de recurso (`productos_index`, `ventas_index`, etc.). Permite invalidar selectivamente el cache por tenant o por tipo de recurso.

### Caja
Modelo que representa una caja registradora o punto de cobro en una sucursal. Permite llevar el control de apertura, cierre y cuadre de caja.

### CajaChica
Modelo para fondos de caja chica (petty cash). Campos: `monto_inicial`, `monto_actual`, `responsable_id`, `empresa_id`. Tiene relación `hasMany(MovimientoCajaChica)`.

### Cantón
Subdivisión administrativa de Costa Rica. Cada provincia tiene múltiples cantones. Se usa en direcciones de clientes, proveedores y empresas. Ejemplo: San José (provincia) → Curridabat (cantón).

### Cargo
Modelo que representa un puesto o cargo laboral (ej. "Gerente de Ventas", "Contador"). Se usa en la nómina y en la estructura organizacional.

### CategoriaProducto
Modelo para categorías de productos (ej. "Alimentos", "Electrónicos", "Servicios"). Permite clasificar el catálogo de productos.

### CCSS (Caja Costarricense de Seguro Social)
Institución de seguridad social de Costa Rica. El módulo de nómina genera la planilla (`PlanillaCcss`) para la declaración mensual a la CCSS.

### CI/CD
Continuous Integration / Continuous Deployment. El pipeline usa GitHub Actions con workflows para: tests PHPUnit, análisis PHPStan, mutation testing (Infection), y cobertura de código (Codecov). Targets del Makefile: `ci-test`, `ci-quality`, `ci-security`, `deploy-staging`, `deploy-prod`.

### ClaveNumericaGenerator
Servicio de Hacienda que genera la clave numérica de 50 caracteres para comprobantes electrónicos según normas DGT. Estructura: país (506) + fecha + emisor + consecutivo + situación + código seguridad.

### Cliente
Modelo Eloquent para clientes. Campos clave: `tipo_identificacion`, `numero_identificacion`, `nombre`, `apellidos`, `email`, `telefono`, `provincia`, `canton`, `distrito`, `limite_credito`, `plazo_credito_dias`, `activo`. Pertenece a una empresa (tenant).

### Comprobante Electrónico (`ComprobanteElectronicoFe`)
Modelo central del módulo de facturación electrónica. Representa un documento electrónico según normativa DGT v4.4. Tipos de documento: `01` (Factura electrónica), `02` (Nota de débito), `03` (Nota de crédito), `04` (Tiquete electrónico). Campos: `clave`, `consecutivo`, `fecha_emision`, `estado`, `xml_original`, `xml_firmado`, `respuesta_hacienda_xml`.

### ComprobanteRecibidoElectronico
Modelo para comprobantes electrónicos recibidos de proveedores. Permite registrar facturas de compra electrónicas.

### Condición de Venta
Campo en comprobantes electrónicos que indica la modalidad de pago: `01` (Contado), `02` (Crédito), `03` (Consignación), `04` (Apartado), `05` (Arrendamiento), `99` (Otros).

### config/
Directorio de configuración de Laravel. Archivos clave:
- `app.php` — Configuración general de la aplicación
- `hacienda.php` — Facturación electrónica y endpoints de Hacienda
- `multitenancy.php` — Multi-tenancy (Spatie)
- `gemini.php` — Google Gemini API
- `openai.php` — OpenAI API
- `rate-limiting.php` — Límites de tasa granulares
- `sanctum.php` — Tokens de autenticación
- `cors.php` — CORS headers
- `encryption.php` — Encriptación de campos

### Consecutivo (`ConsecutivoFe`)
Modelo que gestiona la numeración secuencial de comprobantes electrónicos. Cada tipo de documento tiene su propia secuencia dentro de cada empresa. Formato: 20 dígitos según normativa DGT.

### ConsecutivoFeService
Servicio que genera y asigna consecutivos de facturación electrónica. Es thread-safe y usa bloqueo pesimista para evitar duplicados.

### Controlador (Controller)
Los controladores están organizados en `app/Http/Controllers/API/` (77 controladores), `app/Http/Controllers/Api/` (5 controladores Hacienda + AI), y `app/Http/Controllers/` (6 controladores raíz). Total: 93 controladores.

### CORS (Cross-Origin Resource Sharing)
Configurado en `config/cors.php`. Permite solicitudes desde dominios externos definidos, con los headers y métodos HTTP apropiados.

### CrIdentificacion (Rule)
Regla de validación custom (`app/Rules/CrIdentificacion.php`) para identificaciones costarricenses. Valida:
- **Cédula física**: 9 dígitos
- **Cédula jurídica**: 10 dígitos, inicia con `3`
- **DIMEX**: 11–12 dígitos
- **NITE**: 10 dígitos

### CreditScoringService
Servicio de IA que evalúa el riesgo crediticio de clientes en una escala 0–100 basándose en historial de pagos, montos, frecuencia de compras, y comportamiento general.

### CrTelefono (Rule)
Regla de validación custom para teléfonos costarricenses. Patrón: `/^[2-8]\d{7}$/`. Acepta prefijo `+506` y diversos separadores.

### CuentaBancaria
Modelo para cuentas bancarias de la empresa. Permite registrar cuentas en diferentes bancos para conciliación bancaria.

### CuentaContable
Modelo del plan de cuentas contables. Campos: `codigo`, `nombre`, `descripcion`, `tipo_cuenta_id`, `cuenta_padre_id`, `permite_movimientos`, `saldo_actual`, `activo`. Implementa estructura jerárquica (padre-hijo) para el catálogo contable.

### CuentaPorCobrar
Modelo para cuentas por cobrar (Accounts Receivable). Registra deudas de clientes pendientes de cobro. Relación con `Venta` y `Cliente`.

### CuentaPorPagar
Modelo para cuentas por pagar (Accounts Payable). Registra deudas con proveedores pendientes de pago. Relación con `OrdenCompra` y `Proveedor`.

---

## D — DTOs, Docker y despliegue

### DTO (Data Transfer Object)
Objeto inmutable que transporta datos entre capas del sistema (request → servicio → modelo). El proyecto tiene 60 DTOs que cubren ~65% de las operaciones. Convención de nombres: `{Modelo}CreateDTO`, `{Modelo}UpdateDTO`. Se crean desde requests vía `::fromRequest($request)`.

### DeduccionLegal
Modelo para deducciones legales en nómina: impuesto sobre la renta, cuota CCSS obrera, cuota CCSS patronal, INS, Banco Popular, etc.

### Departamento
Modelo de la estructura organizacional. Cada empleado pertenece a un departamento (ej. "Contabilidad", "Ventas", "IT").

### DetalleAsiento
Líneas de un asiento contable. Cada línea tiene: `cuenta_contable_id`, `debe`, `haber`, `referencia`. La suma de `debe` debe igualar la suma de `haber` (principio de partida doble).

### DetalleEntradaInventario
Líneas de una entrada de inventario. Cada línea: `producto_id`, `cantidad`, `costo_unitario`, `almacen_destino`.

### DetalleOrdenCompra
Líneas de una orden de compra. Cada línea: `producto_id`, `cantidad`, `precio_unitario`, `descuento`, `subtotal`.

### DetalleSalidaInventario
Líneas de una salida de inventario. Cada línea: `producto_id`, `cantidad`, `costo_unitario`, `motivo`.

### DetalleVenta
Líneas de una venta. Cada línea: `producto_id`, `cantidad`, `precio_unitario`, `descuento`, `impuesto`, `subtotal`.

### DGT (Dirección General de Tributación)
Autoridad tributaria de Costa Rica. Define las normativas de facturación electrónica (actualmente v4.4 — DGT-R-000-2024). La API implementa integración completa con los servicios de DGT/Hacienda.

### Distrito
Subdivisión administrativa de Costa Rica dentro de un cantón. Nivel más granular de dirección: Provincia → Cantón → Distrito.

### Docker
Plataforma de contenedores usada para desarrollo y despliegue. El `docker-compose.yml` define 7 servicios:
- **nginx** — Servidor web (puerto 8000)
- **php** — PHP-FPM 8.4 con la aplicación Laravel
- **mysql** — MySQL 8.0 (puerto 3306)
- **redis** — Redis 7 Alpine (puerto 6379)
- **phpmyadmin** — Administración de BD (puerto 8080, profile `tools`)
- **mailhog** — Testing de emails (puertos 1025/8025, profile `development`)
- **queue** — Worker de colas (profile `production`)
- **scheduler** — Tareas programadas (profile `production`)

### Dockerfile
Build multi-stage:
1. **Etapa composer**: Instala dependencias PHP sobre imagen Alpine
2. **Etapa final**: PHP 8.4-FPM Alpine con extensiones: `bcmath`, `gd`, `intl`, `mbstring`, `pdo_mysql`, `pdo_pgsql`, `pcntl`, `zip`, `redis`. XDebug opcional via `INSTALL_XDEBUG=true`.

### DomainException
Clase base de excepciones de dominio (`app/Exceptions/DomainException.php`). Las excepciones específicas extienden esta clase: `HaciendaException`, `InventarioException`, `VentaException`, `ContabilidadException`, `CompraException`, `NominaException`, `MultiTenancyException`, `FacturacionElectronicaException`, `AIServiceException`.

---

## E — Eventos, excepciones y encriptación

### Eliminado (`eliminado`)
Campo booleano para soft delete custom. `false` = registro visible, `true` = registro eliminado lógicamente. Implementado por el trait `HasCustomSoftDeletes`. A diferencia del soft delete estándar de Laravel (`deleted_at`), este proyecto usa un booleano por compatibilidad con el esquema de BD.

### Eloquent
ORM (Object-Relational Mapping) de Laravel. El proyecto tiene 95 modelos Eloquent que mapean las tablas de MySQL.

### Empleado
Modelo para empleados. Campos: `nombre`, `primer_apellido`, `segundo_apellido`, `tipo_documento`, `numero_documento`, `fecha_nacimiento`, `fecha_ingreso`, `cargo_id`, `departamento_id`, `salario`, `email`, `telefono`. Relaciones: `belongsTo(Empresa, Cargo, Departamento)`, `hasMany(PagoNomina, NominaEmpleado)`.

### EncryptionService
Servicio centralizado de encriptación/desencriptación con soporte para rotación de llaves. Usa AES-256-CBC. Los campos encriptados son buscables a través de hashes.

### EncryptsAttributes (Trait) — DEPRECATED
Trait antiguo para encriptación de atributos. Reemplazado por `HasEncryptedAttributes` que usa `EncryptionService`.

### EntradaInventario
Modelo para registrar entradas de inventario (compras, devoluciones, ajustes positivos). Campos: `almacen_id`, `documento_referencia`, `monto_total`, `usuario_id`. Relación: `hasMany(DetalleEntradaInventario)`.

### Empresa
Modelo central de multi-tenancy. Extiende `Spatie\Multitenancy\Models\Tenant`. Campos: `nombre`, `nombre_comercial`, `razon_social`, `num_identificacion_dgt`, `tipo_identificacion`, `actividad_economica_principal`, `proveedor_sistemas`, `certificado_llave_fe`, `pin_llave_fe_hash`, `subdominio`, `regimen_tributario_id`, `activo`. Toda la data del sistema se filtra por `empresa_id`.

### Envelope (respuesta)
Formato estándar de respuesta JSON definido por el trait `ApiResponse`:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa",
  "meta": { "current_page": 1, "total": 50 },
  "trace_id": "abc-123-def"
}
```

### ErrorResponseDTO
DTO para respuestas de error estandarizadas. Incluye: `success: false`, `message`, `errors` (mapa campo → mensajes), `trace_id`.

### Etiqueta
Modelo para un sistema de etiquetas (tags) flexible. Se puede asociar a cualquier entidad mediante `EntidadEtiqueta` (tabla pivote polimórfica).

### Evento (Event)
Los eventos del sistema se despachan cuando ocurren acciones de dominio relevantes:
- `ClienteCreadoEvent` — Cliente nuevo
- `VentaCreadaEvent` — Venta nueva
- `FacturaEmitidaEvent` — Factura electrónica emitida
- `PagoRecibidoEvent` — Pago recibido
- `InventarioBajoEvent` — Inventario bajo mínimo
- `WebhookEvent` — Evento para despachar webhooks

---

## F — Facturación electrónica y Hacienda

### Factura Electrónica
Documento tributario electrónico válido para Hacienda de Costa Rica. Tipo `01`. Debe firmarse digitalmente (XAdES-EPES) y enviarse al API de recepción de Hacienda. Contiene: emisor, receptor, líneas de detalle, impuestos, totales, clave numérica de 50 caracteres.

### FacturaEmitidaEvent
Evento de dominio disparado cuando se emite un comprobante electrónico. Los listeners pueden enviar webhooks, notificaciones, o registrar en auditoría.

### FeCertificadoDigital
Modelo para certificados digitales de firma electrónica. Campos: `empresa_id`, `certificado` (encriptado), `pin_hash`, `fecha_vencimiento`. Cada empresa tiene su propio certificado emitido por una CA autorizada por el BCCR.

### FeLineaDetalle
Modelo para las líneas de detalle dentro de un comprobante electrónico. Contiene: código CABYS, descripción, cantidad, unidad de medida, precio unitario, descuentos, impuestos, total línea.

### FeOAuthToken
Modelo para tokens OAuth 2.0 del API de Hacienda. Se almacenan encriptados y se renuevan automáticamente vía `OAuthTokenManager`.

### FirmaDigitalService
Servicio para gestionar la firma digital de documentos XML. Coordina con `XadesEpesSigner` para aplicar la firma XAdES-EPES al comprobante.

### FormaPago
Modelo catálogo para formas de pago: `01` (Efectivo), `02` (Tarjeta), `03` (Cheque), `04` (Transferencia), `05` (Recaudado por terceros), `99` (Otros). Definido según normativa DGT.

### FormRequest
Clase de validación de Laravel. El proyecto tiene 170+ FormRequests que validan datos de entrada con reglas específicas por operación. Convención: `Store{Modelo}Request`, `Update{Modelo}Request`.

---

## G — GDPR, Gemini y glosario de IA

### GDPR (General Data Protection Regulation)
El proyecto implementa soporte básico para cumplimiento GDPR:
- `DataRetentionPolicy` — Modelo para políticas de retención de datos
- `GdprDeletionRequest` — Modelo para solicitudes de eliminación de datos
- Rutas definidas en `routes/api/compliance.php`

### GeminiService
Servicio de integración con Google Gemini API (tier gratuito). Modelos disponibles:
- `gemini-2.0-flash` — Chat (15 RPM)
- `gemini-1.5-flash` — Vision/OCR (15 RPM)
- `gemini-1.5-pro` — Tareas complejas (2 RPM)
Configuración de generación: `temperature: 0.7`, `top_p: 0.95`, `top_k: 40`, `max_output_tokens: 2048`.

### GdprDeletionRequest
Modelo para gestionar solicitudes de eliminación de datos personales según GDPR. Incluye estado de la solicitud, datos del solicitante, fechas de procesamiento.

---

## H — Hacienda, HTTP y helpers

### Hacienda
*Ministerio de Hacienda de Costa Rica*. El sistema se integra con el API de recepción de comprobantes electrónicos. Ambientes:
- **Sandbox (ATV)**: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token`
- **Producción**: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token`

### HaciendaApiClient
Servicio HTTP para comunicarse con el API de Hacienda. Implementa OAuth 2.0, rate limiting automático, reintentos con backoff exponencial, y logging de cada transacción.

### HaciendaComprobante
Modelo para almacenar la relación entre comprobantes locales y su estado en Hacienda.

### HaciendaIntegrationService
Servicio de orquestación de alto nivel para el flujo completo de facturación electrónica: crear comprobante → firmar XML → enviar a Hacienda → consultar estado → procesar respuesta.

### HasActiveScope (Trait)
Trait que provee query scopes para filtrar por campo `activo`:
- `scopeActivo()` — Solo registros activos
- `scopeInactivo()` — Solo registros inactivos
- `scopeConActivo()` — Todos (activos e inactivos)

### HasAuditFields (Trait)
Trait que auto-registra campos de auditoría: `created_by`, `updated_by`, `deleted_by`. Además crea entradas en `auditoria_actividades` con IP, user agent, y datos antes/después.

### HasAuditableEvents (Trait)
Trait para logging automático de eventos de modelo (created, updated, deleted). Registra en el servicio de auditoría.

### HasCacheableQueries (Trait)
Trait para controladores que implementa caching automático de queries. Requiere definir `$cacheTags` y `$cacheTTL`. Auto-genera prefijos, y es tenant-aware usando `empresa_{id}`.

### HasCustomSoftDeletes (Trait)
Trait que implementa soft delete usando el campo booleano `eliminado` en vez del `deleted_at` estándar de Laravel. Métodos: `forceDelete()`, `restore()`, `trashed()`.

### HasEmpresaContext (Trait)
Trait central de multi-tenancy para controladores. Métodos:
- `getEmpresaId(): ?int` — Obtiene la empresa del usuario autenticado o del tenant actual
- `scopeEmpresa(Builder $query, ?int $empresaId): Builder` — Filtra un query por empresa
- `assertEmpresa(Model $model, ?int $empresaId): void` — Verifica que un modelo pertenezca a la empresa; lanza 403 si no
- `resolveEmpresaOrFail(?int $requested): int` — Valida que el empresa_id solicitado coincida con el tenant activo

### HasEncryptedAttributes (Trait)
Trait moderno para encriptación automática de campos de modelo. Usa `EncryptionService` con AES-256. Soporta rotación de llaves y búsqueda por hash.

### HasPermissionCache (Trait)
Trait para modelos `Usuario` y `Rol` que cachea permisos en Redis. TTL: 1 hora. Llaves con prefijo de tenant para aislamiento.

### HasSafeErrorHandling (Trait)
Trait para controladores que maneja errores de forma segura. En producción oculta detalles de excepciones; en desarrollo los expone. Incluye logging estructurado con `trace_id`.

### HeaderSubdomainTenantFinder
Finder personalizado de multi-tenancy (`app/Multitenancy/TenantFinder/HeaderSubdomainTenantFinder.php`). Resuelve la empresa (tenant) por:
1. Headers: `X-Empresa-Id`, `X-Tenant-Id`, `X-Tenant`
2. Subdominio: `{subdominio}.api.ursol.com`

### Health Check
Endpoint de observabilidad (`/up` y `/api/health`) que verifica el estado de la aplicación, base de datos, Redis y otros servicios.

### HorarioRuta
Modelo del módulo de transporte. Define los horarios de una ruta de bus.

---

## I — Inventario, IA e infraestructura

### Infection PHP
Herramienta de mutation testing. Modifica el código fuente de forma controlada (mutaciones) y verifica que los tests detecten los cambios. Meta del proyecto: MSI (Mutation Score Indicator) ≥ 50%.

### Ingress (Kubernetes)
Recurso de Kubernetes que gestiona el acceso HTTP/HTTPS externo al cluster. Configurado con TLS automático vía cert-manager y nginx-ingress.

### InventarioBajoEvent
Evento disparado automáticamente cuando el stock de un producto cae por debajo de su `stock_minimo`. Activa notificaciones y posibles webhooks.

### InventarioProducto
Tabla pivote que relaciona productos con almacenes y mantiene el stock. Campos: `almacen_id`, `producto_id`, `stock_actual`, `costo_promedio`, `stock_minimo`, `stock_maximo`, `ubicacion`.

### InventarioException
Excepción de dominio para errores del módulo de inventario (stock insuficiente, almacén inactivo, etc.).

### InventarioService
Servicio de negocio para gestión de inventario: consultas de stock, movimientos, alertas de mínimo, cálculo de costo promedio.

---

## J — Jobs y JSON

### Jobs (Queued)
Trabajos asincrónicos procesados por Redis queue workers. El proyecto define 10 jobs:

**Generales:**
- `CleanCacheJob` — Limpieza periódica de cache
- `DeliverWebhookJob` — Entrega HTTP de webhooks
- `GeneratePdfReportJob` — Generación de reportes PDF
- `SendEmailJob` — Envío de correos electrónicos
- `ProcessImportJob` — Importación masiva de datos

**Hacienda:**
- `EnviarComprobanteJob` — Envío de comprobantes a Hacienda
- `ConsultarEstadoComprobanteJob` — Consulta de estado en Hacienda
- `ProcesarRespuestaHaciendaJob` — Procesamiento de respuestas de Hacienda

### JSON
Formato principal de comunicación de la API. Todas las respuestas siguen el envelope estandarizado del trait `ApiResponse`. Los headers requeridos son: `Accept: application/json`, `Content-Type: application/json`.

---

## K — Kubernetes y k6

### k6
Herramienta de load testing usada para pruebas de rendimiento. Los scripts están en `tests/Load/`:
- `smoke-test.js` — 1 VU (virtual user), 30 segundos
- `load-ventas-facturacion.js` — 10 VUs, 2 minutos
- `load-n1-detection.js` — Detección de degradación por N+1 queries

### Kubernetes
Plataforma de orquestación de contenedores. La configuración usa Kustomize con overlays para desarrollo y producción:
- **base/** — Namespace, ConfigMap, Secret, Deployment, Service, Ingress, PVC, StatefulSets, HPA, Jobs
- **overlays/development/** — 1 réplica, recursos reducidos
- **overlays/production/** — 3+ réplicas, HPA (2-20 pods), SSL/TLS

### Kustomize
Herramienta de gestión de manifests de Kubernetes. Permite aplicar capas (overlays) sobre una base común sin duplicar YAML.

---

## L — Laravel, logging y listeners

### L5-Swagger
Paquete (`darkaonline/l5-swagger`) que genera documentación OpenAPI/Swagger automáticamente desde atributos PHP (`#[OA\Get(...)]`, `#[OA\Post(...)]`, etc.) en los controladores. Accesible en `/api/documentation`.

### Laravel
Framework PHP (v12.x) sobre el que está construido el proyecto. Componentes de Laravel utilizados: Eloquent ORM, Sanctum, Queue, Events, Policies, FormRequests, Resources, Middleware, Artisan, Service Providers.

### Laravel Sanctum
Sistema de autenticación por tokens API. Cada usuario tiene uno o más tokens personales. Soporta: creación, revocación, expiración. Middleware: `auth:sanctum`.

### Listener
Los listeners escuchan eventos y ejecutan acciones:
- `DispatchWebhookListener` — Escucha `WebhookEvent` y despacha el `DeliverWebhookJob`

### LogAccesoSistema
Modelo que registra cada acceso al sistema: usuario, IP, user agent, timestamp, resultado (éxito/fallo).

### Logging
El sistema usa logging estructurado con contexto. Cada log entry incluye: `trace_id`, `user_id`, `empresa_id`, `method`, `url`, `ip`. Integración con Sentry para errores en producción.

---

## M — Modelos, migraciones y multi-tenancy

### Makefile
Archivo de automatización con targets para Docker, testing, despliegue y CI/CD:
- `make build` / `make up` / `make down` / `make restart` — Docker
- `make test` / `make test-coverage` — Testing
- `make ci-test` / `make ci-quality` / `make ci-security` — CI
- `make deploy-staging` / `make deploy-prod` / `make rollback` — Despliegue
- `make shell` / `make shell-mysql` / `make shell-redis` — Acceso a contenedores
- `make logs` / `make logs-php` / `make logs-mysql` — Ver logs

### Marca
Modelo catálogo para marcas de productos (ej. "Samsung", "Apple", "Genérico").

### MasterDataSeeder
Seeder de producción que pobla 14 catálogos esenciales (formas de pago, tipos de impuesto, unidades de medida, etc.) con datos idempotentes usando `updateOrInsert()`.

### DemoDataSeeder
Seeder de demostración que crea una empresa demo, usuarios demo (admin, gerente, contador, vendedor), productos de ejemplo, y clientes ficticios. Solo para ambientes de desarrollo.

### MensajeHacienda
Modelo para almacenar mensajes del API de Hacienda. Cada respuesta de Hacienda (aceptación, rechazo, error) se registra con su XML, código, y detalle.

### MensajeHaciendaService
Servicio que procesa y almacena las respuestas del API de Hacienda, interpretando códigos de respuesta y actualizando el estado del comprobante.

### Medio de Pago
Campo en comprobantes electrónicos: `01` (Efectivo), `02` (Tarjeta), `03` (Cheque), `04` (Transferencia — depósito bancario), `05` (Recaudado por terceros), `99` (Otros).

### Migración (Migration)
El proyecto tiene 100 migraciones que definen el esquema de la base de datos. Convenciones:
- Nombres: `YYYY_MM_DD_HHMMSS_create_{tabla}_table.php`
- Timestamps: `creado_en` / `actualizado_en` (en español)
- Soft delete: campo `eliminado` (boolean)
- Tenant: campo `empresa_id` con foreign key

### ModeloBus
Modelo catálogo del módulo de transporte. Define modelos/tipos de buses (ej. "Mercedes-Benz OH 1628", "Hyundai County").

### Moneda
La API soporta múltiples monedas. La moneda por defecto se configura en la empresa (`moneda_defecto`). La moneda base de Costa Rica es el Colón (CRC). El modelo `TipoCambioHistorial` almacena tasas de cambio históricas.

### MovimientoBancario
Modelo para transacciones bancarias: depósitos, retiros, transferencias, comisiones. Se usa para conciliación bancaria.

### MovimientoCajaChica
Modelo para movimientos de caja chica: ingresos y egresos pequeños. Campos: tipo, monto, descripción, fecha, responsable.

### MovimientoPresupuesto
Modelo para ajustes de presupuesto: aumentos, reducciones, transferencias entre partidas.

### Multi-tenancy
Arquitectura donde múltiples empresas (tenants) comparten la misma instancia de aplicación pero con datos completamente aislados. Implementado con `spatie/laravel-multitenancy`:
- Modelo tenant: `Empresa`
- Finder: `HeaderSubdomainTenantFinder`
- Headers: `X-Empresa-Id`, `X-Tenant-Id`, `X-Tenant`
- Scope automático: `BelongsToTenant` trait
- Control manual: `HasEmpresaContext` trait
- Jobs: tenant-aware por defecto

---

## N — Nómina, notificaciones y naming

### Naming Conventions (Convenciones de nombres)
El proyecto sigue convenciones consistentes:

**Columnas de BD:**
- Timestamps: `creado_en`, `actualizado_en` (español)
- Booleans: `activo`, `eliminado`, `es_principal`, `controla_inventario`
- Foreign keys: `{modelo}_id` (ej. `cliente_id`, `empresa_id`)
- Montos: `monto_*`, `subtotal_*`, `total_*`
- Fechas: `fecha_*` (ej. `fecha_venta`, `fecha_ingreso`)
- Estados: `estado_*` (ej. `estado_venta`, `estado_hacienda`)
- Porcentajes: `*_porcentaje`, `tasa_*`

**Archivos PHP:**
- Controllers: `{Modelo}Controller.php`
- Services: `{Modelo}Service.php`
- DTOs: `{Modelo}CreateDTO.php` / `{Modelo}UpdateDTO.php`
- Requests: `Store{Modelo}Request.php` / `Update{Modelo}Request.php`
- Resources: `{Modelo}Resource.php`
- Policies: `{Modelo}Policy.php`
- Factories: `{Modelo}Factory.php`
- Tests: `{Modelo}Test.php` (Feature), `{Modelo}ServiceTest.php` (Unit)

**Permisos RBAC:** `{módulo}.{acción}` — ej. `productos.ver`, `ventas.crear`, `inventario.eliminar`

### NominaEmpleado
Modelo que registra el cálculo detallado de la nómina de un empleado para un período específico: salario base, horas extra, deducciones, aportes patronales, neto a pagar.

### Notificación (`Notificacion`)
Modelo para notificaciones internas del sistema. Se pueden generar automáticamente por eventos (inventario bajo, pago recibido, etc.) y se consultan por usuario.

### NotificacionService
Servicio para crear, enviar y gestionar notificaciones. Soporta notificaciones push, email y en-app.

### Nota de Crédito
Tipo de comprobante electrónico `03`. Se emite para anular parcial o totalmente una factura previa. Debe referenciar la clave de la factura original.

### Nota de Débito
Tipo de comprobante electrónico `02`. Se emite para incrementar el monto de una factura previamente emitida.

---

## O — Observers, OAuth y OpenAPI

### OAuth 2.0
Protocolo de autenticación usado para comunicarse con el API de Hacienda. El `OAuthTokenManager` gestiona el ciclo de vida de los tokens: obtención, almacenamiento (encriptado), refresh, y revocación.

### OAuthTokenManager
Servicio de Hacienda que gestiona tokens OAuth. Almacena tokens en el modelo `FeOAuthToken`, los renueva automáticamente antes de expirar, y maneja errores de autenticación.

### Observer
Patrón que escucha eventos del ciclo de vida de modelos Eloquent (`creating`, `created`, `updating`, `updated`, `deleting`, `deleted`). Observers activos:
- `AuditObserver` — Logging de auditoría global
- `ProductoObserver` — Invalidación de cache al cambiar productos
- `PermisoObserver` — Limpieza de cache de permisos
- `RolObserver` — Limpieza de cache de roles

### ObserverServiceProvider
Proveedor que registra todos los observers de modelos. Los observers vacíos (`AsientoContableObserver`, `ClienteObserver`, `VentaObserver`) fueron eliminados en FASE 19.7.

### OpenAI
Proveedor secundario de IA. Se usa como fallback cuando Gemini no está disponible o para tareas que requieren modelos específicos. Configuración en `config/openai.php`.

### OpenAPI (Swagger)
Especificación para documentar APIs REST. El proyecto usa atributos PHP (`#[OA\Get(...)]`, `#[OA\Post(...)]`, `#[OA\Schema(...)]`) para generar automáticamente la documentación accesible en `/api/documentation`.

### OrdenCompra
Modelo para órdenes de compra a proveedores. Campos: `numero_orden`, `fecha_orden`, `fecha_entrega_esperada`, `monto_total`, `estado` (borrador, enviada, recibida, cancelada), `observaciones`.

### OWASP Top 10
Las 10 vulnerabilidades web más críticas según OWASP. El proyecto implementa protección para las 10 categorías:
- A01: Control de acceso (RBAC + Policies)
- A02: Fallos criptográficos (AES-256, bcrypt)
- A03: Inyección (Eloquent ORM, validación)
- A04: Diseño inseguro (DTOs, FormRequests)
- A05: Configuración incorrecta (headers seguridad)
- A06: Componentes vulnerables (Composer audit)
- A07: Autenticación (Sanctum, rate limiting)
- A08: Integridad de datos (firmado, auditoría)
- A09: Logging y monitoreo (Sentry, audit logs)
- A10: SSRF (validación de URLs)

---

## P — Policies, permisos y patrones

### Pact PHP
Herramienta de contract testing. Verifica que el API cumple con contratos definidos entre consumidores y proveedores. Tests en `tests/Contract/`:
- **Consumer tests**: AuthApi, ClienteApi, ComprobanteFe, Inventario, Producto, Venta
- **Provider verification**: `UrsolCastApiVerificationTest`

### Pago
Modelo general para registrar pagos. Campos: monto, fecha, forma de pago, referencia, estado.

### PagoCuentaCobrar
Modelo para pagos recibidos en cuentas por cobrar. Registra: monto, fecha, forma de pago, cuenta asociada, referencia de comprobante.

### PagoCuentaPagar
Modelo para pagos realizados en cuentas por pagar. Registra: monto, fecha, forma de pago, proveedor, referencia.

### PagoNomina
Modelo para pagos de nómina. Campos: `empleado_id`, `periodo_nomina_id`, `fecha_pago`, `monto_bruto`, `total_deducciones`, `monto_neto_pagado`, `metodo_pago_id`, `referencia_pago`, `estado`.

### PaginatedResponseDTO
DTO que estandariza metadata de paginación: `current_page`, `per_page`, `total`, `last_page`, `from`, `to`.

### PeriodoNomina
Modelo para períodos de nómina (quincenal, mensual, semanal). Campos: `fecha_inicio`, `fecha_fin`, `empresa_id`, `estado` (abierto, cerrado, procesado).

### Permiso
Modelo que define un permiso individual. Formato: `{módulo}.{acción}`. Ejemplo: `productos.ver`. 68 permisos definidos en 17 módulos con 4 acciones cada uno: `ver`, `crear`, `editar`, `eliminar`.

### PHPStan
Herramienta de análisis estático para PHP. El proyecto mantiene **Level 8** (el más estricto) con **0 errores**. Configuración en `phpstan.neon`. Se ejecuta: `php vendor/bin/phpstan analyse app/ --level 8`.

### PHPUnit
Framework de testing para PHP (v11.5). El proyecto tiene 1261 tests en 142 archivos. Configuración en `phpunit.xml`. Usa SQLite in-memory para tests de BD. Atributos: `#[Test]`, `#[CoversClass]`, `#[Group]`.

### PlanillaCcss
Modelo para la generación de planilla de la CCSS (Caja Costarricense de Seguro Social). Contiene los datos de empleados y aportes para la declaración mensual.

### Policy
Clase que define reglas de autorización para un modelo. El proyecto tiene 80+ policies registradas en `AuthServiceProvider`. Cada policy extiende `BasePolicy` y define un `$permission` prefix (ej. `'productos'`). Los controladores invocan `$this->authorize('acción', $modelo)`.

### PredictionService
Servicio de IA para predicciones de inventario: demanda futura, alertas de stock bajo, estacionalidad. Usa análisis de datos históricos con Gemini.

### Presupuesto
Modelo para presupuestos. Campos: `nombre`, `periodo_inicio`, `periodo_fin`, `empresa_id`. Relación: `hasMany(DetallePresupuesto, MovimientoPresupuesto)`.

### Producto
Modelo central del catálogo. Campos: `codigo`, `codigo_barras`, `nombre`, `descripcion`, `precio_venta`, `precio_compra`, `stock_minimo`, `stock_maximo`, `tipo_producto`, `vende`, `compra`, `controla_inventario`, `categoria_id`, `marca_id`, `unidad_medida_id`. Relaciones: `belongsTo(Empresa, CategoriaProducto, Marca, UnidadMedida, TipoImpuesto, Cabys)`.

### Proveedor
Modelo para proveedores. Similar a Cliente con campos de identificación, contacto y crédito. Relaciones: `hasMany(OrdenCompra, CuentaPorPagar)`.

### Provincia
Nivel superior de la división administrativa de Costa Rica. 7 provincias: San José, Alajuela, Cartago, Heredia, Guanacaste, Puntarenas, Limón. Código de 1 dígito.

---

## Q — Queue y quality assurance

### Queue (Cola)
Sistema de procesamiento asíncrono usando Redis como driver. Colas definidas: `hacienda` (envío FE), `reports` (generación de reportes), `emails` (correos), `imports` (importación de datos), `maintenance` (limpieza). Worker: `php artisan queue:work --tries=3 --timeout=90 --sleep=3`.

### Quality Assurance
El proyecto mantiene múltiples capas de calidad:
- **PHPStan Level 8**: Análisis estático sin errores
- **PHPUnit**: 1261 tests, 100% passing
- **Pact**: Contract testing consumidor-proveedor
- **Infection**: Mutation testing (MSI ≥ 50%)
- **k6**: Load testing
- **Codecov**: Cobertura de código

---

## R — RBAC, Redis, rutas y rate limiting

### Rate Limiting
Sistema de limitación de peticiones configurado en `config/rate-limiting.php`:
- **Autenticados**: 120 requests/minuto (estándar)
- **Login**: 5 requests/minuto
- **Pagos**: Limitado por operación
- **Reportes**: Limitado para generación heavy
- **Hacienda API**: Respeta los límites de Hacienda
- **IP blocking**: Bloqueo de 1 hora después de 10 violaciones

### RBAC (Role-Based Access Control)
Sistema de control de acceso basado en roles. Estructura:
- **17 módulos**: empresas, sucursales, usuarios, roles, productos, clientes, proveedores, inventario, ventas, compras, contabilidad, nomina, cuentas_cobrar, cuentas_pagar, facturacion, reportes, configuracion
- **4 acciones**: ver, crear, editar, eliminar
- **68 permisos totales**: 17 × 4
- **8 roles**: Administrador, Gerente, Contador, Vendedor, Comprador, Bodeguero, Usuario, Auditor

### Redis
Base de datos en memoria usada para:
- **Cache**: Datos de consultas frecuentes (productos, catálogos)
- **Sesiones**: Almacenamiento de sesiones de usuario
- **Colas**: Backend para jobs asincrónicos
- **Rate limiting**: Contadores de peticiones
Versión: Redis 7 Alpine.

### RegimenTributario
Modelo catálogo para regímenes tributarios de Costa Rica: Tradicional, Simplificado, Facturador electrónico, etc. Cada empresa tiene un régimen asignado.

### Resource (API Resource)
Transformadores de Laravel que convierten modelos Eloquent a JSON. El proyecto tiene 79 Resources. Convención: `{Modelo}Resource.php`. Usan el patrón `return ['campo' => $this->campo]` para controlar qué datos se exponen.

### RetencionImpuesto
Modelo para registrar retenciones de impuestos aplicadas en transacciones. Campos: tipo de retención, porcentaje, monto, referencia.

### Rol
Modelo que define un rol del sistema. Campos: `nombre`, `descripcion`, `activo`. 8 roles predefinidos:
1. **Administrador** — Acceso total (68 permisos)
2. **Gerente** — Gestión operacional
3. **Contador** — Módulo contable
4. **Vendedor** — Módulo de ventas
5. **Comprador** — Módulo de compras
6. **Bodeguero** — Módulo de inventario
7. **Usuario** — Acceso básico
8. **Auditor** — Solo lectura en auditoría

### RolPermiso
Tabla pivote que relaciona roles con permisos. Permite asignación granular de permisos por rol.

### RolUsuario
Tabla pivote que asigna roles a usuarios. Un usuario puede tener múltiples roles.

### Ruta (routes/api/)
Las rutas API están particionadas en 14 archivos por dominio:
- `auth.php` — Autenticación
- `core.php` — Empresas, Sucursales, Usuarios, Roles
- `catalogos.php` — Catálogos del sistema
- `configuracion.php` — Configuración y utilidades
- `inventario.php` — Inventario y almacenes
- `ventas.php` — Ventas y clientes
- `compras.php` — Compras y proveedores
- `contabilidad.php` — Contabilidad y presupuestos
- `nomina.php` — Nómina y RRHH
- `transporte.php` — Transporte (buses, rutas)
- `fe.php` — Facturación electrónica y Hacienda
- `compliance.php` — GDPR y auditoría
- `ai.php` — Inteligencia artificial
- `observabilidad.php` — Health checks y métricas

### Ruta (Modelo)
Modelo del módulo de transporte que define las rutas de buses.

---

## S — Servicios, seguridad y Sanctum

### SalidaInventario
Modelo para registrar salidas de inventario (ventas, consumos, ajustes negativos, mermas). Relación: `hasMany(DetalleSalidaInventario)`.

### Sanctum
Ver [Laravel Sanctum](#laravel-sanctum).

### Seeder
Scripts que poblan la base de datos. Dos tipos:
- `MasterDataSeeder` — Datos de producción: 14 catálogos idempotentes con `updateOrInsert()`
- `DemoDataSeeder` — Datos de demostración para desarrollo

### Sentry
Plataforma de monitoreo de errores en producción (`sentry/sentry-laravel`). Captura excepciones, errores no manejados, performance traces, y las envía al dashboard de Sentry.

### SentryService
Servicio wrapper para integración con Sentry. Permite enviar eventos, breadcrumbs y contexto personalizado.

### Service Layer (Capa de Servicios)
Patrón arquitectónico donde la lógica de negocio vive en clases `Service`, separada de los controladores. Beneficios: controladores delgados, lógica testeable unitariamente, reutilización entre controladores. El proyecto tiene 62 servicios (10 AI + 8 Hacienda + 44 core).

### SesionUsuario
Modelo que registra sesiones activas de usuarios. Campos: token, IP, user agent, último acceso, expiración.

### Soft Delete (Eliminación lógica)
Patrón donde los registros no se eliminan físicamente de la BD, sino que se marcan con `eliminado = true`. Implementado por `HasCustomSoftDeletes`. Permite recuperar datos y mantener integridad referencial.

### Sucursal
Modelo para sucursales de una empresa. Campos: nombre, dirección, teléfono, tipo, empresa. Relaciones: `belongsTo(Empresa)`, `hasMany(Almacen, Caja)`.

### Sunset (Middleware)
Middleware para API versioning que agrega header `Sunset` indicando la fecha de deprecación de una versión de la API. Ejemplo: `Sunset: Sat, 01 Jan 2027 00:00:00 GMT`.

---

## T — Testing, traits y transporte

### TasaImpuesto
Modelo para tasas de impuestos vigentes. Campos: `tipo_impuesto_id`, `tasa_porcentaje`, `fecha_inicio_vigencia`, `fecha_fin_vigencia`. Permite tener múltiples tasas históricas para un mismo tipo de impuesto.

### TestCase (`tests/TestCase.php`)
Clase base de todos los tests. Provee helpers para: autenticación en tests (`actingAs`), creación de empresa y usuario de prueba, ejecución de seeders necesarios, y setup de SQLite in-memory.

### Testing (Estructura de tests)
4 capas de testing:
1. **Feature** (75 archivos, ~600 tests) — Integración y E2E con PHPUnit
2. **Unit** (58 archivos, ~350 tests) — Lógica de negocio con PHPUnit
3. **Contract** (9 archivos, ~47 tests) — Contratos API con Pact PHP
4. **Load** (6 archivos) — Performance con k6

### Throttle (Middleware)
Middleware de Laravel para rate limiting. Configuraciones usadas:
- `throttle:login` — 5/min para login
- `throttle:120,1` — 120/min estándar
- `throttle:reports` — Específico para reportes
- `throttle:payment_process` — Específico para pagos

### TipoCambioHistorial
Modelo para almacenar tasas de cambio históricas entre monedas (CRC/USD, CRC/EUR, etc.).

### TipoCliente
Modelo catálogo para clasificar clientes (ej. "Consumidor final", "Crédito", "Exento", "Gobierno").

### TipoComprobanteFeFactory
Factory para tipos de comprobantes electrónicos:
- `01` — Factura electrónica
- `02` — Nota de débito electrónica
- `03` — Nota de crédito electrónica
- `04` — Tiquete electrónico

### TipoCuenta
Catálogo de tipos de cuentas contables: Activo, Pasivo, Capital, Ingreso, Gasto. Base del plan de cuentas.

### TipoIdentificación
Catálogo de tipos de identificación según DGT:
- `01` — Cédula física
- `02` — Cédula jurídica
- `03` — DIMEX (Documento de Identidad Migratorio para Extranjeros)
- `04` — NITE (Número de Identificación Tributario Especial)
- `05` — Extranjero

### TipoImpuesto
Catálogo de tipos de impuestos: IVA, Impuesto selectivo de consumo, etc. Cada tipo puede tener múltiples tasas vigentes.

### TiqueteDetalle
Modelo del módulo de transporte para detalles de tiquetes de bus.

### Tiquete Electrónico
Tipo de comprobante electrónico `04`. Similar a factura pero para consumidores finales que no requieren crédito fiscal. Requisitos de datos del receptor son más laxos.

### Trace ID
Identificador único generado por cada petición HTTP para trazabilidad. Se incluye en logs, respuestas de error, y se propaga a servicios externos. Formato UUID.

### Trait
Mecanismo de PHP para reutilización de código. El proyecto define 12 traits en `app/Traits/`:
- `ApiResponse` — Respuestas JSON estandarizadas
- `BelongsToTenant` — Multi-tenancy automático
- `EncryptsAttributes` — (DEPRECATED) Encriptación de campos
- `HasActiveScope` — Scopes de activo/inactivo
- `HasAuditFields` — Campos de auditoría
- `HasAuditableEvents` — Eventos auditables
- `HasCacheableQueries` — Cache de queries
- `HasCustomSoftDeletes` — Soft delete con booleano
- `HasEmpresaContext` — Contexto de empresa/tenant
- `HasEncryptedAttributes` — Encriptación moderna
- `HasPermissionCache` — Cache de permisos
- `HasSafeErrorHandling` — Manejo seguro de errores

### Transporte (Módulo)
Módulo para gestión de transporte público (buses). Modelos: `BusUnidad`, `ModeloBus`, `Ruta`, `HorarioRuta`, `TiqueteDetalle`. Rutas en `routes/api/transporte.php`.

### Transformer (DTO)
Clases en `app/DTOs/Transformers/` que transforman modelos a formatos de salida específicos. Disponibles: `ClienteTransformer`, `ProductoTransformer`, `VentaTransformer`.

---

## U — Usuarios, unidades y utilidades

### UnidadMedida
Modelo catálogo para unidades de medida: kilogramo, metro, unidad, litro, servicio profesional, etc. Se asignan a productos para facturación y control de inventario.

### UrlShortener
Modelo utilitario para acortar URLs. Almacena: URL original, código corto, conteo de clics, expiración.

### Usuario
Modelo principal de usuarios. Campos: `nombre`, `apellidos`, `email`, `password_hash` (hidden), `cargo_id`, `empresa_id`, `activo`, `eliminado`, `creado_en`, `actualizado_en`. Métodos RBAC: `hasPermission(string)`, `hasRole(string)`, `assignRole(Rol)`. Relaciones: `belongsTo(Empresa, Cargo)`, `belongsToMany(Rol)`.

### UsuarioService
Servicio de negocio para gestión de usuarios: creación, actualización, cambio de contraseña, asignación de roles, desactivación.

---

## V — Ventas, versionado y validaciones

### V1 / V2 (API Versioning)
La API soporta dos versiones:
- `/api/v1/` — Versión estable actual
- `/api/v2/` — Versión con mejoras (nueva estructura de respuestas, campos adicionales)
También se soporta versioning por header: `Accept: application/vnd.ursol.v2+json`. El middleware `Sunset` indica fechas de deprecación.

### Validación
El sistema de validación incluye:
- **FormRequests** (170+): Validación declarativa por endpoint
- **Rules custom**: `CrIdentificacion`, `CrTelefono`
- **DTOs**: Validación en capa de servicio
- **Reglas Laravel**: `required`, `string`, `max:{n}`, `email`, `unique`, `exists`, `numeric`, `date`, `Password::min(8)->mixedCase()->numbers()->symbols()`

### Venta
Modelo para transacciones de venta. Campos: `empresa_id`, `cliente_id`, `usuario_id`, `fecha_venta`, `numero_comprobante`, `serie_comprobante`, `moneda`, `subtotal_bruto_total`, `monto_descuento_total`, `subtotal_neto_total`, `monto_impuesto_total`, `monto_total_venta`, `estado_venta` (borrador, pendiente, pagada, anulada), `forma_pago_id`, `observaciones`. Relaciones: `belongsTo(Empresa, Cliente, Usuario, FormaPago)`, `hasMany(DetalleVenta)`.

### VentaCreadaEvent
Evento disparado cuando se crea una venta. Permite que listeners ejecuten acciones post-venta: actualizar inventario, generar factura electrónica, enviar notificación.

### VentaService
Servicio de negocio para ventas. Métodos: `crear()`, `obtener()`, `listar()`, `porCliente()`, `entreFechas()`, `totalEnPeriodo()`, `cambiarEstado()`.

---

## W — Webhooks y workers

### Webhook
Modelo para registrar endpoints de webhook. Campos: URL, eventos suscritos, secreto (para firma), estado (activo/inactivo), empresa. Permite que sistemas externos sean notificados en tiempo real.

### WebhookDispatcherService
Servicio que coordina la entrega de webhooks. Cuando ocurre un evento suscrito, despacha un `DeliverWebhookJob` a la cola.

### WebhookEvent
Evento de dominio genérico que transporta datos de un webhook. Disparado por diversas acciones del sistema (nueva venta, factura emitida, pago recibido).

### WebhookLog
Modelo para registrar intentos de entrega de webhooks: URL destino, payload, response status, headers, duración, reintentos.

### WebhookService
Servicio para gestionar la configuración de webhooks: crear, actualizar, eliminar, listar webhooks de una empresa.

### Worker (Queue Worker)
Proceso que consume y ejecuta jobs de las colas Redis. En Docker se ejecuta como contenedor `ursol_queue` con: `php artisan queue:work --tries=3 --timeout=90 --sleep=3`. En Kubernetes se despliega como Deployment separado con 2–3 réplicas.

---

## X — XML, XAdES y XDebug

### XAdES-EPES
Estándar de firma digital XML (XML Advanced Electronic Signatures — Explicit Policy-based Electronic Signatures). Requerido por Hacienda de Costa Rica para firmar comprobantes electrónicos. La firma incluye: certificado X.509, timestamp, referencia a política de firma, hash SHA-256.

### XadesEpesSigner
Servicio (`app/Services/Hacienda/XadesEpesSigner.php`) que aplica la firma XAdES-EPES a documentos XML de comprobantes electrónicos. Usa el certificado digital de la empresa y sigue las especificaciones de Hacienda.

### XDebug
Extensión PHP para debugging y profiling. Opcional en Docker (activar con `INSTALL_XDEBUG=true` en build). No se incluye en producción por rendimiento.

### XML
Formato principal para comprobantes electrónicos. La API genera XML según esquema v4.4 de DGT Costa Rica. Servicios involucrados:
- `XmlComprobanteBuilder` — Construye el XML del comprobante
- `XadesEpesSigner` — Firma el XML
- `HaciendaApiClient` — Envía el XML firmado a Hacienda

### XmlComprobanteBuilder
Servicio de Hacienda que construye el documento XML del comprobante electrónico según el esquema v4.4 de DGT. Genera: cabecera, emisor, receptor, detalle de líneas, resumen, otros documentos referencia, y campo de proveedor de sistemas (v4.4).

---

## Apéndice: Acrónimos y abreviaturas

| Acrónimo | Significado |
|----------|-------------|
| API | Application Programming Interface |
| ATV | Administración Tributaria Virtual (sandbox de Hacienda) |
| BCCR | Banco Central de Costa Rica |
| CABYS | Catálogo de Bienes y Servicios |
| CA | Certificate Authority |
| CCSS | Caja Costarricense de Seguro Social |
| CI/CD | Continuous Integration / Continuous Deployment |
| CORS | Cross-Origin Resource Sharing |
| CQRS | Command Query Responsibility Segregation (eliminado en v3.0.0) |
| CRC | Colón Costarricense (moneda) |
| CRUD | Create, Read, Update, Delete |
| DGT | Dirección General de Tributación |
| DIMEX | Documento de Identidad Migratorio para Extranjeros |
| DTO | Data Transfer Object |
| E2E | End-to-End (testing) |
| ERP | Enterprise Resource Planning |
| FE | Facturación Electrónica |
| GDPR | General Data Protection Regulation |
| HPA | Horizontal Pod Autoscaler (Kubernetes) |
| INS | Instituto Nacional de Seguros |
| IVA | Impuesto al Valor Agregado |
| MSI | Mutation Score Indicator |
| NITE | Número de Identificación Tributario Especial |
| ORM | Object-Relational Mapping |
| OWASP | Open Web Application Security Project |
| RBAC | Role-Based Access Control |
| RPD | Requests Per Day |
| RPM | Requests Per Minute |
| RRHH | Recursos Humanos |
| SSRF | Server-Side Request Forgery |
| TLS | Transport Layer Security |
| TPM | Tokens Per Minute |
| TTL | Time To Live |
| VU | Virtual User (k6) |
| XAdES | XML Advanced Electronic Signatures |

---

## Apéndice: Diagrama de relaciones entre módulos

```
┌───────────────────────────────────────────────────────────────────┐
│                          EMPRESA (Tenant)                        │
│  ┌─────────┐  ┌──────────┐  ┌──────────┐  ┌───────────────────┐ │
│  │Sucursal │  │ Usuario  │  │   Rol    │  │    Configuración  │ │
│  │         │  │          │  │          │  │                   │ │
│  └────┬────┘  └────┬─────┘  └────┬─────┘  └───────────────────┘ │
│       │            │             │                                │
│  ┌────▼────┐  ┌────▼─────┐ ┌────▼─────┐                        │
│  │ Almacén │  │RolUsuario│ │RolPermiso│                        │
│  └────┬────┘  └──────────┘ └──────────┘                        │
│       │                                                          │
│  ┌────▼──────────────────────────────────────────────┐          │
│  │              INVENTARIO                            │          │
│  │  Producto ← InventarioProducto → Almacén          │          │
│  │  EntradaInventario ← DetalleEntrada               │          │
│  │  SalidaInventario  ← DetalleSalida                │          │
│  └────┬──────────────────────────────────────────────┘          │
│       │                                                          │
│  ┌────▼──────────────┐  ┌─────────────────────────────┐        │
│  │      VENTAS       │  │        COMPRAS              │        │
│  │  Cliente          │  │  Proveedor                  │        │
│  │  Venta ← Detalle  │  │  OrdenCompra ← Detalle     │        │
│  │  CuentaPorCobrar  │  │  CuentaPorPagar             │        │
│  │  PagoCuentaCobrar │  │  PagoCuentaPagar            │        │
│  └────┬──────────────┘  └──────────┬──────────────────┘        │
│       │                            │                             │
│  ┌────▼────────────────────────────▼──────────────────┐        │
│  │             CONTABILIDAD                            │        │
│  │  CuentaContable (plan de cuentas)                  │        │
│  │  AsientoContable ← DetalleAsiento                  │        │
│  │  TipoImpuesto ← TasaImpuesto                      │        │
│  │  Presupuesto ← DetallePresupuesto                  │        │
│  │  CuentaBancaria → MovimientoBancario               │        │
│  │  CajaChica → MovimientoCajaChica                   │        │
│  └────┬───────────────────────────────────────────────┘        │
│       │                                                          │
│  ┌────▼───────────────────┐  ┌────────────────────────┐        │
│  │  FACTURACIÓN ELECTR.   │  │       NÓMINA           │        │
│  │  ComprobanteElecFe     │  │  Empleado → Cargo      │        │
│  │  FeLineaDetalle        │  │  PeriodoNomina         │        │
│  │  ConsecutivoFe         │  │  PagoNomina            │        │
│  │  FeCertificadoDigital  │  │  NominaEmpleado        │        │
│  │  MensajeHacienda       │  │  DeduccionLegal        │        │
│  │  FeOAuthToken          │  │  PlanillaCcss          │        │
│  └────────────────────────┘  └────────────────────────┘        │
│                                                                  │
│  ┌──────────────┐  ┌────────────┐  ┌───────────────────┐       │
│  │  TRANSPORTE  │  │    IA      │  │   COMPLIANCE      │       │
│  │  BusUnidad   │  │  10 Svcs   │  │  AuditLog         │       │
│  │  ModeloBus   │  │  32+ EP    │  │  AuditoriaActiv.  │       │
│  │  Ruta        │  │  Gemini    │  │  DataRetention    │       │
│  │  HorarioRuta │  │  OpenAI    │  │  GdprDeletion     │       │
│  └──────────────┘  └────────────┘  └───────────────────┘       │
└───────────────────────────────────────────────────────────────────┘
```

---

**Última actualización:** 1 de abril de 2026  
**Versión del glosario:** 1.0.0  
**Basado en:** Código fuente Ursol CAST API v4.1.0
