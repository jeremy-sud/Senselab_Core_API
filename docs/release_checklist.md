# Checklist Pre-Release — SENSELAB‑CAST API

**Última actualización:** 23 de abril de 2026  
**Versión actual:** v5.0.2 (Post-auditoría — Roadmap 100%)  
**Versión objetivo producción:** v5.0.2 → api.senselab.com  
**Referencia:** Ver [ROADMAP.md](../ROADMAP.md) para detalle de cada FASE  

---

## ⛔ ANÁLISIS DE INFRAESTRUCTURA — senselab.com (23 abr 2026)

> Análisis realizado vía FTP (65.181.111.240) el 23 de abril de 2026.
> **RESULTADO: Deploy de la API en este servidor NO es viable en el estado actual.** Ver detalles abajo.

### 🔴 CRÍTICO — Servidor comprometido con malware activo

Se detectaron los siguientes archivos maliciosos en `public_html/`:

| Archivo | Tipo | Descripción |
|---|---|---|
| `m.php` | **Dropper** | Descarga payload de un C2 remoto, sobreescribe `index.php`, lo marca `chmod 0444` (inmutable), luego se autodestruye con `unlink(__FILE__)` |
| `hgfr9612.php` | **Payload** (31 KB) | Archivo descargado por `m.php` desde servidor C2 externo |
| `s.php` | **Backdoor disfrazada** | Se camufla como plugin WP "Advanced Custom Shortcodes v4.2.1". Contiene código ofuscado que conecta a `z60402_2.lstark.shop` (servidor C2) para exfiltrar datos |
| `index.php` | **Modificado** | Reemplazado por el dropper, marcado de solo lectura (`0444`) |

**Evidencia en `error_log`:**
```
[24-Apr-2026 01:01:41 UTC] PHP Fatal error: Call to undefined function
register_activation_hook() in /home/senselabcr/public_html/s.php on line 72
```
El malware estuvo activo el 24 de abril a las 01:01 UTC.

**Acciones inmediatas requeridas antes de cualquier deploy:**
- [ ] **Cambiar TODAS las contraseñas** desde cPanel (FTP, WordPress, DB) — las actuales deben considerarse comprometidas.
- [ ] Eliminar los archivos maliciosos: `m.php`, `hgfr9612.php`, `s.php`
- [ ] Restaurar `index.php` desde un backup limpio (permisos actuales: `0444`, bloqueado por malware)
- [ ] Revisar `wp-content/plugins/` y `wp-content/themes/` por backdoors adicionales
- [ ] Revisar `.htaccess` (modificado el 23 abr 18:35 UTC, posibles redirects maliciosos)
- [ ] Notificar al proveedor de hosting (Hostinger/cPanel) del compromiso
- [ ] Verificar si otros sitios en la misma cuenta `senselabcr` fueron afectados
- [ ] Después de limpiar, hacer escaneo completo con Wordfence o similar

---

### 🔴 BLOQUEANTE — Tipo de hosting incompatible con Laravel 12 + Docker

El servidor `senselab.com` es **hosting compartido cPanel**, lo cual es **incompatible** con los requisitos de esta API:

| Requisito de la API | Hosting compartido senselab.com | Estado |
|---|---|---|
| Docker + Docker Compose | ❌ No disponible en shared hosting | BLOQUEANTE |
| Redis 7 (colas Horizon, caché) | ❌ No disponible | BLOQUEANTE |
| MySQL 8.0 con réplicas | ⚠️ MySQL disponible pero sin réplicas ni root | LIMITANTE |
| PHP 8.4-FPM con extensiones | ⚠️ Versión PHP no confirmada en cPanel | LIMITANTE |
| SSH / `php artisan migrate` | ⚠️ SSH puede estar restringido en shared hosting | LIMITANTE |
| Laravel Horizon (supervisor) | ❌ Requiere proceso supervisor permanente | BLOQUEANTE |
| Configuración Nginx personalizada | ❌ Nginx/Apache gestionado por cPanel, sin control total | BLOQUEANTE |

**Estructura actual del servidor:**
```
/home/senselabcr/public_html/     ← WordPress (senselab.com)
  ├── wp-config.php             ← DB host: 159.89.191.229 (externo)
  ├── wp-admin/                 ← WordPress admin
  ├── wp-content/               ← Plugins, temas
  └── [archivos malware]        ← m.php, s.php, hgfr9612.php
```

---

### ✅ SOLUCIÓN RECOMENDADA — Separar WordPress de la API

La arquitectura correcta es mantener los dos servicios en servidores separados:

```
┌──────────────────────────────────┐     ┌─────────────────────────────────────┐
│  senselab.com (hosting actual)      │     │  api.senselab.com (VPS nuevo)           │
│  Shared hosting cPanel           │     │  Ubuntu 22.04 + Docker               │
│  ─────────────────────────────   │     │  ───────────────────────────────────  │
│  WordPress (sitio web)           │────▶│  Laravel 12 API (esta API)            │
│  PHP (versión cPanel)            │     │  PHP 8.4-FPM + Nginx + MySQL + Redis  │
│  BD WordPress: db_hiafqqfmjm     │     │  Laravel Horizon + Docker Compose     │
└──────────────────────────────────┘     └─────────────────────────────────────┘
```

**Opciones de VPS recomendadas:**

| Proveedor | Plan mínimo | Precio aprox. | URL |
|---|---|---|---|
| DigitalOcean | 2 GB RAM Droplet | ~$12/mes | digitalocean.com |
| Linode (Akamai) | 2 GB Nanode | ~$12/mes | linode.com |
| Vultr | 2 GB Cloud Compute | ~$12/mes | vultr.com |
| Hostinger VPS | 2 GB VPS | ~$8/mes | hostinger.com |

**Pasos para el subdominio:**
1. Crear VPS con Ubuntu 22.04
2. Agregar registro DNS: `api.senselab.com` → IP del nuevo VPS  
   _(esto se hace en el panel DNS del hosting actual de senselab.com, en cPanel > Zone Editor)_
3. Seguir la Sección 0 de este checklist para el deploy

---

## Estado de Pre-requisitos de Producción

> Resumen rápido de las fases del ROADMAP que impactan directamente la preparación para producción.

| Requisito | FASE ROADMAP | Estado | Notas |
|-----------|-------------|--------|-------|
| PHPStan Level 8, 0 errores | FASE 4, 10 | ✅ Completado | Baseline vacío |
| Seguridad pre-producción | FASE 17 | ✅ Completado | Swagger auth, rate limiters, FormRequest max |
| Correcciones auditoría crítica | FASE 14.5 | ✅ Completado | N+1, cache tenant, $e->getMessage() |
| Excepciones dominio + ApiResponse | FASE 15 | ✅ Completado | 9 excepciones tipadas, envelope unificado |
| Service Layer completo | FASE 16 | ✅ Completado | BaseService + 22 servicios + 38 DTOs |
| Seeders producción separados | FASE 18.5 | ✅ Completado | MasterDataSeeder (14 catálogos) vs DemoDataSeeder |
| CI pipelines auditados | FASE 19.4 | ✅ Completado | 7 workflows, Codecov, PHPStan L8 |
| Contract testing (Pact) | FASE 19.2 | ✅ Completado | 22 consumer tests en 6 suites |
| Mutation testing (Infection) | FASE 19.3 | ✅ Completado | MSI ≥50%, covered MSI ≥70% |
| Load testing (k6) | FASE 19.1 | ✅ Completado | 3 scripts, 4 escenarios |
| Migration rollback verificado | FASE 18.5 | ✅ Completado | 100 migraciones up/down probadas |
| E2E Hacienda sandbox | FASE 19.6 | ✅ Completado | 8 E2E tests (OAuth, XML, firma, envío, consulta) |
| API versionado | FASE 18 | ✅ Completado | v1/v2 prefijos, header Sunset → v4.0.0 |
| Hacienda v4.4 Compliance | Fase A+B+C | ✅ Completado | 38/38 brechas resueltas (100%) |
| Webhooks Event-Driven | FASE 20 | ✅ Completado | 5 eventos, HMAC-SHA256, cola dedicada → v4.2.0 |

### Bloqueantes para producción

- [x] **FASE 19.6:** Test suite E2E contra sandbox de Hacienda completado.
- [x] **FASE 18:** API versionado implementado (v4.0.0).
- [x] **Hacienda v4.4:** 38/38 brechas resueltas.
- [ ] Ejecutar todas las validaciones de este checklist
- [ ] Aprobación final del equipo

---

## 0. Infraestructura — Deploy en api.senselab.com

> Esta sección cubre los pasos **únicos del primer deploy** en el servidor de senselab.com.
> Los deploys posteriores se gestionan automáticamente vía GitHub Actions (ver sección 12).

### 0.1 Servidor (VPS / Cloud)

- [ ] Contratar servidor con mínimo **2 GB RAM** (recomendado 4 GB), Ubuntu 22.04 LTS.
- [ ] Instalar Docker + Docker Compose en el servidor:
  ```bash
  curl -fsSL https://get.docker.com | sh
  sudo usermod -aG docker $USER
  ```
- [ ] Crear directorio de trabajo y clonar el repositorio:
  ```bash
  mkdir -p /var/www/senselab-core-api
  git clone git@github.com:SenseLab-dev/Senselab_Core_API.git /var/www/senselab-core-api
  ```
- [ ] Crear directorio de backups con permisos adecuados:
  ```bash
  mkdir -p /backups && chmod 700 /backups
  ```

### 0.2 DNS y SSL

- [ ] Crear registro DNS tipo **A**: `api.senselab.com` → IP del servidor.
- [ ] Esperar propagación DNS (puede tardar hasta 24h — verificar con `dig api.senselab.com`).
- [ ] Instalar Certbot y obtener certificado Let's Encrypt:
  ```bash
  sudo apt install certbot python3-certbot-nginx
  sudo certbot --nginx -d api.senselab.com
  # Configura renovación automática:
  sudo systemctl enable certbot.timer
  ```
- [ ] Verificar que el certificado SSL renueva automáticamente:
  ```bash
  sudo certbot renew --dry-run
  ```

### 0.3 Variables de entorno de producción

- [ ] Copiar `.env.example` y completar con valores reales de producción:
  ```bash
  cd /var/www/senselab-core-api
  cp .env.example .env
  ```
- [ ] Valores críticos que **deben** cambiarse del ejemplo:
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://api.senselab.com

  # Base de datos (contenedor Docker interno)
  DB_HOST=mysql
  DB_DATABASE=senselab_core_prod
  DB_USERNAME=senselab_user
  DB_PASSWORD=<contraseña_segura_nueva>
  DB_ROOT_PASSWORD=<root_password_seguro>

  # Redis (contenedor Docker interno)
  REDIS_HOST=redis

  # CORS — solo permitir el frontend de senselab.com
  FRONTEND_URL=https://www.senselab.com

  # Sanctum — dominios estáticos de producción
  SANCTUM_STATEFUL_DOMAINS=www.senselab.com,api.senselab.com

  # Hacienda (credenciales de producción, NO sandbox)
  HACIENDA_ENVIRONMENT=production
  HACIENDA_CERT_PATH=/var/www/senselab-core-api/storage/certs/prod.p12
  HACIENDA_CERT_PASSWORD=<pin_certificado>
  HACIENDA_OAUTH_CLIENT_ID=api-prod

  # Logs
  LOG_LEVEL=error
  LOG_CHANNEL=stack

  # Sentry (opcional pero recomendado)
  SENTRY_LARAVEL_DSN=<dsn_de_sentry>
  ```
- [ ] Generar `APP_KEY` de producción:
  ```bash
  php artisan key:generate
  ```

### 0.4 GitHub Secrets para CI/CD automático

> Configurar en GitHub → Settings → Secrets and variables → Actions

- [ ] `PRODUCTION_SERVER` — IP pública del servidor
- [ ] `PRODUCTION_USER` — usuario SSH (ej: `deploy`)
- [ ] `SSH_PRIVATE_KEY` — llave privada SSH (la pública debe estar en `~/.ssh/authorized_keys` del servidor)
- [ ] `DB_ROOT_PASSWORD` — contraseña root MySQL de producción
- [ ] `SENTRY_LARAVEL_DSN` — DSN de Sentry (si aplica)

### 0.5 Primer deploy manual

- [ ] Ejecutar el deploy inicial desde el servidor (solo la primera vez):
  ```bash
  cd /var/www/senselab-core-api
  docker-compose -f docker-compose.yml up -d
  docker-compose exec php php artisan migrate --force
  docker-compose exec php php artisan db:seed --class=MasterDataSeeder --force
  docker-compose exec php php artisan config:cache
  docker-compose exec php php artisan route:cache
  docker-compose exec php php artisan l5-swagger:generate
  ```
- [ ] Verificar que el health check responde correctamente:
  ```bash
  curl https://api.senselab.com/up
  # Esperado: HTTP 200
  ```
- [ ] A partir de aquí, todos los deploys futuros son automáticos via GitHub Actions al publicar un release.

---

## 1. Secrets y configuración

> **Relacionado:** FASE 17 (✅), auditoría técnica 9 Mar 2026

- [x] **FASE 17:** Verificado: 0 secrets hardcodeados. Todas las API keys usan `env()`.
- [x] `.env` excluido de versionamiento (`.gitignore` verificado).
- [ ] Rotar claves sensibles antes del primer deploy a producción:
  - `APP_KEY` — Regenerar con `php artisan key:generate`
  - Certificado Hacienda `.p12` — Usar llave de producción (no la de pruebas)
  - API keys de Gemini/OpenAI — Rotar post-staging
  - `HACIENDA_OAUTH_PASSWORD` — Credenciales de producción (no sandbox)
- [ ] Limpiar historial git con BFG si se detectan secrets históricos (parcialmente pendiente desde FASE 14.5).
- [ ] Verificar que `.env.example` no contenga valores reales (solo placeholders).
- [ ] Ejecutar auditoría de secrets:

```bash
git grep -nE "password|passwd|secret|token|apikey|api_key|pwd|admin123" -- ':!*.md' ':!*.lock' ':!vendor/' || true
```

---

## 2. Acceso y autenticación

> **Relacionado:** FASE 17 (✅), FASE 14.5 (✅)

- [x] **FASE 14.5:** Contraseñas requieren `Password::min(8)->mixedCase()->numbers()->symbols()`.
- [x] **FASE 14.5:** `$hidden = ['password_hash']` en modelo Usuario.
- [x] **FASE 14.5:** `$e->getMessage()` protegido con `config('app.debug')` en 5 servicios AI.
- [ ] Asegurar `APP_ENV=production` y `APP_DEBUG=false` en `.env` de producción.
- [ ] Confirmar `SANCTUM_STATEFUL_DOMAINS=www.senselab.com,api.senselab.com` en `.env` de producción.
- [ ] Confirmar política de tokens: `login()` revoca tokens previos (`$usuario->tokens()->delete()`).
- [ ] Verificar que logs no registren tokens ni contraseñas (revisar canales `daily` y `sentry`).
- [ ] Confirmar `SESSION_ENCRYPT=true` (default `true` desde FASE 14.5).

---

## 3. Autorización y RBAC

> **Relacionado:** FASE 14 (✅), FASE 18.5 (✅)

- [x] **FASE 14:** 68 permisos en 17 módulos, slugs con formato `-` (e.g., `ver-productos`, `crear-ventas`).
- [x] **FASE 18.5:** Seeders de permisos en `MasterDataSeeder` verificados con `SeederIntegrityTest`.
- [ ] Verificar que `MasterDataSeeder` solo crea roles/permisos de producción (sin roles de prueba).
- [ ] Pre-calentar caché de permisos en deploy:
  ```bash
  php artisan tinker --execute="App::make(\App\Services\PermissionService::class)->warmupPermissionCache();"
  ```
- [ ] Confirmar que `BasePolicy` con `$permission` cubre todos los controladores que requieren autorización.
- [ ] Dual auth layer verificado: route middleware `permission:X` + BasePolicy — tests cubren ambos formatos.

---

## 4. Multitenancy

> **Relacionado:** FASE 14.5 (✅)

- [x] **FASE 14.5:** Cache aislado por tenant — tags incluyen `empresa_{id}` en `HasCacheableQueries` y `ProductoObserver`.
- [ ] Documentar y validar headers esperados (`X-Empresa-Id`) y comportamiento por subdominio.
- [ ] Eliminar tenant/empresa de demo creados por `DemoDataSeeder` en entorno de producción:
  ```bash
  # En producción: usar solo MasterDataSeeder (sin DemoDataSeeder)
  php artisan db:seed --class=MasterDataSeeder --force
  ```
- [ ] Confirmar que jobs en cola son tenant-aware cuando corresponda.
- [ ] Verificar que logs incluyan `empresa_id` para trazabilidad cross-tenant (DT-11 pendiente).

---

## 5. Seguridad de transporte y headers

> **Relacionado:** FASE 17 (✅)

- [x] **FASE 17:** `SecurityHeaders` middleware implementado: CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Permissions-Policy.
- [ ] Forzar HTTPS / HSTS en reverse proxy (nginx/Kubernetes ingress).
- [ ] Ajustar `config/cors.php` — reemplazar `*` por orígenes de producción permitidos:
  ```php
  'allowed_origins' => [env('FRONTEND_URL')],  // https://www.senselab.com
  ```
- [ ] Verificar que los headers de seguridad se aplican correctamente en producción:
  ```bash
  curl -I https://api.senselab.com/up
  # Verificar: X-Content-Type-Options, X-Frame-Options, Strict-Transport-Security, Content-Security-Policy
  ```

---

## 6. Rate limiting y DoS

> **Relacionado:** FASE 17 (✅)

- [x] **FASE 17:** Rate limiters normalizados: `throttle:login` (5/min), `throttle:reports`, `throttle:payment_process`.
- [x] **FASE 17:** 30+ campos FormRequest con `max:` añadido (prevención de payloads excesivos).
- [ ] Ajustar límites para producción si el tráfico esperado lo requiere.
- [ ] Verificar `RateLimitingService::isIPBlocked()` y reglas de bloqueo.
- [ ] Documentar manejo de `429` para clientes (header `Retry-After`).
- [ ] Verificar configuración en `config/rate-limiting.php`.

---

## 7. Facturación Electrónica — Hacienda CR

> **Relacionado:** FASE 19.6 (✅ COMPLETADO) — Ver [ROADMAP.md § FASE 19.6](../ROADMAP.md)  
> **Documentación técnica:** `docs/hacienda/FACTURACION_ELECTRONICA_SETUP.md`

Módulo de Hacienda completamente implementado (v4.4, DGT-R-000-2024). Validación E2E contra sandbox real completada. Hacienda v4.4 al 100% (38/38 brechas).

### 7.1 Infraestructura verificada (✅)

- [x] `OAuthTokenManager` — Flujo password con refresh_token y logout obligatorio.
- [x] `XmlComprobanteBuilder` — XML v4.4 con `ProveedorSistemas`, `CodigoActividadEmisor`, namespaces actualizados.
- [x] `XadesEpesSigner` — Firma XAdES-EPES (ETSI TS 101 903 v1.3.2+, RSA-SHA256).
- [x] `ClaveNumericaGenerator` — Clave de 50 caracteres.
- [x] `HaciendaApiClient` — Rate limiting (8 req/seg, 480 req/min), reintentos automáticos.
- [x] `RateLimiter` — Algoritmo Leaky Bucket dentro de límites oficiales.
- [x] Tests unitarios: 55 tests passing (OAuth, API client, rate limiter, XML, clave numérica).
- [x] Config `hacienda.php` con dual ambiente (sandbox/production).

### 7.2 Configuración de producción (pendiente deploy)

- [ ] **FASE 19.6 — Test suite E2E Hacienda sandbox:**
  1. Obtener credenciales de pruebas en OVi > Tico Factura (formato `cpf-XX-XXXX-XXXX@stag.comprobanteselectronicos.go.cr`).
  2. Generar llave criptográfica de pruebas (.p12 + PIN 4 dígitos) desde OVi > "Credenciales de pruebas".
  3. Crear test E2E que ejecute flujo completo:
     - Auth OAuth 2.0 (`grant_type=password`, `client_id=api-stag`)
     - Generar XML v4.4
     - Firmar XAdES-EPES con llave de pruebas
     - Enviar a sandbox (`api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1`)
     - Consultar estado del comprobante
     - Logout obligatorio (enviar `refresh_token` al endpoint logout)
- [ ] Cambiar `HACIENDA_ENVIRONMENT=production` en `.env` de producción.
- [ ] Configurar URLs de producción:
  ```env
  HACIENDA_OAUTH_URL_PROD=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token
  HACIENDA_LOGOUT_URL_PROD=https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout
  HACIENDA_API_URL_PROD=https://api.comprobanteselectronicos.go.cr/recepcion/v1
  ```
- [ ] Instalar certificado digital de producción (.p12) y configurar `HACIENDA_CERT_PATH` + `HACIENDA_CERT_PASSWORD`.
- [ ] Configurar `HACIENDA_OAUTH_CLIENT_ID=api-prod` y credenciales reales.
- [ ] Configurar `HACIENDA_PROVEEDOR_SISTEMAS` con nombre registrado del proveedor.
- [ ] Verificar que `storage/logs/hacienda/xml/` tiene permisos de escritura y rotación de logs.
- [ ] Confirmar que tokens OAuth se almacenan en BD (`fe_oauth_tokens`) y no en logs.

---

## 8. Documentación y OpenAPI

> **Relacionado:** FASE 17 (✅)

- [x] **FASE 17:** Swagger protegido con `auth:sanctum` en `APP_ENV=production`.
- [ ] Configurar `L5_SWAGGER_GENERATE_ALWAYS=false` en producción.
- [ ] Regenerar specs antes del release:
  ```bash
  php artisan l5-swagger:generate
  ```
- [ ] Verificar que la documentación no expone endpoints internos ni credenciales.
- [ ] Actualizar ejemplos en documentación con placeholders (ver sección 14).

---

## 9. Logs, errores y monitoring

> **Relacionado:** FASE 14.5 (✅), FASE 15 (✅)

- [x] **FASE 15:** Excepciones de dominio tipadas (9 clases) con mapeo a HTTP status codes semánticos.
- [x] **FASE 15:** `ApiResponse` trait: envelope unificado `{success, code, message, data?, errors?, meta?}`.
- [x] **FASE 14.5:** `$e->getMessage()` protegido — no se exponen stack traces en producción.
- [ ] Configurar DSN de Sentry en variables de entorno (`SENTRY_LARAVEL_DSN`).
- [ ] Ajustar logging a nivel `error` en producción (no `debug`):
  ```env
  LOG_LEVEL=error
  LOG_CHANNEL=stack
  ```
- [ ] Verificar que `config/logging.php` no tiene canales que escriban información sensible.
- [ ] Configurar alertas en Sentry para excepciones de dominio críticas (`HaciendaException`, `FacturacionElectronicaException`).

---

## 10. Base de datos y migraciones

> **Relacionado:** FASE 18.5 (✅), FASE 13 (✅)

- [x] **FASE 18.5:** 100 migraciones con rollback verificado (up/down), `MigrationRollbackTest` con 4 tests.
- [x] **FASE 18.5:** Seeders separados: `MasterDataSeeder` (14 catálogos producción) y `DemoDataSeeder` (empresa demo).
- [x] **FASE 18.5:** 4 seeders corregidos a `updateOrInsert()` para idempotencia.
- [x] **FASE 18.5:** Verificado: todos los campos financieros usan `decimal()` (0 `float`/`double`).
- [ ] En producción ejecutar **solo** `MasterDataSeeder` (no `DemoDataSeeder`):
  ```bash
  php artisan db:seed --class=MasterDataSeeder --force
  ```
- [ ] Realizar backup completo antes del primer deploy.
- [ ] Probar script de migración y rollback en staging:
  ```bash
  php artisan migrate --force
  # Si falla:
  php artisan migrate:rollback --step=1 --force
  ```
- [ ] Verificar que no existen seeders con datos sensibles en `database/seeders/`.

---

## 11. Archivos y artefactos

- [x] `.gitignore` verificado: incluye `.env*`, `storage/`, `vendor/`, `node_modules/`.
- [ ] Limpiar `storage/` de archivos temporales, XMLs de prueba en `storage/logs/hacienda/xml/`.
- [ ] Verificar que `.env.example` solo contiene placeholders.
- [ ] Confirmar que certificados `.p12` no están versionados:
  ```bash
  git ls-files | grep -E '\.(p12|pfx|pem|key)$'
  # Resultado esperado: vacío
  ```
- [ ] Verificar permisos de directorios:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```

---

## 12. CI/CD y despliegue

> **Relacionado:** FASE 19.4 (✅)

- [x] **FASE 19.4:** 7 workflows GitHub Actions auditados y corregidos.
- [x] **FASE 19.4:** `tests.yml`: 3 jobs (tests+coverage, code-quality PHPStan L8, security audit).
- [x] **FASE 19.4:** Codecov configurado: 70% proyecto / 80% patch.
- [x] **FASE 19.4:** README badges dinámicos (Codecov, PHPStan, Mutation Testing).
- [ ] Verificar que CI usa secret stores (GitHub Actions secrets) para todas las variables sensibles.
- [ ] Confirmar que `deploy-production.yml` ejecuta:
  1. Tests completos
  2. PHPStan Level 8
  3. Build Docker
  4. Migraciones
  5. Health check post-deploy
- [ ] Documentar y probar rollback:
  ```bash
  # scripts/rollback.sh
  bash scripts/rollback.sh
  ```
- [ ] Verificar healthcheck endpoint responde correctamente:
  ```bash
  curl https://api.senselab.com/up
  # Esperado: HTTP 200
  curl https://api.senselab.com/api/health
  # Esperado: {"status":"ok",...}
  ```

---

## 13. Tests y QA

> **Relacionado:** FASE 14 (✅), FASE 18.5 (✅), FASE 19.1-19.5 (✅)

### Tests automatizados (✅ Infraestructura lista)

- [x] **1261 tests passing**, 0 failing, 0 skipped.
- [x] **PHPStan Level 8**, 0 errores, baseline vacío.
- [x] **FASE 14:** 21 Feature test files, +203 tests (auth, RBAC, multitenancy).
- [x] **FASE 19.2:** Contract testing (Pact) — 22 consumer tests en 6 suites.
- [x] **FASE 19.3:** Mutation testing (Infection) — MSI ≥50%, covered MSI ≥70%.
- [x] **FASE 18.5:** Migration rollback tests — 100 migraciones verificadas.
- [x] **FASE 18.5:** Seeder integrity tests — 7 tests de integridad.

### Pre-release: ejecutar suite completa

- [ ] Ejecutar tests completos:
  ```bash
  php artisan test --parallel
  # O con cobertura:
  make test-coverage
  ```
- [ ] Ejecutar PHPStan:
  ```bash
  php vendor/bin/phpstan analyse app/ --level 8
  ```
- [ ] Ejecutar contract tests:
  ```bash
  make contract-test
  ```
- [ ] Ejecutar mutation testing:
  ```bash
  make mutation-test
  ```
- [ ] Ejecutar load tests contra staging:
  ```bash
  # Smoke test
  k6 run tests/Load/smoke-test.js
  # Load test de endpoints financieros
  k6 run tests/Load/load-ventas-facturacion.js
  # Detección N+1
  k6 run tests/Load/load-n1-detection.js
  ```
- [ ] **FASE 19.6:** Ejecutar E2E Hacienda sandbox (cuando esté implementado).

---

## 14. Ejemplos curl (usar placeholders)

Login:
```bash
curl -X POST https://api.senselab.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"<ADMIN_EMAIL>","password":"<PASSWORD>"}'
```

Request con tenant header y token:
```bash
curl -H "Authorization: Bearer <TOKEN>" \
     -H "X-Empresa-Id: <EMPRESA_ID>" \
     https://api.senselab.com/api/productos
```

Logout:
```bash
curl -X POST https://api.senselab.com/api/logout \
  -H "Authorization: Bearer <TOKEN>" \
  -H "X-Empresa-Id: <EMPRESA_ID>"
```

Health check:
```bash
curl https://api.senselab.com/up
curl https://api.senselab.com/api/health
```

Manejo de `429 Too Many Requests`:
- Leer header `Retry-After` y reintentar respetando ese tiempo.

---

## 15. Operaciones post-deploy

```bash
# 1. Migraciones
php artisan migrate --force

# 2. Seeders de producción (solo catálogos, sin datos demo)
php artisan db:seed --class=MasterDataSeeder --force

# 3. Caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Swagger
php artisan l5-swagger:generate

# 5. Warmup permisos RBAC
php artisan tinker --execute="App::make(\App\Services\PermissionService::class)->warmupPermissionCache();"

# 6. Verificar health
curl https://api.senselab.com/up
curl https://api.senselab.com/api/health
```

---

## 16. Auditoría final (comandos)

```bash
# Buscar secrets en el repo (excluir docs, vendor, lockfiles)
git grep -nE "password|passwd|secret|token|apikey|api_key|pwd|admin123" \
  -- ':!*.md' ':!*.lock' ':!vendor/' ':!pnpm-lock.yaml' || true

# Verificar que no hay certificados versionados
git ls-files | grep -E '\.(p12|pfx|pem|key)$'

# Verificar archivos publicados
ls -la storage/api-docs/

# Verificar .env no está versionado
git ls-files .env

# PHPStan final
php vendor/bin/phpstan analyse app/ --level 8

# Tests finales
php artisan test --parallel
```

---

## 17. Deuda técnica pendiente (no bloqueante)

> Items del ROADMAP que no bloquean producción pero deben planificarse.  
> Ver [ROADMAP.md § Deuda Técnica](../ROADMAP.md) para detalle completo.

| # | Item | Severidad | FASE ROADMAP | Estado |
|---|------|-----------|-------------|--------|
| DT-1 | Timestamps inconsistentes (4 modelos) | 🟡 Medio | Migración independiente | Pendiente |
| ~~DT-2~~ | ~~Observers vacíos (3)~~ | ✅ | ~~FASE 19.7~~ | ✅ Resuelto |
| ~~DT-3~~ | ~~Factories faltantes (GDPR)~~ | ✅ | ~~FASE 19.7~~ | ✅ Resuelto |
| ~~DT-7~~ | ~~`shell_exec()` en HealthCheck~~ | ✅ | ~~v5.0.1~~ | ✅ Resuelto (ya usa `file_get_contents('/proc/uptime')`) |
| ~~DT-8~~ | ~~Distributed tracing (OpenTelemetry)~~ | ✅ | ~~FASE 22~~ | ✅ Resuelto (`TracingMiddleware` + `config/tracing.php`) |
| ~~DT-9~~ | ~~Imports a modelos inexistentes~~ | ✅ | ~~v5.0.1~~ | ✅ Resuelto (import `Comprobante` eliminado, MetricsController corregido) |
| DT-10 | Tests detección N+1 automáticos | 🟢 Bajo | Futuro | Pendiente |
| DT-11 | `tenant_id` en logs | 🟢 Bajo | Futuro | Pendiente |
| ~~DT-12~~ | ~~Respuestas API inconsistentes~~ | ✅ | ~~FASE 15~~ | ✅ Resuelto |

---

## 18. Resumen de fases del ROADMAP vinculadas a producción

| FASE | Descripción | Impacto en producción | Estado |
|------|-------------|----------------------|--------|
| 13 | Cleanup CQRS + Factories | Eliminación de dead code | ✅ v3.0.0 |
| 17 | Seguridad pre-producción | Swagger auth, rate limiters, FormRequest | ✅ v3.0.1 |
| 14 | Tests críticos (+203) | Cobertura de auth, RBAC, multitenancy | ✅ v3.1.0 |
| 14.5 | Correcciones auditoría | N+1, cache tenant, protección mensajes error | ✅ v3.1.1 |
| 15 | Excepciones + ApiResponse | Respuestas estandarizadas, errores semánticos | ✅ v3.2.0 |
| 16 | Service Layer completo | BaseService, 22 servicios, 38 DTOs | ✅ v3.3.0 |
| 18.5 | Seeders + Rollback + k6 | Datos de producción, integridad migraciones | ✅ v3.2.1 |
| 19.1-19.5 | Testing avanzado + CI | Load, contract, mutation testing, CI pipelines | ✅ |
| 19.6 | E2E Hacienda sandbox | Validación facturación electrónica v4.4 | ✅ Completado |
| 18 | API versionado | v1/v2 prefijos, header Sunset | ✅ v4.0.0 |
| 19.7 | PHPStan + DTOs + Deuda técnica | PHPStan 98→0, 60 DTOs, observers eliminados | ✅ v4.1.0 |
| 20 | Webhooks + Event-Driven | 5 eventos, HMAC-SHA256, cola dedicada | ✅ v4.2.0 |
| Hacienda | Compliance v4.4 (Fases A+B+C) | 38/38 brechas, 8 modelos, 49 tests V44 | ✅ 100% |

> **Nota:** Las FASES 20-22 (Webhooks, Reporting, Escalabilidad) son opcionales para el primer release de producción.

---

**Notas finales:**
- Este checklist está vinculado al [ROADMAP.md](../ROADMAP.md) — mantener ambos documentos sincronizados.
- Agregar un paso en CI que ejecute el grep de secrets y falle si encuentra coincidencias.
- Documentar cualquier cambio de última hora en el CHANGELOG.md.
- **URL de producción:** `https://api.senselab.com` — configurada en sección 0 de este checklist.
- **Deploys posteriores:** automáticos al publicar un release en GitHub (ver `.github/workflows/deploy-production.yml`).
- **Rollback de emergencia:** `bash scripts/rollback.sh` desde el servidor o desde GitHub Actions (`workflow_dispatch` en `deploy-production.yml`).