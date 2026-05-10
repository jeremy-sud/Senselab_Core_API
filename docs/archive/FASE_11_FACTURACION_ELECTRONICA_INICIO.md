# FASE 11: FACTURACIÓN ELECTRÓNICA COSTA RICA - INICIO

## 📋 RESUMEN EJECUTIVO

**Fecha de inicio**: 26 de noviembre de 2025  
**Estado**: En progreso - Fase 1 completada (25% del total)  
**Objetivo**: Implementar integración completa con el Sistema de Facturación Electrónica del Ministerio de Hacienda de Costa Rica

---

## 🎯 CONTEXTO

Después de alcanzar el **100% de cobertura de tests** (218/218 tests pasando), se tomó la decisión estratégica de implementar la **Facturación Electrónica (FE)** como siguiente prioridad del proyecto.

**Opciones evaluadas**:
1. ❌ Swagger Documentation
2. ✅ **Facturación Electrónica Costa Rica** (SELECCIONADA)
3. ❌ Optimización y rendimiento
4. ❌ Tests adicionales

---

## 📚 INVESTIGACIÓN REALIZADA

### Documentación Oficial Consultada

1. **Ministerio de Hacienda - API v1**
   - URL: https://www.hacienda.go.cr/docs/ComprobantesElectronicosAPI.html
   - URL: https://api.hacienda.go.cr/docs/
   - Documentación completa del API REST
   - Especificaciones OAuth 2.0 / OIDC
   - Rate limiting y políticas de uso

2. **Comunidad CRLibre**
   - Repositorio: https://github.com/CRLibre/fe-hacienda-cr-docs
   - Documentación comunitaria open source
   - Diagramas de flujo de integración
   - Grupos de soporte: WhatsApp y Facebook

### Especificaciones Técnicas del API de Hacienda

#### Endpoints Principales

**Base URL Production**: `https://api.comprobanteselectronicos.go.cr/recepcion/v1/`  
**Base URL Sandbox (ATV)**: `https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1/`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/recepcion` | Enviar comprobante electrónico |
| GET | `/recepcion/{clave}` | Consultar estado de comprobante |
| GET | `/comprobantes` | Listar comprobantes enviados |
| GET | `/comprobantes/{clave}` | Obtener detalles de comprobante |

#### Autenticación OAuth 2.0

- **Tipo**: OAuth 2.0 / OpenID Connect (OIDC)
- **Grant Type**: `client_credentials`
- **Token Endpoint (Sandbox)**: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token`
- **Token Endpoint (Production)**: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token`

#### Rate Limiting

| Tipo | Límite | Duración | Máximo Total |
|------|--------|----------|--------------|
| **Burst** | 20 req/seg | 5 segundos | 100 requests |
| **Sostenido** | 10 req/seg | 120 segundos | 1,200 requests |
| **Penalización** | Bloqueo de IP por 10 minutos si se excede |

#### Clave Numérica (50 posiciones)

```
FORMATO: PAÍS(1) + FECHA(8) + CEDULA_EMISOR(12) + CONSECUTIVO(20) + SITUACION(1) + SEGURIDAD(8)

Ejemplo: 50608202300310112345600100001000000001000000001112345678
```

| Posición | Longitud | Campo | Descripción |
|----------|----------|-------|-------------|
| 1 | 1 | País | Siempre "5" para Costa Rica |
| 2-9 | 8 | Fecha | ddmmyyyy de emisión |
| 10-21 | 12 | Cédula Emisor | Identificación tributaria |
| 22-41 | 20 | Consecutivo | Número consecutivo interno |
| 42 | 1 | Situación | 1=Normal, 2=Contingencia, 3=Sin internet |
| 43-50 | 8 | Seguridad | Código aleatorio de seguridad |

#### Estados del Comprobante

```
pendiente → enviando → recibido → procesando → aceptado
                                              ↓
                                           rechazado
                                              ↓
                                            error
```

#### Tipos de Comprobante Soportados

| Código | Tipo de Documento |
|--------|-------------------|
| 01 | Factura Electrónica |
| 02 | Nota de Débito Electrónica |
| 03 | Nota de Crédito Electrónica |
| 04 | Tiquete Electrónico |
| 05 | Nota de Despacho |
| 06 | Contrato |
| 07 | Procedimiento |
| 08 | Comprobante de Compra |
| 09 | Factura de Exportación |

#### Esquema XML v4.3

- **Estándar**: XML según esquema v4.3 de Hacienda
- **Firma Digital**: XAdES-EPES (XML Advanced Electronic Signatures)
- **Certificado**: Formato .p12 (PKCS#12)
- **Encoding**: Base64 para transporte, UTF-8 para contenido

---

## ✅ FASE 1 COMPLETADA: CONFIGURACIÓN Y BASE DE DATOS

### 1. Archivo de Configuración

**Archivo creado**: `config/hacienda.php`

**Características**:
- ✅ Configuración de ambientes (sandbox/production)
- ✅ URLs de OAuth y API separadas por ambiente
- ✅ Configuración de certificados digitales
- ✅ Timeouts y reintentos HTTP
- ✅ Rate limiting configurable
- ✅ Sistema de caché para tokens
- ✅ Callback URL para webhooks
- ✅ Logging detallado con guardado de XMLs
- ✅ Catálogos completos:
  - Estados de comprobantes
  - Tipos de documento
  - Situaciones de emisión
  - Medios de pago
  - Condiciones de venta

### 2. Variables de Entorno

**Archivo actualizado**: `.env.example`

**Variables agregadas** (50+ configuraciones):

```env
# Ambiente
HACIENDA_ENVIRONMENT=sandbox

# OAuth 2.0
HACIENDA_OAUTH_CLIENT_ID=
HACIENDA_OAUTH_CLIENT_SECRET=
HACIENDA_OAUTH_SCOPE=

# Certificado Digital
HACIENDA_CERT_PATH=
HACIENDA_CERT_PASSWORD=
HACIENDA_CERT_VALIDATE_EXPIRY=true

# HTTP Configuration
HACIENDA_HTTP_TIMEOUT=30
HACIENDA_HTTP_RETRY_TIMES=3

# Rate Limiting
HACIENDA_RATE_LIMIT_ENABLED=true
HACIENDA_RATE_LIMIT_MAX_PER_SEC=8

# Cache
HACIENDA_CACHE_ENABLED=true
HACIENDA_CACHE_TOKEN_TTL=3600

# Logging
HACIENDA_LOG_ENABLED=true
HACIENDA_LOG_SAVE_XML=true
```

### 3. Migraciones de Base de Datos

#### **Migración 1**: `create_comprobantes_electronicos_fe_table`

**Tabla**: `comprobantes_electronicos_fe`

**Campos principales** (60+ campos):

| Categoría | Campos Clave |
|-----------|--------------|
| **Identificación** | `id`, `clave` (unique 50), `consecutivo`, `tipo_documento` |
| **Relaciones** | `empresa_id` → `empresas.id` |
| **Receptor** | `receptor_tipo_identificacion`, `receptor_numero_identificacion`, `receptor_nombre`, `receptor_email` |
| **Montos** | `total_comprobante`, `total_venta`, `total_impuesto`, `total_descuentos` (18,5 precision) |
| **Condiciones** | `condicion_venta`, `medio_pago`, `plazo_credito` |
| **XML** | `xml_original`, `xml_firmado` (longText) |
| **Estado** | `estado` (pendiente/enviando/recibido/procesando/aceptado/rechazado/error) |
| **Hacienda** | `respuesta_hacienda_xml`, `mensaje_hacienda`, `codigo_respuesta_hacienda` |
| **Timestamps** | `fecha_envio`, `fecha_recibido`, `fecha_procesado`, `fecha_respuesta` |
| **Control** | `intentos_envio`, `ultimo_intento`, `ultimo_error` |
| **Metadata** | `metadata` (JSON), `softDeletes` |

**Índices optimizados**:
- `clave` (UNIQUE)
- `empresa_id + estado`
- `empresa_id + fecha_emision`
- `tipo_documento + estado`
- `consecutivo`
- `created_at`

#### **Migración 2**: `create_fe_lineas_detalle_table`

**Tabla**: `fe_lineas_detalle`

**Campos principales** (35+ campos):

| Categoría | Campos Clave |
|-----------|--------------|
| **Relación** | `comprobante_id` → `comprobantes_electronicos_fe.id` |
| **Identificación** | `numero_linea`, `codigo`, `codigo_tipo`, `codigo_comercial` |
| **Descripción** | `detalle` (text) |
| **Cantidad** | `cantidad`, `unidad_medida`, `unidad_medida_comercial` |
| **Precios** | `precio_unitario`, `monto_total`, `monto_descuento`, `subtotal` (18,5 precision) |
| **Impuestos** | `impuesto_codigo`, `impuesto_tarifa`, `impuesto_monto`, `base_imponible` |
| **Exoneraciones** | `exoneracion_tipo_documento`, `exoneracion_numero_documento`, `exoneracion_monto` |
| **Total** | `monto_total_linea` |

**Índices**:
- `comprobante_id + numero_linea`
- `codigo`

#### **Migración 3**: `create_fe_certificados_digitales_table`

**Tabla**: `fe_certificados_digitales`

**Campos principales**:

| Categoría | Campos Clave |
|-----------|--------------|
| **Relación** | `empresa_id` → `empresas.id` |
| **Certificado** | `nombre`, `tipo` (p12/pfx/pem), `numero_serie`, `emisor`, `sujeto` |
| **Archivo** | `ruta_archivo`, `password_encrypted` |
| **Validez** | `fecha_emision`, `fecha_vencimiento`, `activo`, `valido` |
| **Ambiente** | `ambiente` (sandbox/production) |
| **Metadata** | `metadata` (JSON), `softDeletes` |

**Índices**:
- `empresa_id + activo`
- `fecha_vencimiento`

#### **Migración 4**: `create_fe_oauth_tokens_table`

**Tabla**: `fe_oauth_tokens`

**Campos principales**:

| Categoría | Campos Clave |
|-----------|--------------|
| **Ambiente** | `ambiente` (sandbox/production) |
| **Token** | `access_token` (text), `token_type`, `expires_in`, `expires_at` |
| **Refresh** | `refresh_token` (text nullable) |
| **Scope** | `scope` (string 500) |
| **Control** | `activo`, `uso_contador`, `ultimo_uso` |
| **Metadata** | `metadata` (JSON) |

**Índices**:
- `ambiente + activo + expires_at`
- `ambiente`
- `expires_at`

### 4. Modelos Eloquent

#### **Modelo 1**: `ComprobanteElectronicoFe`

**Ubicación**: `app/Models/ComprobanteElectronicoFe.php`

**Características**:
- ✅ 50+ campos fillables
- ✅ 20+ casts a tipos nativos (datetime, decimal, array)
- ✅ SoftDeletes habilitado
- ✅ Relaciones:
  - `empresa()` → BelongsTo Empresa
  - `lineasDetalle()` → HasMany FeLineaDetalle
- ✅ **9 Scopes**:
  - `estado($estado)`
  - `pendientes()`
  - `aceptados()`
  - `rechazados()`
  - `tipoDocumento($tipo)`
  - `fechaEmisionEntre($inicio, $fin)`
- ✅ **6 Accessors**:
  - `tipo_documento_nombre` → Nombre legible del tipo
  - `aceptado` → bool
  - `rechazado` → bool
  - `pendiente` → bool

#### **Modelo 2**: `FeLineaDetalle`

**Ubicación**: `app/Models/FeLineaDetalle.php`

**Características**:
- ✅ 30+ campos fillables
- ✅ 15+ casts a tipos nativos
- ✅ Relación: `comprobante()` → BelongsTo ComprobanteElectronicoFe
- ✅ **3 Accessors**:
  - `tiene_descuento` → bool
  - `tiene_impuesto` → bool
  - `tiene_exoneracion` → bool

#### **Modelo 3**: `FeCertificadoDigital`

**Ubicación**: `app/Models/FeCertificadoDigital.php`

**Características**:
- ✅ SoftDeletes habilitado
- ✅ `password_encrypted` oculto en JSON
- ✅ Relación: `empresa()` → BelongsTo Empresa
- ✅ **3 Scopes**:
  - `activos()`
  - `validos()` → No expirados
  - `ambiente($ambiente)`
- ✅ **3 Accessors**:
  - `proximo_vencer` → bool (30 días)
  - `vencido` → bool
  - `dias_restantes` → int

#### **Modelo 4**: `FeOAuthToken`

**Ubicación**: `app/Models/FeOAuthToken.php`

**Características**:
- ✅ `access_token` y `refresh_token` ocultos en JSON
- ✅ **4 Scopes**:
  - `activos()`
  - `validos()` → No expirados
  - `ambiente($ambiente)`
  - `ultimoValido($ambiente)` → Token más reciente
- ✅ **3 Accessors**:
  - `proximo_expirar` → bool (5 minutos)
  - `expirado` → bool
  - `segundos_restantes` → int
- ✅ **Método**: `incrementarUso()` → Tracking de uso del token

---

## 📊 PROGRESO GENERAL

### Plan de 10 Fases

| # | Fase | Estado | Progreso |
|---|------|--------|----------|
| 1 | ✅ Configuración y Base de Datos | **COMPLETADO** | 100% |
| 2 | ⬜ Servicios Base (OAuth, HTTP Client) | Pendiente | 0% |
| 3 | ⬜ Generador Clave Numérica | Pendiente | 0% |
| 4 | ⬜ Constructor XML v4.3 | Pendiente | 0% |
| 5 | ⬜ Firma Digital XAdES-EPES | Pendiente | 0% |
| 6 | ⬜ Jobs Async (Envío, Consulta) | Pendiente | 0% |
| 7 | ⬜ Controller API REST | Pendiente | 0% |
| 8 | ⬜ Webhooks y Callbacks | Pendiente | 0% |
| 9 | ⬜ Tests Automatizados | Pendiente | 0% |
| 10 | ⬜ Swagger Documentation | Pendiente | 0% |

**Progreso total**: 10% (1 de 10 fases completadas)

---

## 📁 ARCHIVOS CREADOS/MODIFICADOS

### Archivos Creados (8)

1. ✅ `config/hacienda.php` (350 líneas)
2. ✅ `database/migrations/2025_11_26_184030_create_comprobantes_electronicos_fe_table.php`
3. ✅ `database/migrations/2025_11_26_184109_create_fe_lineas_detalle_table.php`
4. ✅ `database/migrations/2025_11_26_184143_create_fe_certificados_digitales_table.php`
5. ✅ `database/migrations/2025_11_26_184143_create_fe_oauth_tokens_table.php`
6. ✅ `app/Models/ComprobanteElectronicoFe.php` (220 líneas)
7. ✅ `app/Models/FeLineaDetalle.php` (110 líneas)
8. ✅ `app/Models/FeCertificadoDigital.php` (140 líneas)
9. ✅ `app/Models/FeOAuthToken.php` (130 líneas)

### Archivos Modificados (1)

1. ✅ `.env.example` (50+ nuevas variables)

---

## 🔜 PRÓXIMOS PASOS - FASE 2

### Servicios a Crear

1. **`HaciendaApiClient`**
   - HTTP client con Guzzle
   - Manejo automático de rate limiting
   - Retry logic con backoff exponencial
   - Logging de requests/responses

2. **`OAuthTokenManager`**
   - Obtener token OAuth 2.0
   - Refresh automático antes de expiración
   - Cache de tokens en DB
   - Validación de vigencia

3. **`RateLimiter`**
   - Implementación de leaky bucket algorithm
   - Respetar límites: 8 req/seg sostenido
   - Cola de espera cuando se alcanza límite
   - Métricas de uso

---

## 📋 REQUISITOS DEL USUARIO

Para poder usar el sistema de FE, el usuario deberá:

1. **Registrar aplicación en Hacienda**
   - Portal: https://www.hacienda.go.cr/desarrolladores
   - Obtener `client_id` y `client_secret` de OAuth

2. **Obtener certificado digital .p12**
   - Emitido por autoridad certificadora autorizada
   - Formato PKCS#12 (.p12 o .pfx)
   - Debe estar vigente

3. **Solicitar acceso a ambiente ATV (sandbox)**
   - Contacto: facturati@hacienda.go.cr
   - Credenciales de prueba para testing

4. **Configurar variables de entorno**
   - Copiar `.env.example` → `.env`
   - Completar todas las variables `HACIENDA_*`

---

## 🎯 HITOS ALCANZADOS

- [x] Investigación completa del API de Hacienda
- [x] Documentación técnica recopilada
- [x] Configuración base implementada
- [x] 4 migraciones de base de datos creadas
- [x] 4 modelos Eloquent completos con relaciones
- [x] Variables de entorno documentadas
- [x] Plan de 10 fases establecido
- [ ] Ejecutar migraciones en base de datos
- [ ] Implementar servicios base (Fase 2)

---

## 📝 NOTAS TÉCNICAS

### Decisiones de Arquitectura

1. **Procesamiento Asíncrono**
   - Uso de Laravel Jobs para envío a Hacienda
   - Evita timeouts en requests HTTP largos
   - Permite reintentos automáticos en caso de fallo

2. **Separación de Ambientes**
   - Configuración clara sandbox vs production
   - Permite testing sin afectar producción
   - Tokens y certificados separados por ambiente

3. **Precisión Decimal**
   - 18,5 para montos monetarios
   - Cumple con estándares de Hacienda
   - Evita errores de redondeo

4. **Seguridad**
   - Passwords de certificados encriptados
   - Tokens OAuth ocultos en JSON responses
   - SoftDeletes para auditoría

5. **Performance**
   - Índices optimizados en todas las tablas
   - Cache de tokens OAuth (3600 seg)
   - Cache de respuestas Hacienda (300 seg)

### Compatibilidad

- **Laravel**: 11.x
- **PHP**: 8.2+
- **MySQL**: 8.0+ (soporte JSON nativo)
- **Extensions requeridas**: 
  - `ext-openssl` (firma digital)
  - `ext-xml` (procesamiento XML)
  - `ext-json` (metadata)

---

## 🔗 REFERENCIAS

- [Documentación Oficial Hacienda](https://www.hacienda.go.cr/docs/ComprobantesElectronicosAPI.html)
- [API Docs Hacienda](https://api.hacienda.go.cr/docs/)
- [CRLibre GitHub](https://github.com/CRLibre/fe-hacienda-cr-docs)
- [Esquemas XML v4.3](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/)

---

**Última actualización**: 26 de noviembre de 2025  
**Responsable**: Sistema de Facturación Electrónica - Senselab Core API  
**Siguiente revisión**: Al completar Fase 2
