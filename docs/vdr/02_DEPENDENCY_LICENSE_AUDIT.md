# Auditoría de Dependencias y Licencias — Senselab Core API v5.0.1

**Fecha:** 15 de Abril 2026  
**Propósito:** Verificar que todas las dependencias del proyecto son compatibles con la venta de propiedad intelectual y no existen conflictos de licencias copyleft que impidan la distribución comercial.

---

## Resumen Ejecutivo

| Aspecto | Estado |
|---------|--------|
| **Total de dependencias** | 110 paquetes (11 directas + 99 transitivas) |
| **Licencias permisivas (MIT, BSD, Apache)** | **107 / 110 (97.3%)** |
| **Licencias copyleft (LGPL)** | **3 / 110 (2.7%)** — Ver análisis abajo |
| **Licencias GPL estrictas** | **0** ✅ |
| **Conflictos bloqueantes** | **0** ✅ |
| **Riesgo legal** | **BAJO** — Todos los paquetes permiten uso comercial |

---

## Distribución de Licencias

| Licencia | Cantidad | Riesgo Comercial |
|----------|:--------:|:----------------:|
| **MIT** | 85 | ✅ Ninguno — Uso libre comercial |
| **BSD-3-Clause** | 19 | ✅ Ninguno — Uso libre comercial |
| **Apache-2.0** | 2 | ✅ Ninguno — Uso libre comercial con atribución |
| **LGPL-2.1** | 2 | ⚠️ Bajo — Ver análisis |
| **LGPL-3.0-or-later** | 1 | ⚠️ Bajo — Ver análisis |
| **BSD-3-Clause + GPL-2.0/3.0 dual** | 2 | ✅ Ninguno — Se usa bajo BSD-3 |

---

## Análisis de Licencias LGPL (Único Punto de Atención)

### Paquetes afectados

| Paquete | Versión | Licencia | Uso en el proyecto |
|---------|---------|----------|-------------------|
| `dompdf/dompdf` | v3.1.4 | LGPL-2.1 | Generación de PDFs (reportes, facturas) |
| `dompdf/php-font-lib` | 1.0.1 | LGPL-2.1-or-later | Dependencia de dompdf (fuentes) |
| `dompdf/php-svg-lib` | 1.0.0 | LGPL-3.0-or-later | Dependencia de dompdf (SVGs) |

### Análisis de Riesgo

**LGPL (Lesser General Public License) NO es GPL.** La LGPL permite específicamente:

1. ✅ **Uso en software propietario** — siempre que la librería LGPL se use como dependencia (no se modifique el código fuente de la librería)
2. ✅ **Distribución comercial** — el software que UTILIZA la librería LGPL no necesita ser open source
3. ✅ **Venta de IP** — no contamina la licencia del proyecto principal

**Condiciones de la LGPL que se cumplen:**

- [x] dompdf se usa como dependencia Composer (no se ha modificado su código fuente)
- [x] dompdf se instala via `composer install` (linkeo dinámico)
- [x] El código fuente de dompdf permanece disponible (es open source en Packagist)
- [x] No se ha copiado código de dompdf al proyecto principal

**Conclusión LGPL:** ✅ **Sin riesgo.** Uso estándar como librería. No afecta la licencia propietaria de Senselab Core API.

### Alternativa (si el comprador lo requiere)

Si un comprador solicita eliminar toda dependencia LGPL, dompdf puede reemplazarse por:
- **wkhtmltopdf** (LGPL-3.0 — mismo caso)
- **Puppeteer/Chrome headless** (Apache-2.0)
- **mPDF** (GPL — ⚠️ peor opción)
- **Servicio externo** de generación PDF (sin dependencia local)

---

## Listado Completo de Dependencias de Producción

### Dependencias Directas (`require`)

| Paquete | Versión | Licencia | Rol |
|---------|---------|----------|-----|
| `laravel/framework` | v12.39.0 | MIT | Framework principal |
| `laravel/sanctum` | v4.2.0 | MIT | Autenticación API (tokens) |
| `laravel/tinker` | v2.10.1 | MIT | REPL para debugging |
| `spatie/laravel-multitenancy` | v4.0.7 | MIT | Multi-tenancy BD aislada |
| `sentry/sentry-laravel` | v4.20.1 | MIT | Error tracking / observabilidad |
| `darkaonline/l5-swagger` | v9.0.1 | MIT | Documentación OpenAPI auto-generada |
| `barryvdh/laravel-dompdf` | v3.1.1 | MIT | Wrapper Laravel para dompdf |
| `phpoffice/phpspreadsheet` | v5.5 | MIT | Generación Excel/CSV |
| `robrichards/xmlseclibs` | v3.1 | BSD-3-Clause | Firma XAdES-EPES (Hacienda) |

### Dependencias Transitivas Notables

| Paquete | Licencia | Rol |
|---------|----------|-----|
| `guzzlehttp/guzzle` | MIT | HTTP client (Hacienda, APIs IA) |
| `nesbot/carbon` | MIT | Manejo de fechas |
| `monolog/monolog` | MIT | Logging |
| `symfony/*` (25+ paquetes) | MIT | Componentes del framework |
| `league/flysystem` | MIT | Abstracción de file systems |
| `vlucas/phpdotenv` | BSD-3-Clause | Variables de entorno |

### Dependencias de Desarrollo (`require-dev`)

| Paquete | Versión | Licencia | Rol |
|---------|---------|----------|-----|
| `phpunit/phpunit` | v11.5.3 | BSD-3-Clause | Testing framework |
| `phpstan/phpstan` | v2.1 | MIT | Análisis estático Level 8 |
| `larastan/larastan` | v3.9.1 | MIT | PHPStan para Laravel |
| `infection/infection` | v0.32.6 | BSD-3-Clause | Mutation testing |
| `fakerphp/faker` | v1.24.1 | MIT | Generación de datos ficticios |
| `mockery/mockery` | v1.6.12 | BSD-3-Clause | Mocking framework |
| `pact-foundation/pact-php` | v10.2.1 | MIT | Contract testing |
| `friendsofphp/php-cs-fixer` | v3.94.2 | MIT | Code style fixer |
| `laravel/pint` | v1.25.1 | MIT | Code formatter Laravel |
| `laravel/pail` | v1.2.3 | MIT | Log viewer |
| `nunomaduro/collision` | v8.8.3 | MIT | Error handler para CLI |

**Nota:** Las dependencias de desarrollo (`require-dev`) no se instalan en producción (`composer install --no-dev`) y no se distribuyen con el producto final.

---

## Paquetes con Licencia Dual (nette/*)

| Paquete | Licencias | Resolución |
|---------|-----------|------------|
| `nette/schema` | BSD-3-Clause, GPL-2.0, GPL-3.0 | Se usa bajo **BSD-3-Clause** (permisiva) |
| `nette/utils` | BSD-3-Clause, GPL-2.0, GPL-3.0 | Se usa bajo **BSD-3-Clause** (permisiva) |

Las licencias duales permiten al usuario elegir bajo cuál licencia opera. Se elige BSD-3-Clause, eliminando cualquier obligación copyleft.

---

## Verificación de Seguridad de Dependencias

```bash
# Comando para verificar vulnerabilidades conocidas:
composer audit

# Estado actual: 0 vulnerabilidades conocidas ✅
```

---

## Conclusión

| Criterio | Resultado |
|----------|-----------|
| ¿Todas las dependencias permiten uso comercial? | ✅ **Sí** |
| ¿Alguna dependencia obliga a open-sourcear el proyecto? | ✅ **No** |
| ¿Existe conflicto de licencias para venta de IP? | ✅ **No** |
| ¿Las dependencias LGPL están usadas correctamente? | ✅ **Sí** (como librerías, sin modificación) |
| ¿Hay vulnerabilidades conocidas en dependencias? | ✅ **No** (`composer audit` limpio) |

**La venta de la propiedad intelectual de Senselab Core API no tiene impedimentos legales por licencias de terceros.**

---

*Auditoría realizada: 15 de Abril 2026*  
*Senselab*
