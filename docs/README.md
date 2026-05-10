# 📚 Documentación del Proyecto Senselab Core API

**Última actualización:** Abril 2026 — v5.0.1

![PHPStan Level 8](images/badges/phpstan-level8.svg)
![Tests 1270+ Passing](images/badges/tests-1270-passing.svg)
![Audit Score 9.2/10](images/badges/audit-score-9.2.svg)
![Hacienda v4.4 Compliance](images/badges/hacienda-v44-compliance.svg)
![AI Services](images/badges/ai-services-10.svg)
![Multi-Tenant](images/badges/multi-tenant-spatie.svg)

Este directorio contiene toda la documentación organizada del proyecto.

## 📁 Estructura

```
docs/
├── api/           # Documentación de la API (controllers, models, policies)
├── archive/       # Documentación histórica (fases completadas, sprints, sesiones)
├── database/      # Configuración y esquema de base de datos
├── diagrams/      # 📊 Diagramas de arquitectura, ERDs, flujos, benchmarks (Mermaid)
├── guides/        # Guías de instalación, docker, testing, refactorización
├── hacienda/      # Integración con Ministerio de Hacienda CR (normativa + diagramas)
├── images/badges/ # 🏆 Badges SVG de auditoría y métricas
├── sprints/       # Documentación de sprints y resúmenes ejecutivos
├── examples.http  # Ejemplos REST Client para probar endpoints
└── *.md           # Documentos de referencia activa
```

## 📖 Documentos Principales

| Archivo | Descripción |
|---------|-------------|
| [README.md](../README.md) | Documentación principal del proyecto |
| [ESTADO_ACTUAL_PROYECTO.md](../ESTADO_ACTUAL_PROYECTO.md) | Estado actual con métricas reales |
| [CHANGELOG.md](../CHANGELOG.md) | Historial de cambios |
| [SECURITY.md](../SECURITY.md) | Políticas de seguridad |

## 📘 Curso y Glosario

| Archivo | Descripción |
|---------|-------------|
| [Curso Completo: De Cero a Experto](curso_completo_senselab_core_api.md) | Curso autodidacta integral — arquitectura, módulos, seguridad, testing, despliegue, IA, facturación electrónica y roadmap |
| [Glosario Completo de Terminología](GLOSARIO_COMPLETO_SENSELAB_CORE_API.md) | Referencia exhaustiva de toda la terminología: modelos, servicios, DTOs, traits, eventos, jobs, observers, config y más |

## � Diagramas de Arquitectura (NUEVO)

| Diagrama | Tipo | Audiencia |
|----------|------|-----------|
| [Arquitectura Multi-Tenant](diagrams/01-arquitectura-multi-tenant.md) | Topología | Desarrolladores, DevOps |
| [Flujo Facturación Electrónica](diagrams/02-flujo-facturacion-electronica.md) | Secuencia | Desarrolladores, QA |
| [Ciclo de Vida del Dato con IA](diagrams/03-ciclo-vida-dato-ia.md) | Flujo | Ejecutivos, Ventas |
| [Precisión de Servicios IA](diagrams/04-precision-servicios-ia.md) | Gráficos | Ejecutivos, Ventas |
| [ERD Módulo Contable](diagrams/05-erd-modulo-contable.md) | ERD | Desarrolladores |
| [ERD Módulo Logística](diagrams/06-erd-modulo-logistica.md) | ERD | Desarrolladores |
| [Matriz RBAC Seguridad](diagrams/07-matriz-rbac-seguridad.md) | Tabla + Grafo | Admins, Seguridad |
| [Benchmarks Rendimiento Redis](diagrams/08-benchmarks-rendimiento-redis.md) | XY Chart | Ventas, DevOps |

> Ver el [índice completo de diagramas](diagrams/README.md) con resumen ejecutivo y badges.

## �📄 Documentos de Referencia Activa (este directorio)

| Archivo | Descripción |
|---------|-------------|
| [IA_FUNCIONALIDADES.md](IA_FUNCIONALIDADES.md) | Módulo de Inteligencia Artificial (10 servicios, 32 endpoints) |
| [VALORACION_COMERCIAL_Y_PRICING.md](VALORACION_COMERCIAL_Y_PRICING.md) | 💰 Valoración comercial, estudio de mercado y estrategia de pricing (4 tiers) |
| [KNOWN_WARNINGS.md](KNOWN_WARNINGS.md) | Warnings conocidos y aceptados (PHPStan/SonarQube) |
| [PENDIENTES_PROYECTO.md](PENDIENTES_PROYECTO.md) | Lista de pendientes y TODOs |
| [MAPA_ESTRUCTURAL_API.txt](MAPA_ESTRUCTURAL_API.txt) | Mapa estructural de la arquitectura |
| [release_checklist.md](release_checklist.md) | Checklist pre-release para producción |
| [examples.http](examples.http) | Ejemplos REST Client para probar endpoints |

## 📊 Estado Actual (Abril 2026 — v5.0.1)

| Métrica | Valor |
|---------|-------|
| **Controladores API** | 96 |
| **Modelos Eloquent** | 98 |
| **Tests** | 959+ passing (154 archivos) |
| **Servicios** | 67 (10 IA + 9 Hacienda + 48 core) |
| **FormRequests** | 175 |
| **Resources** | 81 |
| **Policies** | 80 |
| **Migraciones** | 103 |
| **DTOs** | 63 (~65% cobertura) |
| **PHPStan** | Level 8 — 0 errores |
| **Auditoría** | 9.2/10 |
| **Hacienda v4.4** | 38/38 compliance (100%) |
| **Rutas** | 16 archivos particionados en `routes/api/` |
| **Fases completadas** | 1-22 ✅ (Roadmap 100%) |

## 🚀 Guías Rápidas

### Instalación
- [Installation Guide](guides/INSTALLATION_GUIDE.md) - Instalación completa
- [Docker Guide](guides/DOCKER_GUIDE.md) - Configuración Docker
- [Docker Quickstart](guides/DOCKER_QUICKSTART.md) - Inicio rápido con Docker
- [Instalación Docker Resumen](guides/INSTALACION_DOCKER_RESUMEN.md) - Resumen rápido

### Desarrollo
- [Contributing](guides/CONTRIBUTING.md) - Guía de contribución
- [Colaboradores README](guides/COLABORADORES_README.md) - Onboarding para colaboradores
- [Testing Guide](guides/TESTING_GUIDE.md) - Cómo ejecutar tests
- [Guía Datos Testeo](guides/GUIA_DATOS_TESTEO.md) - Datos de prueba
- [Como Probar API](guides/COMO_PROBAR_API.md) - Probar endpoints
- [Setup VS Code](guides/SETUP_EXTENSIONES_VSCODE.md) - Extensiones recomendadas
- [Generador de Módulos](guides/GENERADOR_MODULOS.md) - Crear nuevos módulos ERP
- [Refactorización Controladores](guides/REFACTORIZACION_CONTROLADORES.md) - Patrón Service Layer

### API
- [API Documentation](api/API_DOCUMENTATION.md) - Endpoints completos
- [Controllers Summary](api/CONTROLLERS_SUMMARY.md) - Resumen de controllers
- [Controllers Complete](api/CONTROLLERS_COMPLETE_SUMMARY.md) - Detalle completo
- [Models Relations](api/MODELS_RELATIONS.md) - Relaciones de modelos
- [FormRequests Guide](api/FORMREQUESTS_USAGE_GUIDE.md) - Validación
- [Policies Guide](api/POLICIES_GUIDE.md) - Autorización RBAC

### Inteligencia Artificial 🤖
- [IA Funcionalidades](IA_FUNCIONALIDADES.md) - Documentación completa
- **10 Servicios**: OCR, Chatbot, Predicciones, Anomalías, Contenido, CABYS, Credit Scoring
- **32 Endpoints** bajo `/api/ai/`

### Hacienda Costa Rica
- [Facturación Electrónica Setup](hacienda/FACTURACION_ELECTRONICA_SETUP.md) - Configuración FE
- [Facturación Electrónica API](hacienda/FACTURACION_ELECTRONICA_API.md) - Endpoints FE
- [Análisis v4.4](hacienda/ANALISIS_HACIENDA_CR_V44_COMPLETO.md) - Análisis completo
- [Plan Implementación v4.4](hacienda/PLAN_IMPLEMENTACION_V44_HACIENDA.md) - Plan detallado

### Base de Datos
- [Database README](database/DATABASE_README.md) - Esquema de BD
- [Database Config](database/DATABASE_CONFIG.md) - Configuración

### Infraestructura
- [CI/CD Guide](guides/CI_CD_GUIDE.md) - Pipeline de integración continua
- [Backup Strategy](guides/BACKUP_STRATEGY.md) - Estrategia de backups
- [Multi Tenancy](guides/MULTI_TENANCY.md) - Arquitectura multi-tenant
- [Sentry Setup](guides/SENTRY_SETUP.md) - Monitoreo de errores
- [Branding](guides/BRANDING.md) - Guía de marca

## 📊 Sprints

Los documentos de sprints completados están en `sprints/`:

| Sprint | Estado | Documento |
|--------|--------|-----------|
| Sprint 1 | ✅ | [Policies & RBAC](sprints/SPRINT_1_COMPLETADO_100.md) |
| Sprint 2 | ✅ | [Controllers & Bugs](sprints/SPRINT_2_COMPLETADO_100.md) |
| Sprint 3 | ✅ | [Cache & Performance](sprints/SPRINT_3_OPTIMIZACION_CACHE.md) |
| Sprint 4 | ✅ | [Redis Cache](sprints/SPRINT_4_CACHE_REDIS_COMPLETADO.md) |
| Sprint 5 | ✅ | [RBAC Tests](sprints/SPRINT_5_RBAC_TESTS_100_COMPLETADO.md) |
| Sprint 6 | ✅ | [Cache Optimization](sprints/SPRINT_6_CACHE_OPTIMIZATION.md) |
| Sprint 7 | ✅ | [Controllers/Policies](sprints/SPRINT_7_COMPLETITUD_CONTROLLERS_POLICIES.md) |
| Sprint 8 | ✅ | [Módulo IA + PHPStan](sprints/SPRINT_8_COMPLETO.md) |
| Sprint 9 | ✅ | [PHPUnit Attributes](sprints/SPRINT_9.1_PHPUNIT_ATTRIBUTES.md) |
| FASE 10 | ✅ | Service Layer + CQRS |
| FASE 11 | ✅ | Test fixes + Production bugs |

## 📦 Archivos Históricos

La carpeta `archive/` contiene documentación histórica que ya no es activa pero se preserva para referencia:

- Documentos de fases completadas (FASE 1-11)
- Resúmenes de sesiones de trabajo
- Auditorías y análisis anteriores
- Planes de implementación completados
- Backups de archivos refactorizados
