# 📊 Diagramas y Visualizaciones — Ursol CAST API

> Guía visual de arquitectura enterprise para desarrolladores y ejecutivos.  
> Todos los diagramas usan **Mermaid** y se renderizan automáticamente en GitHub, GitLab, VS Code y documentación estática.

---

## 🏆 Badges de Auditoría

![PHPStan Level 8](../images/badges/phpstan-level8.svg)
![Tests 959+ Passing](../images/badges/tests-959-passing.svg)
![Audit Score 9.2/10](../images/badges/audit-score-9.2.svg)
![Hacienda v4.4 Compliance](../images/badges/hacienda-v44-compliance.svg)
![AI Services](../images/badges/ai-services-10.svg)
![Multi-Tenant](../images/badges/multi-tenant-spatie.svg)

---

## 📁 Índice de Diagramas

| # | Diagrama | Tipo | Audiencia | Archivo |
|---|----------|------|-----------|---------|
| 1 | [Arquitectura Multi-Tenant](01-arquitectura-multi-tenant.md) | Graph (Topología) | Desarrolladores, DevOps | `01-arquitectura-multi-tenant.md` |
| 2 | [Flujo Facturación Electrónica](02-flujo-facturacion-electronica.md) | Sequence Diagram | Desarrolladores, QA | `02-flujo-facturacion-electronica.md` |
| 3 | [Ciclo de Vida del Dato con IA](03-ciclo-vida-dato-ia.md) | Flow Diagram | Ejecutivos, Ventas | `03-ciclo-vida-dato-ia.md` |
| 4 | [Precisión de Servicios IA](04-precision-servicios-ia.md) | Bar Chart + Pie | Ejecutivos, Ventas | `04-precision-servicios-ia.md` |
| 5 | [ERD Módulo Contable](05-erd-modulo-contable.md) | Entity-Relationship | Desarrolladores | `05-erd-modulo-contable.md` |
| 6 | [ERD Módulo Logística](06-erd-modulo-logistica.md) | Entity-Relationship | Desarrolladores | `06-erd-modulo-logistica.md` |
| 7 | [Matriz RBAC Seguridad](07-matriz-rbac-seguridad.md) | Table + Graph | Admins, Seguridad | `07-matriz-rbac-seguridad.md` |
| 8 | [Benchmarks Rendimiento Redis](08-benchmarks-rendimiento-redis.md) | XY Chart + Graph | Ventas, DevOps | `08-benchmarks-rendimiento-redis.md` |

---

## 🎯 Resumen Ejecutivo Visual

### Métricas Clave (Abril 2026 — v5.0.1)

```
┌─────────────────────────────────────────────────────────────┐
│                    URSOL CAST API v5.0.1                     │
├──────────────┬──────────────┬──────────────┬────────────────┤
│  96 Controllers │  98 Models   │  67 Services │  80 Policies  │
├──────────────┼──────────────┼──────────────┼────────────────┤
│  175 Requests │  81 Resources │  103 Migrations │ 63 DTOs    │
├──────────────┼──────────────┼──────────────┼────────────────┤
│  154 Test Files │  10 AI Svcs │  9 Hacienda  │ 22 Fases ✅   │
├──────────────┴──────────────┴──────────────┴────────────────┤
│  PHPStan L8 ✅ │ 9.2/10 Audit │ Hacienda v4.4 100% │ Redis  │
└─────────────────────────────────────────────────────────────┘
```

### Para Equipo de Ventas
- **Diagrama 1** → "Cada empresa tiene su propia base de datos aislada"
- **Diagrama 3** → "La IA procesa facturas automáticamente"
- **Diagrama 4** → "92% precisión OCR, 98% clasificación tributaria"
- **Diagrama 8** → "93% más rápido con cache inteligente"

### Para Equipo Técnico
- **Diagrama 2** → Flujo completo de firma XAdES-EPES y envío a Hacienda
- **Diagramas 5-6** → ERDs para entender las relaciones de datos
- **Diagrama 7** → 68 permisos, doble capa enforcement  
- **Diagrama 8** → Arquitectura Redis multi-capa + read replicas
