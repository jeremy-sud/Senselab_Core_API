# 📂 Virtual Data Room — Ursol CAST API v5.0.1

## Índice de Secciones para Google Sites

Este directorio contiene todos los documentos organizados para el sitio de venta:  
**https://sites.google.com/view/repdevursol/home**

---

## Estructura del Sitio (Páginas de Google Sites)

### Página Principal: Home
- **Archivo a subir:** `01_EXECUTIVE_SUMMARY.md` → Convertir a contenido de la página principal
- **Assets visuales:** Badges SVG de `docs/images/badges/`

---

### Sección 1: Technical Core & Source Code
> *El activo principal — calidad verificada*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 1.1 | Auditoría Técnica 9.2/10 | `docs/AUDITORIA_TECNICA_2026-04-13.md` | Subir PDF |
| 1.2 | Reporte PHPStan Level 8 | Generar con `vendor/bin/phpstan analyse --error-format=json` | Subir JSON/PDF |
| 1.3 | Reporte PHPUnit (tests passing) | Generar con `php artisan test --log-junit=report.xml` | Subir XML/PDF |
| 1.4 | Auditoría de Dependencias & Licencias | `docs/vdr/02_DEPENDENCY_LICENSE_AUDIT.md` | Subir PDF |
| 1.5 | Estado Actual del Proyecto | `ESTADO_ACTUAL_PROYECTO.md` | Subir PDF |
| 1.6 | Roadmap 22/22 Fases Completadas | `ROADMAP.md` | Subir PDF |
| 1.7 | CHANGELOG | `CHANGELOG.md` | Subir PDF |

**⚠️ NO subir el código fuente al sitio público.** El código se entrega post-NDA vía repositorio privado o descarga cifrada.

---

### Sección 2: Arquitectura e Infraestructura
> *Production-Ready desde día 1*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 2.1 | Diagramas Arquitectónicos (9 diagramas) | `docs/diagrams/01-*.md` a `08-*.md` | Renderizar Mermaid → PNG y subir |
| 2.2 | Guía Multi-tenancy | `docs/guides/MULTI_TENANCY.md` | Subir PDF |
| 2.3 | Docker Compose + Dockerfile | `docker-compose.yml`, `Dockerfile` | Subir como referencia (no sensible) |
| 2.4 | Kubernetes Manifests | `kubernetes/base/*.yaml` + `kubernetes/README.md` | Subir PDF resumen |
| 2.5 | CI/CD Pipelines (9 workflows) | `.github/workflows/*.yml` | Capturas o subir YMLs |
| 2.6 | Guía Docker | `docs/guides/DOCKER_GUIDE.md` | Subir PDF |
| 2.7 | Observabilidad (Sentry + OpenTelemetry) | `docs/guides/SENTRY_SETUP.md` + configs | Subir PDF |
| 2.8 | Estrategia de Backups | `docs/guides/BACKUP_STRATEGY.md` | Subir PDF |

---

### Sección 3: Módulos de IA y Lógica de Negocio
> *10 servicios de IA — diferenciador estratégico*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 3.1 | Funcionalidades IA (10 servicios) | `docs/IA_FUNCIONALIDADES.md` | Subir PDF |
| 3.2 | Diagramas IA (ciclo de vida + precisión) | `docs/diagrams/03-ciclo-vida-dato-ia.md` + `04-precision-servicios-ia.md` | Renderizar → PNG |
| 3.3 | Resumen de Controllers (95) | `docs/api/CONTROLLERS_COMPLETE_SUMMARY.md` | Subir PDF |
| 3.4 | Modelo de Datos (98 modelos + relaciones) | `docs/api/MODELS_RELATIONS.md` | Subir PDF |
| 3.5 | Glosario Completo | `docs/GLOSARIO_COMPLETO_URSOL_CAST_API.md` | Subir PDF |
| 3.6 | Curso Completo (onboarding) | `docs/curso_completo_ursol_cast_api.md` | Subir PDF |

---

### Sección 4: Compliance & Legal
> *Seguridad jurídica y regulatoria*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 4.1 | Compliance Hacienda v4.4 (38/38) | `docs/hacienda/ANALISIS_HACIENDA_CR_V44_COMPLETO.md` | Subir PDF |
| 4.2 | Flujo Facturación Electrónica | `docs/hacienda/FACTURACION_ELECTRONICA_API.md` + diagrama flujo | Subir PDF + PNG |
| 4.3 | Setup Facturación Electrónica | `docs/hacienda/FACTURACION_ELECTRONICA_SETUP.md` | Subir PDF |
| 4.4 | Seguridad OWASP Top 10 | `SECURITY.md` | Subir PDF |
| 4.5 | Security & Compliance Summary (VDR) | `docs/vdr/05_SECURITY_COMPLIANCE_SUMMARY.md` | **NUEVO** → Subir PDF |
| 4.6 | Declaración de Propiedad Intelectual | `docs/vdr/04_IP_DECLARATION.md` | **NUEVO** → Subir PDF firmado |
| 4.7 | Licencia de Propiedad Exclusiva | `LICENSE` | Subir PDF |

**⚠️ NO subir:** `credenciales_sandbox_hacienda.txt`, archivos `.env`, API keys.

---

### Sección 5: Documentación de Producto
> *200+ archivos de documentación enterprise*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 5.1 | API Reference (Swagger/OpenAPI) | `storage/api-docs/api-docs.json` | Subir JSON + link a Swagger UI staging |
| 5.2 | Guía de Instalación | `docs/guides/INSTALLATION_GUIDE.md` | Subir PDF |
| 5.3 | Guía de Testing | `docs/guides/TESTING_GUIDE.md` | Subir PDF |
| 5.4 | Guía de Webhooks | `docs/guides/webhook-integration.md` | Subir PDF |
| 5.5 | Guía CI/CD | `docs/guides/CI_CD_GUIDE.md` | Subir PDF |
| 5.6 | Documentación API (referencia) | `docs/api/API_DOCUMENTATION.md` | Subir PDF |
| 5.7 | Histórico de Sprints (21 docs) | `docs/sprints/` | Crear 1 PDF consolidado |
| 5.8 | Release Checklist | `docs/release_checklist.md` | Subir PDF |

---

### Sección 6: Valoración Comercial
> *El caso de negocio*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 6.1 | Valoración Comercial & Pricing | `docs/VALORACION_COMERCIAL_Y_PRICING.md` | Subir PDF |
| 6.2 | Estudio de Mercado ERP 2026 | `docs/ESTUDIO_MERCADO_ERP_2026.md` | Subir PDF |
| 6.3 | Executive Summary (One-Pager) | `docs/vdr/01_EXECUTIVE_SUMMARY.md` | **NUEVO** → Subir PDF |

---

### Sección 7: Guía de Transición (Golden Document)
> *De compra a producción en 48 horas*

| # | Documento | Origen | Acción |
|---|-----------|--------|--------|
| 7.1 | Guía de Transición Tecnológica | `docs/vdr/03_TECHNOLOGY_TRANSITION_GUIDE.md` | **NUEVO** → Subir PDF |

---

## Archivos Nuevos Generados para el VDR

| Archivo | Propósito |
|---------|----------|
| `docs/vdr/01_EXECUTIVE_SUMMARY.md` | One-pager ejecutivo para la página principal |
| `docs/vdr/02_DEPENDENCY_LICENSE_AUDIT.md` | Auditoría de dependencias y licencias |
| `docs/vdr/03_TECHNOLOGY_TRANSITION_GUIDE.md` | "Golden Document" — compra a producción en 48h |
| `docs/vdr/04_IP_DECLARATION.md` | Declaración de propiedad intelectual |
| `docs/vdr/05_SECURITY_COMPLIANCE_SUMMARY.md` | Resumen de seguridad y compliance para inversores |

---

## Nomenclatura para Google Drive / Google Sites

Si organizas archivos en Google Drive como respaldo del sitio, usa esta nomenclatura:

```
📂 Ursol_CAST_API_VDR_v5.0.1/
├── 00_Executive_Summary.pdf
├── 01_Technical_Core/
│   ├── 01.1_Auditoria_Tecnica_9.2.pdf
│   ├── 01.2_PHPStan_Level8_Report.pdf
│   ├── 01.3_PHPUnit_Test_Report.pdf
│   ├── 01.4_Dependency_License_Audit.pdf
│   ├── 01.5_Estado_Actual_Proyecto.pdf
│   ├── 01.6_Roadmap_22_Fases.pdf
│   └── 01.7_Changelog.pdf
├── 02_Architecture_Infrastructure/
│   ├── 02.1_Diagrams/ (9 PNGs)
│   ├── 02.2_Multi_Tenancy_Guide.pdf
│   ├── 02.3_Docker_Compose.pdf
│   ├── 02.4_Kubernetes_Manifests.pdf
│   ├── 02.5_CICD_Pipelines.pdf
│   ├── 02.6_Docker_Guide.pdf
│   ├── 02.7_Observability_Guide.pdf
│   └── 02.8_Backup_Strategy.pdf
├── 03_AI_Business_Logic/
│   ├── 03.1_IA_Funcionalidades.pdf
│   ├── 03.2_IA_Diagrams/ (2 PNGs)
│   ├── 03.3_Controllers_Summary.pdf
│   ├── 03.4_Models_Relations.pdf
│   ├── 03.5_Glosario_Completo.pdf
│   └── 03.6_Curso_Completo.pdf
├── 04_Compliance_Legal/
│   ├── 04.1_Hacienda_v44_Compliance.pdf
│   ├── 04.2_Facturacion_Electronica_Flow.pdf
│   ├── 04.3_FE_Setup.pdf
│   ├── 04.4_OWASP_Security.pdf
│   ├── 04.5_Security_Compliance_Summary.pdf
│   ├── 04.6_IP_Declaration.pdf
│   └── 04.7_License.pdf
├── 05_Product_Documentation/
│   ├── 05.1_OpenAPI_Swagger.json
│   ├── 05.2_Installation_Guide.pdf
│   ├── 05.3_Testing_Guide.pdf
│   ├── 05.4_Webhooks_Guide.pdf
│   ├── 05.5_CICD_Guide.pdf
│   ├── 05.6_API_Reference.pdf
│   ├── 05.7_Sprint_History.pdf
│   └── 05.8_Release_Checklist.pdf
├── 06_Commercial_Valuation/
│   ├── 06.1_Valoracion_Comercial_Pricing.pdf
│   ├── 06.2_Estudio_Mercado_ERP_2026.pdf
│   └── 06.3_Executive_Summary.pdf
└── 07_Transition_Guide/
    └── 07.1_Technology_Transition_Guide.pdf
```

---

## Pasos para Armar el Google Sites

### 1. Convertir Markdowns a PDF
```bash
# Instalar mdpdf o usar pandoc
npm install -g mdpdf
# O con pandoc:
# sudo apt install pandoc texlive-xetex

# Ejemplo con mdpdf:
mdpdf docs/AUDITORIA_TECNICA_2026-04-13.md
mdpdf docs/VALORACION_COMERCIAL_Y_PRICING.md
mdpdf docs/vdr/01_EXECUTIVE_SUMMARY.md
# ... repetir para cada documento
```

### 2. Renderizar Diagramas Mermaid a PNG
```bash
# Instalar mermaid-cli
npm install -g @mermaid-js/mermaid-cli

# Renderizar cada diagrama:
mmdc -i docs/diagrams/01-arquitectura-multi-tenant.md -o diagrams/01-arch.png
mmdc -i docs/diagrams/02-flujo-facturacion-electronica.md -o diagrams/02-fe.png
# ... etc.
```

### 3. Generar Reportes Técnicos
```bash
# PHPStan report
vendor/bin/phpstan analyse --error-format=json > phpstan-report.json

# PHPUnit report
php artisan test --log-junit=phpunit-report.xml

# Code coverage
php artisan test --coverage-html=coverage-report/
```

### 4. Subir a Google Sites
- Crear una página por cada sección
- Embedir PDFs usando el widget "Desde Drive"
- Usar los badges SVG como elementos visuales
- Link al Swagger UI de staging para demo en vivo

---

**Generado:** 15 de Abril 2026  
**Sistemas Ursol S.A.**
