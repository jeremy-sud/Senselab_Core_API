<?php

namespace Tests\Unit\Services;

use App\Services\Hacienda\Xml\XadesEpesSigner;
use Tests\TestCase;

/**
 * Tests para XadesEpesSigner
 *
 * Valida:
 * - Generación de firma XAdES-EPES correcta
 * - Estructura de QualifyingProperties
 * - SignaturePolicyIdentifier obligatorio
 * - SigningCertificate
 * - Algoritmos correctos (RSA-SHA256, SHA256)
 *
 * @see DGT-R-000-2024 Anexo 2 "Mecanismo de seguridad"
 */
class XadesEpesSignerTest extends TestCase
{
    protected XadesEpesSigner $signer;

    /**
     * Certificado de prueba autofirmado para tests
     */
    protected string $testPrivateKey;
    protected string $testCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new XadesEpesSigner();

        // Generar certificado autofirmado para pruebas
        $this->generateTestCertificate();
    }

    /**
     * Genera un certificado autofirmado para pruebas
     */
    protected function generateTestCertificate(): void
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Generar par de claves
        $privateKey = openssl_pkey_new($config);

        // Crear CSR
        $dn = [
            'countryName' => 'CR',
            'stateOrProvinceName' => 'San Jose',
            'localityName' => 'San Jose',
            'organizationName' => 'Test Company S.A.',
            'commonName' => 'Test Certificate',
            'emailAddress' => 'test@test.com',
        ];

        $csr = openssl_csr_new($dn, $privateKey, $config);

        // Autofirmar el certificado (válido por 365 días)
        $x509 = openssl_csr_sign($csr, null, $privateKey, 365, $config);

        // Exportar a PEM
        openssl_pkey_export($privateKey, $this->testPrivateKey);
        openssl_x509_export($x509, $this->testCertificate);
    }

    /** @test */
    public function firma_xml_correctamente()
    {
        $xml = $this->getSampleXml();

        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar que el XML firmado no está vacío
        $this->assertNotEmpty($xmlFirmado);

        // Verificar que es XML válido
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($xmlFirmado);
        $this->assertTrue($loaded, 'El XML firmado no es válido');
    }

    /** @test */
    public function incluye_elemento_signature()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar presencia de ds:Signature
        $this->assertStringContainsString('<ds:Signature', $xmlFirmado);
        $this->assertStringContainsString('</ds:Signature>', $xmlFirmado);
    }

    /** @test */
    public function incluye_signed_info()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar ds:SignedInfo
        $this->assertStringContainsString('<ds:SignedInfo>', $xmlFirmado);

        // Verificar método de canonicalización
        $this->assertStringContainsString('http://www.w3.org/2001/10/xml-exc-c14n#', $xmlFirmado);

        // Verificar algoritmo de firma RSA-SHA256
        $this->assertStringContainsString('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', $xmlFirmado);
    }

    /** @test */
    public function incluye_signature_value()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar ds:SignatureValue
        $this->assertStringContainsString('<ds:SignatureValue>', $xmlFirmado);
        $this->assertStringContainsString('</ds:SignatureValue>', $xmlFirmado);
    }

    /** @test */
    public function incluye_key_info_con_certificado()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar ds:KeyInfo
        $this->assertStringContainsString('<ds:KeyInfo>', $xmlFirmado);
        $this->assertStringContainsString('<ds:X509Data>', $xmlFirmado);
        $this->assertStringContainsString('<ds:X509Certificate>', $xmlFirmado);
    }

    /** @test */
    public function incluye_qualifying_properties_xades()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:QualifyingProperties
        $this->assertStringContainsString('<xades:QualifyingProperties', $xmlFirmado);
        $this->assertStringContainsString('</xades:QualifyingProperties>', $xmlFirmado);
    }

    /** @test */
    public function incluye_signed_properties()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:SignedProperties
        $this->assertStringContainsString('<xades:SignedProperties', $xmlFirmado);

        // Verificar xades:SignedSignatureProperties
        $this->assertStringContainsString('<xades:SignedSignatureProperties>', $xmlFirmado);
    }

    /** @test */
    public function incluye_signing_time()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:SigningTime
        $this->assertStringContainsString('<xades:SigningTime>', $xmlFirmado);
    }

    /** @test */
    public function incluye_signing_certificate()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:SigningCertificate
        $this->assertStringContainsString('<xades:SigningCertificate>', $xmlFirmado);
        $this->assertStringContainsString('<xades:Cert>', $xmlFirmado);
        $this->assertStringContainsString('<xades:CertDigest>', $xmlFirmado);
        $this->assertStringContainsString('<xades:IssuerSerial>', $xmlFirmado);
    }

    /** @test */
    public function incluye_signature_policy_identifier()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:SignaturePolicyIdentifier (obligatorio para XAdES-EPES)
        $this->assertStringContainsString('<xades:SignaturePolicyIdentifier>', $xmlFirmado);
        $this->assertStringContainsString('<xades:SignaturePolicyId>', $xmlFirmado);
        $this->assertStringContainsString('<xades:SigPolicyId>', $xmlFirmado);
        $this->assertStringContainsString('<xades:Identifier>', $xmlFirmado);
        $this->assertStringContainsString('<xades:SigPolicyHash>', $xmlFirmado);
    }

    /** @test */
    public function incluye_data_object_format()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar xades:SignedDataObjectProperties
        $this->assertStringContainsString('<xades:SignedDataObjectProperties>', $xmlFirmado);
        $this->assertStringContainsString('<xades:DataObjectFormat', $xmlFirmado);
        $this->assertStringContainsString('<xades:MimeType>', $xmlFirmado);
        $this->assertStringContainsString('text/xml', $xmlFirmado);
    }

    /** @test */
    public function usa_namespaces_correctos()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar namespace XMLDSig
        $this->assertStringContainsString('http://www.w3.org/2000/09/xmldsig#', $xmlFirmado);

        // Verificar namespace XAdES v1.3.2
        $this->assertStringContainsString('http://uri.etsi.org/01903/v1.3.2#', $xmlFirmado);
    }

    /** @test */
    public function incluye_referencia_al_documento()
    {
        $xml = $this->getSampleXml();
        $xmlFirmado = $this->signer->sign($xml, $this->testPrivateKey, $this->testCertificate);

        // Verificar ds:Reference al documento
        $this->assertStringContainsString('<ds:Reference', $xmlFirmado);
        $this->assertStringContainsString('<ds:DigestMethod', $xmlFirmado);
        $this->assertStringContainsString('<ds:DigestValue>', $xmlFirmado);

        // Verificar transform enveloped-signature
        $this->assertStringContainsString('http://www.w3.org/2000/09/xmldsig#enveloped-signature', $xmlFirmado);
    }

    /** @test */
    public function lanza_excepcion_con_certificado_invalido()
    {
        $this->expectException(\Exception::class);

        $xml = $this->getSampleXml();
        $this->signer->sign($xml, 'invalid-key', 'invalid-cert');
    }

    /** @test */
    public function lanza_excepcion_con_xml_invalido()
    {
        $this->expectException(\Exception::class);

        $this->signer->sign('not valid xml <>', $this->testPrivateKey, $this->testCertificate);
    }

    /**
     * Obtener XML de muestra para pruebas (simula factura v4.4)
     */
    protected function getSampleXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica">
    <Clave>50601122531012345678901000100010000000010112345678</Clave>
    <CodigoActividadEmisor>620100</CodigoActividadEmisor>
    <NumeroConsecutivo>00100001010000000001</NumeroConsecutivo>
    <FechaEmision>2025-12-02T10:30:00-06:00</FechaEmision>
    <ProveedorSistemas>SISTEMA ERP TEST</ProveedorSistemas>
    <Emisor>
        <Nombre>Empresa Test S.A.</Nombre>
        <Identificacion>
            <Tipo>02</Tipo>
            <Numero>3101234567</Numero>
        </Identificacion>
    </Emisor>
    <Receptor>
        <Nombre>Cliente Test</Nombre>
        <Identificacion>
            <Tipo>01</Tipo>
            <Numero>109876543</Numero>
        </Identificacion>
    </Receptor>
    <CondicionVenta>01</CondicionVenta>
    <MedioPago>01</MedioPago>
    <DetalleServicio>
        <LineaDetalle>
            <NumeroLinea>1</NumeroLinea>
            <Cantidad>1.00000</Cantidad>
            <UnidadMedida>Unid</UnidadMedida>
            <Detalle>Producto de prueba</Detalle>
            <PrecioUnitario>10000.00000</PrecioUnitario>
            <MontoTotal>10000.00000</MontoTotal>
            <SubTotal>10000.00000</SubTotal>
            <MontoTotalLinea>10000.00000</MontoTotalLinea>
        </LineaDetalle>
    </DetalleServicio>
    <ResumenFactura>
        <CodigoTipoMoneda>
            <CodigoMoneda>CRC</CodigoMoneda>
            <TipoCambio>1.00000</TipoCambio>
        </CodigoTipoMoneda>
        <TotalGravado>0.00000</TotalGravado>
        <TotalExento>10000.00000</TotalExento>
        <TotalVenta>10000.00000</TotalVenta>
        <TotalVentaNeta>10000.00000</TotalVentaNeta>
        <TotalImpuesto>0.00000</TotalImpuesto>
        <TotalComprobante>10000.00000</TotalComprobante>
    </ResumenFactura>
</FacturaElectronica>';
    }
}
