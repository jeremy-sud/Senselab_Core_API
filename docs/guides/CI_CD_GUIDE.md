# CI/CD Pipeline - Guía Completa

## 📋 Tabla de Contenidos

- [Descripción General](#descripción-general)
- [Workflows Implementados](#workflows-implementados)
- [Configuración Inicial](#configuración-inicial)
- [Quality Gates](#quality-gates)
- [Deployment Pipeline](#deployment-pipeline)
- [Troubleshooting](#troubleshooting)

## 🎯 Descripción General

Este proyecto utiliza **GitHub Actions** para automatizar testing, análisis de código y despliegue continuo. El pipeline garantiza calidad de código mediante múltiples validaciones antes de cada merge.

### Arquitectura del Pipeline

```
┌─────────────────────────────────────────────────────────────┐
│                    GitHub Push/PR                            │
└────────────────────┬────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
    ┌────▼─────┐          ┌─────▼────┐
    │  Tests   │          │  Quality │
    │  Suite   │          │   Gates  │
    └────┬─────┘          └─────┬────┘
         │                      │
         │  ✅ Pass             │  ✅ Pass
         │                      │
         └──────────┬───────────┘
                    │
              ┌─────▼──────┐
              │   Deploy   │
              │  Staging   │
              └─────┬──────┘
                    │
              Manual Approval
                    │
              ┌─────▼──────┐
              │   Deploy   │
              │ Production │
              └────────────┘
```

## 🔄 Workflows Implementados

### 1. Tests Workflow (`tests.yml`)

**Trigger:** Push o PR a `main` o `develop`

**Jobs:**
- **tests**: Ejecuta suite completa de PHPUnit con cobertura
- **code-quality**: Valida PSR-12 y PHPStan
- **security**: Verifica vulnerabilidades en dependencias

**Métricas de Calidad:**
- ✅ 100% de tests deben pasar
- ✅ Cobertura mínima: 70%
- ✅ PHPStan nivel 6 sin errores
- ✅ PSR-12 compliance
- ✅ Sin vulnerabilidades críticas

**Tiempo estimado:** 5-8 minutos

### 2. Code Analysis (`code-analysis.yml`)

**Trigger:** Push o PR a `main` o `develop`

**Análisis:**
- **SonarQube**: Quality gate completo
- **PHPMD**: Detección de code smells
- **PHPCPD**: Duplicación de código
- **PHPCS**: Estándares de código

**Umbrales:**
- Complejidad ciclomática: < 10
- Duplicación: < 3%
- Deuda técnica: < 1 día
- Bugs: 0
- Vulnerabilidades: 0

### 3. Deploy to Staging (`deploy-staging.yml`)

**Trigger:** Push a `develop` o manual

**Proceso:**
1. Checkout código
2. Install dependencies (producción)
3. Run tests
4. Build Docker image
5. Deploy a servidor staging
6. Run migrations
7. Smoke tests
8. Notificación Slack

**Entorno:** https://staging.senselab-core.com

### 4. Deploy to Production (`deploy-production.yml`)

**Trigger:** Release publicado o manual

**Proceso:**
1. Checkout código
2. Full test suite
3. Build Docker image
4. **Backup completo** (DB + archivos)
5. Deploy con zero-downtime
6. Migrations
7. Cache optimization
8. Smoke tests
9. Rollback automático si falla

**Entorno:** https://api.senselab-core.com

## ⚙️ Configuración Inicial

### Secrets Requeridos en GitHub

Navegar a: `Settings > Secrets and variables > Actions`

```bash
# Database
DB_PASSWORD=<secure-password>

# Staging Server
STAGING_SERVER=staging.senselab-core.com
STAGING_USER=deploy
SSH_PRIVATE_KEY=<ssh-key-staging>

# Production Server
PRODUCTION_SERVER=api.senselab-core.com
PRODUCTION_USER=deploy
SSH_PRIVATE_KEY_PROD=<ssh-key-production>

# SonarQube
SONAR_TOKEN=<sonarqube-token>
SONAR_HOST_URL=https://sonarqube.senselab-core.com

# Codecov
CODECOV_TOKEN=<codecov-token>

# Notificaciones
SLACK_WEBHOOK=<slack-webhook-url>
```

### Environments en GitHub

Crear environments en `Settings > Environments`:

**1. Staging**
- No requiere aprobación
- Auto-deploy desde `develop`

**2. Production**
- ✅ Requiere aprobación manual
- Reviewers: Team Leads
- Protection rules activadas

## 🎯 Quality Gates

### Test Coverage

```bash
# Ejecutar localmente con reporte
docker exec senselab_php vendor/bin/phpunit --coverage-html coverage

# Ver reporte
open coverage/index.html
```

**Umbrales:**
- Líneas: ≥ 70%
- Funciones: ≥ 65%
- Clases: ≥ 70%

### PHPStan Analysis

```bash
# Análisis completo
docker exec senselab_php vendor/bin/phpstan analyse

# Generar baseline
docker exec senselab_php vendor/bin/phpstan analyse --generate-baseline
```

**Nivel:** 6 (de 9)

### PHP CS Fixer

```bash
# Ver cambios necesarios
docker exec senselab_php vendor/bin/php-cs-fixer fix --dry-run --diff

# Aplicar correcciones
docker exec senselab_php vendor/bin/php-cs-fixer fix
```

**Estándar:** PSR-12 + reglas custom

## 🚀 Deployment Pipeline

### Staging Deployment

```bash
# Automático al merge a develop
git checkout develop
git merge feature/nueva-funcionalidad
git push origin develop
# → GitHub Actions inicia deploy automático
```

**Verificación Post-Deploy:**
```bash
# Health check
curl https://staging.senselab-core.com/api/health

# Version check
curl https://staging.senselab-core.com/api/version
```

### Production Deployment

```bash
# 1. Crear release tag
git tag -a v1.2.0 -m "Release v1.2.0 - Facturación Electrónica E2E"
git push origin v1.2.0

# 2. Crear GitHub Release
# → Ir a GitHub > Releases > New Release
# → Seleccionar tag v1.2.0
# → Describir cambios
# → Publish release

# 3. Aprobar deployment
# → GitHub Actions esperará aprobación manual
# → Reviewers aprueban en GitHub UI

# 4. Deployment se ejecuta automáticamente
```

**Checklist Pre-Production:**
- [ ] Tests 100% passing en staging
- [ ] QA sign-off
- [ ] Database migrations validadas
- [ ] Rollback plan documentado
- [ ] Team notificado
- [ ] Monitoring activado

### Rollback Manual

```bash
# Conectar a servidor producción
ssh deploy@api.senselab-core.com

cd /var/www/senselab-core-api

# Ver último backup
ls -lh /backups/

# Rollback de código
git reset --hard <previous-commit-hash>
docker-compose down && docker-compose up -d

# Rollback de database
BACKUP_DIR="/backups/20231130_143022"
docker-compose exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD senselab_core < $BACKUP_DIR/database.sql

# Restart y verificación
docker-compose exec php php artisan migrate:rollback
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan up

# Verificar
curl https://api.senselab-core.com/api/health
```

## 🐛 Troubleshooting

### Tests Fallando en CI pero Pasan Local

**Causa común:** Diferencias de entorno

```bash
# Ejecutar tests en modo CI
APP_ENV=testing php artisan test

# Ver diferencias de configuración
php artisan config:show database
```

**Solución:**
- Verificar `.env.example` actualizado
- Revisar seeds/factories
- Validar versiones de PHP/MySQL

### PHPStan Errores en CI

```bash
# Actualizar baseline
vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon

# Commit el baseline
git add phpstan-baseline.neon
git commit -m "chore: update PHPStan baseline"
```

### Deploy Falla por Timeout

**Síntomas:** SSH timeout durante deploy

**Solución:**
```yaml
# En workflow, aumentar timeout
timeout-minutes: 30

# Optimizar rsync
rsync -avz --compress-level=9 --timeout=300
```

### Cache de Composer Corrupto

```bash
# Limpiar cache local
rm -rf vendor
composer clear-cache
composer install

# En GitHub Actions
# → Settings > Actions > Caches
# → Eliminar caches viejos
```

## 📊 Monitoring del Pipeline

### GitHub Actions Dashboard

```
Repository > Actions > Workflow runs
```

**Métricas clave:**
- Success rate (objetivo: > 95%)
- Average duration (objetivo: < 10 min)
- Queue time (objetivo: < 1 min)

### Alertas Configuradas

**Slack Notifications:**
- ❌ Test failures
- ⚠️ Coverage drop > 5%
- ✅ Successful deployments
- 🚨 Production rollbacks

**Email Notifications:**
- Security vulnerabilities
- Deployment aprobación pendiente

## 🔐 Seguridad

### Secrets Management

- **Nunca** commitear secrets en código
- Rotar SSH keys cada 90 días
- Usar GitHub Secrets para credenciales
- Audit logs habilitados

### Dependency Scanning

```bash
# Manual security check
composer audit

# Update dependencies
composer update --with-dependencies
```

## 📈 Próximos Pasos

- [ ] Implementar canary deployments
- [ ] A/B testing en staging
- [ ] Performance testing automatizado
- [ ] Blue-green deployment strategy
- [ ] Auto-scaling basado en métricas

## 📞 Soporte

**Problemas con CI/CD:**
- GitHub Issues con label `ci/cd`
- Slack: #devops-support
- Email: devops@senselab.com

**Emergencias Producción:**
- On-call: +506 XXXX-XXXX
- Slack: #production-incidents
