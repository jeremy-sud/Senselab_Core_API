<?php

namespace App\Services\Hacienda\Xml;

use App\Exceptions\HaciendaException;
use App\Models\FeCertificadoDigital;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Servicio de Firma Digital XAdES-EPES para Comprobantes Electrónicos
 *
 * Implementa firma digital XML según estándar XAdES-EPES requerido por Hacienda CR v4.4.
 * Usa certificados digitales en formato .p12 (PKCS#12).
 *
 * Esta clase actúa como fachada para el firmador XAdES-EPES, proporcionando
 * integración con el sistema de certificados de la base de datos.
 *
 * @see XadesEpesSigner Para la implementación de firma XAdES-EPES
 * @see DGT-R-000-2024 Anexo 2 - Mecanismo de seguridad
 */
class FirmaDigitalService
{
    /**
     * Algoritmos de firma y hash
     */
    const SIGNATURE_ALGORITHM = XMLSecurityKey::RSA_SHA256;
    const DIGEST_ALGORITHM = XMLSecurityDSig::SHA256;

    /**
     * Certificado digital a utilizar
     */
    protected ?FeCertificadoDigital $certificado = null;

    /**
     * Datos del certificado parseado
     */
    /** @var array<string, mixed>|null */
    /** @var array<string, mixed>|null */
    protected ?array $certData = null;

    /**
     * Clave privada extraída
     */
    protected \OpenSSLAsymmetricKey|false|null $privateKey = null;

    /**
     * Instancia del firmador XAdES-EPES
     */
    protected ?XadesEpesSigner $xadesSigner = null;

    /**
     * Firmar XML de comprobante electrónico usando XAdES-EPES
     *
     * Implementa la firma según el Anexo 2 de DGT-R-000-2024:
     * - Firma XAdES-EPES (Extended-Electronic Signature con Policy)
     * - SignaturePolicyIdentifier obligatorio
     * - QualifyingProperties con SigningCertificate, DataObjectFormat
     *
     * @param string $xmlString XML sin firmar (versión 4.4)
     * @param int $certificadoId ID del certificado a usar
     * @return string XML firmado con XAdES-EPES
     * @throws \Exception
     */
    public function firmar(string $xmlString, int $certificadoId): string
    {
        // Cargar y validar certificado
        $this->cargarCertificado($certificadoId);

        // Obtener URL de política desde configuración
        $policyUrl = config('hacienda.policy_url', 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.4/ResolucionComprobantesElectronicosDGT-R-48-2016_v4.4.pdf');

        // Inicializar firmador XAdES-EPES
        $this->xadesSigner = new XadesEpesSigner();

        if (!$this->certData || !isset($this->certData['cert'])) {
            throw HaciendaException::pkcs12Error();
        }

        $xmlFirmado = $this->xadesSigner->sign($xmlString, $this->privateKey, $this->certData['cert']);

        Log::info('XML firmado con XAdES-EPES exitosamente', [
            'certificado_id' => $certificadoId,
            'certificado_sujeto' => $this->certificado->sujeto,
            'policy_url' => $policyUrl,
            'xml_length' => strlen($xmlFirmado),
        ]);

        return $xmlFirmado;
    }

    /**
     * Firmar XML usando método legacy (XMLDSig básico)
     *
     * DEPRECATED: Usar firmar() para XAdES-EPES completo
     * Mantenido para compatibilidad con XML v4.3 existentes
     *
     * @deprecated Use firmar() en su lugar
     * @param string $xmlString XML sin firmar
     * @param int $certificadoId ID del certificado a usar
     * @return string XML firmado (sin XAdES-EPES completo)
     * @throws \Exception
     */
    public function firmarLegacy(string $xmlString, int $certificadoId): string
    {
        // Cargar y validar certificado
        $this->cargarCertificado($certificadoId);

        // Parsear XML
        $doc = new DOMDocument();
        $doc->loadXML($xmlString);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        // Crear objeto de firma
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        // Agregar referencia al documento completo
        $objDSig->addReference(
            $doc,
            self::DIGEST_ALGORITHM,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        // Crear nueva clave de firma
        $objKey = new XMLSecurityKey(self::SIGNATURE_ALGORITHM, ['type' => 'private']);
        
        // Cargar clave privada desde el certificado
        $objKey->loadKey($this->privateKey);

        // Firmar el documento
        $objDSig->sign($objKey);

        if (!$this->certData || !isset($this->certData['cert'])) {
            throw HaciendaException::pkcs12Error();
        }

        // Agregar certificado a la firma
        $objDSig->add509Cert($this->certData['cert']);

        // Insertar la firma en el documento
        $rootNode = $doc->documentElement;
        $objDSig->appendSignature($rootNode);

        // Obtener XML firmado
        $xmlFirmado = $doc->saveXML();
        if ($xmlFirmado === false) {
            throw HaciendaException::xmlGeneracionError();
        }

        Log::info('XML firmado con método legacy (sin XAdES-EPES completo)', [
            'certificado_id' => $certificadoId,
            'xml_length' => strlen($xmlFirmado),
        ]);

        return $xmlFirmado;
    }

    /**
     * Cargar y validar certificado digital
     *
     * @param int $certificadoId ID del certificado
     * @throws \Exception
     */
    protected function cargarCertificado(int $certificadoId): void
    {
        $this->certificado = FeCertificadoDigital::find($certificadoId);

        if (!$this->certificado) {
            throw HaciendaException::certificadoNoEncontrado($certificadoId);
        }

        if (!$this->certificado->activo) {
            throw HaciendaException::certificadoInactivo();
        }

        if ($this->certificado->vencido) {
            throw HaciendaException::certificadoVencido(
                $this->certificado->fecha_vencimiento->format('d/m/Y')
            );
        }

        if ($this->certificado->proximo_vencer) {
            Log::warning('Certificado digital próximo a vencer', [
                'certificado_id' => $certificadoId,
                'fecha_vencimiento' => $this->certificado->fecha_vencimiento->format('d/m/Y'),
                'dias_restantes' => $this->certificado->dias_restantes,
            ]);
        }

        // Verificar que el archivo existe
        if (!Storage::exists($this->certificado->ruta_archivo)) {
            throw HaciendaException::certificadoArchivoNoEncontrado(
                $this->certificado->ruta_archivo
            );
        }

        // Leer certificado
        $this->leerCertificadoP12();
    }

    /**
     * Leer y parsear certificado .p12
     *
     * @throws \Exception
     */
    protected function leerCertificadoP12(): void
    {
        $rutaCompleta = Storage::path($this->certificado->ruta_archivo);
        
        if (!file_exists($rutaCompleta)) {
            throw HaciendaException::certificadoArchivoNoEncontrado($rutaCompleta);
        }

        // Leer contenido del certificado
        $p12Content = file_get_contents($rutaCompleta);

        if ($p12Content === false) {
            throw HaciendaException::certificadoLecturaError();
        }

        // Desencriptar password del certificado
        $password = $this->desencriptarPassword();

        // Parsear certificado PKCS#12
        $certs = [];
        $success = openssl_pkcs12_read($p12Content, $certs, $password);

        if (!$success) {
            throw HaciendaException::certificadoPasswordError(
                'Error al leer el certificado .p12. Verifica que la contraseña sea correcta. ' .
                'Error OpenSSL: ' . openssl_error_string()
            );
        }

        if (!isset($certs['pkey']) || !isset($certs['cert'])) {
            throw HaciendaException::certificadoParseError(
                'El certificado .p12 no contiene la clave privada o el certificado público'
            );
        }

        // Guardar datos del certificado
        $this->certData = $certs;
        $this->privateKey = $certs['pkey'];

        // Extraer y validar información del certificado
        $this->validarInformacionCertificado($certs['cert']);

        Log::debug('Certificado .p12 cargado correctamente', [
            'certificado_id' => $this->certificado->id,
            'tiene_clave_privada' => isset($certs['pkey']),
            'tiene_certificado' => isset($certs['cert']),
            'tiene_cadena' => isset($certs['extracerts']),
        ]);
    }

    /**
     * Validar información del certificado X.509
     *
     * @param string $certPem Certificado en formato PEM
     * @throws \Exception
     */
    protected function validarInformacionCertificado(string $certPem): void
    {
        $certData = openssl_x509_parse($certPem);

        if ($certData === false) {
            throw HaciendaException::certificadoParseError('Error al parsear el certificado X.509');
        }

        // Verificar fechas de validez
        $validFrom = Carbon::createFromTimestamp($certData['validFrom_time_t']);
        $validTo = Carbon::createFromTimestamp($certData['validTo_time_t']);
        $now = Carbon::now();

        if ($now->isBefore($validFrom)) {
            throw HaciendaException::certificadoParseError(
                'El certificado aún no es válido. Será válido desde: ' . $validFrom->format('d/m/Y H:i:s')
            );
        }

        if ($now->isAfter($validTo)) {
            throw HaciendaException::certificadoVencido($validTo->format('d/m/Y H:i:s'));
        }

        // Actualizar información en BD si es necesario
        $actualizaciones = [];

        if (!$this->certificado->numero_serie || $this->certificado->numero_serie !== $certData['serialNumber']) {
            $actualizaciones['numero_serie'] = $certData['serialNumber'];
        }

        if (!$this->certificado->emisor || $this->certificado->emisor !== ($certData['issuer']['CN'] ?? '')) {
            $actualizaciones['emisor'] = $certData['issuer']['CN'] ?? $certData['issuer']['O'] ?? 'Desconocido';
        }

        if (!$this->certificado->sujeto || $this->certificado->sujeto !== ($certData['subject']['CN'] ?? '')) {
            $actualizaciones['sujeto'] = $certData['subject']['CN'] ?? $certData['subject']['O'] ?? 'Desconocido';
        }

        if (!$this->certificado->fecha_emision || $this->certificado->fecha_emision->ne($validFrom)) {
            $actualizaciones['fecha_emision'] = $validFrom;
        }

        if (!$this->certificado->fecha_vencimiento || $this->certificado->fecha_vencimiento->ne($validTo)) {
            $actualizaciones['fecha_vencimiento'] = $validTo;
        }

        if (!empty($actualizaciones)) {
            $this->certificado->update($actualizaciones);
            
            Log::info('Información del certificado actualizada en BD', [
                'certificado_id' => $this->certificado->id,
                'campos_actualizados' => array_keys($actualizaciones),
            ]);
        }

        Log::debug('Certificado validado', [
            'numero_serie' => $certData['serialNumber'],
            'emisor' => $actualizaciones['emisor'] ?? $this->certificado->emisor,
            'sujeto' => $actualizaciones['sujeto'] ?? $this->certificado->sujeto,
            'valido_desde' => $validFrom->format('d/m/Y'),
            'valido_hasta' => $validTo->format('d/m/Y'),
            'dias_restantes' => $now->diffInDays($validTo, false),
        ]);
    }

    /**
     * Desencriptar password del certificado
     *
     * @return string Password desencriptado
     * @throws \Exception
     */
    protected function desencriptarPassword(): string
    {
        if (!$this->certificado->password_encrypted) {
            throw HaciendaException::certificadoPasswordError(
                'El certificado no tiene contraseña configurada'
            );
        }

        // Por ahora usamos base64, pero deberías usar encriptación real (Laravel Crypt)
        // $password = Crypt::decryptString($this->certificado->password_encrypted);
        
        // Temporal: asumimos que está en base64
        $password = base64_decode($this->certificado->password_encrypted);

        if ($password === false) {
            throw HaciendaException::certificadoPasswordError(
                'Error al desencriptar la contraseña del certificado'
            );
        }

        return $password;
    }

    /**
     * Verificar firma de un XML firmado
     *
     * @param string $xmlFirmado XML con firma digital
     * @return bool True si la firma es válida
     * @throws \Exception
     */
    public function verificarFirma(string $xmlFirmado): bool
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlFirmado);

        // Buscar nodo de firma
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        
        $signatureNodes = $xpath->query('//ds:Signature');

        if ($signatureNodes === false || $signatureNodes->length === 0) {
            throw HaciendaException::xmlSinFirma();
        }

        $signatureNode = $signatureNodes->item(0);
        if (!$signatureNode instanceof \DOMElement) {
            throw HaciendaException::xmlFirmaInvalida();
        }

        // Crear objeto de verificación
        $objDSig = new XMLSecurityDSig();
        $objDSig->idKeys = ['ID'];
        $objDSig->idNS = [];
        
        $objDSig->sigNode = $signatureNode;
        $objDSig->canonicalizeSignedInfo();

        // Obtener clave pública del certificado en la firma
        $objKey = $objDSig->locateKey();
        
        if (!$objKey) {
            throw HaciendaException::xmlClavePublicaNoEncontrada();
        }

        if (!$this->certData || !isset($this->certData['cert'])) {
            throw HaciendaException::pkcs12Error();
        }

        $objKey->loadKey($this->certData['cert'], false, true);

        // Verificar firma
        $valido = $objDSig->verify($objKey);

        Log::info('Verificación de firma XML', [
            'valido' => $valido,
        ]);

        return (bool) $valido;
    }

    /**
     * Extraer certificado de un XML firmado
     *
     * @param string $xmlFirmado XML con firma
     * @return array<string, mixed>|null Datos del certificado
     */
    public function extraerCertificado(string $xmlFirmado): ?array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlFirmado);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        
        $certNodes = $xpath->query('//ds:X509Certificate');

        if ($certNodes === false || $certNodes->length === 0) {
            return null;
        }

        $certNode = $certNodes->item(0);
        if ($certNode === null) {
            return null;
        }
        $certBase64 = $certNode->nodeValue;
        $certPem = "-----BEGIN CERTIFICATE-----\n" .
                   chunk_split($certBase64, 64) .
                   "-----END CERTIFICATE-----";

        $certData = openssl_x509_parse($certPem);

        if ($certData === false) {
            return null;
        }

        return [
            'numero_serie' => $certData['serialNumber'] ?? null,
            'emisor' => $certData['issuer']['CN'] ?? $certData['issuer']['O'] ?? null,
            'sujeto' => $certData['subject']['CN'] ?? $certData['subject']['O'] ?? null,
            'valido_desde' => isset($certData['validFrom_time_t']) ?
                Carbon::createFromTimestamp($certData['validFrom_time_t'])->toDateTimeString() : null,
            'valido_hasta' => isset($certData['validTo_time_t']) ?
                Carbon::createFromTimestamp($certData['validTo_time_t'])->toDateTimeString() : null,
        ];
    }

    /**
     * Convertir XML firmado a Base64 para envío a Hacienda
     *
     * @param string $xmlFirmado XML firmado
     * @return string XML en Base64
     */
    public function convertirABase64(string $xmlFirmado): string
    {
        return base64_encode($xmlFirmado);
    }

    /**
     * Obtener información del certificado actualmente cargado
     *
     * @return array<string, mixed>|null
     */
    public function getInformacionCertificado(): ?array
    {
        if (!$this->certificado) {
            return null;
        }

        return [
            'id' => $this->certificado->id,
            'nombre' => $this->certificado->nombre,
            'numero_serie' => $this->certificado->numero_serie,
            'emisor' => $this->certificado->emisor,
            'sujeto' => $this->certificado->sujeto,
            'fecha_emision' => $this->certificado->fecha_emision?->format('d/m/Y'),
            'fecha_vencimiento' => $this->certificado->fecha_vencimiento?->format('d/m/Y'),
            'dias_restantes' => $this->certificado->dias_restantes,
            'activo' => $this->certificado->activo,
            'valido' => $this->certificado->valido,
            'proximo_vencer' => $this->certificado->proximo_vencer,
            'vencido' => $this->certificado->vencido,
            'ambiente' => $this->certificado->ambiente,
        ];
    }
}
