# Plan de Implementación: Migración a v4.4 Hacienda CR

## ✅ ESTADO: COMPLETADO AL 100% (Abril 2026)

> **Nota:** Este plan ha sido completamente implementado en múltiples fases.
>
> **Implementación inicial (Diciembre 2025):**
> - `648fb9d` - feat(hacienda): Implementar firma XAdES-EPES y actualizar a v4.4
> - `810a2f6` - feat(hacienda): Completar implementación v4.4 con campos adicionales
> - `87ddb88` - test: Agregar tests unitarios para XadesEpesSigner
>
> **Auditoría y remediación completa (Abril 2026):**
> - Análisis comparativo campo por campo: `docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md`
> - Se identificaron y resolvieron **38 brechas** (8 críticas, 11 altas, 14 medias, 5 bajas)
> - Fase A: Migraciones y modelos (`2026_04_07_000000_hacienda_v44_compliance_full.php` — 5 tablas nuevas, 20+ columnas)
> - Fase B: XML builder, validaciones, config
> - Fase C: Surtidos y códigos comerciales (`2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php` — 3 tablas)
> - 49 tests Hacienda V44 (124 assertions) pasando ✅
>
> **Auditoría firma digital (17 Abril 2026):**
> - Ver `docs/hacienda/AUDITORIA_FIRMA_DIGITAL_2026-04-17.md`
> - OAuth 2.0 sandbox verificado ✅
> - Incompatibilidad .p12 con OpenSSL 3.x identificada y solucionada
> - `HaciendaIntegrationService` (código legacy muerto) eliminado
>
> **Resultado final:** 38/38 brechas resueltas = **100% compliance v4.4**

## Índice
1. [Resumen de Cambios](#resumen-de-cambios)
2. [Fase 1: Actualizar FirmaDigitalService](#fase-1-firmadigitalservice)
3. [Fase 2: Actualizar XmlComprobanteBuilder](#fase-2-xmlcomprobantebuilder)
4. [Fase 3: Configuración](#fase-3-configuración)
5. [Fase 4: Migraciones de BD](#fase-4-migraciones)
6. [Fase 5: Validaciones](#fase-5-validaciones)

---

## Resumen de Cambios

### Estado de Remediación (actualizado 18 abril 2026)

| Criticidad | Total | Resueltas |
|:----------:|:-----:|:---------:|
| 🔴 Crítico | 8 | **8** ✅ |
| 🟠 Alto | 11 | **11** ✅ |
| 🟡 Medio | 14 | **14** ✅ |
| 🟢 Bajo | 5 | **5** ✅ |
| **Total** | **38** | **38** ✅ |

**Archivos principales modificados:**
- Migraciones: `2026_04_07_000000_hacienda_v44_compliance_full.php`, `2026_04_10_000000_hacienda_v44_fase_c_surtido_codigo_comercial.php`
- XML: `XmlComprobanteBuilder.php` (OtrosCargos, InformacionReferencia, Exoneraciones, BaseImponible, CodigoComercial {0,5}, DetalleSurtido)
- Modelos nuevos: `FeLineaImpuesto`, `FeMedioPago`, `FeInformacionReferencia`, `FeOtroCargo`, `FeLineaDescuento`, `FeCodigoComercial`, `FeDetalleSurtido`, `FeSurtidoImpuesto`
- Validaciones: `StoreComprobanteElectronicoRequest`, `CrIdentificacion`
- Config: `config/hacienda.php` (catálogos completos v4.4)
- Tests: 49 tests V44 pasando (124 assertions)

> Para el análisis detallado campo por campo, ver `docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md`

### Urgente (Bloquea envío a Hacienda) - ✅ COMPLETADO
1. ✅ `ProveedorSistemas` - Campo nuevo obligatorio → Implementado en XmlComprobanteBuilder
2. ✅ Namespace v4.4 en XMLs → Actualizado a ResolucionDGT-R-000-2024
3. ✅ XAdES con `SignaturePolicyIdentifier` → Implementado en XadesEpesSigner

### Alta Prioridad - ✅ COMPLETADO
4. ✅ `CodigoActividadEmisor` (renombrado) → Actualizado en XML
5. ✅ `BaseImponible` obligatorio cuando hay impuesto → Agregado en agregarDetalleServicio()
6. ✅ `TotalDesgloseImpuesto` en ResumenFactura → Implementado

### Media Prioridad - ✅ COMPLETADO
7. ✅ Nuevos tipos de identificación (05, 06) → Soportados
8. ✅ Nuevas condiciones de venta (12-15) → Soportadas
9. ✅ `MedioPago` movido a `ResumenFactura` → Implementado

---

## Fase 1: FirmaDigitalService - ✅ COMPLETADO

### 1.1 Instalar dependencia para XAdES

La librería `robrichards/xmlseclibs` NO soporta XAdES. Necesitamos agregar soporte manualmente.

```bash
# El paquete actual es suficiente, pero debemos extenderlo
composer require robrichards/xmlseclibs:^3.1
```

### 1.2 Crear nueva clase XadesEpesSigner - ✅ IMPLEMENTADO

```php
<?php
// app/Services/Hacienda/Xml/XadesEpesSigner.php

namespace App\Services\Hacienda\Xml;

use DOMDocument;
use DOMElement;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Carbon\Carbon;

/**
 * Firmador XAdES-EPES para Comprobantes Electrónicos Hacienda CR v4.4
 *
 * Implementa firma XAdES-EPES según ETSI TS 101 903 v1.3.2+
 * Requerido por Ministerio de Hacienda de Costa Rica.
 */
class XadesEpesSigner
{
    // Namespaces
    const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    const NS_XADES = 'http://uri.etsi.org/01903/v1.3.2#';

    // Algoritmos
    const ALGO_RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    const ALGO_SHA256 = 'http://www.w3.org/2001/04/xmlenc#sha256';
    const ALGO_C14N = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    const ALGO_ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    // Política de firma v4.4
    const POLICY_URL = 'https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-000-2024.pdf';
    const POLICY_HASH = 'NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ==';

    protected DOMDocument $doc;
    protected string $privateKey;
    protected string $certificate;
    protected array $certInfo;
    protected string $signatureId;

    /**
     * Firmar documento XML con XAdES-EPES
     */
    public function sign(string $xml, string $privateKey, string $certificate): string
    {
        $this->privateKey = $privateKey;
        $this->certificate = $certificate;
        $this->certInfo = openssl_x509_parse($certificate);
        $this->signatureId = 'id-' . $this->generateUuid();

        // Cargar documento
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->loadXML($xml);
        $this->doc->preserveWhiteSpace = false;

        // Crear estructura de firma
        $signatureElement = $this->createSignatureElement();

        // Insertar firma antes del cierre del elemento raíz
        $root = $this->doc->documentElement;
        $root->appendChild($signatureElement);

        return $this->doc->saveXML();
    }

    /**
     * Crear elemento ds:Signature completo con XAdES
     */
    protected function createSignatureElement(): DOMElement
    {
        // Crear elemento Signature
        $signature = $this->doc->createElementNS(self::NS_DS, 'ds:Signature');
        $signature->setAttribute('Id', $this->signatureId);

        // 1. SignedInfo
        $signedInfo = $this->createSignedInfo();
        $signature->appendChild($signedInfo);

        // 2. SignatureValue (calculado después)
        $signatureValue = $this->createSignatureValue($signedInfo);
        $signature->appendChild($signatureValue);

        // 3. KeyInfo
        $keyInfo = $this->createKeyInfo();
        $signature->appendChild($keyInfo);

        // 4. Object con QualifyingProperties (XAdES)
        $object = $this->createXadesObject();
        $signature->appendChild($object);

        return $signature;
    }

    /**
     * Crear ds:SignedInfo
     */
    protected function createSignedInfo(): DOMElement
    {
        $signedInfo = $this->doc->createElementNS(self::NS_DS, 'ds:SignedInfo');

        // CanonicalizationMethod
        $c14n = $this->doc->createElementNS(self::NS_DS, 'ds:CanonicalizationMethod');
        $c14n->setAttribute('Algorithm', self::ALGO_C14N);
        $signedInfo->appendChild($c14n);

        // SignatureMethod
        $sigMethod = $this->doc->createElementNS(self::NS_DS, 'ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', self::ALGO_RSA_SHA256);
        $signedInfo->appendChild($sigMethod);

        // Reference al documento (excluye firma)
        $refDoc = $this->createDocumentReference();
        $signedInfo->appendChild($refDoc);

        // Reference a SignedProperties (XAdES)
        $refProps = $this->createSignedPropertiesReference();
        $signedInfo->appendChild($refProps);

        return $signedInfo;
    }

    /**
     * Crear referencia al documento
     */
    protected function createDocumentReference(): DOMElement
    {
        $reference = $this->doc->createElementNS(self::NS_DS, 'ds:Reference');
        $reference->setAttribute('Id', 'r-id-1');
        $reference->setAttribute('URI', '');

        // Transforms
        $transforms = $this->doc->createElementNS(self::NS_DS, 'ds:Transforms');

        // Transform XPath (excluir Signature)
        $transformXpath = $this->doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transformXpath->setAttribute('Algorithm', 'http://www.w3.org/TR/1999/REC-xpath-19991116');
        $xpath = $this->doc->createElementNS(self::NS_DS, 'ds:XPath', 'not(ancestor-or-self::ds:Signature)');
        $transformXpath->appendChild($xpath);
        $transforms->appendChild($transformXpath);

        // Transform C14N
        $transformC14n = $this->doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transformC14n->setAttribute('Algorithm', self::ALGO_C14N);
        $transforms->appendChild($transformC14n);

        $reference->appendChild($transforms);

        // DigestMethod
        $digestMethod = $this->doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $reference->appendChild($digestMethod);

        // DigestValue
        $docDigest = $this->calculateDocumentDigest();
        $digestValue = $this->doc->createElementNS(self::NS_DS, 'ds:DigestValue', $docDigest);
        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Crear referencia a SignedProperties
     */
    protected function createSignedPropertiesReference(): DOMElement
    {
        $reference = $this->doc->createElementNS(self::NS_DS, 'ds:Reference');
        $reference->setAttribute('Type', 'http://uri.etsi.org/01903#SignedProperties');
        $reference->setAttribute('URI', '#xades-' . $this->signatureId);

        // Transforms
        $transforms = $this->doc->createElementNS(self::NS_DS, 'ds:Transforms');
        $transform = $this->doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transform->setAttribute('Algorithm', self::ALGO_C14N);
        $transforms->appendChild($transform);
        $reference->appendChild($transforms);

        // DigestMethod
        $digestMethod = $this->doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $reference->appendChild($digestMethod);

        // DigestValue (placeholder - se calcula después)
        $digestValue = $this->doc->createElementNS(self::NS_DS, 'ds:DigestValue', '');
        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Crear ds:SignatureValue
     */
    protected function createSignatureValue(DOMElement $signedInfo): DOMElement
    {
        // Canonicalizar SignedInfo
        $c14n = $signedInfo->C14N(true, false);

        // Firmar con clave privada
        $signature = '';
        openssl_sign($c14n, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        $signatureValue = $this->doc->createElementNS(
            self::NS_DS,
            'ds:SignatureValue',
            base64_encode($signature)
        );
        $signatureValue->setAttribute('Id', 'value-' . $this->signatureId);

        return $signatureValue;
    }

    /**
     * Crear ds:KeyInfo
     */
    protected function createKeyInfo(): DOMElement
    {
        $keyInfo = $this->doc->createElementNS(self::NS_DS, 'ds:KeyInfo');

        $x509Data = $this->doc->createElementNS(self::NS_DS, 'ds:X509Data');

        // Limpiar certificado (quitar headers)
        $certClean = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"],
            '',
            $this->certificate
        );

        $x509Cert = $this->doc->createElementNS(self::NS_DS, 'ds:X509Certificate', $certClean);
        $x509Data->appendChild($x509Cert);

        $keyInfo->appendChild($x509Data);

        return $keyInfo;
    }

    /**
     * Crear ds:Object con xades:QualifyingProperties
     */
    protected function createXadesObject(): DOMElement
    {
        $object = $this->doc->createElementNS(self::NS_DS, 'ds:Object');

        $qualifyingProps = $this->doc->createElementNS(self::NS_XADES, 'xades:QualifyingProperties');
        $qualifyingProps->setAttribute('Target', '#' . $this->signatureId);

        // SignedProperties
        $signedProperties = $this->createSignedProperties();
        $qualifyingProps->appendChild($signedProperties);

        $object->appendChild($qualifyingProps);

        return $object;
    }

    /**
     * Crear xades:SignedProperties
     */
    protected function createSignedProperties(): DOMElement
    {
        $signedProps = $this->doc->createElementNS(self::NS_XADES, 'xades:SignedProperties');
        $signedProps->setAttribute('Id', 'xades-' . $this->signatureId);

        // SignedSignatureProperties
        $sigProps = $this->createSignedSignatureProperties();
        $signedProps->appendChild($sigProps);

        // SignedDataObjectProperties
        $dataProps = $this->createSignedDataObjectProperties();
        $signedProps->appendChild($dataProps);

        return $signedProps;
    }

    /**
     * Crear xades:SignedSignatureProperties
     */
    protected function createSignedSignatureProperties(): DOMElement
    {
        $props = $this->doc->createElementNS(self::NS_XADES, 'xades:SignedSignatureProperties');

        // SigningTime
        $signingTime = $this->doc->createElementNS(
            self::NS_XADES,
            'xades:SigningTime',
            Carbon::now()->toIso8601String()
        );
        $props->appendChild($signingTime);

        // SigningCertificate
        $signingCert = $this->createSigningCertificate();
        $props->appendChild($signingCert);

        // SignaturePolicyIdentifier
        $policyId = $this->createSignaturePolicyIdentifier();
        $props->appendChild($policyId);

        return $props;
    }

    /**
     * Crear xades:SigningCertificate
     */
    protected function createSigningCertificate(): DOMElement
    {
        $signingCert = $this->doc->createElementNS(self::NS_XADES, 'xades:SigningCertificate');

        $cert = $this->doc->createElementNS(self::NS_XADES, 'xades:Cert');

        // CertDigest
        $certDigest = $this->doc->createElementNS(self::NS_XADES, 'xades:CertDigest');

        $digestMethod = $this->doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $certDigest->appendChild($digestMethod);

        $certDer = $this->getCertificateDer();
        $digest = base64_encode(hash('sha256', $certDer, true));
        $digestValue = $this->doc->createElementNS(self::NS_DS, 'ds:DigestValue', $digest);
        $certDigest->appendChild($digestValue);

        $cert->appendChild($certDigest);

        // IssuerSerial
        $issuerSerial = $this->doc->createElementNS(self::NS_XADES, 'xades:IssuerSerial');

        $issuerName = $this->buildIssuerDN();
        $x509IssuerName = $this->doc->createElementNS(self::NS_DS, 'ds:X509IssuerName', $issuerName);
        $issuerSerial->appendChild($x509IssuerName);

        $serial = $this->certInfo['serialNumber'] ?? '0';
        $x509Serial = $this->doc->createElementNS(self::NS_DS, 'ds:X509SerialNumber', $serial);
        $issuerSerial->appendChild($x509Serial);

        $cert->appendChild($issuerSerial);

        $signingCert->appendChild($cert);

        return $signingCert;
    }

    /**
     * Crear xades:SignaturePolicyIdentifier
     */
    protected function createSignaturePolicyIdentifier(): DOMElement
    {
        $policyId = $this->doc->createElementNS(self::NS_XADES, 'xades:SignaturePolicyIdentifier');

        $sigPolicyId = $this->doc->createElementNS(self::NS_XADES, 'xades:SignaturePolicyId');

        // SigPolicyId
        $sigPolicyIdInner = $this->doc->createElementNS(self::NS_XADES, 'xades:SigPolicyId');
        $identifier = $this->doc->createElementNS(
            self::NS_XADES,
            'xades:Identifier',
            config('hacienda.policy_url', self::POLICY_URL)
        );
        $sigPolicyIdInner->appendChild($identifier);
        $sigPolicyId->appendChild($sigPolicyIdInner);

        // SigPolicyHash
        $sigPolicyHash = $this->doc->createElementNS(self::NS_XADES, 'xades:SigPolicyHash');

        $digestMethod = $this->doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $sigPolicyHash->appendChild($digestMethod);

        $digestValue = $this->doc->createElementNS(
            self::NS_DS,
            'ds:DigestValue',
            config('hacienda.policy_hash', self::POLICY_HASH)
        );
        $sigPolicyHash->appendChild($digestValue);

        $sigPolicyId->appendChild($sigPolicyHash);
        $policyId->appendChild($sigPolicyId);

        return $policyId;
    }

    /**
     * Crear xades:SignedDataObjectProperties
     */
    protected function createSignedDataObjectProperties(): DOMElement
    {
        $props = $this->doc->createElementNS(self::NS_XADES, 'xades:SignedDataObjectProperties');

        $dataFormat = $this->doc->createElementNS(self::NS_XADES, 'xades:DataObjectFormat');
        $dataFormat->setAttribute('ObjectReference', '#r-id-1');

        $mimeType = $this->doc->createElementNS(self::NS_XADES, 'xades:MimeType', 'application/octet-stream');
        $dataFormat->appendChild($mimeType);

        $props->appendChild($dataFormat);

        return $props;
    }

    /**
     * Calcular digest del documento (excluyendo Signature)
     */
    protected function calculateDocumentDigest(): string
    {
        // Clonar documento sin firma
        $docClone = clone $this->doc;

        // Canonicalizar
        $c14n = $docClone->documentElement->C14N(true, false);

        return base64_encode(hash('sha256', $c14n, true));
    }

    /**
     * Obtener certificado en formato DER
     */
    protected function getCertificateDer(): string
    {
        $certPem = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"],
            '',
            $this->certificate
        );

        return base64_decode($certPem);
    }

    /**
     * Construir DN del emisor del certificado
     */
    protected function buildIssuerDN(): string
    {
        $issuer = $this->certInfo['issuer'] ?? [];

        $parts = [];
        if (isset($issuer['CN'])) $parts[] = 'CN=' . $issuer['CN'];
        if (isset($issuer['OU'])) $parts[] = 'OU=' . $issuer['OU'];
        if (isset($issuer['O'])) $parts[] = 'O=' . $issuer['O'];
        if (isset($issuer['C'])) $parts[] = 'C=' . $issuer['C'];

        return implode(',', $parts);
    }

    /**
     * Generar UUID
     */
    protected function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
```

---

## Fase 2: XmlComprobanteBuilder

### 2.1 Actualizar constantes

```php
<?php
// Reemplazar en XmlComprobanteBuilder.php

/**
 * Versión del esquema XML
 */
const VERSION_ESQUEMA = '4.4';

/**
 * Namespaces XML v4.4
 */
const NAMESPACE_FACTURA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica';
const NAMESPACE_NOTA_DEBITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica';
const NAMESPACE_NOTA_CREDITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica';
const NAMESPACE_TIQUETE = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico';
const NAMESPACE_FACTURA_COMPRA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra';
const NAMESPACE_FACTURA_EXPORTACION = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaExportacion';
const NAMESPACE_RECIBO_PAGO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboElectronicoPago';
```

### 2.2 Agregar método agregarProveedorSistemas()

```php
/**
 * Agregar proveedor de sistemas (NUEVO v4.4 - OBLIGATORIO)
 */
protected function agregarProveedorSistemas(DOMElement $parent): void
{
    $proveedor = config('hacienda.proveedor_sistemas');
    
    if (empty($proveedor)) {
        throw new \InvalidArgumentException(
            'El campo ProveedorSistemas es obligatorio en v4.4. Configure HACIENDA_PROVEEDOR_SISTEMAS en .env'
        );
    }
    
    $element = $this->doc->createElement('ProveedorSistemas', $proveedor);
    $parent->appendChild($element);
}
```

### 2.3 Renombrar método de código de actividad

```php
/**
 * Agregar código de actividad económica del emisor (RENOMBRADO en v4.4)
 */
protected function agregarCodigoActividadEmisor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
{
    $codigoActividad = $comprobante->metadata['codigo_actividad'] 
        ?? $comprobante->empresa->metadata['codigo_actividad'] 
        ?? null;

    if (empty($codigoActividad)) {
        throw new \InvalidArgumentException(
            'El campo CodigoActividadEmisor es obligatorio. Configure la actividad económica.'
        );
    }

    $element = $this->doc->createElement('CodigoActividadEmisor', $codigoActividad);
    $parent->appendChild($element);
}
```

### 2.4 Actualizar método build()

```php
public function build(ComprobanteElectronicoFe $comprobante): string
{
    $this->tipoComprobante = $comprobante->tipo_documento;
    
    $this->doc = new DOMDocument('1.0', 'UTF-8');
    $this->doc->formatOutput = true;
    $this->doc->preserveWhiteSpace = false;

    $root = $this->crearElementoRaiz();

    // Orden correcto v4.4
    $this->agregarClave($root, $comprobante);
    $this->agregarProveedorSistemas($root);                    // NUEVO v4.4
    $this->agregarCodigoActividadEmisor($root, $comprobante);  // RENOMBRADO
    $this->agregarNumeroConsecutivo($root, $comprobante);
    $this->agregarFechaEmision($root, $comprobante);
    $this->agregarEmisor($root, $comprobante);
    $this->agregarReceptor($root, $comprobante);
    $this->agregarCondicionVenta($root, $comprobante);
    $this->agregarPlazoCredito($root, $comprobante);
    // MedioPago se mueve a ResumenFactura en v4.4
    $this->agregarDetalleServicio($root, $comprobante);
    $this->agregarResumenFactura($root, $comprobante);         // Incluye MedioPago ahora
    $this->agregarInformacionReferencia($root, $comprobante);
    $this->agregarOtros($root, $comprobante);

    $this->doc->appendChild($root);

    return $this->doc->saveXML();
}
```

### 2.5 Actualizar ResumenFactura para v4.4

```php
protected function agregarResumenFactura(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
{
    $resumen = $this->doc->createElement('ResumenFactura');

    // Código tipo moneda
    $this->agregarCodigoMoneda($resumen, $comprobante);

    // Totales de servicios y mercancías
    $this->agregarTotalesServiciosMercancias($resumen, $comprobante);

    // Total venta
    $this->agregarCampoDecimal($resumen, 'TotalVenta', $comprobante->total_venta);
    $this->agregarCampoDecimal($resumen, 'TotalDescuentos', $comprobante->total_descuento);
    $this->agregarCampoDecimal($resumen, 'TotalVentaNeta', $comprobante->total_venta_neta);

    // NUEVO v4.4: TotalDesgloseImpuesto (agrupado por código)
    $this->agregarTotalDesgloseImpuesto($resumen, $comprobante);

    $this->agregarCampoDecimal($resumen, 'TotalImpuesto', $comprobante->total_impuesto);

    // MOVIDO v4.4: MedioPago ahora va dentro de ResumenFactura
    $this->agregarMedioPagoEnResumen($resumen, $comprobante);

    $this->agregarCampoDecimal($resumen, 'TotalComprobante', $comprobante->total);

    $parent->appendChild($resumen);
}

/**
 * NUEVO v4.4: Agregar desglose de impuestos
 */
protected function agregarTotalDesgloseImpuesto(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
{
    // Agrupar impuestos por código y tarifa
    $impuestosAgrupados = [];
    
    foreach ($comprobante->lineas as $linea) {
        if ($linea->impuesto_codigo && $linea->impuesto_monto > 0) {
            $key = $linea->impuesto_codigo . '-' . ($linea->impuesto_tarifa_codigo ?? '08');
            
            if (!isset($impuestosAgrupados[$key])) {
                $impuestosAgrupados[$key] = [
                    'codigo' => $linea->impuesto_codigo,
                    'codigo_tarifa' => $linea->impuesto_tarifa_codigo ?? '08',
                    'total' => 0,
                ];
            }
            
            $impuestosAgrupados[$key]['total'] += $linea->impuesto_monto;
        }
    }

    foreach ($impuestosAgrupados as $imp) {
        $desglose = $this->doc->createElement('TotalDesgloseImpuesto');
        
        $codigo = $this->doc->createElement('Codigo', $imp['codigo']);
        $desglose->appendChild($codigo);
        
        if ($imp['codigo'] === '01') { // Solo para IVA
            $codigoTarifa = $this->doc->createElement('CodigoTarifaIVA', $imp['codigo_tarifa']);
            $desglose->appendChild($codigoTarifa);
        }
        
        $totalMonto = $this->doc->createElement('TotalMontoImpuesto', $this->formatearDecimal($imp['total']));
        $desglose->appendChild($totalMonto);
        
        $parent->appendChild($desglose);
    }
}

/**
 * MOVIDO v4.4: MedioPago dentro de ResumenFactura
 */
protected function agregarMedioPagoEnResumen(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
{
    $medioPago = $this->doc->createElement('MedioPago');
    
    $tipo = $this->doc->createElement('TipoMedioPago', $comprobante->medio_pago);
    $medioPago->appendChild($tipo);
    
    $totalMedio = $this->doc->createElement('TotalMedioPago', $this->formatearDecimal($comprobante->total));
    $medioPago->appendChild($totalMedio);
    
    $parent->appendChild($medioPago);
}
```

### 2.6 Actualizar líneas de detalle

```php
protected function agregarLineaDetalle(DOMElement $parent, $linea, int $numeroLinea): void
{
    $lineaElement = $this->doc->createElement('LineaDetalle');

    // Número de línea
    $numLinea = $this->doc->createElement('NumeroLinea', $numeroLinea);
    $lineaElement->appendChild($numLinea);

    // NUEVO v4.4: CodigoCABYS (obligatorio)
    if ($linea->codigo_cabys) {
        $cabys = $this->doc->createElement('CodigoCABYS', $linea->codigo_cabys);
        $lineaElement->appendChild($cabys);
    }

    // Código comercial (opcional)
    if ($linea->codigo_producto) {
        $this->agregarCodigoComercial($lineaElement, $linea);
    }

    // Cantidad
    $cantidad = $this->doc->createElement('Cantidad', $this->formatearDecimal($linea->cantidad, 3));
    $lineaElement->appendChild($cantidad);

    // Unidad de medida
    $unidad = $this->doc->createElement('UnidadMedida', $linea->unidad_medida ?? 'Unid');
    $lineaElement->appendChild($unidad);

    // Detalle/Descripción
    $detalle = $this->doc->createElement('Detalle', $this->escaparXml($linea->descripcion));
    $lineaElement->appendChild($detalle);

    // Precio unitario
    $precio = $this->doc->createElement('PrecioUnitario', $this->formatearDecimal($linea->precio_unitario));
    $lineaElement->appendChild($precio);

    // Monto total
    $montoTotal = $this->doc->createElement('MontoTotal', $this->formatearDecimal($linea->monto_total));
    $lineaElement->appendChild($montoTotal);

    // Descuento (si aplica)
    if ($linea->descuento > 0) {
        $this->agregarDescuento($lineaElement, $linea);
    }

    // Subtotal
    $subtotal = $this->doc->createElement('SubTotal', $this->formatearDecimal($linea->subtotal));
    $lineaElement->appendChild($subtotal);

    // NUEVO v4.4: BaseImponible (obligatorio cuando hay impuesto)
    if ($linea->impuesto_monto > 0) {
        $baseImponible = $linea->base_imponible ?? $linea->subtotal;
        $base = $this->doc->createElement('BaseImponible', $this->formatearDecimal($baseImponible));
        $lineaElement->appendChild($base);
    }

    // Impuesto (si aplica)
    if ($linea->impuesto_monto > 0) {
        $this->agregarImpuesto($lineaElement, $linea);
    }

    // NUEVO v4.4: ImpuestoAsumidoEmisorFabrica (si aplica)
    if ($linea->impuesto_asumido_emisor_fabrica > 0) {
        $impAsumido = $this->doc->createElement(
            'ImpuestoAsumidoEmisorFabrica',
            $this->formatearDecimal($linea->impuesto_asumido_emisor_fabrica)
        );
        $lineaElement->appendChild($impAsumido);
    }

    // Impuesto neto
    $impuestoNeto = $this->doc->createElement('ImpuestoNeto', $this->formatearDecimal($linea->impuesto_neto ?? $linea->impuesto_monto));
    $lineaElement->appendChild($impuestoNeto);

    // Monto total línea
    $totalLinea = $this->doc->createElement('MontoTotalLinea', $this->formatearDecimal($linea->total_linea));
    $lineaElement->appendChild($totalLinea);

    $parent->appendChild($lineaElement);
}
```

---

## Fase 3: Configuración

### 3.1 Actualizar config/hacienda.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versión del Esquema
    |--------------------------------------------------------------------------
    */
    'version' => env('HACIENDA_VERSION', '4.4'),

    /*
    |--------------------------------------------------------------------------
    | Proveedor de Sistemas (OBLIGATORIO v4.4)
    |--------------------------------------------------------------------------
    | Número de cédula del proveedor del sistema de facturación.
    | Debe estar registrado en la DGT.
    */
    'proveedor_sistemas' => env('HACIENDA_PROVEEDOR_SISTEMAS'),

    /*
    |--------------------------------------------------------------------------
    | Política de Firma XAdES-EPES
    |--------------------------------------------------------------------------
    */
    'policy_url' => env(
        'HACIENDA_POLICY_URL',
        'https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-000-2024.pdf'
    ),
    'policy_hash' => env(
        'HACIENDA_POLICY_HASH',
        'NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ=='
    ),

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    */
    'ambiente' => env('HACIENDA_AMBIENTE', 'sandbox'),
    
    'endpoints' => [
        'sandbox' => [
            'api' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
            'idp' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
        ],
        'produccion' => [
            'api' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1',
            'idp' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'max_requests' => env('HACIENDA_RATE_LIMIT', 60),
        'per_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('HACIENDA_CALLBACK_URL'),
];
```

### 3.2 Variables de entorno (.env)

```env
# Hacienda Costa Rica v4.4
HACIENDA_VERSION=4.4
HACIENDA_AMBIENTE=sandbox
HACIENDA_PROVEEDOR_SISTEMAS=106470958
HACIENDA_POLICY_URL=https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-000-2024.pdf
HACIENDA_POLICY_HASH=NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ==

# API Credentials
HACIENDA_USERNAME=cpf-01-1234-5678@comprobanteselectronicos.go.cr
HACIENDA_PASSWORD=secreto
HACIENDA_CALLBACK_URL=https://mi-api.com/webhooks/hacienda
```

---

## Fase 4: Migraciones

### 4.1 Migración para campos nuevos

```php
<?php
// database/migrations/xxxx_xx_xx_add_v44_fields_to_comprobantes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos en comprobantes
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            $table->string('tipo_transaccion', 2)->nullable()
                ->after('medio_pago')
                ->comment('Código tipo transacción v4.4 (01-13)');
                
            $table->string('iva_cobrado_fabrica', 2)->nullable()
                ->after('tipo_transaccion')
                ->comment('IVA cobrado a nivel fábrica (01,02)');
        });

        // Campos en líneas de detalle
        Schema::table('comprobante_lineas', function (Blueprint $table) {
            $table->string('codigo_cabys', 13)->nullable()
                ->after('codigo_producto')
                ->comment('Código CABYS obligatorio v4.4');
                
            $table->decimal('base_imponible', 18, 5)->nullable()
                ->after('subtotal')
                ->comment('Base imponible para cálculo de impuesto');
                
            $table->decimal('impuesto_asumido_emisor_fabrica', 18, 5)->nullable()
                ->after('impuesto_neto')
                ->comment('IVA asumido a nivel de fábrica');
                
            $table->string('iva_cobrado_fabrica', 2)->nullable()
                ->after('impuesto_asumido_emisor_fabrica')
                ->comment('Código IVA nivel fábrica');
        });

        // Configuración de proveedor de sistemas
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('proveedor_sistemas', 12)->nullable()
                ->after('metadata')
                ->comment('Cédula del proveedor de sistemas');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            $table->dropColumn(['tipo_transaccion', 'iva_cobrado_fabrica']);
        });

        Schema::table('comprobante_lineas', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_cabys',
                'base_imponible',
                'impuesto_asumido_emisor_fabrica',
                'iva_cobrado_fabrica'
            ]);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('proveedor_sistemas');
        });
    }
};
```

---

## Fase 5: Validaciones

### 5.1 FormRequest actualizado

```php
<?php
// app/Http/Requests/ComprobanteRequest.php - Agregar reglas

public function rules(): array
{
    return [
        // ... reglas existentes ...

        // Validaciones v4.4
        'lineas.*.codigo_cabys' => ['required', 'string', 'size:13', 'regex:/^\d{13}$/'],
        'lineas.*.base_imponible' => ['required_with:lineas.*.impuesto_monto', 'numeric', 'min:0'],
        'tipo_transaccion' => ['nullable', 'string', 'in:01,02,03,04,05,06,07,08,09,10,11,12,13'],
        'iva_cobrado_fabrica' => ['nullable', 'string', 'in:01,02'],
        
        // Validar condiciones de venta nuevas
        'condicion_venta' => [
            'required',
            'string',
            'in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,99'
        ],
        
        // Validar tipos de identificación nuevos
        'receptor_tipo_identificacion' => [
            'nullable',
            'string',
            'in:01,02,03,04,05,06'  // 05 y 06 nuevos
        ],
    ];
}

public function messages(): array
{
    return [
        'lineas.*.codigo_cabys.required' => 'El código CABYS es obligatorio en v4.4 para cada línea.',
        'lineas.*.codigo_cabys.size' => 'El código CABYS debe tener exactamente 13 dígitos.',
        'lineas.*.base_imponible.required_with' => 'La base imponible es obligatoria cuando hay impuesto.',
    ];
}
```

---

## 📝 Checklist de Implementación

### Fase 1: Firma Digital
- [ ] Crear clase `XadesEpesSigner`
- [ ] Integrar con `FirmaDigitalService`
- [ ] Agregar SignaturePolicyIdentifier
- [ ] Probar firma contra sandbox

### Fase 2: XML Builder
- [ ] Actualizar VERSION_ESQUEMA a 4.4
- [ ] Actualizar todos los NAMESPACE
- [ ] Agregar `agregarProveedorSistemas()`
- [ ] Renombrar a `agregarCodigoActividadEmisor()`
- [ ] Actualizar orden en `build()`
- [ ] Agregar `TotalDesgloseImpuesto`
- [ ] Mover `MedioPago` a `ResumenFactura`
- [ ] Agregar `CodigoCABYS` en líneas
- [ ] Agregar `BaseImponible` en líneas

### Fase 3: Configuración
- [ ] Actualizar `config/hacienda.php`
- [ ] Agregar variables en `.env.example`
- [ ] Documentar en README

### Fase 4: Base de Datos
- [ ] Crear migración para campos nuevos
- [ ] Ejecutar migración en desarrollo
- [ ] Actualizar modelos Eloquent

### Fase 5: Validación y Testing
- [ ] Actualizar FormRequests
- [ ] Crear tests unitarios
- [ ] Probar con sandbox de Hacienda
- [ ] Validar XMLs generados contra XSD

---

*Documento generado: Enero 2025*
*Para: Ursol-CAST-API*
*Versión objetivo: v4.4 Hacienda CR*
