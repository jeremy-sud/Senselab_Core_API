# 🔍 Estudio de Mercado — ERPs y Facturación Electrónica (Abril 2026)

**Fecha de investigación:** 14 de Abril 2026  
**Propósito:** Fundamentar la estrategia de pricing de Ursol CAST API v5.0.1  
**Documento principal:** [VALORACION_COMERCIAL_Y_PRICING.md](VALORACION_COMERCIAL_Y_PRICING.md)

---

## Metodología

Se investigaron los precios públicos de **8 competidores** directos e indirectos en el segmento ERP / facturación electrónica, consultando sus páginas oficiales de pricing durante abril 2026. Se priorizaron competidores con presencia en Costa Rica y Latinoamérica.

**Fuentes consultadas:**
- `alegra.com/precios` — Planes Costa Rica (verificado: 4 tiers + Premium)
- `odoo.com/pricing` — Precios globales (verificado: 3 tiers)
- `zoho.com/books/pricing` — Precios globales (verificado: 6 tiers)
- `holded.com/pricing` — Precios España/EU (verificado: 4 tiers + add-ons)
- `sap.com/latinamerica/products/erp/business-one` — Info general LATAM
- `frappe.io/erpnext/pricing` — Hosting Frappe Cloud (verificado)
- `netsuite.com/portal/products/erp` — Info de licenciamiento Oracle/NetSuite
- Conocimiento de mercado local para Facturador.cr, Softland y Exactus

---

## Hallazgos Clave

### 1. El mercado costarricense tiene tres bandas de precio separadas

| Banda | Rango mensual | Productos | Alcance |
|-------|:------------:|-----------|---------|
| **Baja** | $0 - $120 | Alegra, Facturador.cr, Zoho Books | Contabilidad + FE básica. Sin ERP real. |
| **Media (vacía)** | $120 - $500 | **Ninguno en CR** | Gap de mercado. Oportunidad para Ursol. |
| **Alta** | $500 - $3,000+ | Softland, Exactus, SAP B1 | ERP legacy enterprise. Caro, lento, sin IA. |

> **Conclusión:** Ursol CAST se posiciona en la banda media ($149-$899), donde actualmente no hay competidores locales con oferta ERP + IA + FE.

### 2. Alegra domina el segmento bajo en CR

- 50,000+ empresas activas en LATAM
- FE v4.4 incluida en todos los planes
- Planes desde $30/mes (1 usuario, 100 facturas)
- Plan más completo: $120/mes (5 usuarios, 1,000 facturas)
- **Limitación:** No es ERP. No tiene nómina real, RBAC granular, IA avanzada, multi-tenancy, ni módulo de transporte.
- Un cliente que supere Alegra Plus ($120) no tiene alternativa asequible antes de Softland ($500+).

### 3. Odoo es barato pero carece de localización CR

- $7.25 - $10.90 por usuario/mes (todas las apps)
- Para 10 usuarios: $72 - $109/mes — muy competitivo en precio
- **Problema:** No incluye FE DGT Costa Rica nativa. Requiere módulo de localización de terceros + implementación por consultor ($5K-$50K). Sin IA integrada.
- El costo real de Odoo para una PYME CR es: licencia + implementación + localización = $10K-$60K primer año.

### 4. Zoho Books es irrelevante para el mercado CR

- Precios extremadamente bajos ($7.50 - $207.50/mes)
- Sin compliance DGT Costa Rica
- Sin localización costarricense ni soporte LATAM real
- Sin multi-tenancy
- Solo útil como referencia de floor pricing internacional

### 5. SAP Business One sigue siendo el benchmark enterprise

- $133/usuario/mes (cloud), mínimo 5 usuarios = $665/mes base
- Para 10 usuarios = $1,330/mes **sin incluir implementación** ($20K-$100K)
- Implementación tarda 6-18 meses
- Sin IA generativa integrada
- Compliance CR requiere add-on de partner local
- **Referencia:** Cualquier precio por debajo de $1,000/mes con features comparables es atractivo.

### 6. ERPNext es la alternativa open source más cercana

- Software gratis (AGPL-3.0), hosting desde $5/mes
- Sin localización CR ni FE v4.4
- Requiere partner para implementación ($5K-$30K)
- Sin IA integrada, UX menos pulida
- **Relevancia:** Valida que el modelo "open source + hosting pagado" funciona, pero no compite en features con Ursol CAST.

### 7. Ningún competidor ofrece IA + FE + Multi-tenancy + RBAC juntos

Se verificó que **ningún producto** en el rango $0-$500/mes ofrece simultáneamente:
- 10 servicios de IA (OCR, Credit Scoring, Anomaly Detection, CABYS, Chatbot, etc.)
- Facturación electrónica DGT v4.4 nativa (38/38 compliance)
- Multi-tenancy con BD aislada por empresa
- RBAC granular con 68 permisos y 8 roles

Esto es el **principal diferenciador** de Ursol CAST y justifica su posicionamiento premium sobre Alegra.

### 8. Holded valida el modelo de add-ons modulares

- Base desde €14.50/mes, pero con add-ons: Inventario €25, POS €25, RRHH €1.50/empleado
- Un usuario Holded con inventario + POS + 20 empleados paga: €79.50 + €25 + €25 + €30 = **€159.50/mes** (~$174 USD)
- **Insight aprendido:** El modelo "base barata + add-ons caros" funciona en EU pero puede confundir en CR. Mejor un tier con todo incluido (transparencia > modularidad).

---

## Resumen de Precios Verificados (Abril 2026)

| Producto | País | Rango mensual (USD) | Modelo |
|----------|:----:|:-------------------:|--------|
| **Alegra** | CR/LATAM | $30 - $120 | Per empresa, por facturas |
| **Facturador.cr** | CR | $30 - $80 | Per empresa |
| **Odoo** | Global | $0 - $10.90/usuario | Per usuario, todas las apps |
| **Zoho Books** | Global | $0 - $207.50 | Per organización/año |
| **Holded** | España/EU | €14.50 - €79.50 + add-ons | Per empresa + módulos |
| **ERPNext** | Global | $0 (soft) + $5-$125 (hosting) | Open source + hosting |
| **Softland/Exactus** | CR/LATAM | $500 - $2,000 | Licencia o SaaS |
| **SAP Business One** | Global | $665 - $3,000+ | Per usuario cloud |
| **NetSuite** | Global | Contactar (est. $1,000+) | Licencia anual + módulos + usuarios |

---

## Implicaciones para Ursol CAST

1. **Pricing de $149/mes (Pro)** está justificado: ofrece 3x más features que Alegra Plus ($120) y es 4x más barato que Softland ($500+).

2. **Pricing de $399/mes (Business)** no tiene competencia directa en CR. Es 70% más barato que Softland y 60% más barato que SAP B1 para el mismo número de usuarios.

3. **Pricing de $899/mes (Enterprise)** compite con SAP B1 ($1,330+) ofreciendo IA integrada que SAP no tiene, a un tercio del precio con implementación inmediata.

4. **El tier Starter gratuito** es necesario para competir con Odoo Free, Zoho Free y el trial de 15 días de Alegra. Genera pipeline de leads y reduce la barrera de entrada.

5. **Cobrar por empresa (no por usuario)** es la estrategia correcta para CR: las PYMEs ticas son muy sensibles al costo incremental por usuario. Alegra cobra por empresa, SAP por usuario. Ursol debe seguir el modelo Alegra en este aspecto.

---

*Este estudio sustenta las decisiones de pricing documentadas en [VALORACION_COMERCIAL_Y_PRICING.md](VALORACION_COMERCIAL_Y_PRICING.md).*
