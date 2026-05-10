# Auditoría Técnica del Módulo Hacienda — Firma Digital y Código Crudo

**Fecha:** 17 de abril de 2026  
**Versión del sistema:** v5.0.1  
**Auditor:** GitHub Copilot — Revisión profunda solicitada  
**Alcance:** Todo el módulo `app/Services/Hacienda/`, modelos FE, tests E2E, firma digital XAdES-EPES

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [CRÍTICO — Firma Digital No Funciona](#1-crítico--firma-digital-no-funciona-con-openssl-3x)
3. [CRÍTICO — HaciendaIntegrationService es Código Muerto](#2-crítico--haciendaintegrationservice-es-código-muerto-placeholder)
4. [ALTO — FirmaDigitalService: Password en Base64](#3-alto--firmadigitalservice-password-en-base64-sin-encriptar)
5. [ALTO — FirmaDigitalService Pasa Tipo Incorrecto a XadesEpesSigner](#4-alto--firmadigitalservice-pasa-tipo-incorrecto-a-xadesepessigner)
6. [MEDIO — Duplicidad de Constructores XML](#5-medio--duplicidad-de-constructores-xml)
7. [MEDIO — OAuthTokenManager Usa Guzzle Directo](#6-medio--oauthtokenmanager-usa-guzzle-directo-en-vez-de-laravels-http)
8. [MEDIO — HaciendaApiClient No Usa DI](#7-medio--haciendaapiclient-no-usa-inyección-de-dependencias)
9. [BAJO — RateLimiter No Es Atómico](#8-bajo--ratelimiter-no-es-atómico)
10. [BAJO — Token OAuth TTL Incorrecto](#9-bajo--token-oauth-ttl-incorrecto-en-config)
11. [BAJO — XadesEpesSigner: UUID No Estándar](#10-bajo--xadesepessigner-uuid-no-estándar)
12. [Resultados de Pruebas Sandbox](#resultados-de-pruebas-sandbox)
13. [Plan de Remediación](#plan-de-remediación)

---

## Resumen Ejecutivo

| Severidad | Hallazgos | Descripción |
|-----------|-----------|-------------|
| 🔴 CRÍTICO | 2 | Firma .p12 incompatible con OpenSSL 3.x + servicio placeholder |
| 🟠 ALTO | 2 | Password sin encriptar + tipo incorrecto pasado al firmador |
| 🟡 MEDIO | 3 | Duplicidad XML builder, Guzzle directo, sin DI |
| 🟢 BAJO | 3 | RateLimiter no atómico, TTL incorrecto, UUID no estándar |
| **Total** | **10** | |

### Validación de Sandbox (17 abr 2026)
- **OAuth 2.0:** ✅ Funcional — Token obtenido exitosamente (HTTP 200)
- **Certificado .p12:** ❌ Falla con OpenSSL 3.5.0 — Algoritmo legacy `RC2-40-CBC`
- **Certificado convertido:** ✅ Firma RSA-SHA256 + verificación exitosa

---

## 1. CRÍTICO — Firma Digital No Funciona con OpenSSL 3.x

### Problema

El archivo `.p12` proporcionado por Hacienda CR (sandbox) utiliza algoritmos de cifrado legacy:

```
PKCS7 Encrypted data: pbeWithSHA1And40BitRC2-CBC
Shrouded Keybag: pbeWithSHA1And3-KeyTripleDES-CBC
```

**OpenSSL 3.x** (incluido en PHP 8.4.11) deshabilitó estos algoritmos por defecto:

```
error:0308010C:digital envelope routines::unsupported
Algorithm (RC2-40-CBC : 0)
```

### Evidencia

```bash
$ php -r 'echo OPENSSL_VERSION_TEXT;'
# OpenSSL 3.5.0 8 Apr 2025

$ openssl pkcs12 -in 010647095827.p12 -info -nokeys -nocerts -passin pass:4959
# Error: RC2-40-CBC unsupported
```

**Conversión a formato moderno FUNCIONA:**
```bash
$ openssl pkcs12 -in 010647095827.p12 -out temp.pem -legacy -passin pass:4959 -passout pass:4959
$ openssl pkcs12 -export -in temp.pem -out modern.p12 -passin pass:4959 -passout pass:4959
$ php test.php  # P12 read: OK, Sign: OK, Verify: VALID
```

### Impacto
- **FirmaDigitalService::leerCertificadoP12()** lanza `HaciendaException::certificadoPasswordError()`
- **Los tests E2E de sandbox (`HaciendaSandboxE2ETest`) se saltan** o fallan en firma
- **Ningún comprobante puede ser firmado ni enviado a Hacienda**

### Solución Recomendada

Agregar auto-conversión de `.p12` legacy en `FirmaDigitalService::leerCertificadoP12()`:

```php
protected function leerCertificadoP12(): void
{
    $p12Content = file_get_contents($rutaCompleta);
    $password = $this->desencriptarPassword();
    
    $certs = [];
    $success = openssl_pkcs12_read($p12Content, $certs, $password);
    
    // Si falla, intentar conversión legacy → moderno via CLI
    if (!$success && str_contains(openssl_error_string() ?? '', 'unsupported')) {
        Log::warning('Certificado .p12 usa algoritmos legacy, convirtiendo...');
        $certs = $this->convertirP12Legacy($rutaCompleta, $password);
        $success = !empty($certs);
    }
    
    if (!$success) {
        throw HaciendaException::certificadoPasswordError(...);
    }
    // ... resto del código
}

protected function convertirP12Legacy(string $path, string $password): array
{
    $tempPem = tempnam(sys_get_temp_dir(), 'cert_');
    $tempP12 = tempnam(sys_get_temp_dir(), 'p12_');
    
    try {
        // Paso 1: .p12 legacy → PEM (requiere que openssl CLI tenga -legacy)
        $cmd1 = sprintf(
            'openssl pkcs12 -in %s -out %s -legacy -passin pass:%s -passout pass:%s 2>&1',
            escapeshellarg($path),
            escapeshellarg($tempPem),
            escapeshellarg($password),
            escapeshellarg($password)
        );
        exec($cmd1, $output1, $rc1);
        
        if ($rc1 !== 0) {
            throw new \RuntimeException('No se pudo convertir .p12 legacy: ' . implode("\n", $output1));
        }
        
        // Paso 2: PEM → .p12 moderno
        $cmd2 = sprintf(
            'openssl pkcs12 -export -in %s -out %s -passin pass:%s -passout pass:%s 2>&1',
            escapeshellarg($tempPem),
            escapeshellarg($tempP12),
            escapeshellarg($password),
            escapeshellarg($password)
        );
        exec($cmd2, $output2, $rc2);
        
        if ($rc2 !== 0) {
            throw new \RuntimeException('No se pudo re-exportar .p12: ' . implode("\n", $output2));
        }
        
        // Leer .p12 moderno
        $certs = [];
        openssl_pkcs12_read(file_get_contents($tempP12), $certs, $password);
        
        // Opcional: guardar .p12 moderno para futuras lecturas
        copy($tempP12, $path . '.modern.p12');
        
        return $certs;
    } finally {
        @unlink($tempPem);
        @unlink($tempP12);
    }
}
```

**Alternativa para Docker/producción:** Agregar script de conversión en el build pipeline o documentar que el usuario debe convertir su .p12 antes de subirlo.

---

## 2. CRÍTICO — HaciendaIntegrationService es Código Muerto (Placeholder)

### Problema

`HaciendaIntegrationService` contiene múltiples problemas graves que lo hacen **completamente inusable en producción**:

#### 2.1 `buildXADESSignature()` es un PLACEHOLDER que NO firma nada

```php
// Línea ~480 de HaciendaIntegrationService.php
protected static function buildXADESSignature($xmlContent, $certificate, $privateKey): ?string
{
    // Para esta demostración, simplemente envolveremos el XML
    return $xmlContent; // ← RETORNA EL XML SIN FIRMAR
}
```

#### 2.2 `signWithXADES()` usa `openssl_x509_read()` en archivo .p12

```php
// Intenta leer un .p12 como si fuera PEM — esto SIEMPRE falla
$certificate = openssl_x509_read(file_get_contents($certificatePath));
$pkeyid = openssl_pkey_get_private(file_get_contents($certificatePath), $certificatePassword);
```

Un archivo `.p12` no es un certificado PEM. Debe leerse con `openssl_pkcs12_read()`.

#### 2.3 `buildXml()` genera XML crudo con concatenación de strings

```php
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= "<{$rootElement} xmlns=\"https://www.hacienda.go.cr/docs/esquemas/2016/v4.4\">\n";
$xml .= "  <Ambiente>{$ambiente}</Ambiente>\n";
// ... más concatenación manual sin escape XML
```

Esto es **vulnerable a XSS/inyección XML** y **no cumple con el XSD v4.4** (namespace incorrecto, faltan campos obligatorios como `ProveedorSistemas`, `CodigoActividadEmisor`, `DetalleServicio`, etc.).

#### 2.4 `sendToHacienda()` usa URL inexistente

```php
$apiUrl = config("hacienda.api_urls.{$environment}.send");
// ↑ No existe 'send' en la config — solo existe 'recepcion'
```

#### 2.5 `getStatus()` usa URL inexistente

```php
$apiUrl = config("hacienda.api_urls.{$environment}.get-status");
// ↑ No existe 'get-status' en la config
```

#### 2.6 `generateClave()` NO sigue el formato oficial de 50 dígitos

La clave generada por este método tiene ~22 caracteres, NO los 50 requeridos por Hacienda. Además, existe `ClaveNumericaGenerator` que SÍ genera la clave correctamente.

#### 2.7 Todos los métodos son `static` (anti-patrón para DI/testing)

### Impacto

Este servicio **no puede funcionar para ningún caso de uso**. Sin embargo, ya existen servicios funcionales que hacen lo mismo correctamente:

| Funcionalidad | HaciendaIntegrationService ❌ | Servicio Correcto ✅ |
|---|---|---|
| Generar XML | `buildXml()` — concatenación cruda | `XmlComprobanteBuilder::build()` — DOMDocument v4.4 |
| Firmar XML | `buildXADESSignature()` — placeholder | `XadesEpesSigner::sign()` — XAdES-EPES completo |
| Enviar a Hacienda | `sendToHacienda()` — URL incorrecta | `HaciendaApiClient::enviarComprobante()` — OAuth + retry |
| Consultar estado | `getStatus()` — URL incorrecta | `HaciendaApiClient::consultarEstado()` |
| Generar clave | `generateClave()` — formato incorrecto | `ClaveNumericaGenerator::generar()` — 50 dígitos |
| Gestionar certificado | `openssl_x509_read()` en .p12 | `FirmaDigitalService::cargarCertificado()` — PKCS12 |

### Solución Recomendada

**Eliminar `HaciendaIntegrationService`** completamente y usar los servicios dedicados. Si hay controllers que lo referencian, migrarlos al flujo correcto:

```
ClaveNumericaGenerator → XmlComprobanteBuilder → FirmaDigitalService/XadesEpesSigner → HaciendaApiClient
```

---

## 3. ALTO — FirmaDigitalService: Password en Base64 Sin Encriptar

### Problema

```php
// FirmaDigitalService.php, línea ~270
protected function desencriptarPassword(): string
{
    // Por ahora usamos base64, pero deberías usar encriptación real (Laravel Crypt)
    // $password = Crypt::decryptString($this->certificado->password_encrypted);
    
    // Temporal: asumimos que está en base64
    $password = base64_decode($this->certificado->password_encrypted);
    // ...
}
```

**Base64 NO es encriptación.** Si la base de datos es comprometida, las contraseñas de los certificados quedan expuestas.

### Solución Recomendada

```php
use Illuminate\Support\Facades\Crypt;

protected function desencriptarPassword(): string
{
    if (!$this->certificado->password_encrypted) {
        throw HaciendaException::certificadoPasswordError('...');
    }
    
    return Crypt::decryptString($this->certificado->password_encrypted);
}
```

Y al guardar: `Crypt::encryptString($pin)`.

---

## 4. ALTO — FirmaDigitalService Pasa Tipo Incorrecto a XadesEpesSigner

### Problema

```php
// FirmaDigitalService.php, línea ~99
$this->privateKey = $certs['pkey']; // ← string PEM

// Línea ~83
$xmlFirmado = $this->xadesSigner->sign($xmlString, $this->privateKey, $this->certData['cert']);
//                                                  ^^^^^^^^^^^^^^
// $this->privateKey es: string (PEM) ← CORRECTO para XadesEpesSigner

// PERO la declaración de propiedad dice:
protected \OpenSSLAsymmetricKey|false|null $privateKey = null;
// ↑ El tipo declarado dice OpenSSLAsymmetricKey, pero se almacena un string PEM
```

Cuando `openssl_pkcs12_read()` devuelve `$certs['pkey']`, el valor es **un string PEM**, no un `OpenSSLAsymmetricKey`. La declaración de tipo es incorrecta.

### Además: `firmarLegacy()` pasa el string PEM a `XMLSecurityKey::loadKey()`

```php
$objKey->loadKey($this->privateKey);
// Si $this->privateKey es un string PEM, esto funciona
// Pero si fuera un OpenSSLAsymmetricKey, XMLSecurityKey no lo aceptaría
```

### Solución Recomendada

Corregir la declaración de tipo:

```php
protected string|null $privateKey = null;
```

---

## 5. MEDIO — Duplicidad de Constructores XML

### Problema

Existen **dos** constructores de XML completamente separados:

1. **`XmlComprobanteBuilder`** (1,460 líneas) — DOMDocument, v4.4 completo, 38 brechas
2. **`HaciendaIntegrationService::buildXml()`** (~50 líneas) — Concatenación cruda, incompleto

El segundo es código muerto que nunca debería haber sido mergeado. Ver hallazgo #2.

---

## 6. MEDIO — OAuthTokenManager Usa Guzzle Directo en vez de Laravel HTTP

### Problema

```php
use GuzzleHttp\Client;

$this->client = new Client([
    'timeout' => 30,
    'verify' => true,
    'http_errors' => false,
]);
```

Laravel proporciona `Http::` facade que envuelve Guzzle con:
- Fake/mock para tests (`Http::fake()`)
- Reintentos con `retry()`
- Logging automático con events
- Macro support
- Connection pooling

### Impacto
- Los tests E2E deben hacer requests reales o mockear Guzzle manualmente
- No se beneficia del ecosistema Laravel

### Solución Recomendada

Migrar a `Http::withOptions([...])->post(...)`.

---

## 7. MEDIO — HaciendaApiClient No Usa Inyección de Dependencias

### Problema

```php
public function __construct(?string $ambiente = null)
{
    // ...
    $this->tokenManager = new OAuthTokenManager($this->ambiente);
    $this->rateLimiter = new RateLimiter();
}
```

Instanciar dependencias directamente dentro del constructor dificulta testing y no sigue el service layer pattern del resto del proyecto.

### Solución Recomendada

```php
public function __construct(
    OAuthTokenManager $tokenManager,
    RateLimiter $rateLimiter,
    ?string $ambiente = null
) { ... }
```

Y registrar en el ServiceProvider.

---

## 8. BAJO — RateLimiter No Es Atómico

### Problema

```php
// RateLimiter.php
$current = (int) Cache::get($cacheKey, 0);
Cache::put($cacheKey, $current + 1, $ttl);
```

Si hay N workers de Horizon procesando facturas concurrentemente, hay una race condition donde dos workers pueden leer `current=5` simultáneamente, ambos incrementar a 6, y el verdadero valor debería ser 7.

### Solución Recomendada

Usar `Cache::increment()` que es atómico en Redis:

```php
Cache::increment($cacheKey);
Cache::put($cacheKey, Cache::get($cacheKey, 0), $ttl); // Solo para TTL
```

O mejor aún, usar `Redis::pipeline()` con `INCR` + `EXPIRE`.

---

## 9. BAJO — Token OAuth TTL Incorrecto en Config

### Problema

```php
// config/hacienda.php
'cache' => [
    'token_ttl' => env('HACIENDA_CACHE_TOKEN_TTL', 3600), // 1 hora
```

Pero la respuesta real del sandbox muestra `"expires_in": 300` (5 minutos). El valor por defecto de 3600s en config no se usa directamente, pero puede confundir.

El `OAuthTokenManager` sí usa `$data['expires_in']` del response, así que no es un bug funcional, pero la documentación y el valor por defecto son engañosos.

---

## 10. BAJO — XadesEpesSigner UUID No Estándar

### Problema

```php
protected function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    // Produce: "550e8400e29b41d4a716446655440000" (sin guiones)
}
```

Los UUIDs estándar (RFC 4122) llevan guiones: `550e8400-e29b-41d4-a716-446655440000`. Laravel tiene `Str::uuid()` que lo genera correctamente. Aunque funcional para IDs de firma, no cumple estrictamente con el formato UUID.

---

## Resultados de Pruebas Sandbox

### Prueba realizada: 17 abril 2026

#### OAuth 2.0
```
Endpoint: https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token
Resultado: HTTP 200 OK ✅
access_token: eyJhbG... (JWT válido)
expires_in: 300 (5 minutos)
refresh_expires_in: 1200 (20 minutos)
Nombre en token: SENSELAB ADMINISTRADOR
```

**Nota importante para el password:** Debe enviarse con `--data-urlencode` o equivalente, ya que contiene `|`, `:`, `$`, `%`, `?`, `+`. El `OAuthTokenManager` usa `form_params` de Guzzle que hace URL-encoding automático — esto es correcto.

#### Certificado .p12
```
Archivo: storage/app/certificates/sandbox/010647095827.p12
PIN: 4959
Algoritmos: pbeWithSHA1And40BitRC2-CBC (LEGACY) ❌ con OpenSSL 3.x
Titular: SENSELAB ADMINISTRADOR
Emisor: CA PERSONA FISICA - SANDBOX
Válido: 2025-07-22 hasta 2029-07-21
Serial: 1753222813512
```

#### Firma Digital (con .p12 convertido)
```
P12 moderno: storage/app/certificates/sandbox/010647095827_modern.p12
openssl_pkcs12_read: OK ✅
openssl_sign (RSA-SHA256): OK ✅
openssl_verify: VALID ✅
Firma: 344 chars base64
```

---

## Plan de Remediación

### Prioridad 1 — Sprint inmediato (estimación: 1-2 días)

| # | Hallazgo | Acción | Archivos |
|---|----------|--------|----------|
| H-1 | P12 legacy + OpenSSL 3.x | Agregar auto-conversión en `FirmaDigitalService` + documentar en SETUP | `FirmaDigitalService.php`, `FACTURACION_ELECTRONICA_SETUP.md` |
| H-2 | HaciendaIntegrationService muerto | Eliminar o deprecar con `@deprecated` y vaciar | `HaciendaIntegrationService.php`, rutas que lo usen |
| H-3 | Password en base64 | Migrar a `Crypt::encryptString()` / `Crypt::decryptString()` | `FirmaDigitalService.php` |
| H-4 | Tipo declarado incorrecto | Cambiar `OpenSSLAsymmetricKey` a `string` | `FirmaDigitalService.php` |

### Prioridad 2 — Siguiente sprint

| # | Hallazgo | Acción | Archivos |
|---|----------|--------|----------|
| H-6 | Guzzle directo en OAuth | Migrar a `Http::` facade | `OAuthTokenManager.php` |
| H-7 | Sin DI en ApiClient | Refactorizar constructor + ServiceProvider | `HaciendaApiClient.php` |
| H-8 | RateLimiter no atómico | Usar `Cache::increment()` | `RateLimiter.php` |

### Prioridad 3 — Backlog

| # | Hallazgo | Acción |
|---|----------|--------|
| H-9 | Token TTL confuso | Actualizar comentario en config |
| H-10 | UUID sin guiones | Migrar a `Str::uuid()` |

---

## Archivos Analizados

| Archivo | Líneas | Hallazgos |
|---------|--------|-----------|
| `app/Services/Hacienda/Xml/XadesEpesSigner.php` | ~650 | H-10 |
| `app/Services/Hacienda/Xml/FirmaDigitalService.php` | ~370 | H-1, H-3, H-4 |
| `app/Services/Hacienda/Xml/XmlComprobanteBuilder.php` | ~1460 | Limpio ✅ |
| `app/Services/Hacienda/HaciendaIntegrationService.php` | ~510 | H-2 (completo) |
| `app/Services/Hacienda/HaciendaApiClient.php` | ~300 | H-7 |
| `app/Services/Hacienda/OAuthTokenManager.php` | ~400 | H-6 |
| `app/Services/Hacienda/RateLimiter.php` | ~200 | H-8 |
| `app/Services/Hacienda/ClaveNumericaGenerator.php` | ~200 | Limpio ✅ |
| `config/hacienda.php` | ~305 | H-9 |
| `tests/Feature/Hacienda/HaciendaSandboxE2ETest.php` | ~830 | Limpio ✅ |
| `tests/Unit/Services/XadesEpesSignerTest.php` | ~200 | Limpio ✅ |

---

## Conclusión

El módulo de Hacienda tiene **dos capas implementadas**:

1. **Capa funcional (servicios dedicados):** `XmlComprobanteBuilder`, `XadesEpesSigner`, `ClaveNumericaGenerator`, `HaciendaApiClient`, `OAuthTokenManager` — Código de calidad, tests completos, v4.4 compliance.

2. **Capa legacy/placeholder:** `HaciendaIntegrationService` — Código crudo, XML concatenado, firma placeholder, URLs incorrectas. **Debe eliminarse.**

El bloqueante principal es la **incompatibilidad del .p12 con OpenSSL 3.x**, que afecta tanto al código como a los tests E2E del sandbox. La solución es implementar auto-conversión + documentar el workaround manual.
