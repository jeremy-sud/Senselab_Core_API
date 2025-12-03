<?php

namespace App\Services\Hacienda\Xml;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Firmador XAdES-EPES para Comprobantes Electrónicos Hacienda CR v4.4
 *
 * Implementa firma XAdES-EPES según ETSI TS 101 903 v1.3.2+
 * Requerido por Ministerio de Hacienda de Costa Rica.
 * 
 * Características:
 * - Firma XMLDSig con perfil XAdES-EPES (Extended Policy Explicit Signature)
 * - Empaquetado ENVELOPED
 * - Algoritmos: RSA-SHA256, SHA-256
 * - Canonicalización: Exclusive C14N
 * - SignaturePolicyIdentifier obligatorio
 * 
 * @see DGT-R-000-2024 Anexo 2 "Mecanismo de seguridad para la autenticación e integridad"
 */
class XadesEpesSigner
{
    // ========================================================================
    // NAMESPACES
    // ========================================================================
    
    /** @var string Namespace XMLDSig */
    const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    
    /** @var string Namespace XAdES v1.3.2 */
    const NS_XADES = 'http://uri.etsi.org/01903/v1.3.2#';

    // ========================================================================
    // ALGORITMOS
    // ========================================================================
    
    /** @var string Algoritmo de firma RSA-SHA256 */
    const ALGO_RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    
    /** @var string Algoritmo de digest SHA256 */
    const ALGO_SHA256 = 'http://www.w3.org/2001/04/xmlenc#sha256';
    
    /** @var string Algoritmo de canonicalización Exclusive C14N */
    const ALGO_C14N = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    
    /** @var string Transform enveloped signature */
    const ALGO_ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    
    /** @var string Transform XPath */
    const ALGO_XPATH = 'http://www.w3.org/TR/1999/REC-xpath-19991116';

    // ========================================================================
    // POLÍTICA DE FIRMA v4.4
    // ========================================================================
    
    /** @var string URL de la política de firma v4.4 (se actualizará cuando Hacienda publique URL final) */
    const DEFAULT_POLICY_URL = 'https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-000-2024.pdf';
    
    /** @var string Hash de la política de firma v4.4 (base64) */
    const DEFAULT_POLICY_HASH = 'NmI5Njk1ZThkNzI0MmIzMGJmZDAyNDc4YjUwNzkzODM2NTBiOWUxNTBkMmI2YjgzYzZjM2I5NTZlNDQ4OWQzMQ==';

    // ========================================================================
    // PROPIEDADES
    // ========================================================================
    
    /** @var DOMDocument Documento XML a firmar */
    protected DOMDocument $doc;
    
    /** @var string Clave privada en formato PEM */
    protected string $privateKey;
    
    /** @var string Certificado X.509 en formato PEM */
    protected string $certificate;
    
    /** @var array Información parseada del certificado */
    protected array $certInfo;
    
    /** @var string ID único de la firma */
    protected string $signatureId;
    
    /** @var string URL de la política de firma */
    protected string $policyUrl;
    
    /** @var string Hash de la política de firma */
    protected string $policyHash;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->policyUrl = config('hacienda.xades.policy_url', self::DEFAULT_POLICY_URL);
        $this->policyHash = config('hacienda.xades.policy_hash', self::DEFAULT_POLICY_HASH);
    }

    /**
     * Firmar documento XML con XAdES-EPES
     *
     * @param string $xml XML sin firmar
     * @param string $privateKey Clave privada en formato PEM
     * @param string $certificate Certificado X.509 en formato PEM
     * @return string XML firmado
     * @throws \Exception
     */
    public function sign(string $xml, string $privateKey, string $certificate): string
    {
        $this->privateKey = $privateKey;
        $this->certificate = $certificate;
        
        // Parsear información del certificado
        $this->certInfo = openssl_x509_parse($certificate);
        if ($this->certInfo === false) {
            throw new \Exception('No se pudo parsear el certificado X.509');
        }
        
        // Generar ID único para la firma
        $this->signatureId = 'id-' . $this->generateUuid();

        // Cargar documento XML
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = true;
        $this->doc->formatOutput = false;
        
        if (!$this->doc->loadXML($xml)) {
            throw new \Exception('No se pudo cargar el documento XML');
        }

        // Crear y agregar elemento de firma
        $signatureElement = $this->createSignatureElement();

        // Insertar firma al final del elemento raíz
        $root = $this->doc->documentElement;
        $root->appendChild($signatureElement);

        $xmlFirmado = $this->doc->saveXML();

        Log::debug('XAdES-EPES: XML firmado exitosamente', [
            'signature_id' => $this->signatureId,
            'cert_subject' => $this->certInfo['subject']['CN'] ?? 'N/A',
            'xml_length' => strlen($xmlFirmado),
        ]);

        return $xmlFirmado;
    }

    /**
     * Crear elemento ds:Signature completo con XAdES
     */
    protected function createSignatureElement(): DOMElement
    {
        // Crear elemento Signature con namespace
        $signature = $this->doc->createElementNS(self::NS_DS, 'ds:Signature');
        $signature->setAttribute('Id', $this->signatureId);
        
        // Asegurar que el namespace xades está declarado
        $signature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xades', self::NS_XADES);

        // 1. Crear SignedInfo (sin firmar aún)
        $signedInfo = $this->createSignedInfo();
        $signature->appendChild($signedInfo);

        // 2. Calcular y agregar SignatureValue
        $signatureValue = $this->calculateSignatureValue($signedInfo);
        $signature->appendChild($signatureValue);

        // 3. Agregar KeyInfo
        $keyInfo = $this->createKeyInfo();
        $signature->appendChild($keyInfo);

        // 4. Agregar Object con QualifyingProperties (XAdES)
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
        $c14nMethod = $this->doc->createElementNS(self::NS_DS, 'ds:CanonicalizationMethod');
        $c14nMethod->setAttribute('Algorithm', self::ALGO_C14N);
        $signedInfo->appendChild($c14nMethod);

        // SignatureMethod
        $sigMethod = $this->doc->createElementNS(self::NS_DS, 'ds:SignatureMethod');
        $sigMethod->setAttribute('Algorithm', self::ALGO_RSA_SHA256);
        $signedInfo->appendChild($sigMethod);

        // Reference al documento (con transform enveloped + XPath)
        $refDoc = $this->createDocumentReference();
        $signedInfo->appendChild($refDoc);

        // Reference a SignedProperties (XAdES)
        $refProps = $this->createSignedPropertiesReference();
        $signedInfo->appendChild($refProps);

        return $signedInfo;
    }

    /**
     * Crear referencia al documento principal
     */
    protected function createDocumentReference(): DOMElement
    {
        $reference = $this->doc->createElementNS(self::NS_DS, 'ds:Reference');
        $reference->setAttribute('Id', 'r-id-1');
        $reference->setAttribute('URI', '');

        // Transforms
        $transforms = $this->doc->createElementNS(self::NS_DS, 'ds:Transforms');

        // Transform 1: XPath para excluir el nodo Signature
        $transformXpath = $this->doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transformXpath->setAttribute('Algorithm', self::ALGO_XPATH);
        $xpath = $this->doc->createElementNS(self::NS_DS, 'ds:XPath', 'not(ancestor-or-self::ds:Signature)');
        $transformXpath->appendChild($xpath);
        $transforms->appendChild($transformXpath);

        // Transform 2: Canonicalización C14N
        $transformC14n = $this->doc->createElementNS(self::NS_DS, 'ds:Transform');
        $transformC14n->setAttribute('Algorithm', self::ALGO_C14N);
        $transforms->appendChild($transformC14n);

        $reference->appendChild($transforms);

        // DigestMethod
        $digestMethod = $this->doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $reference->appendChild($digestMethod);

        // DigestValue - calcular digest del documento
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

        // DigestValue - Se calculará después de crear SignedProperties
        // Por ahora ponemos un placeholder que se actualizará
        $digestValue = $this->doc->createElementNS(self::NS_DS, 'ds:DigestValue', '');
        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Calcular y crear ds:SignatureValue
     */
    protected function calculateSignatureValue(DOMElement $signedInfo): DOMElement
    {
        // Primero necesitamos actualizar el digest de SignedProperties
        $this->updateSignedPropertiesDigest($signedInfo);
        
        // Canonicalizar SignedInfo
        $c14n = $this->canonicalizeNode($signedInfo);
        
        // Firmar con clave privada
        $signature = '';
        $result = openssl_sign($c14n, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        
        if (!$result) {
            throw new \Exception('Error al firmar: ' . openssl_error_string());
        }

        $signatureValue = $this->doc->createElementNS(
            self::NS_DS,
            'ds:SignatureValue',
            base64_encode($signature)
        );
        $signatureValue->setAttribute('Id', 'value-' . $this->signatureId);

        return $signatureValue;
    }

    /**
     * Actualizar el digest de SignedProperties en SignedInfo
     */
    protected function updateSignedPropertiesDigest(DOMElement $signedInfo): void
    {
        // Crear temporalmente el nodo SignedProperties para calcular su digest
        $signedProperties = $this->createSignedProperties();
        
        // Canonicalizar SignedProperties
        $tempDoc = new DOMDocument('1.0', 'UTF-8');
        $importedNode = $tempDoc->importNode($signedProperties, true);
        $tempDoc->appendChild($importedNode);
        $c14n = $tempDoc->documentElement->C14N(true, false);
        
        // Calcular digest
        $digest = base64_encode(hash('sha256', $c14n, true));
        
        // Buscar y actualizar el DigestValue de SignedProperties en SignedInfo
        $xpath = new DOMXPath($this->doc);
        $xpath->registerNamespace('ds', self::NS_DS);
        
        // Buscar el Reference que apunta a SignedProperties
        $nodes = $signedInfo->getElementsByTagNameNS(self::NS_DS, 'Reference');
        foreach ($nodes as $ref) {
            if (strpos($ref->getAttribute('URI'), 'xades-') !== false) {
                $digestValueNodes = $ref->getElementsByTagNameNS(self::NS_DS, 'DigestValue');
                if ($digestValueNodes->length > 0) {
                    $digestValueNodes->item(0)->nodeValue = $digest;
                }
                break;
            }
        }
    }

    /**
     * Crear ds:KeyInfo
     */
    protected function createKeyInfo(): DOMElement
    {
        $keyInfo = $this->doc->createElementNS(self::NS_DS, 'ds:KeyInfo');

        $x509Data = $this->doc->createElementNS(self::NS_DS, 'ds:X509Data');

        // Limpiar certificado (quitar headers PEM y saltos de línea)
        $certClean = $this->cleanCertificate($this->certificate);

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

        // SigningTime (ISO 8601)
        $signingTime = $this->doc->createElementNS(
            self::NS_XADES,
            'xades:SigningTime',
            Carbon::now('America/Costa_Rica')->toIso8601String()
        );
        $props->appendChild($signingTime);

        // SigningCertificate
        $signingCert = $this->createSigningCertificate();
        $props->appendChild($signingCert);

        // SignaturePolicyIdentifier (OBLIGATORIO para XAdES-EPES)
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

        // Calcular digest del certificado DER
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

        $serialNumber = $this->certInfo['serialNumber'] ?? '0';
        // Convertir a decimal si es necesario
        if (is_string($serialNumber) && ctype_xdigit($serialNumber)) {
            $serialNumber = hexdec($serialNumber);
        }
        $x509Serial = $this->doc->createElementNS(self::NS_DS, 'ds:X509SerialNumber', (string)$serialNumber);
        $issuerSerial->appendChild($x509Serial);

        $cert->appendChild($issuerSerial);

        $signingCert->appendChild($cert);

        return $signingCert;
    }

    /**
     * Crear xades:SignaturePolicyIdentifier
     * 
     * Este nodo es OBLIGATORIO para XAdES-EPES según Hacienda CR
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
            $this->policyUrl
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
            $this->policyHash
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
        // Clonar documento
        $docClone = clone $this->doc;
        
        // Remover cualquier nodo Signature existente
        $xpath = new DOMXPath($docClone);
        $xpath->registerNamespace('ds', self::NS_DS);
        $signatures = $xpath->query('//ds:Signature');
        foreach ($signatures as $sig) {
            $sig->parentNode->removeChild($sig);
        }

        // Canonicalizar el documento completo
        $c14n = $docClone->documentElement->C14N(true, false);

        return base64_encode(hash('sha256', $c14n, true));
    }

    /**
     * Canonicalizar un nodo DOM
     */
    protected function canonicalizeNode(DOMElement $node): string
    {
        // Crear documento temporal para canonicalización correcta
        $tempDoc = new DOMDocument('1.0', 'UTF-8');
        $importedNode = $tempDoc->importNode($node, true);
        $tempDoc->appendChild($importedNode);
        
        return $tempDoc->documentElement->C14N(true, false);
    }

    /**
     * Obtener certificado en formato DER (binario)
     */
    protected function getCertificateDer(): string
    {
        $certClean = $this->cleanCertificate($this->certificate);
        return base64_decode($certClean);
    }

    /**
     * Limpiar certificado (quitar headers PEM y saltos de línea)
     */
    protected function cleanCertificate(string $cert): string
    {
        return str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r", ' '],
            '',
            $cert
        );
    }

    /**
     * Construir DN (Distinguished Name) del emisor del certificado
     */
    protected function buildIssuerDN(): string
    {
        $issuer = $this->certInfo['issuer'] ?? [];

        $parts = [];
        
        // Orden estándar RFC 2253
        if (isset($issuer['CN'])) {
            $parts[] = 'CN=' . $this->escapeRdn($issuer['CN']);
        }
        if (isset($issuer['OU'])) {
            $ou = is_array($issuer['OU']) ? $issuer['OU'][0] : $issuer['OU'];
            $parts[] = 'OU=' . $this->escapeRdn($ou);
        }
        if (isset($issuer['O'])) {
            $parts[] = 'O=' . $this->escapeRdn($issuer['O']);
        }
        if (isset($issuer['C'])) {
            $parts[] = 'C=' . $this->escapeRdn($issuer['C']);
        }
        if (isset($issuer['serialNumber'])) {
            $parts[] = '2.5.4.5=#' . bin2hex($issuer['serialNumber']);
        }

        return implode(',', $parts);
    }

    /**
     * Escapar valor RDN según RFC 2253
     */
    protected function escapeRdn(string $value): string
    {
        // Caracteres que necesitan escape: , = + < > # ; \ "
        $chars = [',', '=', '+', '<', '>', '#', ';', '\\', '"'];
        $escaped = [];
        
        foreach ($chars as $char) {
            $escaped[] = '\\' . $char;
        }
        
        return str_replace($chars, $escaped, $value);
    }

    /**
     * Generar UUID v4
     */
    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
        
        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Establecer URL de política de firma personalizada
     */
    public function setPolicyUrl(string $url): self
    {
        $this->policyUrl = $url;
        return $this;
    }

    /**
     * Establecer hash de política de firma personalizado
     */
    public function setPolicyHash(string $hash): self
    {
        $this->policyHash = $hash;
        return $this;
    }
}
