# 📚 Documentación del Proyecto Ursol CAST API

**Última actualización:** 5 de Diciembre 2025

Este directorio contiene toda la documentación organizada del proyecto.

## 📁 Estructura

```
docs/
├── api/           # Documentación de la API (controllers, models, policies)
├── archive/       # Documentación histórica (sprints completados, sesiones)
├── database/      # Configuración y esquema de base de datos
├── guides/        # Guías de instalación, docker, testing
├── hacienda/      # Integración con Ministerio de Hacienda CR
└── sprints/       # Documentación de sprints y resúmenes ejecutivos
```

## 📖 Documentos Principales (Raíz)

| Archivo | Descripción |
|---------|-------------|
| [README.md](../README.md) | Documentación principal del proyecto |
| [ESTADO_ACTUAL_PROYECTO.md](../ESTADO_ACTUAL_PROYECTO.md) | Estado actual con métricas reales |
| [IA_FUNCIONALIDADES.md](../IA_FUNCIONALIDADES.md) | Módulo de Inteligencia Artificial |
| [CHANGELOG.md](../CHANGELOG.md) | Historial de cambios |
| [SECURITY.md](../SECURITY.md) | Políticas de seguridad |
| [KNOWN_WARNINGS.md](KNOWN_WARNINGS.md) | Warnings conocidos y aceptados 🆕 |

## 📊 Estado Actual (Diciembre 2025)

| Métrica | Valor |
|---------|-------|
| **Controladores API** | 88 |
| **Rutas API** | 559 |
| **Modelos Eloquent** | 83 |
| **Tests Pasando** | 405 |
| **Servicios** | 18 (10 de IA) |
| **Endpoints IA** | 32 |

## 🚀 Guías Rápidas

### Instalación
- [Installation Guide](guides/INSTALLATION_GUIDE.md) - Instalación completa
- [Docker Guide](guides/DOCKER_GUIDE.md) - Configuración Docker
- [Docker Quickstart](guides/DOCKER_QUICKSTART.md) - Inicio rápido con Docker

### Desarrollo
- [Contributing](guides/CONTRIBUTING.md) - Guía de contribución
- [Testing Guide](guides/TESTING_GUIDE.md) - Cómo ejecutar tests
- [Como Probar API](guides/COMO_PROBAR_API.md) - Probar endpoints
- **[Generador de Módulos](guides/GENERADOR_MODULOS.md)** - Crear nuevos módulos ERP automáticamente 🆕

### API
- [API Documentation](api/API_DOCUMENTATION.md) - Endpoints completos
- [Controllers Summary](api/CONTROLLERS_SUMMARY.md) - Resumen de controllers
- [Models Relations](api/MODELS_RELATIONS.md) - Relaciones de modelos

### Inteligencia Artificial 🤖
- [IA Funcionalidades](../IA_FUNCIONALIDADES.md) - Documentación completa de IA
- **10 Servicios**: OCR, Chatbot, Predicciones, Anomalías, Contenido, CABYS, Credit Scoring
- **32 Endpoints** bajo `/api/ai/`
- **Gratuito**: Usa Google Gemini API

### Hacienda Costa Rica
- [Facturación Electrónica Setup](hacienda/FACTURACION_ELECTRONICA_SETUP.md) - Configuración FE
- [Facturación Electrónica API](hacienda/FACTURACION_ELECTRONICA_API.md) - Endpoints FE
- [Plan Implementación v4.4](hacienda/PLAN_IMPLEMENTACION_V44_HACIENDA.md) - Plan detallado

### Base de Datos
- [Database README](database/DATABASE_README.md) - Esquema de BD
- [Database Config](database/DATABASE_CONFIG.md) - Configuración

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
| Sprint 8 | ✅ | [Módulo IA Completo](../IA_FUNCIONALIDADES.md) |

## 📦 Archivos Históricos

La carpeta `archive/` contiene documentación histórica que ya no es activa pero se preserva para referencia:

- Documentos de fases completadas (FASE_*.md)
- Resúmenes de sesiones de trabajo
- Auditorías y análisis anteriores
- Documentos de progreso/iteración
