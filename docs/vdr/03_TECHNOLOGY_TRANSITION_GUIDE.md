# Guía de Transición Tecnológica — Ursol CAST API v5.0.1

## De "Comprar los Archivos" a "Sistema en Producción" en 48 Horas

**Documento Confidencial — Sistemas Ursol S.A.**  
**Fecha:** 15 de Abril 2026  
**Dirigido a:** Equipo técnico del comprador  
**Pre-requisito:** Firma del contrato de transferencia de IP

---

## Tabla de Contenidos

- [Fase 0: Pre-Entrega (Hora 0)](#fase-0-pre-entrega-hora-0)
- [Fase 1: Recepción del Activo (Horas 0-2)](#fase-1-recepción-del-activo-horas-0-2)
- [Fase 2: Entorno Local (Horas 2-6)](#fase-2-entorno-local-horas-2-6)
- [Fase 3: Verificación de Calidad (Horas 6-12)](#fase-3-verificación-de-calidad-horas-6-12)
- [Fase 4: Infraestructura Cloud (Horas 12-24)](#fase-4-infraestructura-cloud-horas-12-24)
- [Fase 5: Deployment a Producción (Horas 24-36)](#fase-5-deployment-a-producción-horas-24-36)
- [Fase 6: Validación y Go-Live (Horas 36-48)](#fase-6-validación-y-go-live-horas-36-48)
- [Post-Transición: Soporte 30 Días](#post-transición-soporte-30-días)
- [Checklist de Transición](#checklist-de-transición)

---

## Fase 0: Pre-Entrega (Hora 0)

### Lo que recibe el comprador

| Entregable | Formato | Tamaño aprox. |
|------------|---------|---------------|
| Repositorio Git completo | Clone o ZIP | ~150 MB (sin vendor/) |
| Documentación (200+ archivos) | Markdown + JSON | ~15 MB |
| OpenAPI Specification | JSON | ~1 MB |
| Manifiestos Kubernetes | YAML | ~50 KB |
| Docker configs | Dockerfile + Compose | ~20 KB |
| CI/CD Workflows (9) | YAML (GitHub Actions) | ~30 KB |
| Script de setup automatizado | Bash | ~5 KB |

### Requisitos del equipo del comprador

| Rol | Cantidad | Habilidades |
|-----|:--------:|-------------|
| **DevOps / SRE** | 1 | Docker, Kubernetes, AWS/GCP/Azure |
| **Backend Developer** | 1 | PHP 8.4, Laravel 12, MySQL |
| **DBA** (opcional) | 1 | MySQL 8.0, replicación |

### Requisitos de infraestructura mínima

| Componente | Especificación mínima | Recomendado |
|------------|----------------------|-------------|
| Servidor/VM | 2 vCPU, 4 GB RAM | 4 vCPU, 8 GB RAM |
| MySQL | 8.0+ | 8.0+ con read replica |
| Redis | 7.0+ | 7.0+ cluster |
| PHP | 8.4.x | 8.4.x con OPcache |
| Almacenamiento | 20 GB SSD | 50 GB SSD |
| SSL | Certificado válido | Let's Encrypt o ACM |

### Servicios externos necesarios

| Servicio | Propósito | Costo estimado |
|----------|----------|---------------|
| Gemini API (Google) | Servicios IA (OCR, CABYS, chat) | ~$50-200/mes |
| OpenAI API (fallback) | Fallback IA | ~$20-50/mes |
| Sentry | Error tracking | Free tier o $26/mes |
| Hacienda CR (DGT) | Facturación electrónica | Gratis (credenciales DGT) |
| SMTP (Mailgun/SES) | Email transaccional | ~$10-35/mes |

---

## Fase 1: Recepción del Activo (Horas 0-2)

### 1.1 Transferencia del repositorio

**Opción A — GitHub Transfer (recomendado):**
```bash
# El repositorio se transfiere directamente a la organización del comprador
# Preserva: issues, PRs, historial Git completo, Actions
# Tiempo: ~5 minutos
```

**Opción B — Clone + Push a nuevo remoto:**
```bash
git clone --mirror <url-repositorio-ursol>
cd Ursol-CAST-API.git
git remote set-url origin <url-repositorio-comprador>
git push --mirror
```

### 1.2 Verificación de integridad

```bash
# Verificar que todo el código está presente
git log --oneline | wc -l          # Esperar 1,500+ commits
find app/ -name "*.php" | wc -l    # Esperar ~580+ archivos PHP
find tests/ -name "*.php" | wc -l  # Esperar ~160+ archivos de test
find database/migrations/ -name "*.php" | wc -l  # Esperar 103 migraciones
```

### 1.3 Lectura de documentación prioritaria

Orden de lectura recomendado (2 horas):

1. **`README.md`** — Visión general (15 min)
2. **`ESTADO_ACTUAL_PROYECTO.md`** — Métricas actuales (10 min)
3. **`docs/guides/INSTALLATION_GUIDE.md`** — Setup (20 min)
4. **`docs/guides/DOCKER_GUIDE.md`** — Docker (15 min)
5. **`docs/api/API_DOCUMENTATION.md`** — Especificación API (30 min)
6. **`SECURITY.md`** — Seguridad OWASP (15 min)
7. **`docs/GLOSARIO_COMPLETO_URSOL_CAST_API.md`** — Referencia rápida (15 min)

---

## Fase 2: Entorno Local (Horas 2-6)

### 2.1 Setup con Docker (método más rápido)

```bash
# 1. Clonar el repositorio
git clone <url-repositorio> && cd Ursol-CAST-API

# 2. Copiar variables de entorno
cp .env.example .env

# 3. Configurar .env (editar con los valores reales)
# Las variables críticas a configurar:
#   DB_HOST=mysql
#   DB_DATABASE=ursol_cast
#   DB_USERNAME=ursol
#   DB_PASSWORD=<password-seguro>
#   REDIS_HOST=redis
#   GEMINI_API_KEY=<tu-key>
#   OPENAI_API_KEY=<tu-key>
#   SENTRY_DSN=<tu-dsn>

# 4. Levantar con Docker Compose
docker compose up -d

# 5. Instalar dependencias dentro del contenedor
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 6. Generar documentación Swagger
docker compose exec app php artisan l5-swagger:generate

# 7. Verificar que funciona
curl http://localhost:8000/api/health
# Esperar: {"status": "ok", ...}
```

### 2.2 Setup nativo (alternativa)

```bash
# Requisitos: PHP 8.4, Composer, MySQL 8.0, Redis 7.0, Node.js 20+
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
# Visitar: http://localhost:8000
```

### 2.3 Verificación del entorno local

```bash
# Verificar versiones
php -v              # PHP 8.4.x
composer --version   # Composer 2.x
mysql --version      # MySQL 8.0+
redis-cli ping       # PONG

# Verificar la aplicación
php artisan about    # Info completa de Laravel
php artisan route:list --count  # ~400+ rutas registradas
```

---

## Fase 3: Verificación de Calidad (Horas 6-12)

Esta fase valida que el activo funciona como se prometió.

### 3.1 Tests automatizados

```bash
# Ejecutar suite completa (esperar ~3-5 min para 1,261+ tests)
php artisan test

# O con cobertura de código
php artisan test --coverage

# Tests por tipo
php artisan test --testsuite=Unit       # ~68 archivos
php artisan test --testsuite=Feature    # ~79 archivos
php artisan test --testsuite=Contract   # ~7 archivos

# Generar reporte JUnit XML (para CI)
php artisan test --log-junit=storage/test-report.xml
```

**Resultado esperado:** `Tests: 1,261+ passed, 0 failed`

### 3.2 Análisis estático

```bash
# PHPStan Level 8 — debe dar 0 errores
vendor/bin/phpstan analyse

# PHP CS Fixer — verificar estilo de código
vendor/bin/php-cs-fixer fix --dry-run --diff
```

**Resultado esperado:** `[OK] No errors` (PHPStan)

### 3.3 Verificación de facturación electrónica

```bash
# Verificar configuración Hacienda (sandbox)
php artisan tinker
>>> config('hacienda')
# Debe mostrar configuración de endpoints, certificados, etc.

# Los test e2e de Hacienda se ejecutan contra sandbox:
php artisan test --filter=Hacienda
```

### 3.4 Verificación de módulos IA

```bash
# Verificar que las APIs de IA están configuradas
php artisan tinker
>>> config('gemini.api_key')  # Debe tener valor
>>> config('openai.api_key')  # Debe tener valor (fallback)

# Tests de IA
php artisan test --filter=AI
php artisan test --filter=Gemini
```

---

## Fase 4: Infraestructura Cloud (Horas 12-24)

### 4.1 Opción A: AWS (Recomendado)

| Servicio AWS | Uso | Costo mensual estimado |
|-------------|-----|:----------------------:|
| ECS Fargate o EC2 | Contenedores PHP | $50-150 |
| RDS MySQL 8.0 | Base de datos principal | $30-100 |
| RDS Read Replica | Lecturas escaladas | $30-100 |
| ElastiCache Redis | Cache + colas | $25-75 |
| ALB | Load balancer | $20-30 |
| S3 | Almacenamiento de archivos | $5-15 |
| ACM | Certificados SSL | Gratis |
| CloudWatch | Logs + métricas | $10-30 |
| **Total estimado** | | **$170-500/mes** |

```bash
# Ejemplo con ECS Fargate:
# 1. Crear ECR repository
aws ecr create-repository --repository-name ursol-cast-api

# 2. Build & push Docker image
docker build -t ursol-cast-api .
docker tag ursol-cast-api:latest <account>.dkr.ecr.<region>.amazonaws.com/ursol-cast-api:latest
docker push <account>.dkr.ecr.<region>.amazonaws.com/ursol-cast-api:latest

# 3. Usar los manifiestos de Kubernetes del proyecto
# kubernetes/base/ contiene todos los archivos necesarios
# kubernetes/overlays/production/ tiene la configuración de producción
```

### 4.2 Opción B: DigitalOcean / Hetzner (Económico)

| Componente | Servicio | Costo mensual |
|------------|---------|:-------------:|
| App Server | Droplet 4GB | $24 |
| Database | Managed MySQL | $15 |
| Redis | Managed Redis | $15 |
| **Total** | | **$54/mes** |

### 4.3 Opción C: Kubernetes (Escala enterprise)

Los manifiestos ya están incluidos:

```bash
# Aplicar configuración base
kubectl apply -k kubernetes/base/

# O con overlay de producción
kubectl apply -k kubernetes/overlays/production/

# Verificar deployment
kubectl get pods -n ursol-cast-api
kubectl logs -f deployment/ursol-cast-api -n ursol-cast-api
```

### 4.4 Variables de entorno para producción

```env
# === CRÍTICAS (producción) ===
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.sudominio.com

# Base de datos principal
DB_CONNECTION=mysql
DB_HOST=<rds-endpoint>
DB_DATABASE=ursol_cast_production
DB_USERNAME=ursol_prod
DB_PASSWORD=<password-seguro-32-chars>

# Read Replica (opcional pero recomendado)
DB_READ_HOST=<rds-replica-endpoint>

# Redis
REDIS_HOST=<elasticache-endpoint>
REDIS_PASSWORD=<redis-password>

# Hacienda Costa Rica
HACIENDA_AMBIENTE=produccion
HACIENDA_CERT_PATH=/secure/certificado.p12
HACIENDA_CERT_PIN=<pin-certificado>

# IA
GEMINI_API_KEY=<gemini-key>
OPENAI_API_KEY=<openai-key>

# Observabilidad
SENTRY_DSN=<sentry-dsn>
OTEL_EXPORTER_OTLP_ENDPOINT=<otel-endpoint>

# Email
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@sudominio.com

# Seguridad
SANCTUM_STATEFUL_DOMAINS=sudominio.com
SESSION_DOMAIN=.sudominio.com
```

---

## Fase 5: Deployment a Producción (Horas 24-36)

### 5.1 Primer deployment

```bash
# En el servidor/contenedor de producción:

# 1. Instalar dependencias (sin dev)
composer install --no-dev --optimize-autoloader

# 2. Compilar optimizaciones Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Ejecutar migraciones
php artisan migrate --force

# 4. Ejecutar seeders de datos maestros (SOLO la primera vez)
php artisan db:seed --class=MasterDataSeeder --force

# 5. Generar documentación Swagger
php artisan l5-swagger:generate

# 6. Iniciar workers de cola
php artisan horizon
# O con supervisor:
# supervisorctl start horizon

# 7. Health check
curl https://api.sudominio.com/api/health
```

### 5.2 Configurar CI/CD

Los 9 workflows de GitHub Actions ya están configurados. Solo necesitan:

```yaml
# En GitHub Settings > Secrets, agregar:
secrets:
  DB_PASSWORD: <password>
  GEMINI_API_KEY: <key>
  OPENAI_API_KEY: <key>
  SENTRY_DSN: <dsn>
  DEPLOY_SSH_KEY: <private-key>
  DOCKER_REGISTRY: <registry-url>
```

### 5.3 Configurar SSL

```bash
# Con Let's Encrypt (gratis)
certbot --nginx -d api.sudominio.com

# O con AWS ACM (automático con ALB)
# Se configura en el ALB listener
```

### 5.4 Configurar cron jobs

```bash
# Agregar al crontab del servidor:
* * * * * cd /path/to/ursol-cast-api && php artisan schedule:run >> /dev/null 2>&1
```

---

## Fase 6: Validación y Go-Live (Horas 36-48)

### 6.1 Checklist de validación

```bash
# 1. Health check
curl https://api.sudominio.com/api/health
# ✅ {"status": "ok"}

# 2. Autenticación
curl -X POST https://api.sudominio.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@test.com", "password": "password"}'
# ✅ Retorna token Sanctum

# 3. Swagger UI
open https://api.sudominio.com/api/documentation
# ✅ Swagger UI carga con todos los endpoints

# 4. Facturación electrónica (sandbox primero)
# Crear una factura de prueba via API
# ✅ Recibir XML firmado y clave numérica

# 5. Redis cache
redis-cli -h <host> ping
# ✅ PONG

# 6. Workers
php artisan horizon:status
# ✅ Horizon is running

# 7. Multi-tenancy
# Crear un tenant de prueba y verificar aislamiento de BD
```

### 6.2 Tests de rendimiento básicos

```bash
# Instalar Apache Bench o hey
# 100 requests, 10 concurrentes
ab -n 100 -c 10 -H "Authorization: Bearer <token>" \
  https://api.sudominio.com/api/v1/products

# Resultados esperados:
# Tiempo promedio por request: < 200ms
# Requests por segundo: > 50 (con cache)
# 0 errores
```

### 6.3 Configurar monitoreo

1. **Sentry**: Ya configurado — verificar que errores llegan al dashboard
2. **Horizon**: Acceder a `/horizon` para monitorear colas
3. **Logs**: `storage/logs/laravel.log` o CloudWatch

---

## Post-Transición: Soporte 30 Días

### Incluido en la venta

| Servicio | Detalle |
|----------|---------|
| Soporte técnico por email/chat | Respuesta en < 24h (L-V) |
| Sesiones de screen-sharing | Hasta 4 sesiones de 1 hora |
| Resolución de bugs de transición | Bugs encontrados durante setup |
| Explicación de arquitectura | Sesiones técnicas con el desarrollador |
| Acceso al desarrollador principal | Canal directo con Jeremy Arias |

### No incluido (servicios adicionales)

| Servicio | Tarifa |
|----------|--------|
| Desarrollo de nuevas features | $70/hora |
| Consultoría arquitectónica | $100/hora |
| Training extensivo (>4 sesiones) | $1,500/día |
| Soporte extendido (post 30 días) | $2,000/mes |

---

## Checklist de Transición

### Hora 0-2: Recepción
- [ ] Repositorio transferido/clonado
- [ ] Verificación de integridad (commits, archivos)
- [ ] Documentación prioritaria leída
- [ ] Accesos a servicios externos configurados

### Hora 2-6: Entorno Local
- [ ] Docker Compose levantado exitosamente
- [ ] `.env` configurado
- [ ] Migraciones ejecutadas
- [ ] Seeds ejecutados
- [ ] `curl localhost:8000/api/health` → OK
- [ ] Swagger UI accesible en `/api/documentation`

### Hora 6-12: Verificación
- [ ] `php artisan test` → 1,261+ passing, 0 failed
- [ ] `vendor/bin/phpstan analyse` → 0 errores
- [ ] Facturación electrónica probada (sandbox)
- [ ] Servicios IA probados (OCR, clasificación)
- [ ] Multi-tenancy verificado (crear tenant de prueba)

### Hora 12-24: Infraestructura Cloud
- [ ] Servidor/cluster provisionado
- [ ] MySQL producción configurado
- [ ] Redis configurado
- [ ] SSL certificado instalado
- [ ] Variables de entorno de producción configuradas
- [ ] Docker image construida y subida al registry

### Hora 24-36: Deployment
- [ ] Primera migración en producción ejecutada
- [ ] Seeds de datos maestros ejecutados
- [ ] Laravel caches optimizados
- [ ] Horizon (workers) corriendo
- [ ] Cron job configurado
- [ ] CI/CD secrets configurados

### Hora 36-48: Validación
- [ ] Health check en producción → OK
- [ ] Login y autenticación → OK
- [ ] Swagger UI en producción → OK
- [ ] Test de facturación electrónica (sandbox) → OK
- [ ] Test de rendimiento básico → < 200ms promedio
- [ ] Monitoreo (Sentry) recibiendo datos
- [ ] Horizon dashboard accesible

### Post Go-Live
- [ ] Sesión de handover con desarrollador completada
- [ ] Equipo del comprador autónomo para operaciones diarias
- [ ] Runbook de operaciones documentado
- [ ] Credenciales de producción Hacienda configuradas (cuando estén listas)

---

## Diagrama de Transición

```
Hora 0          Hora 2          Hora 6          Hora 12         Hora 24         Hora 36         Hora 48
  │               │               │               │               │               │               │
  ▼               ▼               ▼               ▼               ▼               ▼               ▼
┌─────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│Recepción│→ │ Setup    │→ │Verificar │→ │ Cloud    │→ │ Deploy   │→ │Validación│→ │ GO LIVE  │
│ del     │  │ Local    │  │ Calidad  │  │ Infra    │  │ Prod     │  │ & Test   │  │    ✅    │
│ Activo  │  │ Docker   │  │ Tests    │  │ AWS/GCP  │  │ CI/CD    │  │ UAT      │  │          │
└─────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘
   2h            4h             6h             12h            12h            12h
```

---

## FAQ Técnico para el Comprador

### ¿Necesito un desarrollador Laravel para mantener esto?
**Sí.** Se recomienda al menos 1 desarrollador con experiencia en Laravel 10+ y PHP 8.x. El código está documentado a nivel enterprise, pero la persona debe entender inyección de dependencias, Eloquent ORM, y el patrón Service-Repository.

### ¿Puedo usar un front-end diferente?
**Sí.** Ursol CAST es un API puro (headless). Puede conectarse con React, Vue, Angular, Flutter, React Native, o cualquier cliente HTTP. La especificación OpenAPI/Swagger facilita la generación de SDKs cliente.

### ¿Puedo hostear en mi propio servidor?
**Sí.** El sistema funciona en cualquier infraestructura que soporte PHP 8.4, MySQL 8.0 y Redis. Docker opcional pero recomendado.

### ¿Los servicios de IA son propios o de terceros?
Los servicios de IA utilizan **APIs de Gemini (Google) y OpenAI** como motores, pero toda la lógica de integración, prompts, fallbacks, parsing de respuestas y orquestación es código propio de Ursol CAST. No dependes de un servicio IA específico — el patrón de fallback permite cambiar de proveedor.

### ¿Qué pasa si Hacienda cambia la versión de FE?
El módulo de facturación electrónica está diseñado modular. Las 38 brechas de la v4.4 están mapeadas en `docs/hacienda/`. Actualizar a una futura v4.5 o v5.0 requeriría ajustes en los servicios de Hacienda, pero la arquitectura está preparada para ello.

### ¿Los datos de un tenant pueden ver los de otro?
**No.** El sistema usa Spatie Multi-tenancy con **bases de datos aisladas** por tenant. Cada empresa tiene su propia BD MySQL. Es aislamiento físico, no lógico.

---

*Documento confidencial — Sistemas Ursol S.A. © 2026*  
*Versión: 15 de Abril 2026*
