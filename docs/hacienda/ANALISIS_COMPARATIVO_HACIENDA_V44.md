# Análisis Comparativo: Senselab\_Core\_API vs Especificación Hacienda CR (DGT-R-000-2024 v4.4)

**Fecha del análisis:** 7 de abril de 2026\
**Última actualización:** 10 de abril de 2026\
**Versión de la API:** v4.2.0 (FASE 20)\
**Versión del esquema Hacienda:** 4.4\
**Documento de referencia:** Anexo 1 – Estructura XML Comprobantes Electrónicos (97 páginas)\
**Autor:** Auditoría Técnica Automatizada

***

> #### 📋 Estado de Remediación (actualizado 10 de abril de 2026)
>
> **Archivos principales modificados:**
>
> * Migración: `2026_04_07_000000_hacienda_v44_compliance_full.php` (5 tablas nuevas, 20+ columnas)
> * Migración: `2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php` (3 tablas nuevas: `fe_codigo_comercial`, `fe_detalle_surtido`, `fe_surtido_impuesto`)
> * XML: `XmlComprobanteBuilder.php` (OtrosCargos, InformacionReferencia, OtroContenido, Exoneraciones, BaseImponible, Emails múltiples, CodigoComercial {0,5}, DetalleSurtido)
> * Modelos: `ComprobanteElectronicoFe`, `FeLineaDetalle`, + 8 modelos nuevos (incl. `FeCodigoComercial`, `FeDetalleSurtido`, `FeSurtidoImpuesto`)
> * Validaciones: `StoreComprobanteElectronicoRequest`, `CrIdentificacion`
> * Config: `config/hacienda.php` (catálogos completos v4.4)
> * Tests: 49 tests V44 pasando (124 assertions)

| Criticidad |  Total | ✅ Resueltas | ⏳ Fase C |
| :--------: | :----: | :---------: | :------: |
| 🔴 Crítico |    8   |    **8**    |     0    |
|   🟠 Alto  |   11   |    **11**   |     0    |
|  🟡 Medio  |   14   |    **14**   |     0    |
|   🟢 Bajo  |    5   |    **5**    |     0    |
|  **Total** | **38** |    **38**   |   **0**  |

***

## Tabla de Contenidos

1. [Resumen Ejecutivo](ANALISIS_COMPARATIVO_HACIENDA_V44.md#1-resumen-ejecutivo)
2. [Alcance del Análisis](ANALISIS_COMPARATIVO_HACIENDA_V44.md#2-alcance-del-análisis)
3. [Estado Actual de la API](ANALISIS_COMPARATIVO_HACIENDA_V44.md#3-estado-actual-de-la-api)
4. [Análisis Detallado por Sección del Documento](ANALISIS_COMPARATIVO_HACIENDA_V44.md#4-análisis-detallado-por-sección-del-documento)
   * 4.1 [Encabezado del Comprobante](ANALISIS_COMPARATIVO_HACIENDA_V44.md#41-encabezado-del-comprobante)
   * 4.2 [Detalle de Mercancía o Servicio](ANALISIS_COMPARATIVO_HACIENDA_V44.md#42-detalle-de-mercancía-o-servicio)
   * 4.3 [Resumen del Comprobante / Totales / Moneda](ANALISIS_COMPARATIVO_HACIENDA_V44.md#43-resumen-del-comprobante--totales--moneda)
   * 4.4 [Información de Referencia](ANALISIS_COMPARATIVO_HACIENDA_V44.md#44-información-de-referencia)
   * 4.5 [Otros (Uso Comercial)](ANALISIS_COMPARATIVO_HACIENDA_V44.md#45-otros-uso-comercial)
5. [Brechas Críticas — Hacienda Rechazaría el Comprobante](ANALISIS_COMPARATIVO_HACIENDA_V44.md#5-brechas-críticas--hacienda-rechazaría-el-comprobante)
6. [Brechas de Alta Prioridad — Campos Condicionales-Obligatorios](ANALISIS_COMPARATIVO_HACIENDA_V44.md#6-brechas-de-alta-prioridad--campos-condicionales-obligatorios)
7. [Brechas de Prioridad Media](ANALISIS_COMPARATIVO_HACIENDA_V44.md#7-brechas-de-prioridad-media)
8. [Brechas de Baja Prioridad](ANALISIS_COMPARATIVO_HACIENDA_V44.md#8-brechas-de-baja-prioridad)
9. [Análisis de Validaciones](ANALISIS_COMPARATIVO_HACIENDA_V44.md#9-análisis-de-validaciones)
10. [Análisis de Estructura de Base de Datos](ANALISIS_COMPARATIVO_HACIENDA_V44.md#10-análisis-de-estructura-de-base-de-datos)
11. [Análisis del Generador XML](ANALISIS_COMPARATIVO_HACIENDA_V44.md#11-análisis-del-generador-xml)
12. [Tabla Consolidada de Brechas](ANALISIS_COMPARATIVO_HACIENDA_V44.md#12-tabla-consolidada-de-brechas)
13. [Plan de Remediación Recomendado](ANALISIS_COMPARATIVO_HACIENDA_V44.md#13-plan-de-remediación-recomendado)
14. [Apéndice A: Mapeo Completo Campo por Campo](ANALISIS_COMPARATIVO_HACIENDA_V44.md#apéndice-a-mapeo-completo-campo-por-campo)
15. [Apéndice B: Archivos Relevantes del Proyecto](ANALISIS_COMPARATIVO_HACIENDA_V44.md#apéndice-b-archivos-relevantes-del-proyecto)

***

## 1. Resumen Ejecutivo

La API Senselab-Core cuenta con una implementación **sólida** de facturación electrónica para Costa Rica, incluyendo soporte para la versión 4.4 del esquema de Hacienda. Sin embargo, tras un análisis exhaustivo comparando campo por campo con la especificación oficial, se identificaron **38 brechas** distribuidas así:

| Criticidad | Cantidad | Descripción                                 |
| :--------: | :------: | ------------------------------------------- |
| 🔴 Crítico |   **8**  | Hacienda rechazaría el comprobante          |
|   🟠 Alto  |  **11**  | Campos condicionales-obligatorios faltantes |
|  🟡 Medio  |  **14**  | Campos condicionales no implementados       |
|   🟢 Bajo  |   **5**  | Brechas menores o de uso muy específico     |

**Elementos correctamente implementados:**

* ✅ Generación de Clave Numérica (50 dígitos)
* ✅ Firma XAdES-EPES con política v4.4
* ✅ Integración OAuth 2.0 con API Hacienda
* ✅ Rate Limiting inteligente
* ✅ ProveedorSistemas (nuevo v4.4)
* ✅ CodigoActividadEmisor (renombrado v4.4)
* ✅ BaseImponible por línea (nuevo v4.4)
* ✅ ImpuestoNeto por línea (nuevo v4.4)
* ✅ CodigoCABYS (nuevo v4.4)
* ✅ CodigoTipoMoneda con TipoCambio
* ✅ Estructura Emisor con Ubicación
* ✅ Certificados digitales .p12

***

## 2. Alcance del Análisis

### Documentos de Hacienda revisados:

* **Anexo 1** – Estructura XML v4.4 (páginas 18-57): Encabezado, Detalle, Resumen, Referencias, Otros
* Secciones: a) Datos del Encabezado, b) Detalle de mercancía/servicio, c) Resumen/Totales, d) Información de referencia, e) Otros

### Tipos de comprobante cubiertos:

| Código | Tipo                               | Abreviatura |
| :----: | ---------------------------------- | :---------: |
|   01   | Factura Electrónica                |      FE     |
|   02   | Nota de Débito Electrónica         |      ND     |
|   03   | Nota de Crédito Electrónica        |      NC     |
|   04   | Tiquete Electrónico                |      TE     |
|   08   | Factura Electrónica de Compras     |     FEC     |
|   09   | Factura Electrónica de Exportación |     FEE     |
|   10   | Recibo Electrónico de Pago         |     REP     |

### Condiciones de campo (según Hacienda):

| Código | Significado                                        |
| :----: | -------------------------------------------------- |
|  **1** | Obligatorio                                        |
|  **2** | Condicional (obligatorio bajo ciertas condiciones) |
|  **3** | Opcional                                           |
|  **4** | No aplica                                          |

### Archivos de la API analizados:

* 10 Modelos (Models)
* 9 Migraciones de base de datos
* 6 Servicios (Services)
* 3 Generadores/Builders XML
* 2 Reglas de validación custom
* 1 FormRequest
* 1 DTO
* 1 Archivo de configuración
* 14 Archivos de rutas

***

## 3. Estado Actual de la API

### 3.1 Arquitectura de Facturación Electrónica

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE UN COMPROBANTE                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. CREAR ──► ComprobanteElectronicoFe (DB)                     │
│       │       - tipo_documento, receptor, líneas, montos         │
│       │       - Estado: "pendiente"                              │
│       │                                                          │
│  2. CLAVE ──► ClaveNumericaGenerator                            │
│       │       - 50 dígitos: país(5)+fecha(8)+cédula(12)         │
│       │         +consecutivo(20)+situación(1)+seguridad(8)      │
│       │                                                          │
│  3. HACIENDA ──► HaciendaIntegrationService                    │
│       │         - Crea HaciendaComprobante                      │
│       │         - Estado: "pending"                              │
│       │                                                          │
│  4. XML ──► XmlComprobanteBuilder                               │
│       │     - Estructura v4.4 completa                           │
│       │     - Guarda en: xml_contenido                          │
│       │                                                          │
│  5. FIRMA ──► FirmaDigitalService + XadesEpesSigner            │
│       │       - Carga certificado .p12                          │
│       │       - XAdES-EPES con política v4.4                    │
│       │       - Estado: "signed"                                │
│       │                                                          │
│  6. ENVÍO ──► HaciendaApiClient                                │
│       │       - OAuth2 token (OAuthTokenManager)                │
│       │       - Rate limit (RateLimiter)                        │
│       │       - POST /recepcion/v1                              │
│       │       - Estado: "sent"                                  │
│       │                                                          │
│  7. RESPUESTA ──► MensajeHacienda                              │
│       │           - accepted / rejected                          │
│       │           - XML respuesta                                │
│       │                                                          │
│  8. WEBHOOK ──► FacturaEmitidaEvent                             │
│               - Notifica sistemas externos                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Modelos de Datos Existentes

| Modelo                           | Tabla                                 | Propósito                               |
| -------------------------------- | ------------------------------------- | --------------------------------------- |
| `ComprobanteElectronicoFe`       | `comprobantes_electronicos_fe`        | Comprobante de emisión principal        |
| `FeLineaDetalle`                 | `fe_lineas_detalle`                   | Líneas de detalle (productos/servicios) |
| `HaciendaComprobante`            | `hacienda_comprobantes`               | Tracking de envíos a Hacienda           |
| `ComprobanteRecibidoElectronico` | `comprobantes_recibidos_electronicos` | Comprobantes recibidos de proveedores   |
| `ConsecutivoFe`                  | `consecutivos_fe`                     | Control de secuencias numéricas         |
| `TipoComprobanteFe`              | `tipos_comprobantes_fe`               | Catálogo de tipos de comprobante        |
| `MensajeHacienda`                | `mensajes_hacienda`                   | Respuestas de Hacienda                  |
| `FeCertificadoDigital`           | `fe_certificados_digitales`           | Certificados .p12                       |
| `FeOAuthToken`                   | `fe_oauth_tokens`                     | Tokens OAuth 2.0                        |

### 3.3 Servicios de Integración

| Servicio                        | Ubicación                    | Función                              |
| ------------------------------- | ---------------------------- | ------------------------------------ |
| `HaciendaIntegrationService`    | `app/Services/Hacienda/`     | Orquestación del flujo completo      |
| `HaciendaApiClient`             | `app/Services/Hacienda/`     | Cliente HTTP para API Hacienda       |
| `OAuthTokenManager`             | `app/Services/Hacienda/`     | Gestión de tokens OAuth 2.0          |
| `RateLimiter`                   | `app/Services/Hacienda/`     | Rate limiting (8 req/s, 480 req/min) |
| `XmlComprobanteBuilder`         | `app/Services/Hacienda/Xml/` | Constructor de XML v4.4              |
| `FirmaDigitalService`           | `app/Services/Hacienda/Xml/` | Firma XAdES-EPES                     |
| `XadesEpesSigner`               | `app/Services/Hacienda/Xml/` | Implementación XAdES-EPES            |
| `ComprobanteElectronicoService` | `app/Services/`              | Lógica de negocio comprobantes       |
| `ConsecutivoFeService`          | `app/Services/`              | Gestión de consecutivos              |
| `MensajeHaciendaService`        | `app/Services/`              | Gestión mensajes Hacienda            |

***

## 4. Análisis Detallado por Sección del Documento

### 4.1 Encabezado del Comprobante

#### Campos de Identificación

| Etiqueta                  |  Tamaño  | Condición (FE/FEE/FEC/TE/NC/ND/REP) |   API Estado   | Detalle                                                     |
| ------------------------- | :------: | :---------------------------------: | :------------: | ----------------------------------------------------------- |
| `Clave`                   |    50    |            1/1/1/1/1/1/1            | ✅ Implementado | Generado por `ClaveNumericaGenerator`. 50 dígitos correcto. |
| `ProveedorSistemas`       |    20    |            1/1/1/1/1/1/1            | ✅ Implementado | Campo en modelo `Empresa`. Generado en XML v4.4.            |
| `CodigoActividadEmisor`   |     6    |            1/1/3/1/1/1/4            | ✅ Implementado | Usa `actividad_economica_principal` de Empresa.             |
| `CodigoActividadReceptor` |     6    |            2/4/1/4/2/2/4            | ❌ **FALTANTE** | No existe campo ni XML. Obligatorio en FEC.                 |
| `NumeroConsecutivo`       |    20    |            1/1/1/1/1/1/1            | ✅ Implementado | Via `ConsecutivoFeService`.                                 |
| `FechaEmision`            | DateTime |            1/1/1/1/1/1/1            | ✅ Implementado | Formato RFC3339.                                            |

#### Datos del Emisor

| Etiqueta                    |  Tamaño |  Condición (FE)  |    API Estado    | Detalle                                                        |
| --------------------------- | :-----: | :--------------: | :--------------: | -------------------------------------------------------------- |
| `Emisor`                    | Complex |         1        |         ✅        | Datos desde modelo Empresa.                                    |
| `> Nombre`                  |   100   |         1        |         ✅        | `razon_social` de Empresa. Min 5, max 100.                     |
| `> Identificacion > Tipo`   |    2    |         1        |         ✅        | `tipo_identificacion` de Empresa.                              |
| `> Identificacion > Numero` |    20   |         1        |         ✅        | `num_identificacion_dgt` de Empresa.                           |
| `> Registrofiscal8707`      |    12   |         2        |  ❌ **FALTANTE**  | Para bebidas alcohólicas Ley 8707. Campo inexistente.          |
| `> NombreComercial`         |    80   |         3        | ⚠️ **NO EN XML** | Campo existe en Empresa pero NO se genera en XML builder.      |
| `> Ubicacion`               | Complex |         1        |         ✅        | Parcialmente: usa datos de Empresa.                            |
| `> > Provincia`             |    1    |         1        |         ✅        | Desde `empresa->provincia`.                                    |
| `> > Canton`                |    2    |         1        |         ✅        | Desde `empresa->canton`.                                       |
| `> > Distrito`              |    2    |         1        |         ✅        | Desde `empresa->distrito`.                                     |
| `> > Barrio`                |    50   |         3        |    ⚠️ **BUG**    | XML intenta usar `empresa->barrio` pero CAMPO NO EXISTE en DB. |
| `> > OtrasSenas`            |   250   |         1        |         ✅        | Usa `empresa->direccion`.                                      |
| `> OtrasSenasExtranjero`    |   300   | 4 (FE) / 2 (FEC) |  ❌ **FALTANTE**  | Para tipo identificación 05 (Extranjero No Domiciliado).       |
| `> Telefono > CodigoPais`   |    3    |  1 (si hay tel)  |         ✅        | Hardcoded "506".                                               |
| `> Telefono > NumTelefono`  |    20   |  1 (si hay tel)  |         ✅        | Desde `empresa->telefono`.                                     |
| `> CorreoElectronico {1,4}` |   160   |   1/1/2/1/1/1/1  |  ⚠️ **PARCIAL**  | Solo soporta 1 correo. Spec permite 1-4.                       |

#### Datos del Receptor

| Etiqueta                    |  Tamaño | Condición (FE) |   API Estado   | Detalle                                                   |
| --------------------------- | :-----: | :------------: | :------------: | --------------------------------------------------------- |
| `Receptor`                  | Complex |        1       |        ✅       | Datos básicos implementados.                              |
| `> Nombre`                  |   100   |        1       |        ✅       | `receptor_nombre`. Min 3, max 100.                        |
| `> Identificacion > Tipo`   |    2    |        1       |        ✅       | `receptor_tipo_identificacion`.                           |
| `> Identificacion > Numero` |    20   |        1       |        ✅       | `receptor_numero_identificacion`.                         |
| `> NombreComercial`         |    80   |        3       | ❌ **FALTANTE** | No existe campo ni generación XML.                        |
| `> Ubicacion`               | Complex |     2 (FE)     | ❌ **FALTANTE** | No existen campos de ubicación del receptor.              |
| `> > Provincia`             |    1    |        1       |        ❌       | `receptor_provincia` NO existe en tabla.                  |
| `> > Canton`                |    2    |        1       |        ❌       | `receptor_canton` NO existe en tabla.                     |
| `> > Distrito`              |    2    |        1       |        ❌       | `receptor_distrito` NO existe en tabla.                   |
| `> > Barrio`                |    50   |        3       |        ❌       | `receptor_barrio` NO existe en tabla.                     |
| `> > OtrasSenas`            |   160   |        1       |        ❌       | `receptor_otras_senas` NO existe en tabla.                |
| `> OtrasSenasExtranjero`    |   300   |        2       | ❌ **FALTANTE** | Para Extranjero No Domiciliado.                           |
| `> Telefono`                | Complex |        3       | ❌ **FALTANTE** | No hay campos de teléfono del receptor.                   |
| `> CorreoElectronico`       |   160   |        2       | ⚠️ **PARCIAL** | Existe como `receptor_email` VARCHAR(100). Spec: max 160. |

#### Condiciones de Venta

| Etiqueta              | Tamaño |   Condición   |   API Estado   | Detalle                                                   |
| --------------------- | :----: | :-----------: | :------------: | --------------------------------------------------------- |
| `CondicionVenta`      |    2   | 1/1/1/1/1/1/1 |        ✅       | Implementado. Pero validación incompleta (falta 05-15).   |
| `CondicionVentaOtros` |   100  |       2       | ❌ **FALTANTE** | Obligatorio cuando CondicionVenta=99. No existe campo.    |
| `PlazoCredito`        |    5   |       2       |        ✅       | Existe `plazo_credito`. Obligatorio si condición=02 o 10. |

***

### 4.2 Detalle de Mercancía o Servicio

#### Estructura `LineaDetalle`

| Etiqueta                       |  Tamaño |  Condición (FE)  |      API Estado     | Detalle                                                                    |
| ------------------------------ | :-----: | :--------------: | :-----------------: | -------------------------------------------------------------------------- |
| `DetalleServicio`              | Complex |         2        |          ✅          | Implementado via relación `lineasDetalle()`.                               |
| `> LineaDetalle {1,1000}`      | Complex |         1        |          ✅          | Modelo `FeLineaDetalle`. Hasta 1000 líneas.                                |
| `> > NumeroLinea`              |  PosInt |         1        |          ✅          | `numero_linea`.                                                            |
| `> > PartidaArancelaria`       |    12   | 4 (FE) / 2 (FEE) |    ❌ **FALTANTE**   | Necesario para exportación. No existe campo.                               |
| `> > CodigoCABYS`              |    13   |         1        |          ✅          | `codigo_cabys`. Agregado en v4.4.                                          |
| `> > CodigoComercial {0,5}`    | Complex |         2        |    ⚠️ **PARCIAL**   | Solo 1 campo `codigo_comercial`. Spec: hasta 5 con estructura Tipo+Codigo. |
| `> > > Tipo`                   |    2    |         1        |          ⚠️         | Existe `codigo_tipo` pero no se genera como sub-estructura.                |
| `> > > Codigo`                 |    20   |         1        |          ⚠️         | Existe `codigo` pero sin estructura jerárquica.                            |
| `> > Cantidad`                 |   16,3  |         1        |          ✅          | `cantidad` DECIMAL(18,5).                                                  |
| `> > UnidadMedida`             |    15   |         1        |          ✅          | `unidad_medida`. Default 'Unid'.                                           |
| `> > TipoTransaccion`          |    2    |         2        | ⚠️ **NO CONECTADO** | Campo en migración v4.4, pero NO en model fillable ni XML builder.         |
| `> > UnidadMedidaComercial`    |    20   |         3        |          ✅          | `unidad_medida_comercial`.                                                 |
| `> > Detalle`                  |   200   |         1        |          ✅          | `detalle`. Min 3, max 200.                                                 |
| `> > NumeroVINoSerie {1,1000}` |    17   |         2        |    ❌ **FALTANTE**   | Para vehículos/aeronaves/embarcaciones.                                    |
| `> > RegistroMedicamento`      |   100   |         2        |    ❌ **FALTANTE**   | Para medicamentos con registro sanitario.                                  |
| `> > FormaFarmaceutica`        |    3    |         2        |    ❌ **FALTANTE**   | Código forma farmacéutica.                                                 |
| `> > DetalleSurtido`           | Complex |         2        |    ❌ **FALTANTE**   | Estructura completa de surtidos/combos.                                    |
| `> > PrecioUnitario`           |   18,5  |         1        |          ✅          | `precio_unitario`.                                                         |
| `> > MontoTotal`               |   18,5  |         1        |          ✅          | `monto_total` = cantidad × precio.                                         |

#### Descuentos por Línea

| Etiqueta                    |  Tamaño | Condición |   API Estado   | Detalle                                                |
| --------------------------- | :-----: | :-------: | :------------: | ------------------------------------------------------ |
| `> > Descuento {0,5}`       | Complex |     3     | ⚠️ **PARCIAL** | Solo 1 descuento. Spec: hasta 5 secuenciales.          |
| `> > > MontoDescuento`      |   18,5  |     1     |        ✅       | `monto_descuento`.                                     |
| `> > > CodigoDescuento`     |    2    |     1     | ❌ **FALTANTE** | **OBLIGATORIO** cuando hay descuento. No existe campo. |
| `> > > CodigoDescuentoOTRO` |   100   |     2     | ❌ **FALTANTE** | Cuando código=99.                                      |
| `> > > NaturalezaDescuento` |    80   |     2     |        ✅       | `naturaleza_descuento`. Pero solo para código=99.      |

#### Impuestos por Línea

| Etiqueta                        |  Tamaño |     Condición    |    API Estado   | Detalle                                                 |
| ------------------------------- | :-----: | :--------------: | :-------------: | ------------------------------------------------------- |
| `> > Impuesto {1,1000}`         | Complex |         1        | ⚠️ **LIMITADO** | **Solo 1 impuesto por línea.** Spec: hasta 1000.        |
| `> > > Codigo`                  |    2    |         1        |        ✅        | `impuesto_codigo`.                                      |
| `> > > CodigoImpuestoOTRO`      |   100   |         2        |  ❌ **FALTANTE** | Cuando código=99.                                       |
| `> > > CodigoTarifaIVA`         |    2    |         2        |        ✅        | `impuesto_codigo_tarifa`.                               |
| `> > > Tarifa`                  |   4,2   |         2        |        ✅        | `impuesto_tarifa`.                                      |
| `> > > FactorCalculoIVA`        |   5,4   |         2        |  ❌ **FALTANTE** | Para bienes usados (código impuesto 08).                |
| `> > > DatosImpuestoEspecifico` | Complex |         2        |  ❌ **FALTANTE** | Para códigos 03,04,05,06 (Combustibles, Alcohol, etc.). |
| `> > > > CantidadUnidadMedida`  |   7,2   |         1        |        ❌        | Parte de impuestos específicos.                         |
| `> > > > Porcentaje`            |   4,2   |         2        |        ❌        | Para código 04 (Bebidas Alcohólicas).                   |
| `> > > > Proporcion`            |   5,2   |         2        |        ❌        | Cálculo: CantidadUnidadMedida × Porcentaje.             |
| `> > > > VolumenUnidadConsumo`  |   7,2   |         2        |        ❌        | Para código 05 (Bebidas sin alcohol).                   |
| `> > > > ImpuestoUnidad`        |   18,5  |         1        |        ❌        | Monto impuesto por unidad.                              |
| `> > > Monto`                   |   18,5  |         1        |        ✅        | `impuesto_monto`.                                       |
| `> > > MontoExportacion`        |   18,5  | 4 (FE) / 2 (FEE) |  ❌ **FALTANTE** | Para exportación de mercancías.                         |

#### Exoneración por Línea

| Etiqueta                         |  Tamaño  | Condición |   API Estado   | Detalle                                   |
| -------------------------------- | :------: | :-------: | :------------: | ----------------------------------------- |
| `> > > Exoneracion`              |  Complex |     2     | ⚠️ **PARCIAL** | Implementación básica.                    |
| `> > > > TipoDocumentoEX1`       |     2    |     1     |        ✅       | `exoneracion_tipo_documento`.             |
| `> > > > TipoDocumentoOTRO`      |    100   |     2     | ❌ **FALTANTE** | Cuando tipo=99.                           |
| `> > > > NumeroDocumento`        |    40    |     1     |        ✅       | `exoneracion_numero_documento`.           |
| `> > > > Articulo`               |     6    |     2     | ❌ **FALTANTE** | Artículo de ley (códigos 02,03,06,07,08). |
| `> > > > Inciso`                 |     6    |     2     | ❌ **FALTANTE** | Inciso del artículo.                      |
| `> > > > NombreInstitucion`      |    160   |     1     |        ✅       | `exoneracion_nombre_institucion`.         |
| `> > > > NombreInstitucionOtros` |    160   |     2     | ❌ **FALTANTE** | Cuando institución=99.                    |
| `> > > > FechaEmisionEX`         | DateTime |     1     |        ✅       | `exoneracion_fecha_emision`.              |
| `> > > > tarifaexonerada`        |    4,2   |     1     | ❌ **FALTANTE** | Puntos de tarifa exonerados.              |
| `> > > > MontoExoneracion`       |   18,5   |     1     |        ✅       | `exoneracion_monto`.                      |

#### Campos Adicionales por Línea

| Etiqueta                           | Tamaño | Condición |   API Estado   | Detalle                                |
| ---------------------------------- | :----: | :-------: | :------------: | -------------------------------------- |
| `> > SubTotal`                     |  18,5  |     1     |        ✅       | `subtotal`.                            |
| `> > IVACobradoFabrica`            |    2   |     2     | ❌ **FALTANTE** | Cuando IVA cobrado a nivel de fábrica. |
| `> > BaseImponible`                |  18,5  |     1     |        ✅       | `base_imponible`. Implementado v4.4.   |
| `> > ImpuestoAsumidoEmisorFabrica` |  18,5  |   1 (FE)  | ❌ **FALTANTE** | Para regalías/bonificaciones/fábrica.  |
| `> > ImpuestoNeto`                 |  18,5  |     1     |        ✅       | `impuesto_neto`. Implementado v4.4.    |
| `> > MontoTotalLinea`              |  18,5  |     1     |        ✅       | `monto_total_linea`.                   |

***

### 4.3 Resumen del Comprobante / Totales / Moneda

#### Moneda

| Etiqueta             |  Tamaño | Condición | API Estado | Detalle                         |
| -------------------- | :-----: | :-------: | :--------: | ------------------------------- |
| `ResumenFactura`     | Complex |     1     |      ✅     | Implementado.                   |
| `> CodigoTipoMoneda` | Complex |     1     |      ✅     | Estructura correcta.            |
| `> > CodigoMoneda`   |    3    |     1     |      ✅     | `moneda`. Default CRC.          |
| `> > TipoCambio`     |   18,5  |     1     |      ✅     | `tipo_cambio`. Default 1.00000. |

#### Totales por Categoría

| Etiqueta                  | Tipo | Condición (FE) |            API en DB            |    API en XML   | Estado |
| ------------------------- | :--: | :------------: | :-----------------------------: | :-------------: | :----: |
| `TotalServGravados`       | 18,5 |        2       |   ✅ `total_servicios_gravados`  |        ✅        |   OK   |
| `TotalServExentos`        | 18,5 |        2       |   ✅ `total_servicios_exentos`   |        ✅        |   OK   |
| `TotalServExonerado`      | 18,5 |        2       |  ✅ `total_servicios_exonerados` | ❌ **NO EN XML** |   ⚠️   |
| `TotalServNoSujeto`       | 18,5 |        2       |         ❌ **NO EXISTE**         |        ❌        |    ❌   |
| `TotalMercanciasGravadas` | 18,5 |        2       |  ✅ `total_mercancias_gravadas`  |        ✅        |   OK   |
| `TotalMercanciasExentas`  | 18,5 |        2       |   ✅ `total_mercancias_exentas`  |        ✅        |   OK   |
| `TotalMercExonerada`      | 18,5 |        2       | ✅ `total_mercancias_exoneradas` | ❌ **NO EN XML** |   ⚠️   |
| `TotalMercNoSujeta`       | 18,5 |        2       |         ❌ **NO EXISTE**         |        ❌        |    ❌   |
| `TotalGravado`            | 18,5 |        2       |        ✅ `total_gravado`        |        ✅        |   OK   |
| `TotalExento`             | 18,5 |        2       |         ✅ `total_exento`        |        ✅        |   OK   |
| `TotalExonerado`          | 18,5 |        2       |       ✅ `total_exonerado`       | ❌ **NO EN XML** |   ⚠️   |
| `TotalNoSujeto`           | 18,5 |        2       |         ❌ **NO EXISTE**         |        ❌        |    ❌   |
| `TotalVenta`              | 18,5 |        1       |                ✅                |        ✅        |   OK   |
| `TotalDescuentos`         | 18,5 |        2       |                ✅                |        ✅        |   OK   |
| `TotalVentaNeta`          | 18,5 |        1       |                ✅                |        ✅        |   OK   |

#### Desglose de Impuestos y Totales Finales

| Etiqueta                         |   Tipo  | Condición |     API Estado    | Detalle                                              |
| -------------------------------- | :-----: | :-------: | :---------------: | ---------------------------------------------------- |
| `TotalDesgloseImpuesto {1,1000}` | Complex |     2     | ⚠️ **INCOMPLETO** | Falta `CodigoTarifaIVA` en el desglose.              |
| `> Codigo`                       |    2    |     1     |         ✅         | Agrupado por código de impuesto.                     |
| `> CodigoTarifaIVA`              |    2    |     2     |   ❌ **FALTANTE**  | Debería desglosar per tarifa también.                |
| `> TotalMontoImpuesto`           |   18,5  |     1     |         ✅         | Sumatoria de montos de impuesto.                     |
| `TotalImpuesto`                  |   18,5  |     2     |         ✅         | `total_impuesto`.                                    |
| `TotalImpAsumEmisorFabrica`      |   18,5  |     2     |   ❌ **FALTANTE**  | Total impuestos asumidos por emisor/fábrica.         |
| `TotalIVADevuelto`               |   18,5  |     2     |         ⚠️        | Campo existe en DB pero NO se genera en XML.         |
| `TotalOtrosCargos`               |   18,5  |     2     |         ⚠️        | Solo como campo numérico. Sin estructura de detalle. |
| `TotalComprobante`               |   18,5  |     1     |         ✅         | `total_comprobante`.                                 |

#### Medios de Pago

| Etiqueta           |   Tipo  | Condición |    API Estado   | Detalle                                           |
| ------------------ | :-----: | :-------: | :-------------: | ------------------------------------------------- |
| `MedioPago {1,4}`  | Complex |     2     | ⚠️ **LIMITADO** | Solo 1 medio de pago como campo enum.             |
| `> TipoMedioPago`  |    2    |     2     |        ✅        | `medio_pago`. Pero solo 1. Spec: hasta 4.         |
| `> MedioPagoOtros` |   100   |     2     |  ❌ **FALTANTE** | Descripción cuando tipo=99.                       |
| `> TotalMedioPago` |   18,5  |     2     |        ⚠️       | Se genera en XML pero no es por medio individual. |

***

### 4.4 Información de Referencia

| Etiqueta                       |   Tipo   | Condición (FE/NC/ND/REP) |   API Estado   | Detalle                                           |
| ------------------------------ | :------: | :----------------------: | :------------: | ------------------------------------------------- |
| `InformacionReferencia {0,10}` |  Complex |          2/1/1/1         | ⚠️ **PARCIAL** | Implementado básico, almacenado en metadata JSON. |
| `> TipoDocIR`                  |     2    |             1            |        ✅       | Tipo de documento de referencia.                  |
| `> TipoDocRefOTRO`             |    100   |             2            | ❌ **FALTANTE** | Cuando tipo=99.                                   |
| `> Numero`                     |    50    |             2            |        ✅       | Clave numérica del documento referenciado.        |
| `> FechaEmisionIR`             | DateTime |             1            |        ✅       | Fecha del documento referenciado.                 |
| `> Codigo`                     |     2    |             2            |        ✅       | Código de referencia (nota 9).                    |
| `> CodigoReferenciaOTRO`       |    100   |             2            | ❌ **FALTANTE** | Cuando código=99.                                 |
| `> Razon`                      |    180   |             2            |        ✅       | Razón de la referencia.                           |

**Problema adicional:** No es tabla independiente. Se almacena en JSON dentro del campo `metadata` del comprobante. No permite búsquedas eficientes ni validación relacional.

***

### 4.5 Otros (Uso Comercial)

| Etiqueta                |   Tipo  | Condición |   API Estado   | Detalle                       |
| ----------------------- | :-----: | :-------: | :------------: | ----------------------------- |
| `Otros`                 | Complex |     3     |        ✅       | Implementado parcial.         |
| `> OtroTexto {0,∞}`     |   500   |     3     |        ✅       | Generado en XML si hay datos. |
| `> OtroContenido {0,∞}` |   500   |     3     | ❌ **FALTANTE** | No implementado.              |

***

### 4.6 Sección de Otros Cargos

| Etiqueta                  |   Tipo  | Condición |   API Estado   | Detalle                                    |
| ------------------------- | :-----: | :-------: | :------------: | ------------------------------------------ |
| `OtrosCargos {0,15}`      | Complex |     2     | ❌ **FALTANTE** | Solo existe `total_otros_cargos` numérico. |
| `> TipoDocumentoOC`       |    2    |     1     |        ❌       | Tipo de documento otros cargos (nota 16).  |
| `> TipoDocumentoOTROS`    |   100   |     2     |        ❌       | Cuando tipo=99.                            |
| `> IdentificacionTercero` | Complex |     2     |        ❌       | Para cobra a terceros (código 04).         |
| `> > Tipo`                |    2    |     1     |        ❌       | Tipo identificación tercero.               |
| `> > Numero`              |    20   |     1     |        ❌       | Número identificación tercero.             |
| `> NombreTercero`         |   100   |     2     |        ❌       | Nombre del tercero.                        |
| `> Detalle`               |   160   |     1     |        ❌       | Descripción del cargo.                     |
| `> PorcentajeOC`          |   9,5   |     2     |        ❌       | Porcentaje del cargo.                      |
| `> MontoCargo`            |   18,5  |     1     |        ❌       | Monto total del cargo.                     |

***

## 5. Brechas Críticas — Hacienda Rechazaría el Comprobante

### BRECHA #1: `clave` VARCHAR(29) en `hacienda_comprobantes`

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `database/migrations/2025_02_08_create_hacienda_comprobantes_table.php`\
**Documento Hacienda:** Clave = String(50), campo fijo de 50 posiciones (pág. 18)\
**Estado API:** La tabla `hacienda_comprobantes` define `clave` como `VARCHAR(29)`. La tabla `comprobantes_electronicos_fe` sí usa `VARCHAR(50)`.

**Impacto:** Claves numéricas se truncarían al guardarse en `hacienda_comprobantes`, causando:

* Pérdida de datos de seguimiento
* Inconsistencia entre tablas
* Imposibilidad de consultar estado por clave

**Corrección requerida:** Migración ALTER TABLE para ampliar a VARCHAR(50).

***

### BRECHA #2: `CodigoDescuento` obligatorio cuando hay descuento

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `fe_lineas_detalle` (tabla), `XmlComprobanteBuilder` (XML)\
**Documento Hacienda:** Ver Nota 20. Obligatorio cuando se incluye `MontoDescuento`. String(2). (pág. 35)\
**Estado API:** No existe campo `codigo_descuento` en tabla ni modelo. No se genera en XML.

**Impacto:** Todo comprobante con descuento será rechazado por Hacienda.

**Corrección requerida:**

* Agregar columna `codigo_descuento` VARCHAR(2) a `fe_lineas_detalle`
* Agregar al model fillable
* Agregar generación en XML builder dentro de `<Descuento>`

***

### BRECHA #3: `TotalDesgloseImpuesto` sin `CodigoTarifaIVA`

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `XmlComprobanteBuilder::agregarResumenFactura()`\
**Documento Hacienda:** Debe agrupar por `Codigo` + `CodigoTarifaIVA`, cada combinación con su `TotalMontoImpuesto`. (pág. 52)\
**Estado API:** Solo agrupa por `Codigo` de impuesto, sin desglosar por tarifa IVA.

**Impacto:** Si un comprobante tiene líneas con tarifa 13% y otras con 4%, Hacienda rechazará porque no puede verificar la consistencia de los montos por tarifa.

**Corrección requerida:** Modificar lógica de agrupación para iterar por `(codigo_impuesto, codigo_tarifa_iva)`.

***

### BRECHA #4: Ubicación del Receptor no almacenada

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `comprobantes_electronicos_fe` (tabla), `ComprobanteElectronicoFe` (modelo)\
**Documento Hacienda:** Receptor > Ubicacion es obligatorio (condición 1-2) en FE, FEC, NC, ND. Requiere Provincia, Canton, Distrito, OtrasSenas. (pág. 23-24)\
**Estado API:** No existen campos: `receptor_provincia`, `receptor_canton`, `receptor_distrito`, `receptor_barrio`, `receptor_otras_senas`.

**Impacto:** Facturas Electrónicas sin ubicación del receptor serán rechazadas.

**Corrección requerida:**

* Migración agregando 5 columnas al comprobante
* Actualizar modelo, DTO, FormRequest
* Agregar generación en XML builder

***

### BRECHA #5: Solo 1 impuesto por línea (spec permite 1,000)

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `fe_lineas_detalle` — campos únicos: `impuesto_codigo`, `impuesto_tarifa`, `impuesto_monto`\
**Documento Hacienda:** `Impuesto {1,1000}` — se pueden utilizar para una misma línea la cantidad de códigos de impuestos que se requieran. (pág. 37)\
**Estado API:** Estructura plana con un solo conjunto de campos de impuesto.

**Impacto:** Productos que requieren IVA (código 01) + Impuesto Selectivo de Consumo (código 02) simultáneamente NO se pueden facturar. Lo mismo aplica para productos con IVA + impuestos específicos (04, 05, 06). Hacienda rechazará comprobantes que deberían incluir múltiples impuestos y solo incluyen uno.

**Corrección requerida:**

* Crear tabla `fe_linea_impuestos` (relación 1:N con `fe_lineas_detalle`)
* Migrar datos existentes
* Actualizar XML builder para iterar impuestos por línea

***

### BRECHA #6: Solo 1 medio de pago (spec permite 1-4)

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `comprobantes_electronicos_fe.medio_pago` — campo único VARCHAR(2)\
**Documento Hacienda:** `MedioPago {1,4}` — Hasta 4 medios de pago con `TipoMedioPago` + `TotalMedioPago`. (pág. 53-54)\
**Estado API:** Solo almacena un código de medio de pago como campo plano.

**Impacto:** Pagos mixtos (efectivo + tarjeta, transferencia + cheque) no se pueden representar. Cuando un cliente paga con 2+ medios, el comprobante será incorrecto ante Hacienda.

**Corrección requerida:**

* Crear tabla `fe_medios_pago` (relación 1:N con comprobante)
* Con campos: `tipo_medio_pago`, `medio_pago_otros`, `total_medio_pago`
* Actualizar XML builder

***

### BRECHA #7: Emisor `Barrio` — Campo inexistente en DB pero usado en XML

**Severidad:** 🔴 CRÍTICA (Error en Runtime)\
**Ubicación:** `XmlComprobanteBuilder` intenta acceder a `$comprobante->empresa->barrio`\
**Estado API:** El campo `barrio` NO existe en la tabla `empresas` ni en el modelo `Empresa`.

**Impacto:**

* Si PHP strict mode → Exception al intentar acceder a propiedad inexistente
* Si PHP lax → `null`, y el XML genera `<Barrio>01</Barrio>` siempre (hardcoded fallback)
* Dato incorrecto ante Hacienda: barrio siempre es "01" independiente de la ubicación real

**Corrección requerida:** Agregar `barrio` a tabla `empresas` y modelo.

***

### BRECHA #8: `receptor_numero_identificacion` VARCHAR(12) — Spec requiere 20

**Severidad:** 🔴 CRÍTICA\
**Ubicación:** `comprobantes_electronicos_fe.receptor_numero_identificacion` VARCHAR(12)\
**Documento Hacienda:** String(20) — El "Extranjero No Domiciliado" y "No Contribuyente" pueden contener hasta 20 caracteres alfanuméricos. (pág. 23)\
**Estado API:** VARCHAR(12) truncaría números de Extranjeros No Domiciliados y No Contribuyentes.

**Corrección requerida:** ALTER TABLE para ampliar a VARCHAR(20).

***

## 6. Brechas de Alta Prioridad — Campos Condicionales-Obligatorios

### BRECHA #9: `CodigoActividadReceptor`

**Prioridad:** 🟠 ALTA\
**Documento:** String(6). Condicional (FE=2, FEE=4, FEC=1, NC=2, ND=2). Obligatorio en FEC. (pág. 18)\
**API:** No existe campo ni generación XML.\
**Impacto:** Facturas Electrónicas de Compra serán rechazadas.

***

### BRECHA #10: `CondicionVentaOtros`

**Prioridad:** 🟠 ALTA\
**Documento:** String(100). Obligatorio cuando `CondicionVenta`=99. Min 5, max 100 caracteres. (pág. 25)\
**API:** No existe campo ni validación.\
**Impacto:** Si un contribuyente usa condición "Otros", el comprobante se rechaza.

***

### BRECHA #11: `Emisor > NombreComercial` no se genera en XML

**Prioridad:** 🟠 ALTA\
**Documento:** String(80). Opcional pero obligatorio si existe nombre comercial. Min 3, max 80. (pág. 21)\
**API:** Campo `nombre_comercial` existe en modelo `Empresa`, pero el `XmlComprobanteBuilder` NO lo incluye en el XML generado.

***

### BRECHA #12: `Receptor > NombreComercial`

**Prioridad:** 🟠 ALTA\
**Documento:** String(80). Opcional (condición 3). (pág. 23)\
**API:** No existe campo ni generación XML.

***

### BRECHA #13: `OtrosCargos` sin estructura de detalle

**Prioridad:** 🟠 ALTA\
**Documento:** Tipo complejo con hasta 15 repeticiones. Incluye: TipoDocumentoOC, IdentificacionTercero, Detalle, PorcentajeOC, MontoCargo. (pág. 46-48)\
**API:** Solo existe `total_otros_cargos` como campo numérico. Sin tabla ni XML de detalle.

***

### BRECHA #14: `InformacionReferencia` no es tabla independiente

**Prioridad:** 🟠 ALTA\
**Documento:** Hasta 10 repeticiones. Obligatorio en NC, ND, REP, FEC. (pág. 55-56)\
**API:** Almacenado en campo JSON `metadata`. Falta `TipoDocRefOTRO` y `CodigoReferenciaOTRO`.\
**Impacto:** No permite validación relacional ni búsquedas eficientes.

***

### BRECHA #15: Solo 1 descuento por línea (spec permite 5)

**Prioridad:** 🟠 ALTA\
**Documento:** `Descuento {0,5}` — Hasta 5, cada uno calculado sobre base menos descuento anterior. (pág. 35)\
**API:** Solo 1 campo `monto_descuento` y `naturaleza_descuento`.

***

### BRECHA #16: `ImpuestoAsumidoEmisorFabrica` y `TotalImpAsumEmisorFabrica`

**Prioridad:** 🟠 ALTA\
**Documento:** Obligatorio cuando hay regalías (código descuento 01), bonificaciones (código 03), o impuestos cobrados a nivel de fábrica. (pág. 45-46)\
**API:** No implementado en ningún nivel.

***

### BRECHA #17: Múltiples `CorreoElectronico` del Emisor {1,4}

**Prioridad:** 🟠 ALTA\
**Documento:** Se puede incluir máximo 4 direcciones de correo. Obligatorio mínimo 1. (pág. 22)\
**API:** Solo soporta 1 correo electrónico del emisor.

***

### BRECHA #18: `BaseImponible` — Cálculo potencialmente incorrecto

**Prioridad:** 🟠 ALTA\
**Documento:** Se obtiene de `Subtotal + Imp.Selectivo(02) + Imp.Bebidas Alcohólicas(04) + Imp.Bebidas sin alcohol(05) + Imp.Cemento(12)` cuando corresponda. (pág. 36)\
**API:** Campo existe, pero el cálculo puede no incluir la suma de impuestos específicos.

***

### BRECHA #19: Totales Exonerados no se generan en XML

**Prioridad:** 🟠 ALTA\
**Documento:** `TotalServExonerado`, `TotalMercExonerada`, `TotalExonerado` — Obligatorios cuando hay exoneraciones. (pág. 50-51)\
**API:** Campos existen en base de datos pero el `XmlComprobanteBuilder` NO los incluye en `ResumenFactura`.

***

## 7. Brechas de Prioridad Media

### BRECHA #20: `PartidaArancelaria`

* **Necesario para:** Factura Electrónica de Exportación (FEE).
* String(12). Obligatorio cuando CAByS corresponde a un producto (primer dígito 0-4).

### BRECHA #21: `TipoTransaccion` — En migración pero no conectado

* Campo agregado en v4.4 migration, pero NO está en model `fillable` ni se genera en XML builder.
* String(2). Ver nota 22. Condicional.

### BRECHA #22: `Registrofiscal8707`

* String(12). Para facturación de bebidas alcohólicas según Ley 8707.
* Obligatorio cuando se facturan códigos CAByS de bebidas alcohólicas.

### BRECHA #23: `NumeroVINoSerie {1,1000}`

* String(17). Para vehículos, aeronaves o embarcaciones.
* Se convierte en obligatorio según el código CAByS.

### BRECHA #24: `RegistroMedicamento` y `FormaFarmaceutica`

* `RegistroMedicamento`: String(100). Para medicamentos con registro sanitario.
* `FormaFarmaceutica`: String(3). Código forma farmacéutica (nota 19).

### BRECHA #25: `OtrasSenasExtranjero` (Emisor y Receptor)

* String(300). Para tipo de identificación 05 (Extranjero No Domiciliado).
* Obligatorio en FEC cuando emisor es código 05. Min 5, max 300.

### BRECHA #26: `TotalServNoSujeto`, `TotalMercNoSujeta`, `TotalNoSujeto`

* Obligatorio cuando hay servicios/mercancías No Sujetos de IVA (tarifas 01 y 11 de nota 8.1).
* Campos no existen en tabla ni modelo.
* Decimal(18,5).

### BRECHA #27: `DatosImpuestoEspecifico`

* Tipo complejo para impuestos no tarifarios:
  * Código 03: Combustibles
  * Código 04: Bebidas Alcohólicas
  * Código 05: Bebidas sin alcohol / jabones
  * Código 06: Tabaco
* Requiere: CantidadUnidadMedida, Porcentaje, Proporcion, VolumenUnidadConsumo, ImpuestoUnidad.

### BRECHA #28: `FactorCalculoIVA`

* Decimal(5,4). Para bienes usados (código de impuesto 08).
* Factor establecido por Ministerio de Hacienda.

### BRECHA #29: `MontoExportacion`

* Decimal(18,5). Uso exclusivo FEE para impuestos especiales de exportación.

### BRECHA #30: `TotalIVADevuelto` no se genera en XML

* Campo existe en DB (`total_iva_devuelto`), pero NO se incluye en el XML generado.
* Obligatorio cuando se facturan servicios de salud con medio de pago "Tarjeta".

### BRECHA #31: `DetalleSurtido` (Surtidos/Combos)

* Estructura completa para paquetes de productos con:
  * `LineaDetalleSurtido {1,20}`
  * `CodigoCABYSSurtido`, `CantidadSurtido`, `PrecioUnitarioSurtido`, etc.
  * `ImpuestoSurtido {1,1000}` con impuestos individuales
* Obligatorio cuando se facturan surtidos con diferentes tarifas IVA.

### BRECHA #32: `IVACobradoFabrica` por línea

* String(2). Ver nota 21.
* Se convierte en obligatorio cuando IVA se cobra a nivel de fábrica.

### BRECHA #33: `CodigoComercial` estructura {0,5}

* Tipo complejo: `Tipo`(2) + `Codigo`(20), hasta 5 repeticiones.
* API solo tiene 1 campo plano `codigo_comercial`.
* Obligatorio para CAByS de surtidos.

***

## 8. Brechas de Baja Prioridad

### BRECHA #34: `Exoneracion` — Campos incompletos

* Falta: `Articulo`, `Inciso`, `NombreInstitucionOtros`, `TipoDocumentoOTRO`, `tarifaexonerada`.
* Solo afecta comprobantes con exoneraciones.

### BRECHA #35: `Receptor > Telefono`

* Tipo complejo: CodigoPais(3) + NumTelefono(20).
* Opcional (condición 3 en la mayoría de comprobantes).

### BRECHA #36: `receptor_email` longitud insuficiente

* DB: VARCHAR(100). Spec: String(160).
* Podría truncar correos largos.

### BRECHA #37: Validaciones de FormRequest incompletas

* `condicion_venta`: Solo valida `01,02,03,04,99`. Faltan códigos 05-15 de v4.4.
* `receptor_tipo_identificacion`: Solo `01,02,03,04`. Faltan `05` (Extranjero No Domiciliado) y `06` (No Contribuyente).
* `clave_numerica`: No se valida formato de 50 dígitos en entrada.
* `consecutivo`: No valida tamaño exacto de 20 dígitos.

### BRECHA #38: `OtroContenido` en sección Otros

* String(500). Elemento opcional para contenido estructurado.
* Rara vez utilizado.

***

## 9. Análisis de Validaciones

### 9.1 Validaciones Existentes

**`StoreComprobanteElectronicoRequest`:**

| Campo                          | Regla Actual                     | Regla Correcta Según Hacienda                                  |                  Estado                 |
| ------------------------------ | -------------------------------- | -------------------------------------------------------------- | :-------------------------------------: |
| `consecutivo`                  | `required\|string\|max:20`       | `required\|string\|size:20\|regex:/^\d{20}$/`                  | ⚠️ No valida 20 exactos ni solo dígitos |
| `condicion_venta`              | `required\|in:01,02,03,04,99`    | `required\|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,99` |             ⚠️ Faltan 05-15             |
| `medio_pago`                   | `required\|in:01,02,03,04,05,99` | `required\|in:01,02,03,04,05,99`                               |                   ✅ OK                  |
| `receptor_tipo_identificacion` | `nullable\|in:01,02,03,04`       | `nullable\|in:01,02,03,04,05,06`                               |             ⚠️ Faltan 05, 06            |
| `unidad_medida`                | `in:Sp,m,kg,s,I,Os,...`          | Según nota 15 de Hacienda (más códigos)                        |              ⚠️ Incompleto              |

**Regla `CrIdentificacion`:**

* ✅ Valida cédula física (9 dígitos, sin cero al inicio)
* ✅ Valida cédula jurídica (10 dígitos, inicia con 3)
* ✅ Valida DIMEX (11-12 dígitos, sin cero al inicio)
* ✅ Valida NITE (10 dígitos)
* ❌ NO valida tipo 05 (Extranjero No Domiciliado): hasta 20 caracteres alfanuméricos
* ❌ NO valida tipo 06 (No Contribuyente): hasta 20 caracteres alfanuméricos

**Regla `CrTelefono`:**

* ✅ Valida formato CR: `/^[2-8]\d{7}$/`
* Nota: Hacienda solo exige "mínimo 8 dígitos, máximo 20". La regla es más restrictiva.

### 9.2 Validaciones Faltantes

| Validación                     | Importancia | Descripción                                                          |
| ------------------------------ | :---------: | -------------------------------------------------------------------- |
| Clave numérica (50 dígitos)    |      🔴     | No se valida en FormRequest (solo se genera)                         |
| Formato fechas RFC3339         |      🟡     | FechaEmision debe ser `YYYY-MM-DDThh:mi:ss[Z\|(+\|-)hh:mm]`          |
| Montos > 0                     |      🔴     | PrecioUnitario y Cantidad deben ser > 0, MontoDescuento ≤ MontoTotal |
| CódigoCABYS válido             |      🟠     | Debe existir en catálogo BCCR vigente                                |
| Nombre mín. 5 chars (Emisor)   |      🟡     | Spec: "mínimo 5 caracteres"                                          |
| Nombre mín. 3 chars (Receptor) |      🟡     | Spec: "mínimo 3 caracteres"                                          |
| OtrasSenas mín. 5 chars        |      🟡     | Spec: "mínimo 5 caracteres, máximo 250"                              |
| Email formato regex            |      🟡     | Spec provee regex específico de validación                           |
| TipoCambio=1 para CRC          |      🟠     | Si moneda=CRC, tipo\_cambio DEBE ser 1                               |

***

## 10. Análisis de Estructura de Base de Datos

### 10.1 Tablas que Requieren Nuevas Columnas

#### `comprobantes_electronicos_fe` — Columnas Faltantes

```sql
-- Receptor ubicación
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_provincia VARCHAR(1) NULLABLE AFTER receptor_email;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_canton VARCHAR(2) NULLABLE AFTER receptor_provincia;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_distrito VARCHAR(2) NULLABLE AFTER receptor_canton;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_barrio VARCHAR(50) NULLABLE AFTER receptor_distrito;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_otras_senas VARCHAR(160) NULLABLE AFTER receptor_barrio;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_otras_senas_extranjero VARCHAR(300) NULLABLE;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_nombre_comercial VARCHAR(80) NULLABLE;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_telefono_codigo_pais VARCHAR(3) NULLABLE;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN receptor_telefono_numero VARCHAR(20) NULLABLE;

-- Receptor/Emisor adicionales
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN codigo_actividad_receptor VARCHAR(6) NULLABLE;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN condicion_venta_otros VARCHAR(100) NULLABLE;

-- Totales No Sujeto
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN total_servicios_no_sujeto DECIMAL(18,5) DEFAULT 0;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN total_mercancias_no_sujeta DECIMAL(18,5) DEFAULT 0;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN total_no_sujeto DECIMAL(18,5) DEFAULT 0;
ALTER TABLE comprobantes_electronicos_fe ADD COLUMN total_imp_asum_emisor_fabrica DECIMAL(18,5) DEFAULT 0;

-- Receptor número identificación ampliado
ALTER TABLE comprobantes_electronicos_fe MODIFY receptor_numero_identificacion VARCHAR(20) NULLABLE;
```

#### `fe_lineas_detalle` — Columnas Faltantes

```sql
-- Descuento
ALTER TABLE fe_lineas_detalle ADD COLUMN codigo_descuento VARCHAR(2) NULLABLE AFTER monto_descuento;
ALTER TABLE fe_lineas_detalle ADD COLUMN codigo_descuento_otro VARCHAR(100) NULLABLE AFTER codigo_descuento;

-- Impuestos adicionales
ALTER TABLE fe_lineas_detalle ADD COLUMN factor_calculo_iva DECIMAL(5,4) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN iva_cobrado_fabrica VARCHAR(2) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN impuesto_asumido_emisor_fabrica DECIMAL(18,5) DEFAULT 0;

-- Exoneración
ALTER TABLE fe_lineas_detalle ADD COLUMN exoneracion_articulo INT NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN exoneracion_inciso INT NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN exoneracion_tarifa_exonerada DECIMAL(4,2) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN exoneracion_tipo_documento_otro VARCHAR(100) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN exoneracion_nombre_institucion_otros VARCHAR(160) NULLABLE;

-- Exportación
ALTER TABLE fe_lineas_detalle ADD COLUMN partida_arancelaria VARCHAR(12) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN monto_exportacion DECIMAL(18,5) NULLABLE;

-- Campos adicionales
ALTER TABLE fe_lineas_detalle ADD COLUMN tipo_transaccion VARCHAR(2) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN numero_vin_serie VARCHAR(17) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN registro_medicamento VARCHAR(100) NULLABLE;
ALTER TABLE fe_lineas_detalle ADD COLUMN forma_farmaceutica VARCHAR(3) NULLABLE;
```

#### `hacienda_comprobantes` — Clave truncada

```sql
ALTER TABLE hacienda_comprobantes MODIFY clave VARCHAR(50);
```

#### `empresas` — Barrio faltante

```sql
ALTER TABLE empresas ADD COLUMN barrio VARCHAR(50) NULLABLE AFTER distrito;
```

### 10.2 Tablas Nuevas Requeridas

#### `fe_linea_impuestos` — Múltiples impuestos por línea

```sql
CREATE TABLE fe_linea_impuestos (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    linea_detalle_id BIGINT NOT NULL,
    codigo VARCHAR(2) NOT NULL,                   -- Nota 8: 01=IVA, 02=Selectivo, etc.
    codigo_impuesto_otro VARCHAR(100) NULLABLE,    -- Cuando código=99
    codigo_tarifa_iva VARCHAR(2) NULLABLE,         -- Nota 8.1
    tarifa DECIMAL(4,2) NULLABLE,
    factor_calculo_iva DECIMAL(5,4) NULLABLE,      -- Para bienes usados código 08
    monto DECIMAL(18,5) NOT NULL DEFAULT 0,
    monto_exportacion DECIMAL(18,5) NULLABLE,
    -- Impuestos específicos (códigos 03,04,05,06)
    cantidad_unidad_medida DECIMAL(7,2) NULLABLE,
    porcentaje DECIMAL(4,2) NULLABLE,
    proporcion DECIMAL(5,2) NULLABLE,
    volumen_unidad_consumo DECIMAL(7,2) NULLABLE,
    impuesto_unidad DECIMAL(18,5) NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (linea_detalle_id) REFERENCES fe_lineas_detalle(id) ON DELETE CASCADE,
    INDEX (linea_detalle_id)
);
```

#### `fe_medios_pago` — Múltiples medios por comprobante

```sql
CREATE TABLE fe_medios_pago (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    comprobante_id BIGINT NOT NULL,
    tipo_medio_pago VARCHAR(2) NOT NULL,           -- Nota 6
    medio_pago_otros VARCHAR(100) NULLABLE,         -- Cuando tipo=99
    total_medio_pago DECIMAL(18,5) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (comprobante_id) REFERENCES comprobantes_electronicos_fe(id) ON DELETE CASCADE,
    INDEX (comprobante_id)
);
```

#### `fe_informacion_referencia` — Referencias del comprobante

```sql
CREATE TABLE fe_informacion_referencia (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    comprobante_id BIGINT NOT NULL,
    tipo_doc VARCHAR(2) NOT NULL,                  -- Nota 10
    tipo_doc_otro VARCHAR(100) NULLABLE,            -- Cuando tipo=99
    numero VARCHAR(50) NULLABLE,                    -- Clave numérica referenciada
    fecha_emision DATETIME NOT NULL,
    codigo VARCHAR(2) NULLABLE,                     -- Nota 9
    codigo_referencia_otro VARCHAR(100) NULLABLE,   -- Cuando código=99
    razon VARCHAR(180) NULLABLE,                    -- Min 3, max 180
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (comprobante_id) REFERENCES comprobantes_electronicos_fe(id) ON DELETE CASCADE,
    INDEX (comprobante_id)
);
```

#### `fe_otros_cargos` — Otros cargos del comprobante

```sql
CREATE TABLE fe_otros_cargos (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    comprobante_id BIGINT NOT NULL,
    tipo_documento_oc VARCHAR(2) NOT NULL,          -- Nota 16
    tipo_documento_otros VARCHAR(100) NULLABLE,     -- Cuando tipo=99
    -- Identificación Tercero (cuando tipo_documento_oc=04)
    tercero_tipo_identificacion VARCHAR(2) NULLABLE,
    tercero_numero_identificacion VARCHAR(20) NULLABLE,
    nombre_tercero VARCHAR(100) NULLABLE,
    detalle VARCHAR(160) NOT NULL,
    porcentaje_oc DECIMAL(9,5) NULLABLE,
    monto_cargo DECIMAL(18,5) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (comprobante_id) REFERENCES comprobantes_electronicos_fe(id) ON DELETE CASCADE,
    INDEX (comprobante_id)
);
```

#### `fe_linea_descuentos` — Múltiples descuentos por línea

```sql
CREATE TABLE fe_linea_descuentos (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    linea_detalle_id BIGINT NOT NULL,
    orden INT NOT NULL DEFAULT 1,                   -- 1-5, secuencial
    monto_descuento DECIMAL(18,5) NOT NULL,
    codigo_descuento VARCHAR(2) NOT NULL,           -- Nota 20
    codigo_descuento_otro VARCHAR(100) NULLABLE,    -- Cuando código=99
    naturaleza_descuento VARCHAR(80) NULLABLE,      -- Cuando código=99, min 3 max 80
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (linea_detalle_id) REFERENCES fe_lineas_detalle(id) ON DELETE CASCADE,
    INDEX (linea_detalle_id)
);
```

***

## 11. Análisis del Generador XML

### 11.1 Elementos XML que el Builder genera correctamente

```xml
✅ <?xml version="1.0" encoding="UTF-8"?>
✅ <FacturaElectronica xmlns="...v4.4/facturaElectronica">
✅     <Clave>50 dígitos</Clave>
✅     <CodigoActividadEmisor>620100</CodigoActividadEmisor>
✅     <NumeroConsecutivo>20 dígitos</NumeroConsecutivo>
✅     <FechaEmision>RFC3339</FechaEmision>
✅     <ProveedorSistemas>identificación</ProveedorSistemas>
✅     <Emisor>
✅         <Nombre>razón social</Nombre>
✅         <Identificacion><Tipo>02</Tipo><Numero>3101...</Numero></Identificacion>
✅         <Ubicacion><Provincia/><Canton/><Distrito/>
⚠️             <Barrio>siempre "01" - campo no existe en DB</Barrio>
✅             <OtrasSenas/></Ubicacion>
✅         <Telefono><CodigoPais>506</CodigoPais><NumTelefono>...</NumTelefono></Telefono>
✅         <CorreoElectronico>email</CorreoElectronico>
✅     </Emisor>
✅     <Receptor>
✅         <Nombre/><Identificacion><Tipo/><Numero/></Identificacion>
❌         <!-- FALTA: Ubicacion, NombreComercial, Telefono -->
✅         <CorreoElectronico/>
✅     </Receptor>
✅     <CondicionVenta>01</CondicionVenta>
✅     <PlazoCredito>30</PlazoCredito>
✅     <DetalleServicio>
✅         <LineaDetalle>
✅             <NumeroLinea/><Codigo><Tipo/><Codigo/></Codigo>
✅             <Cantidad/><UnidadMedida/><Detalle/>
✅             <PrecioUnitario/><MontoTotal/>
⚠️             <Descuento><MontoDescuento/><NaturalezaDescuento/></Descuento>
❌             <!-- FALTA: CodigoDescuento OBLIGATORIO -->
✅             <SubTotal/><BaseImponible/>
✅             <Impuesto><Codigo/><CodigoTarifa/><Tarifa/><Monto/></Impuesto>
❌             <!-- FALTA: Soporte para múltiples <Impuesto> -->
✅             <ImpuestoNeto/><MontoTotalLinea/>
✅         </LineaDetalle>
✅     </DetalleServicio>
✅     <ResumenFactura>
✅         <CodigoTipoMoneda><CodigoMoneda/><TipoCambio/></CodigoTipoMoneda>
✅         <TotalServGravados/><TotalServExentos/>
❌         <!-- FALTA: TotalServExonerado, TotalServNoSujeto -->
✅         <TotalMercanciasGravadas/><TotalMercanciasExentas/>
❌         <!-- FALTA: TotalMercExonerada, TotalMercNoSujeta -->
✅         <TotalGravado/><TotalExento/>
❌         <!-- FALTA: TotalExonerado, TotalNoSujeto -->
✅         <TotalVenta/><TotalDescuentos/><TotalVentaNeta/>
⚠️         <TotalDesgloseImpuesto>
✅             <Codigo/>
❌             <!-- FALTA: CodigoTarifaIVA -->
✅             <TotalMontoImpuesto/>
           </TotalDesgloseImpuesto>
✅         <TotalImpuesto/>
❌         <!-- FALTA: TotalImpAsumEmisorFabrica, TotalIVADevuelto -->
✅         <TotalOtrosCargos/>
✅         <MedioPago><TipoMedioPago/><TotalMedioPago/></MedioPago>
✅         <TotalComprobante/>
✅     </ResumenFactura>
⚠️     <InformacionReferencia><!-- Parcial, falta OTRO tipos --></InformacionReferencia>
✅     <Otros><OtroTexto/></Otros>
✅     <ds:Signature><!-- XAdES-EPES Completo --></ds:Signature>
</FacturaElectronica>
```

### 11.2 Correcciones necesarias en XmlComprobanteBuilder

| Método                           | Corrección Requerida                                                               |
| -------------------------------- | ---------------------------------------------------------------------------------- |
| `agregarEmisor()`                | Agregar `<NombreComercial>` si existe                                              |
| `agregarEmisor()`                | Fix `<Barrio>` para usar campo real de DB                                          |
| `agregarReceptor()`              | Agregar `<Ubicacion>`, `<NombreComercial>`, `<Telefono>`, `<OtrasSenasExtranjero>` |
| `agregarDetalleServicio()`       | Soportar múltiples `<Impuesto>` por línea                                          |
| `agregarDetalleServicio()`       | Agregar `<CodigoDescuento>` dentro de `<Descuento>`                                |
| `agregarDetalleServicio()`       | Agregar `<CodigoComercial>` con estructura `<Tipo>` + `<Codigo>`                   |
| `agregarResumenFactura()`        | Agregar `TotalServExonerado`, `TotalMercExonerada`, `TotalExonerado`               |
| `agregarResumenFactura()`        | Agregar `TotalServNoSujeto`, `TotalMercNoSujeta`, `TotalNoSujeto`                  |
| `agregarResumenFactura()`        | Agregar `CodigoTarifaIVA` a `TotalDesgloseImpuesto`                                |
| `agregarResumenFactura()`        | Agregar `TotalImpAsumEmisorFabrica`, `TotalIVADevuelto`                            |
| `agregarResumenFactura()`        | Soportar múltiples `<MedioPago>`                                                   |
| `agregarInformacionReferencia()` | Agregar `TipoDocRefOTRO`, `CodigoReferenciaOTRO`                                   |
| Nuevo método                     | `agregarOtrosCargos()` — sección completa                                          |
| Nuevo método                     | `agregarCodigoActividadReceptor()`                                                 |

***

## 12. Tabla Consolidada de Brechas

|  #  | Brecha                                                      | Sección         | Prioridad | Esfuerzo |    Tipo de Cambio   | Estado |
| :-: | ----------------------------------------------------------- | --------------- | :-------: | :------: | :-----------------: | :----: |
|  1  | `clave` VARCHAR(29) → 50                                    | Encabezado      |     🔴    |   Bajo   |      Migración      |    ✅   |
|  2  | `CodigoDescuento` obligatorio                               | Detalle         |     🔴    |   Bajo   |   Migración + XML   |    ✅   |
|  3  | `TotalDesgloseImpuesto` + `CodigoTarifaIVA`                 | Resumen         |     🔴    |   Medio  |         XML         |    ✅   |
|  4  | Receptor Ubicación (5 campos)                               | Encabezado      |     🔴    |   Medio  |   Migración + XML   |    ✅   |
|  5  | Múltiples impuestos por línea {1,1000}                      | Detalle         |     🔴    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  6  | MedioPago múltiple {1,4}                                    | Resumen         |     🔴    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  7  | Emisor `Barrio` no existe en DB                             | Encabezado      |     🔴    |   Bajo   |      Migración      |    ✅   |
|  8  | `receptor_numero_identificacion` VARCHAR(12) → 20           | Encabezado      |     🔴    |   Bajo   |      Migración      |    ✅   |
|  9  | `CodigoActividadReceptor`                                   | Encabezado      |     🟠    |   Bajo   |   Migración + XML   |    ✅   |
|  10 | `CondicionVentaOtros`                                       | Encabezado      |     🟠    |   Bajo   |   Migración + XML   |    ✅   |
|  11 | Emisor `NombreComercial` en XML                             | Encabezado      |     🟠    |   Bajo   |         XML         |    ✅   |
|  12 | Receptor `NombreComercial`                                  | Encabezado      |     🟠    |   Bajo   |   Migración + XML   |    ✅   |
|  13 | `OtrosCargos` estructura completa                           | Resumen         |     🟠    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  14 | `InformacionReferencia` como tabla                          | Referencia      |     🟠    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  15 | Múltiples descuentos {0,5}                                  | Detalle         |     🟠    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  16 | `ImpuestoAsumidoEmisorFabrica`                              | Detalle/Resumen |     🟠    |   Medio  |   Migración + XML   |    ✅   |
|  17 | Emisor CorreoElectronico {1,4}                              | Encabezado      |     🟠    |   Medio  |   Migración + XML   |    ✅   |
|  18 | `BaseImponible` cálculo                                     | Detalle         |     🟠    |   Medio  |        Lógica       |    ✅   |
|  19 | Totales Exonerados en XML                                   | Resumen         |     🟠    |   Bajo   |         XML         |    ✅   |
|  20 | `PartidaArancelaria`                                        | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  21 | `TipoTransaccion` no conectado                              | Detalle         |     🟡    |   Bajo   |     Modelo + XML    |    ✅   |
|  22 | `Registrofiscal8707`                                        | Encabezado      |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  23 | `NumeroVINoSerie`                                           | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  24 | `RegistroMedicamento` / `FormaFarmaceutica`                 | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  25 | `OtrasSenasExtranjero`                                      | Encabezado      |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  26 | `TotalServNoSujeto` / `TotalMercNoSujeta` / `TotalNoSujeto` | Resumen         |     🟡    |   Medio  |   Migración + XML   |    ✅   |
|  27 | `DatosImpuestoEspecifico`                                   | Detalle         |     🟡    |   Alto   |  Tabla nueva + XML  |    ✅   |
|  28 | `FactorCalculoIVA`                                          | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  29 | `MontoExportacion`                                          | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  30 | `TotalIVADevuelto` en XML                                   | Resumen         |     🟡    |   Bajo   |         XML         |    ✅   |
|  31 | `DetalleSurtido`                                            | Detalle         |     🟡    | Muy Alto |     Tablas + XML    |    ✅   |
|  32 | `IVACobradoFabrica` por línea                               | Detalle         |     🟡    |   Bajo   |   Migración + XML   |    ✅   |
|  33 | `CodigoComercial` {0,5} estructura                          | Detalle         |     🟡    |   Medio  |  Tabla nueva + XML  |    ✅   |
|  34 | Exoneración campos incompletos                              | Detalle         |     🟢    |   Bajo   |   Migración + XML   |    ✅   |
|  35 | Receptor Telefono                                           | Encabezado      |     🟢    |   Bajo   |   Migración + XML   |    ✅   |
|  36 | `receptor_email` VARCHAR(100) → 160                         | Encabezado      |     🟢    |   Bajo   |      Migración      |    ✅   |
|  37 | Validaciones FormRequest                                    | Validaciones    |     🟢    |   Medio  | FormRequest + Rules |    ✅   |
|  38 | `OtroContenido`                                             | Otros           |     🟢    |   Bajo   |         XML         |    ✅   |

***

## 13. Plan de Remediación Recomendado

### Fase A — Inmediata (Evitar Rechazos de Hacienda) ✅ COMPLETADA (7-9 abril 2026)

**Objetivo:** Corregir las 8 brechas críticas que causarían rechazos.\
**Estado:** ✅ Todas las brechas críticas resueltas.\
**Migración:** `2026_04_07_000000_hacienda_v44_compliance_full.php`

| Tarea                                                                         | Brechas | Tipo                  |
| ----------------------------------------------------------------------------- | :-----: | --------------------- |
| Migración: `clave` VARCHAR(29) → 50 en `hacienda_comprobantes`                |    #1   | ALTER TABLE           |
| Migración: `receptor_numero_identificacion` VARCHAR(12) → 20                  |    #8   | ALTER TABLE           |
| Migración: `barrio` en tabla `empresas`                                       |    #7   | ADD COLUMN            |
| Migración: `codigo_descuento`, `codigo_descuento_otro` en `fe_lineas_detalle` |    #2   | ADD COLUMN            |
| Migración: 5 campos Receptor Ubicación en `comprobantes_electronicos_fe`      |    #4   | ADD COLUMN            |
| XML: Fix `TotalDesgloseImpuesto` con `CodigoTarifaIVA`                        |    #3   | XmlComprobanteBuilder |
| XML: Agregar `<CodigoDescuento>` en `<Descuento>`                             |    #2   | XmlComprobanteBuilder |
| XML: Agregar `<Ubicacion>` del Receptor                                       |    #4   | XmlComprobanteBuilder |
| XML: Fix `<Barrio>` del Emisor para usar campo real                           |    #7   | XmlComprobanteBuilder |
| XML: Agregar `<NombreComercial>` del Emisor                                   |   #11   | XmlComprobanteBuilder |
| Modelo: Actualizar fillable/casts de modelos afectados                        |  Varios | Models                |
| DTO/FormRequest: Actualizar validaciones                                      |   #37   | Requests              |

### Fase B — Corto Plazo (Soporte Estructural Completo) ✅ COMPLETADA (7-9 abril 2026)

**Objetivo:** Implementar tablas relacionales y soporte múltiple.\
**Estado:** ✅ Todas las tablas creadas, XML builder actualizado, validaciones completas.

| Tarea                                                                  |  Brechas | Tipo                  |
| ---------------------------------------------------------------------- | :------: | --------------------- |
| Crear tabla `fe_linea_impuestos` + modelo + migración datos existentes |    #5    | Tabla nueva           |
| Crear tabla `fe_medios_pago` + modelo                                  |    #6    | Tabla nueva           |
| Crear tabla `fe_informacion_referencia` + modelo                       |    #14   | Tabla nueva           |
| Crear tabla `fe_otros_cargos` + modelo                                 |    #13   | Tabla nueva           |
| Crear tabla `fe_linea_descuentos` + modelo                             |    #15   | Tabla nueva           |
| XML: Refactorizar para iterar múltiples impuestos por línea            |    #5    | XmlComprobanteBuilder |
| XML: Refactorizar para múltiples `<MedioPago>`                         |    #6    | XmlComprobanteBuilder |
| XML: Implementar `<OtrosCargos>` completo                              |    #13   | XmlComprobanteBuilder |
| XML: Implementar `<InformacionReferencia>` desde tabla                 |    #14   | XmlComprobanteBuilder |
| Agregar campos No Sujeto y Exonerado a DB y XML                        | #19, #26 | Migración + XML       |
| Agregar `ImpuestoAsumidoEmisorFabrica` a línea y resumen               |    #16   | Migración + XML       |
| Agregar `TotalIVADevuelto` al XML                                      |    #30   | XML                   |
| Agregar `CodigoActividadReceptor`, `CondicionVentaOtros`               |  #9, #10 | Migración + XML       |
| Actualizar todas las validaciones FormRequest                          |    #37   | FormRequests          |

### Fase C — Mediano Plazo (Campos Específicos por Industria) ✅ COMPLETADA (10 abril 2026)

**Objetivo:** Cubrir casos de uso especializados.\
**Estado:** ✅ Todas las brechas resueltas. 38/38 cumplimiento completo.\
**Migración:** `2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php`

| Tarea                                                                          |  Brechas | Industria                     |   Estado   |
| ------------------------------------------------------------------------------ | :------: | ----------------------------- | :--------: |
| `DetalleSurtido` completo (tabla `fe_detalle_surtido` + `fe_surtido_impuesto`) |    #31   | Manufactura / Retail          | ✅ Resuelto |
| `CodigoComercial` estructura {0,5} (tabla `fe_codigo_comercial`)               |    #33   | Retail / Manufactura          | ✅ Resuelto |
| Soporte múltiples correos emisor                                               |    #17   | General                       | ✅ Resuelto |
| `BaseImponible` auto-cálculo (imp. 02,12)                                      |    #18   | General                       | ✅ Resuelto |
| `DatosImpuestoEspecifico` (códigos 03-06)                                      |    #27   | Combustibles, Alcohol, Tabaco |      ✅     |
| `PartidaArancelaria`, `MontoExportacion`                                       | #20, #29 | Exportación                   |      ✅     |
| `Registrofiscal8707`                                                           |    #22   | Bebidas Alcohólicas           |      ✅     |
| `NumeroVINoSerie`                                                              |    #23   | Automotriz / Aeronáutica      |      ✅     |
| `RegistroMedicamento`, `FormaFarmaceutica`                                     |    #24   | Farmacéutico                  |      ✅     |
| `FactorCalculoIVA`                                                             |    #28   | Bienes Usados                 |      ✅     |
| `IVACobradoFabrica`                                                            |    #32   | Manufactura                   |      ✅     |
| `OtrasSenasExtranjero`                                                         |    #25   | Internacional                 |      ✅     |
| Exoneración campos completos                                                   |    #34   | Zona Franca / ONG             |      ✅     |

***

## Apéndice A: Mapeo Completo Campo por Campo

### Leyenda de Condiciones Hacienda

| Código |             Significado             |
| :----: | :---------------------------------: |
|    1   |             Obligatorio             |
|    2   | Condicional (obligatorio si aplica) |
|    3   |               Opcional              |
|    4   |       No aplica para ese tipo       |

### Tabla Maestra: Encabezado

|  #  | Campo Hacienda                 | Tipo     | Tam. |  FE | FEE | FEC |  TE |  NC |  ND | REP | Campo API                        | Tabla API                      |      Estado     |
| :-: | ------------------------------ | -------- | :--: | :-: | :-: | :-: | :-: | :-: | :-: | :-: | -------------------------------- | ------------------------------ | :-------------: |
|  1  | Clave                          | String   |  50  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `clave`                          | comprobantes\_electronicos\_fe |        ✅        |
|  2  | ProveedorSistemas              | String   |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `proveedor_sistemas`             | empresas                       |        ✅        |
|  3  | CodigoActividadEmisor          | String   |   6  |  1  |  1  |  3  |  1  |  1  |  1  |  4  | `actividad_economica_principal`  | empresas                       |        ✅        |
|  4  | CodigoActividadReceptor        | String   |   6  |  2  |  4  |  1  |  4  |  2  |  2  |  4  | —                                | —                              |        ❌        |
|  5  | NumeroConsecutivo              | String   |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `consecutivo`                    | comprobantes\_electronicos\_fe |        ✅        |
|  6  | FechaEmision                   | DateTime |   —  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `fecha_emision`                  | comprobantes\_electronicos\_fe |        ✅        |
|  7  | Emisor.Nombre                  | String   |  100 |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `razon_social`                   | empresas                       |        ✅        |
|  8  | Emisor.Identificacion.Tipo     | String   |   2  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `tipo_identificacion`            | empresas                       |        ✅        |
|  9  | Emisor.Identificacion.Numero   | String   |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `num_identificacion_dgt`         | empresas                       |        ✅        |
|  10 | Emisor.Registrofiscal8707      | String   |  12  |  2  |  2  |  2  |  2  |  2  |  2  |  4  | —                                | —                              |        ❌        |
|  11 | Emisor.NombreComercial         | String   |  80  |  3  |  3  |  3  |  3  |  3  |  3  |  4  | `nombre_comercial`               | empresas                       |   ⚠️ No en XML  |
|  12 | Emisor.Ubicacion.Provincia     | String   |   1  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | `provincia`                      | empresas                       |        ✅        |
|  13 | Emisor.Ubicacion.Canton        | String   |   2  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | `canton`                         | empresas                       |        ✅        |
|  14 | Emisor.Ubicacion.Distrito      | String   |   2  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | `distrito`                       | empresas                       |        ✅        |
|  15 | Emisor.Ubicacion.Barrio        | String   |  50  |  3  |  3  |  3  |  3  |  2  |  2  |  4  | —                                | —                              |   ❌ No existe   |
|  16 | Emisor.Ubicacion.OtrasSenas    | String   |  250 |  1  |  1  |  1  |  1  |  1  |  1  |  4  | `direccion`                      | empresas                       |        ✅        |
|  17 | Emisor.OtrasSenasExtranjero    | String   |  300 |  4  |  4  |  2  |  4  |  4  |  4  |  4  | —                                | —                              |        ❌        |
|  18 | Emisor.Telefono.CodigoPais     | Int      |   3  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | hardcoded 506                    | —                              |        ✅        |
|  19 | Emisor.Telefono.NumTelefono    | Int      |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | `telefono`                       | empresas                       |        ✅        |
|  20 | Emisor.CorreoElectronico {1,4} | String   |  160 |  1  |  1  |  2  |  1  |  1  |  1  |  1  | `email`                          | empresas                       |    ⚠️ Solo 1    |
|  21 | Receptor.Nombre                | String   |  100 |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `receptor_nombre`                | comprobantes\_electronicos\_fe |        ✅        |
|  22 | Receptor.Identificacion.Tipo   | String   |   2  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `receptor_tipo_identificacion`   | comprobantes\_electronicos\_fe |        ✅        |
|  23 | Receptor.Identificacion.Numero | String   |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `receptor_numero_identificacion` | comprobantes\_electronicos\_fe |  ⚠️ VARCHAR(12) |
|  24 | Receptor.NombreComercial       | String   |  80  |  3  |  3  |  3  |  3  |  3  |  3  |  4  | —                                | —                              |        ❌        |
|  25 | Receptor.Ubicacion.Provincia   | String   |   1  |  1  |  4  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  26 | Receptor.Ubicacion.Canton      | String   |   2  |  1  |  4  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  27 | Receptor.Ubicacion.Distrito    | String   |   2  |  1  |  4  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  28 | Receptor.Ubicacion.Barrio      | String   |  50  |  3  |  4  |  3  |  2  |  2  |  2  |  4  | —                                | —                              |        ❌        |
|  29 | Receptor.Ubicacion.OtrasSenas  | String   |  160 |  1  |  4  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  30 | Receptor.OtrasSenasExtranjero  | String   |  300 |  2  |  2  |  4  |  3  |  2  |  2  |  4  | —                                | —                              |        ❌        |
|  31 | Receptor.Telefono.CodigoPais   | Int      |   3  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  32 | Receptor.Telefono.NumTelefono  | Int      |  20  |  1  |  1  |  1  |  1  |  1  |  1  |  4  | —                                | —                              |        ❌        |
|  33 | Receptor.CorreoElectronico     | String   |  160 |  2  |  2  |  2  |  2  |  2  |  2  |  2  | `receptor_email`                 | comprobantes\_electronicos\_fe | ⚠️ VARCHAR(100) |
|  34 | CondicionVenta                 | String   |   2  |  1  |  1  |  1  |  1  |  1  |  1  |  1  | `condicion_venta`                | comprobantes\_electronicos\_fe |        ✅        |
|  35 | CondicionVentaOtros            | String   |  100 |  2  |  2  |  2  |  2  |  2  |  2  |  4  | —                                | —                              |        ❌        |
|  36 | PlazoCredito                   | Int      |   5  |  2  |  2  |  2  |  2  |  2  |  2  |  4  | `plazo_credito`                  | comprobantes\_electronicos\_fe |        ✅        |

***

## Apéndice B: Archivos Relevantes del Proyecto

### Modelos

| Archivo                                         | Descripción                      |
| ----------------------------------------------- | -------------------------------- |
| `app/Models/ComprobanteElectronicoFe.php`       | Comprobante de emisión principal |
| `app/Models/FeLineaDetalle.php`                 | Líneas de detalle                |
| `app/Models/HaciendaComprobante.php`            | Tracking envíos a Hacienda       |
| `app/Models/ComprobanteRecibidoElectronico.php` | Comprobantes recibidos           |
| `app/Models/ConsecutivoFe.php`                  | Control consecutivos             |
| `app/Models/TipoComprobanteFe.php`              | Catálogo tipos                   |
| `app/Models/MensajeHacienda.php`                | Respuestas Hacienda              |
| `app/Models/FeCertificadoDigital.php`           | Certificados .p12                |
| `app/Models/FeOAuthToken.php`                   | Tokens OAuth                     |
| `app/Models/Empresa.php`                        | Datos del emisor                 |

### Servicios

| Archivo                                                | Descripción          |
| ------------------------------------------------------ | -------------------- |
| `app/Services/Hacienda/HaciendaIntegrationService.php` | Orquestación flujo   |
| `app/Services/Hacienda/HaciendaApiClient.php`          | Cliente HTTP         |
| `app/Services/Hacienda/OAuthTokenManager.php`          | Gestión OAuth        |
| `app/Services/Hacienda/RateLimiter.php`                | Rate limiting        |
| `app/Services/Hacienda/Xml/XmlComprobanteBuilder.php`  | Constructor XML v4.4 |
| `app/Services/Hacienda/Xml/FirmaDigitalService.php`    | Firma digital        |
| `app/Services/Hacienda/Xml/XadesEpesSigner.php`        | XAdES-EPES           |
| `app/Services/ComprobanteElectronicoService.php`       | Lógica de negocio    |
| `app/Services/ConsecutivoFeService.php`                | Consecutivos         |
| `app/Services/MensajeHaciendaService.php`              | Mensajes             |

### Migraciones

| Archivo                                                                         | Tabla                                                      |
| ------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `2025_02_08_create_hacienda_comprobantes_table.php`                             | hacienda\_comprobantes                                     |
| `2025_11_19_100146_000000_create_consecutivos_fe_table.php`                     | consecutivos\_fe                                           |
| `2025_11_19_100147_000000_create_comprobantes_recibidos_electronicos_table.php` | comprobantes\_recibidos\_electronicos                      |
| `2025_11_22_150000_create_mensajes_hacienda_table.php`                          | mensajes\_hacienda                                         |
| `2025_11_22_150100_create_tipos_comprobantes_fe_table.php`                      | tipos\_comprobantes\_fe                                    |
| `2025_11_26_184030_create_comprobantes_electronicos_fe_table.php`               | comprobantes\_electronicos\_fe                             |
| `2025_11_26_184109_create_fe_lineas_detalle_table.php`                          | fe\_lineas\_detalle                                        |
| `2025_11_26_184143_create_fe_certificados_digitales_table.php`                  | fe\_certificados\_digitales                                |
| `2025_11_26_184143_create_fe_oauth_tokens_table.php`                            | fe\_oauth\_tokens                                          |
| `2025_12_03_023558_add_v44_fields_to_fe_tables.php`                             | ALTER fe\_lineas\_detalle + comprobantes\_electronicos\_fe |

### Configuración

| Archivo               | Descripción                        |
| --------------------- | ---------------------------------- |
| `config/hacienda.php` | Configuración completa de Hacienda |

### Validaciones

| Archivo                                                    | Descripción                |
| ---------------------------------------------------------- | -------------------------- |
| `app/Http/Requests/StoreComprobanteElectronicoRequest.php` | FormRequest principal      |
| `app/Rules/CrIdentificacion.php`                           | Regla: identificaciones CR |
| `app/Rules/CrTelefono.php`                                 | Regla: teléfonos CR        |

### DTOs

| Archivo                                            | Descripción              |
| -------------------------------------------------- | ------------------------ |
| `app/DTOs/API/ComprobanteElectronicoCreateDTO.php` | DTO creación comprobante |

### Excepciones

| Archivo                                              | Descripción                |
| ---------------------------------------------------- | -------------------------- |
| `app/Exceptions/HaciendaException.php`               | Excepciones de integración |
| `app/Exceptions/FacturacionElectronicaException.php` | Excepciones de negocio     |

### Documentación Existente

| Archivo                                              | Descripción             |
| ---------------------------------------------------- | ----------------------- |
| `docs/hacienda/FACTURACION_ELECTRONICA_SETUP.md`     | Guía de configuración   |
| `docs/hacienda/FACTURACION_ELECTRONICA_API.md`       | Documentación endpoints |
| `docs/hacienda/PLAN_IMPLEMENTACION_V44_HACIENDA.md`  | Plan migración v4.4     |
| `docs/hacienda/ANALISIS_HACIENDA_CR_V44_COMPLETO.md` | Análisis previo         |

***

***

## 15. Auditoría Profunda — Segundo Análisis Comparativo (7 de abril de 2026, sesión 2)

**Metodología:** Se re-analizó campo por campo el PDF oficial "Anexos y Estructuras para la Emisión de Comprobantes Electrónicos v4.4" (97 páginas, Noviembre 2024 — DGT) contra el código fuente actual de la API (v4.2.0 FASE 20), verificando migraciones, modelos, XML builder, validaciones y servicios.

### 15.1 Nuevas Brechas Identificadas — Encabezado

#### 15.1.1 `CodigoActividadReceptor` (Pág. 18 del PDF)

| Aspecto         | Spec v4.4                 | API Actual  |
| --------------- | ------------------------- | ----------- |
| Etiqueta XML    | `CodigoActividadReceptor` | ❌ No existe |
| Tipo            | String(6)                 | —           |
| Condición FE    | 2 (Condicional)           | —           |
| Condición FEC   | **1 (Obligatorio)**       | —           |
| Condición NC/ND | 2 (Condicional)           | —           |

**Impacto:** Hacienda **rechazará** FEC sin este campo. Para FE, NC y ND es condicional pero será obligatorio próximamente según nota al pie del documento.

**Remediación necesaria:**

* Migración: agregar `codigo_actividad_receptor` string(6) nullable a `comprobantes_electronicos_fe`
* Modelo: agregar a fillable
* XML Builder: generar `<CodigoActividadReceptor>` después de `<CodigoActividadEmisor>`
* Validación: obligatorio cuando tipo\_documento='03' (FEC)

#### 15.1.2 `Registrofiscal8707` — Emisor (Pág. 20 del PDF)

| Aspecto                       | Spec v4.4            | API Actual  |
| ----------------------------- | -------------------- | ----------- |
| Etiqueta XML                  | `Registrofiscal8707` | ❌ No existe |
| Tipo                          | String(12)           | —           |
| Condición FE/FEE/FEC/TE/NC/ND | 2 (Condicional)      | —           |
| Condición REP                 | 4 (Inexistente)      | —           |

**Impacto:** Obligatorio cuando se facturan códigos CAByS de bebidas alcohólicas según Ley 8707. Hacienda genera **advertencia** si falta.

**Remediación necesaria:**

* Migración: agregar `registro_fiscal_8707` string(12) nullable a tabla `empresas`
* Modelo Empresa: agregar a fillable
* XML Builder: generar `<Registrofiscal8707>` dentro del nodo `<Emisor>` cuando exista

#### 15.1.3 `NombreComercial` del Emisor (Pág. 21 del PDF)

| Aspecto      | Spec v4.4                     | API Actual                |
| ------------ | ----------------------------- | ------------------------- |
| Etiqueta XML | `NombreComercial`             | ❌ **NO se genera en XML** |
| Campo DB     | `nombre_comercial` en Empresa | ✅ Existe en modelo        |
| Condición    | 3 (Opcional en todos)         | —                         |

**Impacto:** Bajo — es opcional. Pero el campo existe en la BD y NO se emite en el XML, lo cual es un bug.

**Remediación necesaria:**

* XML Builder: en `agregarEmisor()`, generar `<NombreComercial>` si `empresa->nombre_comercial` tiene valor

#### 15.1.4 `Barrio` del Emisor (Pág. 21 del PDF)

| Aspecto                 | Spec v4.4               | API Actual            |
| ----------------------- | ----------------------- | --------------------- |
| Etiqueta XML            | `Barrio`                | ⚠️ Se intenta generar |
| Campo DB Empresa        | `barrio`                | ❌ **NO existe en DB** |
| Tipo v4.4               | String(50), texto libre | —                     |
| Condición FE/FEE/FEC/TE | 3 (Opcional)            | —                     |
| Condición NC/ND         | 2 (Condicional)         | —                     |

**Impacto:** El XML Builder intenta usar `empresa->barrio` pero la columna no existe en la tabla `empresas`. Esto podría causar un error silencioso o un campo vacío.

**Remediación necesaria:**

* Migración: agregar `barrio` string(50) nullable a tabla `empresas`
* Modelo Empresa: agregar a fillable (ya está si se agregó columna)

#### 15.1.5 `OtrasSenasExtranjero` — Emisor (Pág. 22 del PDF)

| Aspecto         | Spec v4.4              | API Actual  |
| --------------- | ---------------------- | ----------- |
| Etiqueta XML    | `OtrasSenasExtranjero` | ❌ No existe |
| Tipo            | String(300)            | —           |
| Condición FEC   | 2 (Condicional)        | —           |
| Todos los demás | 4 (Inexistente)        | —           |

**Impacto:** Obligatorio en FEC cuando tipo\_identificacion emisor = '05' (Extranjero No Domiciliado).

#### 15.1.6 Receptor — Campos Faltantes Completos (Pág. 22-24 del PDF)

| Campo                         | Spec v4.4   | API DB | API XML |  Condición FE  |
| ----------------------------- | ----------- | ------ | ------- | :------------: |
| `NombreComercial` receptor    | String(80)  | ❌      | ❌       |        3       |
| `Ubicacion` receptor completa | ComplexType | ❌      | ❌       |        2       |
| `> Provincia`                 | String(1)   | ❌      | ❌       |        1       |
| `> Canton`                    | String(2)   | ❌      | ❌       |        1       |
| `> Distrito`                  | String(2)   | ❌      | ❌       |        1       |
| `> Barrio`                    | String(50)  | ❌      | ❌       |        3       |
| `> OtrasSenas`                | String(160) | ❌      | ❌       |        1       |
| `OtrasSenasExtranjero`        | String(300) | ❌      | ❌       |        2       |
| `Telefono > CodigoPais`       | Integer(3)  | ❌      | ❌       | 1 (si hay tel) |
| `Telefono > NumTelefono`      | Integer(20) | ❌      | ❌       | 1 (si hay tel) |

**Impacto CRÍTICO:** Para FE, la ubicación del receptor es **condicional** (obligatoria cuando el receptor tiene domicilio en el país). Para FEC es **obligatoria**. Hacienda rechazará comprobantes FEC sin ubicación del receptor.

**Remediación necesaria (migración masiva):**

```
receptor_nombre_comercial    VARCHAR(80)   nullable
receptor_provincia           VARCHAR(1)    nullable
receptor_canton              VARCHAR(2)    nullable
receptor_distrito            VARCHAR(2)    nullable
receptor_barrio              VARCHAR(50)   nullable
receptor_otras_senas         VARCHAR(160)  nullable
receptor_otras_senas_extranjero VARCHAR(300) nullable
receptor_telefono_codigo     VARCHAR(3)    nullable
receptor_telefono_numero     VARCHAR(20)   nullable
```

#### 15.1.7 `CondicionVentaOtros` (Pág. 25 del PDF)

| Aspecto      | Spec v4.4                                | API Actual  |
| ------------ | ---------------------------------------- | ----------- |
| Etiqueta XML | `CondicionVentaOtros`                    | ❌ No existe |
| Tipo         | String(100)                              | —           |
| Condición    | 2 — Obligatorio cuando CondicionVenta=99 | —           |

**Impacto:** Si un usuario selecciona condición de venta "Otros" (99), Hacienda rechazará sin este campo.

#### 15.1.8 Correo Electrónico del Emisor — Múltiple (Pág. 22 del PDF)

| Aspecto      | Spec v4.4               | API Actual                 |
| ------------ | ----------------------- | -------------------------- |
| Repeticiones | {1,4} (hasta 4 correos) | Solo 1 correo              |
| Tamaño       | 160 caracteres          | `email` VARCHAR en Empresa |

**Impacto:** Bajo — 1 correo es suficiente para la mayoría. Pero la spec permite hasta 4.

#### 15.1.9 Correo Electrónico del Receptor (Pág. 24 del PDF)

| Aspecto     | Spec v4.4      | API Actual                    |
| ----------- | -------------- | ----------------------------- |
| Tamaño Spec | 160 caracteres | `receptor_email` VARCHAR(100) |

**Impacto:** Si un correo tiene más de 100 caracteres, se truncaría. La spec permite 160.

***

### 15.2 Nuevas Brechas Identificadas — Detalle de Líneas

#### 15.2.1 `PartidaArancelaria` (Pág. 26 del PDF)

| Aspecto         | Spec v4.4                       | API Actual  |
| --------------- | ------------------------------- | ----------- |
| Etiqueta XML    | `PartidaArancelaria`            | ❌ No existe |
| Tipo            | String(12)                      | —           |
| Condición FEE   | **2 (Condicional-Obligatorio)** | —           |
| Condición NC/ND | 2 (si modifica FEE)             | —           |

**Impacto:** **CRÍTICO para exportación.** Hacienda rechazará FEE con mercancías sin partida arancelaria.

#### 15.2.2 `CodigoComercial` — Estructura Completa (Pág. 27 del PDF)

| Aspecto      | Spec v4.4                      | API Actual                      |
| ------------ | ------------------------------ | ------------------------------- |
| Repeticiones | {0,5} — hasta 5                | Solo 1 campo `codigo_comercial` |
| Estructura   | ComplexType: `Tipo` + `Codigo` | Campo plano                     |

**Impacto:** Medio — La spec requiere una estructura jerárquica con Tipo y Código como sub-elementos, y permite hasta 5 repeticiones. La API solo tiene un campo plano.

#### 15.2.3 `TipoTransaccion` (Pág. 28 del PDF)

| Aspecto              | Spec v4.4                                            | API Actual                |
| -------------------- | ---------------------------------------------------- | ------------------------- |
| Etiqueta XML         | `TipoTransaccion`                                    | ❌ No en XML               |
| Campo DB             | `tipo_transaccion` en comprobantes\_electronicos\_fe | ✅ Existe                  |
| Modelo fillable      | —                                                    | ❌ **NO está en fillable** |
| Condición FE/FEE/FEC | 2 (Condicional)                                      | —                         |
| Nota 22 códigos      | 01-13                                                | —                         |

**Impacto:** El campo existe en DB pero NO está en el modelo fillable, NO se genera en XML. Obligatorio para transacciones con tratamientos tributarios específicos.

#### 15.2.4 Campos de Línea Completamente Faltantes

| Campo                          | Spec Etiqueta                | Tipo                | Condición FE | Estado |
| ------------------------------ | ---------------------------- | ------------------- | :----------: | ------ |
| `NumeroVINoSerie`              | NumeroVINoSerie              | String(17) {1,1000} |       2      | ❌      |
| `RegistroMedicamento`          | RegistroMedicamento          | String(100)         |       2      | ❌      |
| `FormaFarmaceutica`            | FormaFarmaceutica            | String(3)           |       2      | ❌      |
| `DetalleSurtido`               | DetalleSurtido               | ComplexType {1,20}  |       2      | ❌      |
| `IVACobradoFabrica`            | IVACobradoFabrica            | String(2)           |       2      | ❌      |
| `ImpuestoAsumidoEmisorFabrica` | ImpuestoAsumidoEmisorFabrica | Decimal(18,5)       |    1 (FE)    | ❌      |

#### 15.2.5 `CodigoDescuento` — OBLIGATORIO cuando hay descuento (Pág. 35 del PDF)

| Aspecto      | Spec v4.4                                | API Actual  |
| ------------ | ---------------------------------------- | ----------- |
| Etiqueta XML | `CodigoDescuento`                        | ❌ No existe |
| Tipo         | String(2) — Nota 20                      | —           |
| Condición    | **1 (Obligatorio cuando hay descuento)** | —           |

**Nota 20 — Códigos de descuento:**

| Código | Descripción                                  |
| :----: | -------------------------------------------- |
|   01   | Descuento por Regalía                        |
|   02   | Descuento por Regalía IVA Cobrado al Cliente |
|   03   | Descuento por Bonificación                   |
|   04   | Descuento por volumen                        |
|   05   | Descuento por Temporada (estacional)         |
|   06   | Descuento promocional                        |
|   07   | Descuento Comercial                          |
|   08   | Descuento por frecuencia                     |
|   09   | Descuento sostenido                          |
|   99   | Otros descuentos                             |

**Impacto CRÍTICO:** Hacienda **rechazará** cualquier comprobante que tenga descuento sin `CodigoDescuento`. Este campo pasó a ser obligatorio en v4.4.

**Campos adicionales de descuento faltantes:**

* `CodigoDescuentoOTRO` — String(100), obligatorio cuando código=99
* `NaturalezaDescuento` — Ahora solo obligatorio con código 99 (antes era siempre opcional)

#### 15.2.6 Impuestos por Línea — Campos Faltantes

| Campo                     | Spec                           | Tipo          | Condición | Estado API |
| ------------------------- | ------------------------------ | ------------- | :-------: | :--------: |
| `CodigoImpuestoOTRO`      | Obligatorio si código=99       | String(100)   |     2     |      ❌     |
| `FactorCalculoIVA`        | Para bienes usados (código 08) | Decimal(5,4)  |     2     |      ❌     |
| `DatosImpuestoEspecifico` | Códigos 03,04,05,06            | ComplexType   |     2     |      ❌     |
| `> CantidadUnidadMedida`  | Litros/mL según impuesto       | Decimal(7,2)  |     1     |      ❌     |
| `> Porcentaje`            | Para bebidas alcohólicas (04)  | Decimal(4,2)  |     2     |      ❌     |
| `> Proporcion`            | Cálculo automático             | Decimal(5,2)  |     2     |      ❌     |
| `> VolumenUnidadConsumo`  | Para bebidas sin alcohol (05)  | Decimal(7,2)  |     2     |      ❌     |
| `> ImpuestoUnidad`        | Monto por unidad               | Decimal(18,5) |     1     |      ❌     |
| `MontoExportacion`        | Para FEE                       | Decimal(18,5) |  2 (FEE)  |      ❌     |

**Impacto:** CRÍTICO para empresas que venden combustibles, bebidas alcohólicas, bebidas sin alcohol, productos de tabaco o cemento. Hacienda rechazará comprobantes con estos impuestos específicos sin el nodo `DatosImpuestoEspecifico`.

#### 15.2.7 Exoneración — Campos Faltantes

| Campo                    | Spec                   | Tipo         | Condición | Estado API |
| ------------------------ | ---------------------- | ------------ | :-------: | :--------: |
| `TipoDocumentoOTRO`      | Si tipo=99             | String(100)  |     2     |      ❌     |
| `Articulo`               | Número artículo de ley | Integer(6)   |     2     |      ❌     |
| `Inciso`                 | Número inciso de ley   | Integer(6)   |     2     |      ❌     |
| `NombreInstitucionOtros` | Si institución=99      | String(160)  |     2     |      ❌     |
| `tarifaexonerada`        | Puntos de tarifa       | Decimal(4,2) |   **1**   |      ❌     |

**Impacto CRÍTICO:** `tarifaexonerada` es **obligatorio** cuando hay exoneración. Actualmente se usa `exoneracion_porcentaje` pero la spec pide un campo distinto con nombre `tarifaexonerada` y formato Decimal(4,2) que representa puntos de tarifa, no porcentaje.

**Fórmula cambiada en v4.4:**

```
MontoExoneracion = tarifaexonerada × Subtotal
```

#### 15.2.8 Impuestos Múltiples por Línea (Pág. 37 del PDF)

| Aspecto      | Spec v4.4                       | API Actual                |
| ------------ | ------------------------------- | ------------------------- |
| Repeticiones | {1,1000} — hasta 1000 impuestos | **Solo 1**                |
| Estructura   | Array de impuestos              | Campos planos en la línea |

**Impacto CRÍTICO:** La spec permite (y requiere) múltiples impuestos por línea. Por ejemplo, un producto puede tener IVA + Impuesto Selectivo de Consumo. La API actual solo soporta 1 impuesto por línea.

**Remediación necesaria:** Crear tabla `fe_linea_impuestos` con relación 1:N desde `fe_lineas_detalle`.

#### 15.2.9 Descuentos Múltiples por Línea (Pág. 35 del PDF)

| Aspecto      | Spec v4.4                  | API Actual      |
| ------------ | -------------------------- | --------------- |
| Repeticiones | {0,5} — hasta 5 descuentos | **Solo 1**      |
| Estructura   | Array secuencial           | 2 campos planos |

**Impacto Medio:** Cada descuento adicional se calcula sobre la base menos el descuento anterior. La API actual solo soporta 1 descuento por línea.

***

### 15.3 Nuevas Brechas Identificadas — Resumen / Totales

#### 15.3.1 Campos "No Sujeto" — Completamente Faltantes (Pág. 50-51 del PDF)

| Campo XML           | Tipo          | Condición FE |    Estado    |
| ------------------- | ------------- | :----------: | :----------: |
| `TotalServNoSujeto` | Decimal(18,5) |       2      | ❌ DB + ❌ XML |
| `TotalMercNoSujeta` | Decimal(18,5) |       2      | ❌ DB + ❌ XML |
| `TotalNoSujeto`     | Decimal(18,5) |       2      | ❌ DB + ❌ XML |

**Impacto:** Hacienda rechazará comprobantes que tengan servicios/mercancías no sujetas de IVA (códigos tarifa 01 y 11 de la nota 8.1) sin estos campos.

**Fórmulas spec v4.4:**

```
TotalNoSujeto = TotalServNoSujeto + TotalMercNoSujeta
```

#### 15.3.2 Campos Exonerados — Faltan en XML (Pág. 50-51 del PDF)

| Campo XML            |                DB               | XML | Fórmula                                 |
| -------------------- | :-----------------------------: | :-: | --------------------------------------- |
| `TotalServExonerado` |  ✅ `total_servicios_exonerados` |  ❌  | % exoneración × monto servicio          |
| `TotalMercExonerada` | ✅ `total_mercancias_exoneradas` |  ❌  | % exoneración × monto mercancía         |
| `TotalExonerado`     |       ✅ `total_exonerado`       |  ❌  | TotalServExonerado + TotalMercExonerada |

**Impacto:** Los campos existen en la BD pero NO se generan en el XML. Hacienda rechazará comprobantes con exoneraciones.

#### 15.3.3 `TotalDesgloseImpuesto` — Incompleto (Pág. 52 del PDF)

| Subcampo             | Spec v4.4                   | API XML          |
| -------------------- | --------------------------- | ---------------- |
| `Codigo`             | Obligatorio                 | ✅                |
| `CodigoTarifaIVA`    | Condicional (códigos 01,07) | ❌ **FALTANTE**   |
| `TotalMontoImpuesto` | Obligatorio                 | ✅ (como `Monto`) |

**Impacto:** El desglose actual solo agrupa por código de impuesto. La v4.4 requiere desglosar también por `CodigoTarifaIVA`, es decir, si hay líneas con IVA 13% y otras con IVA 4%, deben ser entradas separadas en el desglose.

#### 15.3.4 `TotalImpAsumEmisorFabrica` (Pág. 53 del PDF)

| Aspecto   | Spec v4.4                   | API Actual  |
| --------- | --------------------------- | ----------- |
| Campo XML | `TotalImpAsumEmisorFabrica` | ❌ No existe |
| Campo DB  | —                           | ❌ No existe |
| Tipo      | Decimal(18,5)               | —           |
| Condición | 2 (Condicional)             | —           |

**Impacto:** Obligatorio cuando hay impuestos asumidos por el emisor/fábrica en las líneas de detalle.

#### 15.3.5 `TotalVenta` — Fórmula Cambiada (Pág. 52 del PDF)

| Aspecto | Spec v4.4                                                     | API Actual          |
| ------- | ------------------------------------------------------------- | ------------------- |
| Fórmula | `TotalGravado + TotalExento + TotalExonerado + TotalNoSujeto` | Sin `TotalNoSujeto` |

**Impacto:** La fórmula de cálculo cambió al incluir `TotalNoSujeto`. Sin este campo, la validación de Hacienda rechazará por inconsistencia en los cálculos.

#### 15.3.6 `MedioPago` — Estructura Ampliada (Pág. 53-54 del PDF)

| Subcampo         | Spec v4.4              | API XML |
| ---------------- | ---------------------- | ------- |
| `TipoMedioPago`  | Obligatorio            | ✅       |
| `MedioPagoOtros` | Si código=99           | ❌       |
| `TotalMedioPago` | Condicional (>1 medio) | ✅       |
| Repeticiones     | {1,4}                  | Solo 1  |

**Nuevos códigos de medio de pago v4.4 (Nota 6):**

| Código | Descripción        |
| :----: | ------------------ |
|   06   | SINPE MOVIL        |
|   07   | Plataforma Digital |

**Impacto:** La validación actual solo permite `01,02,03,04,05,99`. Faltan códigos `06` y `07`.

#### 15.3.7 Condiciones de Venta — Códigos Nuevos v4.4 (Nota 5, Pág. 68)

La API solo valida `01,02,03,04,99`. La v4.4 requiere:

| Código | Descripción                                  | Estado API |
| :----: | -------------------------------------------- | :--------: |
|   01   | Contado                                      |      ✅     |
|   02   | Crédito                                      |      ✅     |
|   03   | Consignación                                 |      ✅     |
|   04   | Apartado                                     |      ✅     |
|   05   | Arrendamiento con opción de compra           |      ❌     |
|   06   | Arrendamiento en función financiera          |      ❌     |
|   07   | Cobro a favor de un tercero                  |      ❌     |
|   08   | Servicios prestados al Estado                |      ❌     |
|   09   | Pago de servicios prestado al Estado         |      ❌     |
|   10   | Venta a crédito en IVA hasta 90 días         |      ❌     |
|   11   | Pago de venta a crédito en IVA hasta 90 días |      ❌     |
|   12   | Venta Mercancía No Nacionalizada             |      ❌     |
|   13   | Venta Bienes Usados No Contribuyente         |      ❌     |
|   14   | Arrendamiento Operativo                      |      ❌     |
|   15   | Arrendamiento Financiero                     |      ❌     |
|   99   | Otros                                        |      ✅     |

***

### 15.4 Nuevas Brechas Identificadas — OtrosCargos

#### 15.4.1 Nodo `OtrosCargos` Completo (Pág. 46-48 del PDF)

| Campo                     | Spec v4.4                  | API Actual        |
| ------------------------- | -------------------------- | ----------------- |
| `OtrosCargos` (nodo)      | ComplexType {0,15}         | ❌ No implementado |
| `> TipoDocumentoOC`       | String(2) — Nota 16        | ❌                 |
| `> TipoDocumentoOTROS`    | String(100) si código=99   | ❌                 |
| `> IdentificacionTercero` | ComplexType (si código=04) | ❌                 |
| `> NombreTercero`         | String(100)                | ❌                 |
| `> Detalle`               | String(160)                | ❌                 |
| `> PorcentajeOC`          | Decimal(9,5)               | ❌                 |
| `> MontoCargo`            | Decimal(18,5)              | ❌                 |

**Nota 16 — Tipos de documento otros cargos:**

| Código | Descripción                       |
| :----: | --------------------------------- |
|   01   | Contribución parafiscal           |
|   02   | Timbre de la Cruz Roja            |
|   03   | Timbre Benemérito Cuerpo Bomberos |
|   04   | Cobro de un tercero               |
|   05   | Costos de Exportación             |
|   06   | Impuesto de servicio 10%          |
|   07   | Timbre de Colegios Profesionales  |
|   08   | Depósitos de Garantía             |
|   09   | Multas o Penalizaciones           |
|   10   | Intereses Moratorios              |
|   99   | Otros Cargos                      |

**Impacto:** El campo `total_otros_cargos` existe en la BD pero NO hay implementación del nodo XML `OtrosCargos`. Empresas que cobran timbres, servicio 10%, etc. no pueden emitir comprobantes correctos.

***

### 15.5 Nuevas Brechas — Información de Referencia

#### 15.5.1 Campos Nuevos v4.4 (Pág. 55-56 del PDF)

| Campo                  | Spec v4.4                             | API Actual          |
| ---------------------- | ------------------------------------- | ------------------- |
| `TipoDocIR`            | Etiqueta renombrada de `TipoDoc`      | ⚠️ Verificar nombre |
| `TipoDocRefOTRO`       | String(100) — si código=99            | ❌                   |
| `FechaEmisionIR`       | Etiqueta renombrada de `FechaEmision` | ⚠️ Verificar nombre |
| `CodigoReferenciaOTRO` | String(100) — si código=99            | ❌                   |

**Nuevos códigos de referencia v4.4 (Nota 9):**

| Código | Descripción                       | Estado API |
| :----: | --------------------------------- | :--------: |
|   06   | Devolución de mercancía           |   ❌ Nuevo  |
|   07   | Sustituye comprobante electrónico |   ❌ Nuevo  |
|   08   | Factura Endosada                  |   ❌ Nuevo  |
|   09   | Nota de crédito financiera        |   ❌ Nuevo  |
|   10   | Nota de débito financiera         |   ❌ Nuevo  |
|   11   | Proveedor No Domiciliado          |   ❌ Nuevo  |
|   12   | Crédito por exoneración posterior |   ❌ Nuevo  |

**Nuevos códigos de documento de referencia v4.4 (Nota 10):**

| Código | Descripción                             | Estado API |
| :----: | --------------------------------------- | :--------: |
|   16   | Comprobante de Proveedor No Domiciliado |   ❌ Nuevo  |
|   17   | Nota de Crédito a FEC                   |   ❌ Nuevo  |
|   18   | Nota de Débito a FEC                    |   ❌ Nuevo  |

***

### 15.6 Nuevas Brechas — Tipos de Comprobante

| Tipo                                | Código | DB/XML Schema                 | Estado API |
| ----------------------------------- | :----: | ----------------------------- | :--------: |
| Factura Electrónica                 |   01   | facturaElectronica            |      ✅     |
| Nota de Débito                      |   02   | notaDebitoElectronica         |      ✅     |
| Nota de Crédito                     |   03   | notaCreditoElectronica        |      ✅     |
| Tiquete Electrónico                 |   04   | tiqueteElectronico            |      ✅     |
| **Factura Electrónica Compra**      | **08** | facturaElectronicaCompra      |      ❌     |
| **Factura Electrónica Exportación** | **09** | facturaElectronicaExportacion |      ❌     |
| **Recibo Electrónico de Pago**      | **10** | reciboElectronicoPago         |      ❌     |

**Impacto CRÍTICO:** 3 tipos de comprobante v4.4 no están implementados. La tabla `tipos_comprobantes_fe` solo tiene códigos 01-04.

***

### 15.7 Nuevas Brechas — Tipos de Identificación

| Código | Tipo                          | Estado API |
| :----: | ----------------------------- | :--------: |
|   01   | Cédula Física                 |      ✅     |
|   02   | Cédula Jurídica               |      ✅     |
|   03   | DIMEX                         |      ✅     |
|   04   | NITE                          |      ✅     |
| **05** | **Extranjero No Domiciliado** |      ❌     |
| **06** | **No Contribuyente**          |      ❌     |

**Impacto:** Códigos 05 y 06 son nuevos en v4.4. El `receptor_numero_identificacion` actual es VARCHAR(12) — la spec requiere **20 caracteres** para códigos 05 y 06.

***

### 15.8 Nuevas Brechas — Validaciones del FormRequest

| Validación Requerida                                                                      | Spec v4.4                       |          Estado API          |
| ----------------------------------------------------------------------------------------- | ------------------------------- | :--------------------------: |
| Nombre emisor min 5, max 100                                                              | Obligatorio                     |         ❌ No validado        |
| Nombre receptor min 3, max 100                                                            | Obligatorio                     | ⚠️ Parcial (max 100, no min) |
| `receptor_numero_identificacion` max 20                                                   | Para tipos 05,06                |        ❌ Valida max 12       |
| Condiciones de venta 05-15                                                                | Nota 5 completa                 |        ❌ Solo 01-04,99       |
| Medios de pago 06,07                                                                      | SINPE MOVIL, Plataforma Digital |        ❌ Solo 01-05,99       |
| Unidades de medida completa                                                               | Nota 15 (80+ códigos)           |      ❌ Solo \~10 códigos     |
| `MontoTotal = Cantidad × PrecioUnitario`                                                  | Validación cálculo              |         ❌ No validado        |
| `SubTotal = MontoTotal - MontoDescuento`                                                  | Validación cálculo              |         ❌ No validado        |
| `BaseImponible` obligatorio si gravado                                                    | Campo obligatorio               |         ❌ No validado        |
| `TotalComprobante = TotalVentaNeta + TotalImpuesto + TotalOtrosCargos - TotalIVADevuelto` | Validación cálculo              |         ❌ No validado        |

***

### 15.9 Tabla Consolidada COMPLETA de Brechas (Actualizada)

|  #  | Brecha                                               | Criticidad | Categoría   |   DB  |  XML  | Validación |
| :-: | ---------------------------------------------------- | :--------: | ----------- | :---: | :---: | :--------: |
|  1  | CodigoActividadReceptor                              | 🔴 Crítico | Encabezado  |   ❌   |   ❌   |      ❌     |
|  2  | Registrofiscal8707                                   |   🟠 Alto  | Emisor      |   ❌   |   ❌   |      ❌     |
|  3  | NombreComercial emisor en XML                        |   🟢 Bajo  | Emisor      |   ✅   |   ❌   |      —     |
|  4  | Barrio emisor en DB                                  |  🟡 Medio  | Emisor      |   ❌   |   ⚠️  |      —     |
|  5  | OtrasSenasExtranjero emisor                          |  🟡 Medio  | Emisor      |   ❌   |   ❌   |      ❌     |
|  6  | Receptor ubicación completa                          | 🔴 Crítico | Receptor    |   ❌   |   ❌   |      ❌     |
|  7  | Receptor teléfono                                    |  🟡 Medio  | Receptor    |   ❌   |   ❌   |      ❌     |
|  8  | Receptor NombreComercial                             |   🟢 Bajo  | Receptor    |   ❌   |   ❌   |      ❌     |
|  9  | CondicionVentaOtros                                  |   🟠 Alto  | Encabezado  |   ❌   |   ❌   |      ❌     |
|  10 | Correo emisor múltiple (1-4)                         |   🟢 Bajo  | Emisor      |   ⚠️  |   ⚠️  |      —     |
|  11 | receptor\_email max 160                              |   🟢 Bajo  | Receptor    |   ⚠️  |   —   |     ⚠️     |
|  12 | PartidaArancelaria                                   | 🔴 Crítico | Líneas      |   ❌   |   ❌   |      ❌     |
|  13 | CodigoComercial estructura {0,5}                     |  🟡 Medio  | Líneas      |   ⚠️  |   ⚠️  |      ❌     |
|  14 | TipoTransaccion en XML/modelo                        |   🟠 Alto  | Líneas      |  ✅ DB | ❌ XML |      ❌     |
|  15 | NumeroVINoSerie                                      |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  16 | RegistroMedicamento                                  |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  17 | FormaFarmaceutica                                    |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  18 | DetalleSurtido completo                              |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  19 | CodigoDescuento OBLIGATORIO                          | 🔴 Crítico | Líneas      |   ❌   |   ❌   |      ❌     |
|  20 | CodigoDescuentoOTRO                                  |   🟠 Alto  | Líneas      |   ❌   |   ❌   |      ❌     |
|  21 | Descuentos múltiples {0,5}                           |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  22 | Impuestos múltiples {1,1000}                         | 🔴 Crítico | Líneas      |   ❌   |   ❌   |      ❌     |
|  23 | CodigoImpuestoOTRO                                   |   🟠 Alto  | Líneas      |   ❌   |   ❌   |      ❌     |
|  24 | FactorCalculoIVA                                     |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  25 | DatosImpuestoEspecifico completo                     | 🔴 Crítico | Líneas      |   ❌   |   ❌   |      ❌     |
|  26 | MontoExportacion                                     |   🟠 Alto  | Líneas      |   ❌   |   ❌   |      ❌     |
|  27 | IVACobradoFabrica                                    |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  28 | ImpuestoAsumidoEmisorFabrica línea                   |   🟠 Alto  | Líneas      |   ❌   |   ❌   |      ❌     |
|  29 | Exoneración TipoDocumentoOTRO                        |   🟠 Alto  | Líneas      |   ❌   |   ❌   |      ❌     |
|  30 | Exoneración Articulo/Inciso                          |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  31 | Exoneración NombreInstitucionOtros                   |  🟡 Medio  | Líneas      |   ❌   |   ❌   |      ❌     |
|  32 | Exoneración tarifaexonerada                          | 🔴 Crítico | Líneas      |   ❌   |   ❌   |      ❌     |
|  33 | TotalServNoSujeto                                    | 🔴 Crítico | Resumen     |   ❌   |   ❌   |      ❌     |
|  34 | TotalMercNoSujeta                                    | 🔴 Crítico | Resumen     |   ❌   |   ❌   |      ❌     |
|  35 | TotalNoSujeto                                        | 🔴 Crítico | Resumen     |   ❌   |   ❌   |      ❌     |
|  36 | TotalServExonerado en XML                            | 🔴 Crítico | Resumen     |  ✅ DB | ❌ XML |      —     |
|  37 | TotalMercExonerada en XML                            | 🔴 Crítico | Resumen     |  ✅ DB | ❌ XML |      —     |
|  38 | TotalExonerado en XML                                | 🔴 Crítico | Resumen     |  ✅ DB | ❌ XML |      —     |
|  39 | CodigoTarifaIVA en TotalDesgloseImpuesto             | 🔴 Crítico | Resumen     |   —   |   ❌   |      —     |
|  40 | TotalImpAsumEmisorFabrica                            |   🟠 Alto  | Resumen     |   ❌   |   ❌   |      ❌     |
|  41 | MedioPagoOtros                                       |  🟡 Medio  | Resumen     |   ❌   |   ❌   |      ❌     |
|  42 | MedioPago múltiple {1,4}                             |  🟡 Medio  | Resumen     |   ⚠️  |   ⚠️  |      ❌     |
|  43 | OtrosCargos nodo completo                            |   🟠 Alto  | OtrosCargos |   ❌   |   ❌   |      ❌     |
|  44 | Condiciones venta 05-15                              |   🟠 Alto  | Validación  |   —   |   —   |      ❌     |
|  45 | Medios pago 06,07                                    |  🟡 Medio  | Validación  |   —   |   —   |      ❌     |
|  46 | Tipos identificación 05,06                           |   🟠 Alto  | Validación  |   —   |   —   |      ❌     |
|  47 | receptor\_numero\_identificacion max 20              |   🟠 Alto  | DB + Val    | ⚠️ 12 |   —   |     ⚠️     |
|  48 | FEC (tipo 08) no implementado                        | 🔴 Crítico | Tipo Doc    |   ❌   |   ❌   |      ❌     |
|  49 | FEE (tipo 09) no implementado                        | 🔴 Crítico | Tipo Doc    |   ❌   |   ❌   |      ❌     |
|  50 | REP (tipo 10) no implementado                        | 🔴 Crítico | Tipo Doc    |   ❌   |   ❌   |      ❌     |
|  51 | Referencia TipoDocRefOTRO                            |  🟡 Medio  | Referencia  |   ❌   |   ❌   |      ❌     |
|  52 | Referencia CodigoReferenciaOTRO                      |  🟡 Medio  | Referencia  |   ❌   |   ❌   |      ❌     |
|  53 | Referencia códigos 06-12                             |   🟠 Alto  | Referencia  |   —   |   —   |      ❌     |
|  54 | Referencia doc códigos 16-18                         |   🟠 Alto  | Referencia  |   —   |   —   |      ❌     |
|  55 | NombreInstitucion Nota 23 codificada                 |  🟡 Medio  | Exoneración |   ❌   |   ❌   |      ❌     |
|  56 | Validaciones de cálculo (MontoTotal, SubTotal, etc.) |   🟠 Alto  | Validación  |   —   |   —   |      ❌     |
|  57 | Unidades de medida completas (80+)                   |  🟡 Medio  | Validación  |   —   |   —   |      ❌     |
|  58 | hacienda\_comprobantes clave VARCHAR(29)             |  🟡 Medio  | DB          |   ⚠️  |   —   |      —     |

***

### 15.10 Resumen Estadístico Actualizado

| Criticidad | Cantidad | Descripción                                          |
| :--------: | :------: | ---------------------------------------------------- |
| 🔴 Crítico |  **18**  | Hacienda rechazará el comprobante                    |
|   🟠 Alto  |  **16**  | Campos condicionales-obligatorios u omisiones graves |
|  🟡 Medio  |  **18**  | Campos condicionales no implementados                |
|   🟢 Bajo  |   **6**  | Brechas menores o de uso muy específico              |
|  **Total** |  **58**  | Brechas identificadas                                |

***

### 15.11 Plan de Remediación Priorizado

#### FASE A — Correcciones Críticas (Hacienda rechazaría HOY)

**Sprint A.1 — Estructura de Impuestos y Descuentos (MAYOR IMPACTO)**

1. Crear tabla `fe_linea_impuestos` para soportar {1,1000} impuestos por línea
2. Crear tabla `fe_linea_descuentos` para soportar {0,5} descuentos por línea con `CodigoDescuento`
3. Migrar datos existentes de campos planos a las nuevas tablas
4. Actualizar XML Builder para generar múltiples `<Impuesto>` y `<Descuento>` por línea
5. Actualizar validaciones del FormRequest

**Sprint A.2 — Totales del Resumen**

1. Agregar columnas `total_serv_no_sujeto`, `total_merc_no_sujeta`, `total_no_sujeto` a `comprobantes_electronicos_fe`
2. Generar `TotalServExonerado`, `TotalMercExonerada`, `TotalExonerado` en XML
3. Generar `TotalServNoSujeto`, `TotalMercNoSujeta`, `TotalNoSujeto` en XML
4. Agregar `CodigoTarifaIVA` al nodo `TotalDesgloseImpuesto` en XML
5. Actualizar fórmula de `TotalVenta`

**Sprint A.3 — Campos Obligatorios Faltantes**

1. Campo `CodigoDescuento` en líneas (OBLIGATORIO cuando hay descuento)
2. Campo `tarifaexonerada` en exoneración (OBLIGATORIO)
3. Campo `CodigoActividadReceptor` (OBLIGATORIO para FEC)
4. Ubicación completa del receptor (9 campos)
5. `PartidaArancelaria` para FEE

**Sprint A.4 — Tipos de Comprobante Faltantes**

1. Implementar FEC (tipo 08) — Factura Electrónica de Compra
2. Implementar FEE (tipo 09) — Factura Electrónica de Exportación
3. Implementar REP (tipo 10) — Recibo Electrónico de Pago
4. Actualizar XML Schemas para cada tipo

#### FASE B — Correcciones de Alta Prioridad

**Sprint B.1 — Campos Condicionales-Obligatorios**

1. `CondicionVentaOtros` (cuando condición=99)
2. `TipoTransaccion` en modelo fillable + XML
3. `Registrofiscal8707` en Empresa
4. `DatosImpuestoEspecifico` para impuestos 03,04,05,06
5. `ImpuestoAsumidoEmisorFabrica` línea + total
6. `MontoExportacion` para FEE

**Sprint B.2 — Validaciones Ampliadas**

1. Condiciones de venta 05-15
2. Tipos de identificación 05,06
3. `receptor_numero_identificacion` ampliar a VARCHAR(20)
4. Medios de pago 06,07
5. Códigos de referencia 06-12

**Sprint B.3 — Nodo OtrosCargos**

1. Crear tabla `fe_otros_cargos`
2. Implementar nodo XML `OtrosCargos` completo
3. Validaciones según Nota 16

#### FASE C — Correcciones de Prioridad Media

1. NombreComercial emisor en XML
2. Barrio emisor en DB
3. OtrasSenasExtranjero emisor/receptor
4. NumeroVINoSerie
5. RegistroMedicamento + FormaFarmaceutica
6. DetalleSurtido completo
7. FactorCalculoIVA (bienes usados)
8. IVACobradoFabrica
9. CodigoComercial estructura {0,5}
10. Exoneración Articulo/Inciso
11. MedioPago múltiple {1,4}
12. Referencia campos OTRO
13. NombreInstitucion codificada (Nota 23)
14. Unidades de medida completas

#### FASE D — Correcciones Menores

1. Correo emisor múltiple (1-4)
2. receptor\_email ampliar a 160
3. NombreComercial receptor
4. hacienda\_comprobantes clave VARCHAR(50)
5. Etiquetas XML renombradas (TipoDocIR, FechaEmisionIR)

***

### 15.12 Archivos a Modificar por Orden de Impacto

| Prioridad | Archivo                                  | Cambios                                     |
| :-------: | ---------------------------------------- | ------------------------------------------- |
|    🔴 1   | Nueva migración                          | 20+ columnas nuevas, 3 tablas nuevas        |
|    🔴 2   | `XmlComprobanteBuilder.php`              | 30+ elementos XML faltantes                 |
|    🔴 3   | `ComprobanteElectronicoFe.php`           | Fillable, casts, relaciones                 |
|    🔴 4   | `FeLineaDetalle.php`                     | Fillable, relaciones a impuestos/descuentos |
|    🔴 5   | `StoreComprobanteElectronicoRequest.php` | Validaciones completas                      |
|    🟠 6   | `Empresa.php`                            | Campos nuevos (barrio, registro\_fiscal)    |
|    🟠 7   | `HaciendaIntegrationService.php`         | Tipos de comprobante nuevos                 |
|    🟠 8   | `ComprobanteElectronicoService.php`      | Lógica de cálculo actualizada               |
|    🟡 9   | `config/hacienda.php`                    | Catálogos actualizados                      |
|   🟡 10   | Tests                                    | Nuevos tests para cada brecha corregida     |

***

_Segundo análisis comparativo completado el 7 de abril de 2026. Total de brechas identificadas: 58 (vs 38 del primer análisis). Se recomienda priorizar las 18 brechas críticas antes de la fecha de vigencia del 01 de junio de 2025._
