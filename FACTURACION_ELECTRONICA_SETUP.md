# Guía de Configuración - Facturación Electrónica Costa Rica

## Índice
1. [Requisitos Previos](#requisitos-previos)
2. [Configuración Inicial](#configuración-inicial)
3. [Obtener Credenciales OAuth](#obtener-credenciales-oauth)
4. [Certificado Digital](#certificado-digital)
5. [Ambiente de Pruebas (ATV)](#ambiente-de-pruebas-atv)
6. [Configuración de Variables](#configuración-de-variables)
7. [Ejecución de Migraciones](#ejecución-de-migraciones)
8. [Primer Comprobante](#primer-comprobante)
9. [Troubleshooting](#troubleshooting)

---

## Requisitos Previos

### 1. Acceso al Sistema de Hacienda
- Cuenta en el portal de Administración Tributaria Virtual (ATV)
- URL: https://atv.hacienda.go.cr/
- Credenciales de acceso (usuario y contraseña)

### 2. Certificado Digital
- Certificado .p12 emitido por una Autoridad Certificadora autorizada
- Proveedores autorizados en Costa Rica:
  * ECASA (https://www.ecasa.cr/)
  * CAMERFIRMA (https://www.camerfirma.com/)
  * FIRMA DIGITAL (https://www.firmadigital.cr/)
- Costo aproximado: $50-150 USD anuales
- Vigencia: 1-2 años

### 3. Código de Actividad Económica
- Debe estar registrado en Hacienda
- Consultar en: https://www.hacienda.go.cr/
- Formato: 6 dígitos (ejemplo: 620100)

---

## Configuración Inicial

### 1. Clonar el Proyecto
```bash
git clone https://github.com/jeremy-sud/Ursol-CAST-API.git
cd Ursol-CAST-API
```

### 2. Instalar Dependencias
```bash
composer install
npm install
```

### 3. Configurar Entorno
```bash
cp .env.example .env
php artisan key:generate
```

---

## Obtener Credenciales OAuth

### Paso 1: Acceder al Portal de Hacienda
1. Ingresar a https://atv.hacienda.go.cr/
2. Iniciar sesión con sus credenciales
3. Ir a **Servicios > Facturación Electrónica**

### Paso 2: Solicitar Credenciales API
1. Menú: **Configuración > API OAuth**
2. Crear nueva aplicación:
   - Nombre: `Mi Sistema de Facturación`
   - Tipo: `Aplicación de Servidor`
   - URL de Redirección: `https://tu-dominio.com/api/hacienda/callback`
3. Copiar credenciales generadas:
   - **Client ID**: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
   - **Client Secret**: `yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy`

### Paso 3: Solicitar Acceso al Ambiente de Pruebas (ATV)
**Importante**: Hacienda requiere solicitud por correo electrónico.

Enviar correo a: **facturacionelectronica@hacienda.go.cr**

**Asunto**: Solicitud de acceso al ambiente ATV para pruebas

**Cuerpo del correo**:
```
Estimados señores:

Por medio de la presente solicito acceso al ambiente de pruebas ATV 
para desarrollar y probar la integración de facturación electrónica.

Datos de la empresa:
- Razón Social: [Nombre de su empresa]
- Cédula Jurídica: [Número de cédula]
- Representante Legal: [Nombre completo]
- Correo electrónico: [correo@empresa.com]
- Teléfono: [número de contacto]
- Sistema a utilizar: Ursol-CAST-API

Usuario ATV: [su usuario]

Quedo atento a sus instrucciones.

Saludos cordiales,
[Su nombre]
```

**Tiempo de respuesta**: 2-5 días hábiles

---

## Certificado Digital

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

### Configurar Certificado en el Sistema

1. Crear directorio para certificados:
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

## Ambiente de Pruebas (ATV)

### URLs del Sistema

**Ambiente de Pruebas (ATV)**:
- API OAuth: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token`
- API Comprobantes: `https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1`

**Ambiente de Producción**:
- API OAuth: `https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token`
- API Comprobantes: `https://api.comprobanteselectronicos.go.cr/recepcion/v1`

### Diferencias ATV vs Producción

| Característica | ATV (Pruebas) | Producción |
|---------------|---------------|------------|
| Validación XML | Estricta | Estricta |
| Firma digital | Requerida | Requerida |
| Cédulas válidas | Ficticias aceptadas | Solo reales |
| Rate limiting | 100 req/min | 60 req/min |
| Persistencia | 30 días | Permanente |
| Certificados | Prueba o reales | Solo reales |

---

## Configuración de Variables

### Archivo `.env`

```env
# ==========================================
# FACTURACIÓN ELECTRÓNICA - HACIENDA
# ==========================================

# Ambiente: sandbox (ATV) o production
HACIENDA_ENVIRONMENT=sandbox

# Credenciales OAuth (obtenidas del portal ATV)
HACIENDA_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
HACIENDA_CLIENT_SECRET=yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy
HACIENDA_USERNAME=usuario@empresa.com
HACIENDA_PASSWORD=contraseña_atv

# URLs Ambiente Sandbox (ATV)
HACIENDA_OAUTH_URL_SANDBOX=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token
HACIENDA_API_URL_SANDBOX=https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1

# URLs Ambiente Producción (comentadas por defecto)
#HACIENDA_OAUTH_URL_PRODUCTION=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token
#HACIENDA_API_URL_PRODUCTION=https://api.comprobanteselectronicos.go.cr/recepcion/v1

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
config('hacienda.oauth.client_id'); // Debe mostrar su client_id
```

---

## Ejecución de Migraciones

### 1. Revisar Migraciones Pendientes
```bash
php artisan migrate:status
```

### 2. Ejecutar Migraciones de Facturación Electrónica
```bash
php artisan migrate --path=database/migrations/2025_11_26_184030_create_comprobantes_electronicos_fe_table.php
php artisan migrate --path=database/migrations/2025_11_26_184109_create_fe_lineas_detalle_table.php
php artisan migrate --path=database/migrations/2025_11_26_184143_create_fe_certificados_digitales_table.php
php artisan migrate --path=database/migrations/2025_11_26_184216_create_fe_oauth_tokens_table.php
```

### 3. Verificar Tablas Creadas
```bash
php artisan tinker
```

```php
DB::table('comprobantes_electronicos_fe')->count(); // Debe retornar 0
DB::table('fe_certificados_digitales')->count(); // Debe retornar 0
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

### Error: "Invalid client credentials"

**Causa**: Client ID o Secret incorrectos.

**Solución**:
1. Verificar credenciales en `.env`
2. Confirmar que coinciden con las del portal ATV
3. Ejecutar `php artisan config:clear`

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

**Causa**: XML no cumple con XSD v4.3 de Hacienda.

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
**Autor**: Equipo Ursol-CAST-API
