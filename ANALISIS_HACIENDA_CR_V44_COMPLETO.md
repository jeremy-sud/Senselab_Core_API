# 📋 Análisis Completo: Actualización a v4.4 Hacienda Costa Rica

## 📌 Resumen Ejecutivo

Este documento analiza en profundidad los requisitos de la versión 4.4 de comprobantes electrónicos de Hacienda CR, comparándolos con la implementación actual de la API para identificar cambios necesarios y evitar rechazos.

**Estado Actual:**
- ❌ XML Builder: v4.3 → Requiere actualización a v4.4
- ✅ Firma Digital: Implementación XAdES-EPES funcional, pero requiere ajustes
- ⚠️ Campos nuevos obligatorios: No implementados
- ⚠️ Política de firma: URL debe actualizarse a v4.4

---

## 🔴 Cambios CRÍTICOS Requeridos (Prioridad ALTA)

### 1. Campo `ProveedorSistemas` - NUEVO OBLIGATORIO

```xml
<!-- Después de <Clave> y antes de <CodigoActividadEmisor> -->
<ProveedorSistemas>106470958</ProveedorSistemas>
```

**Especificación:**
- **Tamaño:** String de hasta 12 caracteres
- **Descripción:** Número de cédula del proveedor del sistema de facturación
- **Obligatorio:** SÍ en todos los comprobantes (FE, TE, NC, ND, FEC, FEE, REP)
- **Validación:** Se verificará que el proveedor esté inscrito en el registro de la DGT

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

**Antes:** `CodigoActividad`
**Ahora:** `CodigoActividadEmisor`

```php
// Cambiar
protected function agregarCodigoActividad(...) → protected function agregarCodigoActividadEmisor(...)
$element = $this->doc->createElement('CodigoActividadEmisor', $codigoActividad);
```

---

## 🟠 Cambios en Firma Digital XAdES-EPES (Prioridad ALTA)

### Especificaciones del Anexo 2

**Estándar requerido:**
- XAdES-EPES versión 1.3.2 o superior
- Empaquetado: **ENVELOPED** (obligatorio)
- XPath de firma: `/FacturaElectronica/ds:Signature`

**Algoritmos permitidos:**
| Componente | Algoritmos |
|------------|------------|
| Certificado | RSA 2048, RSA 4096 |
| Digest Firma | SHA-256, SHA-512 |
| Canonicalización | EXC_C14N (`http://www.w3.org/2001/10/xml-exc-c14n#`) |

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
   - `greenterit/xml-dsig` (específica para facturación electrónica LATAM)
   
2. **Opción B:** Implementar manualmente los nodos XAdES sobre XMLSecLibs
   - Más trabajo pero control total

---

## 🟡 Campos Nuevos v4.4 (Prioridad MEDIA)

### En Línea de Detalle

| Campo | Tipo | Tamaño | Descripción | Obligatorio |
|-------|------|--------|-------------|-------------|
| `CodigoCABYS` | String | 13 | Código catálogo bienes y servicios | SÍ |
| `BaseImponible` | Decimal | 18,5 | Base para cálculo de impuesto | SÍ cuando hay impuesto |
| `ImpuestoAsumidoEmisorFabrica` | Decimal | 18,5 | IVA a nivel de fábrica | Condicional |
| `IVACobradoFabrica` | String | 2 | Código IVA nivel fábrica (01,02) | Condicional |
| `TipoTransaccion` | String | 2 | Ver Nota 21 | Condicional |

### Nuevos Tipos de Identificación

| Código | Descripción | Uso |
|--------|-------------|-----|
| 05 | Extranjero No Domiciliado | FEC emisor, FE/FEE/TE receptor |
| 06 | No Contribuyente | FEC emisor (bienes usados) |

### Nuevas Condiciones de Venta

| Código | Descripción |
|--------|-------------|
| 12 | Venta Mercancía No Nacionalizada |
| 13 | Venta Bienes Usados No Contribuyente |
| 14 | Arrendamiento Operativo |
| 15 | Arrendamiento Financiero |

### Nuevos Códigos de Descuento

| Código | Descripción |
|--------|-------------|
| 01 | Descuento por Regalía |
| 02 | Descuento por Regalía IVA Cobrado al Cliente |
| 03 | Descuento por Bonificación |
| 04-11 | Varios (pronto pago, volumen, etc.) |
| 99 | Otros descuentos |

### Nuevos Códigos de Referencia

| Código | Descripción |
|--------|-------------|
| 06 | Devolución de mercancía |
| 07 | Sustituye comprobante electrónico |
| 08 | Factura Endosada |
| 09 | Nota de crédito financiera |
| 10 | Nota de débito financiera |
| 11 | Proveedor No Domiciliado |
| 12 | Crédito por exoneración posterior |

---

## 🔵 Nodo DetalleProductosSurtido (Prioridad BAJA)

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

---

## 🔵 Resumen de Factura - Cambios

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

---

## 📋 Errores Comunes de Rechazo (Análisis XML_RESPUESTA)

Del archivo de respuesta analizado, estos son los errores típicos:

| Código | Mensaje | Causa | Solución |
|--------|---------|-------|----------|
| -99 | Consecutivo duplicado | Numeración ya usada | Validar secuencia antes de enviar |
| -37 | Ubicación emisor no coincide | Provincia/Cantón/Distrito diferente a registro DGT | Sincronizar datos con DGT |
| -407 | Código actividad no registrado | Actividad no registrada en ATV | Verificar actividades inscritas |

---

## 📁 Archivos a Modificar

### 1. `app/Services/Hacienda/Xml/XmlComprobanteBuilder.php`

| Cambio | Prioridad |
|--------|-----------|
| Actualizar VERSION_ESQUEMA a '4.4' | ALTA |
| Actualizar todos los NAMESPACE a v4.4 | ALTA |
| Agregar método `agregarProveedorSistemas()` | ALTA |
| Renombrar `agregarCodigoActividad()` a `agregarCodigoActividadEmisor()` | ALTA |
| Agregar soporte para `TotalDesgloseImpuesto` | MEDIA |
| Mover `MedioPago` dentro de `ResumenFactura` | MEDIA |
| Agregar campos `BaseImponible`, `CodigoCABYS` | MEDIA |
| Agregar soporte para nuevos tipos identificación (05, 06) | MEDIA |
| Agregar nodo `DetalleProductosSurtido` | BAJA |

### 2. `app/Services/Hacienda/Xml/FirmaDigitalService.php`

| Cambio | Prioridad |
|--------|-----------|
| Agregar QualifyingProperties | ALTA |
| Agregar SignaturePolicyIdentifier con URL v4.4 | ALTA |
| Agregar SignedSignatureProperties | ALTA |
| Agregar SigningTime | ALTA |
| Agregar SigningCertificate con digest | ALTA |
| Agregar DataObjectFormat | ALTA |

### 3. `config/hacienda.php`

```php
return [
    // Agregar
    'proveedor_sistemas' => env('HACIENDA_PROVEEDOR_SISTEMAS', ''),
    'version_esquema' => '4.4',
    'policy_url' => 'URLXXXXV4.4', // URL final cuando Hacienda publique
    'policy_hash' => 'NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ==',
];
```

### 4. Migraciones de Base de Datos

```php
// Agregar campos a tablas relacionadas
Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
    $table->string('tipo_transaccion', 2)->nullable()->after('medio_pago');
    $table->boolean('iva_cobrado_fabrica')->default(false)->after('tipo_transaccion');
});

Schema::table('comprobante_lineas', function (Blueprint $table) {
    $table->string('codigo_cabys', 13)->nullable()->after('codigo_producto');
    $table->decimal('base_imponible', 18, 5)->nullable()->after('subtotal');
    $table->decimal('impuesto_asumido_emisor_fabrica', 18, 5)->nullable();
});
```

---

## ⏰ Cronograma de Implementación Sugerido

### Fase 1: Firma Digital XAdES-EPES (1-2 semanas)
1. Investigar/evaluar librería XAdES para PHP
2. Implementar QualifyingProperties
3. Implementar SignaturePolicyIdentifier
4. Testing con ambiente de pruebas de Hacienda

### Fase 2: Actualización XmlComprobanteBuilder (1 semana)
1. Actualizar namespaces a v4.4
2. Agregar ProveedorSistemas
3. Renombrar campos según especificación
4. Agregar campos nuevos obligatorios

### Fase 3: Validaciones y Testing (1 semana)
1. Actualizar validaciones FormRequest
2. Crear tests unitarios para nuevos campos
3. Testing integral con Hacienda sandbox

### Fase 4: Campos Opcionales (1 semana)
1. Implementar DetalleProductosSurtido
2. Nuevos códigos de referencia
3. Tipos de identificación 05, 06

---

## 🔗 Referencias

1. **Documento oficial:** `DGT-R-000-2024DisposicionesTecnicasDeComprobantesElectronicosCP.txt`
2. **Anexo 2:** Mecanismo de seguridad XAdES-EPES
3. **Anexo 3:** Conexión API REST
4. **XMLs de ejemplo:** `XML_GENERADO/`, `XML_FIRMADO/`, `XML_RESPUESTA/`

---

## ✅ Checklist de Validación Pre-Envío

- [ ] Clave numérica de 50 dígitos correcta
- [ ] ProveedorSistemas presente y válido
- [ ] CodigoActividadEmisor registrado en DGT
- [ ] Ubicación emisor coincide con registro DGT
- [ ] Consecutivo no duplicado
- [ ] Firma XAdES-EPES con SignaturePolicyIdentifier
- [ ] Namespace apunta a v4.4
- [ ] CodigoCABYS válido en cada línea
- [ ] BaseImponible calculada correctamente
- [ ] TotalDesgloseImpuesto incluido en ResumenFactura

---

*Documento generado: Enero 2025*
*Versión del análisis: 1.0*
*Basado en: Resolución DGT-R-000-2024*
