# Análisis Completo: Actualización a v4.4 Hacienda Costa Rica

## Resumen Ejecutivo

Este documento fue el análisis inicial de requisitos para la versión 4.4 de comprobantes electrónicos de Hacienda CR. **Todos los cambios identificados han sido implementados.**

> **⚠️ DOCUMENTO HISTÓRICO**: Este análisis fue creado previo a la implementación. Para el estado actual de compliance, consultar:
>
> * `docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md` — Análisis campo por campo más reciente (38 brechas, todas resueltas)
> * `docs/hacienda/AUDITORIA_FIRMA_DIGITAL_2026-04-17.md` — Auditoría de firma digital y código legacy

**Estado Actual (18 abril 2026):**

* ✅ XML Builder: v4.4 completo (namespaces, ProveedorSistemas, CodigoActividadEmisor, BaseImponible, DetalleSurtido, etc.)
* ✅ Firma Digital: XAdES-EPES con SignaturePolicyIdentifier, QualifyingProperties, DataObjectFormat
* ✅ Campos nuevos obligatorios: Todos implementados (38/38 brechas resueltas)
* ✅ Política de firma: URL v4.4 configurada
* ✅ 49 tests Hacienda V44 pasando (124 assertions)
* ✅ Modelos nuevos: 8 (FeLineaImpuesto, FeMedioPago, FeInformacionReferencia, FeOtroCargo, FeLineaDescuento, FeCodigoComercial, FeDetalleSurtido, FeSurtidoImpuesto)
* ✅ Migraciones: 2 nuevas (5 tablas + 3 tablas + 20+ columnas)

***

## ✅ Cambios CRÍTICOS Requeridos (COMPLETADOS)

### 1. Campo `ProveedorSistemas` - NUEVO OBLIGATORIO

```xml
<!-- Después de <Clave> y antes de <CodigoActividadEmisor> -->
<ProveedorSistemas>106470958</ProveedorSistemas>
```

**Especificación:**

* **Tamaño:** String de hasta 12 caracteres
* **Descripción:** Número de cédula del proveedor del sistema de facturación
* **Obligatorio:** SÍ en todos los comprobantes (FE, TE, NC, ND, FEC, FEE, REP)
* **Validación:** Se verificará que el proveedor esté inscrito en el registro de la DGT

**Impacto en código:**

```php
// En XmlComprobanteBuilder.php - Agregar después de agregarClave()
protected function agregarProveedorSistemas(DOMElement $parent): void
{
    // Obtener de config o de la tabla de configuración
    $proveedor = config('hacienda.proveedor_sistemas');
    $element = $this->doc->createElement('ProveedorSistemas', $proveedor);
    $parent->appendChild($element);
}
```

### 2. Actualización de Namespaces a v4.4

**Actual (v4.3):**

```php
const NAMESPACE_FACTURA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica';
```

**Requerido (v4.4):**

```php
const NAMESPACE_FACTURA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica';
const NAMESPACE_NOTA_DEBITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica';
const NAMESPACE_NOTA_CREDITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica';
const NAMESPACE_TIQUETE = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico';
const NAMESPACE_FACTURA_COMPRA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra';
const NAMESPACE_FACTURA_EXPORTACION = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaExportacion';
const NAMESPACE_RECIBO_PAGO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboElectronicoPago';
```

### 3. Campo `CodigoActividadEmisor` - Renombrado

**Antes:** `CodigoActividad` **Ahora:** `CodigoActividadEmisor`

```php
// Cambiar
protected function agregarCodigoActividad(...) → protected function agregarCodigoActividadEmisor(...)
$element = $this->doc->createElement('CodigoActividadEmisor', $codigoActividad);
```

***

## ✅ Cambios en Firma Digital XAdES-EPES (COMPLETADOS)

### Especificaciones del Anexo 2

**Estándar requerido:**

* XAdES-EPES versión 1.3.2 o superior
* Empaquetado: **ENVELOPED** (obligatorio)
* XPath de firma: `/FacturaElectronica/ds:Signature`

**Algoritmos permitidos:**

| Componente       | Algoritmos                                            |
| ---------------- | ----------------------------------------------------- |
| Certificado      | RSA 2048, RSA 4096                                    |
| Digest Firma     | SHA-256, SHA-512                                      |
| Canonicalización | EXC\_C14N (`http://www.w3.org/2001/10/xml-exc-c14n#`) |

### ⚠️ Problemas en Implementación Actual

El servicio `FirmaDigitalService.php` **NO incluye los elementos XAdES requeridos**:

#### Faltante 1: SignaturePolicyIdentifier (CRÍTICO)

```xml
<xades:SignaturePolicyIdentifier>
    <xades:SignaturePolicyId>
        <xades:SigPolicyId>
            <xades:Identifier>URLXXXXV4.4</xades:Identifier>
        </xades:SigPolicyId>
        <xades:SigPolicyHash>
            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
            <ds:DigestValue>NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ==</ds:DigestValue>
        </xades:SigPolicyHash>
    </xades:SignaturePolicyId>
</xades:SignaturePolicyIdentifier>
```

#### Faltante 2: QualifyingProperties

```xml
<ds:Object>
    <xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Target="#signature-id">
        <xades:SignedProperties Id="xades-signature-id">
            <xades:SignedSignatureProperties>
                <xades:SigningTime>2025-01-15T10:30:00Z</xades:SigningTime>
                <xades:SigningCertificate>
                    <xades:Cert>
                        <xades:CertDigest>
                            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                            <ds:DigestValue>...</ds:DigestValue>
                        </xades:CertDigest>
                        <xades:IssuerSerial>
                            <ds:X509IssuerName>...</ds:X509IssuerName>
                            <ds:X509SerialNumber>...</ds:X509SerialNumber>
                        </xades:IssuerSerial>
                    </xades:Cert>
                </xades:SigningCertificate>
                <xades:SignaturePolicyIdentifier>...</xades:SignaturePolicyIdentifier>
            </xades:SignedSignatureProperties>
        </xades:SignedProperties>
    </xades:QualifyingProperties>
</ds:Object>
```

#### Faltante 3: DataObjectFormat

```xml
<xades:SignedDataObjectProperties>
    <xades:DataObjectFormat ObjectReference="#r-id-1">
        <xades:MimeType>application/octet-stream</xades:MimeType>
    </xades:DataObjectFormat>
</xades:SignedDataObjectProperties>
```

### Recomendación: Usar Librería XAdES Completa

La librería `RobRichards\XMLSecLibs` **NO soporta XAdES nativamente**, solo XMLDSig básico.

**Opciones:**

1. **Opción A:** Usar librería PHP que soporte XAdES-EPES nativo
   * `greenterit/xml-dsig` (específica para facturación electrónica LATAM)
2. **Opción B:** Implementar manualmente los nodos XAdES sobre XMLSecLibs
   * Más trabajo pero control total

***

## ✅ Campos Nuevos v4.4 (COMPLETADOS)

### En Línea de Detalle

| Campo                          | Tipo    | Tamaño | Descripción                        | Obligatorio            |
| ------------------------------ | ------- | ------ | ---------------------------------- | ---------------------- |
| `CodigoCABYS`                  | String  | 13     | Código catálogo bienes y servicios | SÍ                     |
| `BaseImponible`                | Decimal | 18,5   | Base para cálculo de impuesto      | SÍ cuando hay impuesto |
| `ImpuestoAsumidoEmisorFabrica` | Decimal | 18,5   | IVA a nivel de fábrica             | Condicional            |
| `IVACobradoFabrica`            | String  | 2      | Código IVA nivel fábrica (01,02)   | Condicional            |
| `TipoTransaccion`              | String  | 2      | Ver Nota 21                        | Condicional            |

### Nuevos Tipos de Identificación

| Código | Descripción               | Uso                            |
| ------ | ------------------------- | ------------------------------ |
| 05     | Extranjero No Domiciliado | FEC emisor, FE/FEE/TE receptor |
| 06     | No Contribuyente          | FEC emisor (bienes usados)     |

### Nuevas Condiciones de Venta

| Código | Descripción                          |
| ------ | ------------------------------------ |
| 12     | Venta Mercancía No Nacionalizada     |
| 13     | Venta Bienes Usados No Contribuyente |
| 14     | Arrendamiento Operativo              |
| 15     | Arrendamiento Financiero             |

### Nuevos Códigos de Descuento

| Código | Descripción                                  |
| ------ | -------------------------------------------- |
| 01     | Descuento por Regalía                        |
| 02     | Descuento por Regalía IVA Cobrado al Cliente |
| 03     | Descuento por Bonificación                   |
| 04-11  | Varios (pronto pago, volumen, etc.)          |
| 99     | Otros descuentos                             |

### Nuevos Códigos de Referencia

| Código | Descripción                       |
| ------ | --------------------------------- |
| 06     | Devolución de mercancía           |
| 07     | Sustituye comprobante electrónico |
| 08     | Factura Endosada                  |
| 09     | Nota de crédito financiera        |
| 10     | Nota de débito financiera         |
| 11     | Proveedor No Domiciliado          |
| 12     | Crédito por exoneración posterior |

***

## ✅ Nodo DetalleProductosSurtido (COMPLETADO)

Nuevo nodo para **combos/paquetes de productos**:

```xml
<DetalleProductosSurtido>
    <LineaDetalleSurtido>
        <NumeroLineaSurtido>1</NumeroLineaSurtido>
        <CodigoCABYSSurtido>4523000000000</CodigoCABYSSurtido>
        <CantidadSurtido>2.000</CantidadSurtido>
        <UnidadMedidaSurtido>Unid</UnidadMedidaSurtido>
        <DetalleSurtido>Producto dentro del combo</DetalleSurtido>
        <PrecioUnitarioSurtido>50.00000</PrecioUnitarioSurtido>
        <MontoTotalSurtido>100.00000</MontoTotalSurtido>
        <SubTotalSurtido>100.00000</SubTotalSurtido>
        <!-- Impuestos de surtido -->
    </LineaDetalleSurtido>
</DetalleProductosSurtido>
```

***

## ✅ Resumen de Factura - Cambios (COMPLETADOS)

### Nuevos Campos

```xml
<ResumenFactura>
    <!-- Campos existentes -->
    
    <!-- NUEVO: Desglose de impuestos por código -->
    <TotalDesgloseImpuesto>
        <Codigo>01</Codigo>
        <CodigoTarifaIVA>08</CodigoTarifaIVA>
        <TotalMontoImpuesto>24.70000</TotalMontoImpuesto>
    </TotalDesgloseImpuesto>
    
    <!-- CAMBIO: MedioPago movido aquí -->
    <MedioPago>
        <TipoMedioPago>01</TipoMedioPago>
        <TotalMedioPago>214.70000</TotalMedioPago>
    </MedioPago>
    
    <TotalComprobante>214.70000</TotalComprobante>
</ResumenFactura>
```

**Nota importante:** `MedioPago` se mueve de nivel raíz a dentro de `ResumenFactura`.

***

## 📋 Errores Comunes de Rechazo (Análisis XML\_RESPUESTA)

Del archivo de respuesta analizado, estos son los errores típicos:

| Código | Mensaje                        | Causa                                              | Solución                          |
| ------ | ------------------------------ | -------------------------------------------------- | --------------------------------- |
| -99    | Consecutivo duplicado          | Numeración ya usada                                | Validar secuencia antes de enviar |
| -37    | Ubicación emisor no coincide   | Provincia/Cantón/Distrito diferente a registro DGT | Sincronizar datos con DGT         |
| -407   | Código actividad no registrado | Actividad no registrada en ATV                     | Verificar actividades inscritas   |

***

## ✅ Archivos Modificados

### 1. `app/Services/Hacienda/Xml/XmlComprobanteBuilder.php` ✅

| Cambio                                                                  | Estado |
| ----------------------------------------------------------------------- | :----: |
| Actualizar VERSION\_ESQUEMA a '4.4'                                     |    ✅   |
| Actualizar todos los NAMESPACE a v4.4                                   |    ✅   |
| Agregar método `agregarProveedorSistemas()`                             |    ✅   |
| Renombrar `agregarCodigoActividad()` a `agregarCodigoActividadEmisor()` |    ✅   |
| Agregar soporte para `TotalDesgloseImpuesto` con `CodigoTarifaIVA`      |    ✅   |
| Mover `MedioPago` dentro de `ResumenFactura`                            |    ✅   |
| Agregar campos `BaseImponible`, `CodigoCABYS`                           |    ✅   |
| Agregar soporte para nuevos tipos identificación (05, 06)               |    ✅   |
| Agregar nodo `DetalleProductosSurtido`                                  |    ✅   |
| OtrosCargos con TipoDocumentoOC e IdentificacionTercero                 |    ✅   |
| InformacionReferencia con códigos 06-12 y tipo 99                       |    ✅   |
| CodigoComercial {0,5} normalizado                                       |    ✅   |
| Exoneraciones con campos completos                                      |    ✅   |
| Múltiples emails emisor (hasta 4)                                       |    ✅   |

### 2. `app/Services/Hacienda/Xml/XadesEpesSigner.php` ✅

| Cambio                                 | Estado |
| -------------------------------------- | :----: |
| QualifyingProperties                   |    ✅   |
| SignaturePolicyIdentifier con URL v4.4 |    ✅   |
| SignedSignatureProperties              |    ✅   |
| SigningTime                            |    ✅   |
| SigningCertificate con digest          |    ✅   |
| DataObjectFormat                       |    ✅   |

### 3. `config/hacienda.php` ✅

* `proveedor_sistemas`, `version_esquema` = '4.4', `policy_url`, `policy_hash`
* Catálogos completos: `condiciones_venta` (01-15, 99), `medios_pago` (01-07, 99)
* `token_ttl` corregido: 3600→300
* `tipos_comprobante` corregidos: códigos 05-09 labels, código 10 (REP) agregado

### 4. Migraciones de Base de Datos ✅

* `2026_04_07_000000_hacienda_v44_compliance_full.php` — 5 tablas nuevas, 20+ columnas
* `2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php` — 3 tablas nuevas

### 5. Modelos nuevos ✅

`FeLineaImpuesto`, `FeMedioPago`, `FeInformacionReferencia`, `FeOtroCargo`, `FeLineaDescuento`, `FeCodigoComercial`, `FeDetalleSurtido`, `FeSurtidoImpuesto`

***

## Cronograma — COMPLETADO

### Fase 2: XmlComprobanteBuilder ✅ COMPLETADO (Dic 2025)

### Fase 3: Validaciones y Testing ✅ COMPLETADO (Abr 2026)

### Fase 4: Campos Opcionales ✅ COMPLETADO (Abr 2026)

Todas las fases completadas con 49 tests (124 assertions) pasando.

***

## 🔗 Referencias

1. **Documento oficial:** `DGT-R-000-2024DisposicionesTecnicasDeComprobantesElectronicosCP.txt`
2. **Anexo 2:** Mecanismo de seguridad XAdES-EPES
3. **Anexo 3:** Conexión API REST
4. **XMLs de ejemplo:** `XML_GENERADO/`, `XML_FIRMADO/`, `XML_RESPUESTA/`

***

## ✅ Checklist de Validación Pre-Envío

* [x] Clave numérica de 50 dígitos correcta
* [x] ProveedorSistemas presente y válido
* [x] CodigoActividadEmisor registrado en DGT
* [x] Ubicación emisor coincide con registro DGT
* [x] Consecutivo no duplicado
* [x] Firma XAdES-EPES con SignaturePolicyIdentifier
* [x] Namespace apunta a v4.4
* [x] CodigoCABYS válido en cada línea
* [x] BaseImponible calculada correctamente
* [x] TotalDesgloseImpuesto incluido en ResumenFactura
* [x] Múltiples impuestos por línea (tabla `fe_linea_impuestos`)
* [x] Múltiples medios de pago (tabla `fe_medios_pago`, 1-4)
* [x] Ubicación del receptor (provincia, cantón, distrito)
* [x] CódigoDescuento obligatorio cuando hay descuento
* [x] Receptor identificación VARCHAR(20)
* [x] Clave VARCHAR(50) en hacienda\_comprobantes

***

_Documento original: Enero 2025_\
&#xNAN;_&#xDA;ltima actualización: 18 de abril de 2026_\
&#xNAN;_&#x45;stado: ✅ TODOS LOS CAMBIOS IMPLEMENTADOS_\
&#xNAN;_&#x42;asado en: Resolución DGT-R-000-2024_\
&#xNAN;_&#x41;nálisis detallado actualizado: `docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md`_
