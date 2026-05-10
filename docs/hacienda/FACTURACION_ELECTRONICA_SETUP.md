# Guía de Configuración - Facturación Electrónica Costa Rica

**Versión API:** v5.0.1  
**Esquema Hacienda:** v4.4 (DGT-R-000-2024)  
**Última actualización:** 18 de abril de 2026

## Índice
1. [Requisitos Previos](#requisitos-previos)
2. [Configuración Inicial](#configuración-inicial)
3. [Obtener Credenciales de Pruebas (Sandbox)](#obtener-credenciales-de-pruebas-sandbox)
4. [Llave Criptográfica de Pruebas](#llave-criptográfica-de-pruebas)
5. [Autenticación OAuth 2.0](#autenticación-oauth-20)
6. [Gestión del Token y Sesión](#gestión-del-token-y-sesión)
7. [Endpoints de Conexión](#endpoints-de-conexión)
8. [Certificado Digital (Producción)](#certificado-digital-producción)
9. [Configuración de Variables](#configuración-de-variables)
10. [Ejecución de Migraciones](#ejecución-de-migraciones)
11. [Primer Comprobante](#primer-comprobante)
12. [Troubleshooting](#troubleshooting)

---

## Requisitos Previos

### 1. Acceso a la Oficina Virtual (OVi) del Ministerio de Hacienda
- Cuenta en la Oficina Virtual del Ministerio de Hacienda
- Credenciales de acceso (identificación y contraseña)

### 2. Certificado Digital (para Producción)
- Certificado .p12 emitido por una Autoridad Certificadora autorizada
- Proveedores autorizados en Costa Rica:
  * ECASA (https://www.ecasa.cr/)
  * CAMERFIRMA (https://www.camerfirma.com/)
  * FIRMA DIGITAL (https://www.firmadigital.cr/)
- Costo aproximado: $50-150 USD anuales
- Vigencia: 1-2 años

> **Nota**: Para el ambiente Sandbox (pruebas), Hacienda proporciona una llave criptográfica de pruebas que se genera desde el portal. Ver sección [Llave Criptográfica de Pruebas](#llave-criptográfica-de-pruebas).

> **⚠️ Compatibilidad OpenSSL 3.x**: Los certificados .p12 generados por Hacienda utilizan cifrado legacy (pbeWithSHA1And40BitRC2-CBC) que no es compatible con OpenSSL 3.x. El sistema incluye auto-conversión automática via `FirmaDigitalService::convertirP12Legacy()`. Si experimenta errores al leer el certificado, convierta manualmente:
> ```bash
> openssl pkcs12 -in llave_pruebas.p12 -out temp.pem -nodes -legacy
> openssl pkcs12 -in temp.pem -export -out llave_pruebas_v3.p12
> rm temp.pem
> ```

### 3. Código de Actividad Económica
- Debe estar registrado en Hacienda
- Consultar en: https://www.hacienda.go.cr/
- Formato: 6 dígitos (ejemplo: 620100)

---

## Configuración Inicial

### 1. Clonar el Proyecto
```bash
git clone https://github.com/SenseLab-dev/Senselab_Core_API.git
cd Senselab_Core_API
```

### 2. Instalar Dependencias
```bash
composer install
pnpm install  # Usamos pnpm por seguridad
```

### 3. Configurar Entorno
```bash
cp .env.example .env
php artisan key:generate
```

---

## Obtener Credenciales de Pruebas (Sandbox)

> **Importante**: Las credenciales del entorno Sandbox (Staging) del Ministerio de Hacienda **no son públicas**. Cada contribuyente debe generarlas de forma individual a través de los portales oficiales.

### Paso 1: Acceder a la Oficina Virtual (OVi)
1. Ingresar a la Oficina Virtual del Ministerio de Hacienda con tu identificación y contraseña.

### Paso 2: Módulo Tico Factura
1. Seleccionar el bloque denominado **"Tico Factura"**.

### Paso 3: Generar Credenciales de Pruebas
1. Si no tienes credenciales aún, selecciona la opción **"Necesito un usuario de pruebas"**.
2. El sistema generará automáticamente:
   - **Usuario**: Con formato `cpf-XX-XXXX-XXXX@stag.comprobanteselectronicos.go.cr`
   - **Contraseña**: Cadena de aproximadamente 20 caracteres generada automáticamente
3. Estos datos siempre estarán disponibles para descargar o copiar en la sección **"Mi Perfil" > "Credenciales de pruebas"**.

> **Nota sobre la contraseña**: Si la contraseña contiene símbolos especiales, asegúrate de que estén correctamente codificados (URL encoded) al configurarlos en el sistema para evitar errores de "credenciales inválidas".

---

## Llave Criptográfica de Pruebas

Para firmar los archivos XML en el entorno Sandbox, necesitas una llave criptográfica específica de pruebas:

### Generar la Llave
1. En la Oficina Virtual (OVi), acceder a **"Credenciales de pruebas"**.
2. Seleccionar **"Generar llave criptográfica"**.
3. Definir un **PIN de 4 dígitos** que servirá para proteger la llave.
4. Descargar el archivo `.p12` generado.
5. Instalar/copiar el archivo en tu equipo y configurarlo usando el PIN definido.

### Configurar la Llave en el Sistema
```bash
# Crear directorio para certificados
mkdir -p storage/app/certificates
chmod 755 storage/app/certificates

# Copiar llave criptográfica de pruebas
cp /ruta/al/llave_pruebas.p12 storage/app/certificates/
chmod 600 storage/app/certificates/llave_pruebas.p12
```

Configurar en `.env`:
```env
HACIENDA_CERT_PATH=storage/app/certificates/llave_pruebas.p12
HACIENDA_CERT_PASSWORD=1234  # Tu PIN de 4 dígitos
```

---

## Autenticación OAuth 2.0

Hacienda utiliza **OAuth 2.0 con OpenID Connect** mediante el flujo **Resource Owner Password Credentials** (grant_type=password).

### Parámetros de Autenticación

Para obtener el token de acceso, se envía una petición `POST` al endpoint del Identity Provider (IdP) con los siguientes campos en formato `application/x-www-form-urlencoded`:

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| `grant_type` | `password` | Siempre `password` para obtención inicial del token |
| `client_id` | `api-stag` (Sandbox) / `api-prod` (Producción) | Identificador del cliente según el ambiente |
| `username` | `cpf-XX-XXXX-XXXX@stag...` | Usuario generado en el portal OVi / Tico Factura |
| `password` | *(contraseña generada)* | Contraseña de ~20 caracteres generada en OVi |
| `scope` | *(vacío)* | No requerido por Hacienda |
| `client_secret` | *(vacío)* | No requerido por Hacienda |

### Ejemplo de Petición (cURL)

```bash
curl -X POST \
  "https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password" \
  -d "client_id=api-stag" \
  -d "username=cpf-01-0123-0456@stag.comprobanteselectronicos.go.cr" \
  -d "password=TU_PASSWORD_URL_ENCODED"
```

---

## Gestión del Token y Sesión

### Respuesta del IdP

La respuesta JSON del servidor de autenticación contiene los siguientes campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `access_token` | string (JWT) | Token que se incluye en el header `Authorization` de cada petición a la API |
| `token_type` | string | Normalmente `bearer` |
| `expires_in` | int | Tiempo de vida del access token en segundos (**actualmente 300 = 5 minutos**) |
| `refresh_token` | string | Token para renovar el access token sin reenviar usuario/contraseña |
| `refresh_expires_in` | int | Tiempo de vida del refresh token (**actualmente 36000 = 10 horas**) |

### Ciclo de Vida de la Sesión

```
┌──────────────────────────────────────────────────────────┐
│  1. Autenticación inicial (grant_type=password)          │
│     → Obtiene access_token (5 min) + refresh_token (10h)│
├──────────────────────────────────────────────────────────┤
│  2. Uso del access_token                                 │
│     Header: Authorization: bearer {access_token}         │
│     → Cada petición a la API de comprobantes             │
├──────────────────────────────────────────────────────────┤
│  3. Refresco del token (antes de que expire)             │
│     grant_type=refresh_token + refresh_token={token}     │
│     → Nuevo access_token sin reenviar credenciales       │
├──────────────────────────────────────────────────────────┤
│  4. Logout (al terminar procesos)                        │
│     POST al LOGOUT_URL con refresh_token                 │
│     → OBLIGATORIO para evitar sesiones huérfanas         │
└──────────────────────────────────────────────────────────┘
```

### Header de Autorización

Toda interacción con la API de Recepción de comprobantes debe incluir:

```
Authorization: bearer {access_token}
```

### Logout (Cierre de Sesión)

> **Buena práctica**: El sistema siempre debe realizar un **Logout** al terminar los procesos para evitar actividades maliciosas, enviando el `refresh_token` al `LOGOUT_URL`.

```bash
curl -X POST \
  "https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=api-stag" \
  -d "refresh_token={refresh_token}"
```

---

## Endpoints de Conexión

### URLs por Ambiente

| Dato | Sandbox (Staging) | Producción |
|------|-------------------|------------|
| **Token URL** | `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token` | `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token` |
| **Logout URL** | `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout` | `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout` |
| **API Recepción** | `https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1` | `https://api.comprobanteselectronicos.go.cr/recepcion/v1` |
| **Client ID** | `api-stag` | `api-prod` |

### Diferencias Sandbox vs Producción

| Característica | Sandbox (Staging) | Producción |
|---------------|-------------------|------------|
| Validación XML | Estricta | Estricta |
| Firma digital | Requerida (llave de pruebas) | Requerida (certificado real) |
| Cédulas válidas | Ficticias aceptadas | Solo reales |
| Rate limiting | 100 req/min | 60 req/min |
| Persistencia | 30 días | Permanente |
| Certificados | Llave criptográfica de pruebas | Solo certificados reales |

---

## Certificado Digital (Producción)

### Obtener Certificado .p12

#### Opción 1: ECASA (Recomendado)
1. Visitar https://www.ecasa.cr/
2. Solicitar certificado de firma digital
3. Documentos requeridos:
   - Cédula jurídica
   - Personería jurídica (vigente)
   - Poder del representante legal
4. Pago: Tarjeta o transferencia
5. Emisión: 1-3 días hábiles
6. Descargar archivo `.p12` y guardar contraseña

#### Opción 2: CAMERFIRMA
1. Visitar https://www.camerfirma.com/cr
2. Proceso similar a ECASA
3. Certificados compatibles con Hacienda

### Configurar Certificado de Producción en el Sistema

1. Crear directorio para certificados (si no existe):
```bash
mkdir -p storage/app/certificates
chmod 755 storage/app/certificates
```

2. Copiar certificado:
```bash
cp /ruta/al/certificado.p12 storage/app/certificates/
chmod 600 storage/app/certificates/certificado.p12
```

3. Registrar en base de datos:
```sql
INSERT INTO fe_certificados_digitales (
    empresa_id,
    nombre,
    ruta_archivo,
    password,
    fecha_emision,
    fecha_vencimiento,
    activo
) VALUES (
    1,
    'Certificado Principal 2025',
    'certificates/certificado.p12',
    '{password_encriptado}', -- Ver sección de encriptación
    '2025-01-01',
    '2026-01-01',
    1
);
```

### Encriptar Contraseña del Certificado

```php
// En tinker: php artisan tinker
use Illuminate\Support\Facades\Crypt;

$passwordEncriptado = Crypt::encryptString('contraseña_del_certificado');
echo $passwordEncriptado;
```

**Nota**: Guardar el password encriptado en la BD, NO en texto plano.

---

## Configuración de Variables

### Archivo `.env`

```env
# ==========================================
# FACTURACIÓN ELECTRÓNICA - HACIENDA
# ==========================================

# Ambiente: sandbox (Staging) o production
HACIENDA_ENVIRONMENT=sandbox

# Credenciales OAuth 2.0 (grant_type=password)
# Sandbox: client_id=api-stag | Producción: client_id=api-prod
HACIENDA_OAUTH_CLIENT_ID=api-stag
HACIENDA_OAUTH_USERNAME=cpf-01-0123-0456@stag.comprobanteselectronicos.go.cr
HACIENDA_OAUTH_PASSWORD=tu_contraseña_generada_en_ovi

# URLs Ambiente Sandbox (Staging)
HACIENDA_OAUTH_URL_SANDBOX=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token
HACIENDA_LOGOUT_URL_SANDBOX=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout
HACIENDA_API_URL_SANDBOX=https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1

# URLs Ambiente Producción (comentadas por defecto)
#HACIENDA_OAUTH_URL_PROD=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token
#HACIENDA_LOGOUT_URL_PROD=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout
#HACIENDA_API_URL_PROD=https://api.comprobanteselectronicos.go.cr/recepcion/v1

# Certificado Digital / Llave Criptográfica (.p12)
HACIENDA_CERT_PATH=storage/app/certificates/llave_pruebas.p12
HACIENDA_CERT_PASSWORD=1234

# Rate Limiting
HACIENDA_RATE_LIMIT_REQUESTS=60
HACIENDA_RATE_LIMIT_DECAY_MINUTES=1

# Timeouts (segundos)
HACIENDA_TIMEOUT=30
HACIENDA_RETRY_ATTEMPTS=3

# Logging
HACIENDA_LOG_REQUESTS=true
HACIENDA_SAVE_XML=true
HACIENDA_XML_PATH=hacienda/xml
```

### Verificar Configuración

```bash
php artisan config:cache
php artisan config:clear
php artisan tinker
```

En tinker:
```php
config('hacienda.environment'); // Debe retornar 'sandbox'
config('hacienda.oauth.client_id'); // Debe retornar 'api-stag'
config('hacienda.oauth.grant_type'); // Debe retornar 'password'
config('hacienda.oauth.username'); // Debe mostrar su usuario
```

---

## Ejecución de Migraciones

### 1. Revisar Migraciones Pendientes
```bash
php artisan migrate:status
```

### 2. Ejecutar Migraciones de Facturación Electrónica

**Migraciones base:**
```bash
php artisan migrate --path=database/migrations/2025_11_26_184030_create_comprobantes_electronicos_fe_table.php
php artisan migrate --path=database/migrations/2025_11_26_184109_create_fe_lineas_detalle_table.php
php artisan migrate --path=database/migrations/2025_11_26_184143_create_fe_certificados_digitales_table.php
php artisan migrate --path=database/migrations/2025_11_26_184216_create_fe_oauth_tokens_table.php
```

**Migraciones v4.4 Compliance (8 tablas nuevas):**
```bash
php artisan migrate --path=database/migrations/2026_04_07_000000_hacienda_v44_compliance_full.php
php artisan migrate --path=database/migrations/2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php
```

Estas migraciones crean las siguientes tablas adicionales:
- `fe_linea_impuestos` — Múltiples impuestos por línea (1:N, hasta 1000)
- `fe_medios_pago` — Múltiples medios de pago (1-4)
- `fe_informacion_referencia` — Referencias normalizadas (0-10)
- `fe_otros_cargos` — Otros cargos (0-15)
- `fe_linea_descuentos` — Descuentos múltiples por línea (0-5)
- `fe_codigo_comercial` — Códigos comerciales por línea (0-5)
- `fe_detalle_surtido` — Detalle surtidos/combos (0-20)
- `fe_surtido_impuesto` — Impuestos de ítems de surtido

Además agrega 20+ columnas nuevas a tablas existentes (receptor ubicación, códigos de descuento, campos de exportación, etc.).

### 3. Verificar Tablas Creadas
```bash
php artisan tinker
```

```php
DB::table('comprobantes_electronicos_fe')->count(); // Debe retornar 0
DB::table('fe_certificados_digitales')->count(); // Debe retornar 0
DB::table('fe_linea_impuestos')->count(); // Debe retornar 0 (v4.4)
DB::table('fe_medios_pago')->count(); // Debe retornar 0 (v4.4)
```

---

## Primer Comprobante

### 1. Preparar Datos de Prueba

Crear archivo `test_comprobante.json`:

```json
{
  "tipo_documento": "01",
  "consecutivo": "00000000000000000001",
  "condicion_venta": "01",
  "medio_pago": "01",
  "receptor_nombre": "Cliente de Prueba",
  "receptor_tipo_identificacion": "01",
  "receptor_numero_identificacion": "109876543",
  "receptor_email": "cliente@test.com",
  "certificado_id": 1,
  "lineas": [
    {
      "numero_linea": 1,
      "codigo": "8523102100000",
      "cantidad": 2,
      "unidad_medida": "Sp",
      "detalle": "Producto de Prueba",
      "precio_unitario": 10000,
      "monto_total": 20000,
      "subtotal": 20000,
      "monto_total_linea": 22600,
      "impuestos": [
        {
          "codigo": "01",
          "codigo_tarifa": "08",
          "tarifa": 13.00,
          "monto": 2600
        }
      ]
    }
  ]
}
```

### 2. Enviar Comprobante vía API

```bash
curl -X POST http://localhost:8000/api/comprobantes \
  -H "Authorization: Bearer {tu_token}" \
  -H "Content-Type: application/json" \
  -d @test_comprobante.json
```

### 3. Verificar Respuesta

Respuesta esperada (HTTP 201):
```json
{
  "message": "Comprobante creado y enviado a cola de procesamiento",
  "data": {
    "id": 1,
    "tipo_documento": "01",
    "consecutivo": "00000000000000000001",
    "clave": "52611202531011234567800000000000000000001154489877",
    "estado": "pendiente",
    "lineas_detalle": [...]
  }
}
```

### 4. Consultar Estado

```bash
curl -X GET http://localhost:8000/api/comprobantes/1 \
  -H "Authorization: Bearer {tu_token}"
```

Estados posibles:
- `pendiente` - Creado, esperando envío
- `enviando` - En proceso de envío
- `recibido` - Recibido por Hacienda, en validación
- `procesando` - Hacienda está procesando
- `aceptado` - ✅ Aprobado por Hacienda
- `rechazado` - ❌ Rechazado por Hacienda
- `error` - Error técnico, revisar logs

---

## Troubleshooting

### Error: "Invalid client credentials" o "invalid_grant"

**Causa**: Usuario, contraseña o client_id incorrectos.

**Solución**:
1. Verificar que `HACIENDA_OAUTH_CLIENT_ID` sea `api-stag` (sandbox) o `api-prod` (producción)
2. Verificar que `HACIENDA_OAUTH_USERNAME` y `HACIENDA_OAUTH_PASSWORD` coincidan con los generados en la OVi
3. Asegurar que la contraseña esté correctamente codificada (URL encoded) si contiene símbolos especiales
4. Ejecutar `php artisan config:clear`
5. Consultar las credenciales en la OVi: **"Mi Perfil" > "Credenciales de pruebas"**

### Error: "Certificate not found or expired"

**Causa**: Certificado no existe o venció.

**Solución**:
```sql
-- Verificar certificados
SELECT * FROM fe_certificados_digitales WHERE activo = 1;

-- Verificar fecha de vencimiento
SELECT nombre, fecha_vencimiento 
FROM fe_certificados_digitales 
WHERE fecha_vencimiento > NOW();
```

### Error: "Invalid XML structure"

**Causa**: XML no cumple con XSD v4.4 de Hacienda.

**Solución**:
1. Revisar logs: `storage/logs/laravel.log`
2. Validar XML manualmente: https://www.hacienda.go.cr/validador
3. Verificar campos obligatorios según tipo de documento

### Error: "Rate limit exceeded"

**Causa**: Excedió límite de requests por minuto.

**Solución**:
```env
# Ajustar en .env
HACIENDA_RATE_LIMIT_REQUESTS=30 # Reducir a 30
```

### Jobs no se procesan

**Causa**: Queue worker no está ejecutándose.

**Solución**:
```bash
# Ejecutar worker manualmente
php artisan queue:work --tries=3

# O configurar supervisor (producción)
sudo apt-get install supervisor
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Configuración supervisor:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log
```

### Logs de Depuración

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver logs de Hacienda
tail -f storage/logs/hacienda.log

# Ver XMLs generados
ls -lah storage/app/hacienda/xml/
```

---

## Contactos de Soporte

### Ministerio de Hacienda
- **Correo**: facturacionelectronica@hacienda.go.cr
- **Teléfono**: +506 2539-4700
- **Horario**: Lunes a Viernes, 8:00 AM - 4:00 PM

### Portal ATV
- **URL**: https://atv.hacienda.go.cr/
- **Manuales**: https://www.hacienda.go.cr/contenido/13329-documentos-tecnicos

### Foro de Desarrolladores
- **URL**: https://tribunet.hacienda.go.cr/foro

---

## Referencias

- [Documentación Técnica Oficial](https://www.hacienda.go.cr/contenido/13329-documentos-tecnicos)
- [Especificación XML v4.3](https://www.hacienda.go.cr/docs/esquemas/2016/v4.3/FacturaElectronica_V4.3.xsd)
- [Códigos de Impuestos](https://www.hacienda.go.cr/docs/tablas/IMPUESTOS.pdf)
- [Catálogo CABYS](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/frmConsultaCabys.aspx)

---

**Última actualización**: 26 de noviembre de 2025
**Versión del sistema**: 1.0.0
**Autor**: Equipo Senselab_Core_API
