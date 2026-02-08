<?php

namespace App\Services\Hacienda;

use App\Models\HaciendaComprobante;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

/**
 * Servicio de Integración con API Hacienda Costa Rica
 *
 * Maneja toda la integración con el sistema de facturación electrónica
 * del Ministerio de Hacienda de Costa Rica (DGT-R-000-2024 v4.4).
 *
 * @package App\Services\Hacienda
 * @version 1.0.0
 */
class HaciendaIntegrationService
{
    /**
     * Tipos de comprobantes soportados
     */
    const TYPE_FACTURA = '01';           // Factura Electrónica
    const TYPE_NOTA_CREDITO = '03';      // Nota de Crédito
    const TYPE_NOTA_DEBITO = '04';       // Nota de Débito
    const TYPE_TIQUETE = '05';           // Tiquete Electrónico
    const TYPE_COMPROBANTE_EGRESO = '07'; // Comprobante de Egreso

    /**
     * Estados de los comprobantes
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ERROR = 'error';

    /**
     * Generar comprobante electrónico
     *
     * @param Comprobante $comprobante Comprobante a enviar
     * @param string $tipo Tipo de comprobante (01=Factura, 03=Nota, etc.)
     * @return HaciendaComprobante|null
     */
    public static function generateComprobante(Comprobante $comprobante, string $tipo = self::TYPE_FACTURA): ?HaciendaComprobante
    {
        try {
            // Validar datos del comprobante
            if (! static::validateComprobante($comprobante)) {
                Log::error('Validación fallida para comprobante', ['id' => $comprobante->id]);
                return null;
            }

            // Verificar si ya existe en Hacienda
            $existing = HaciendaComprobante::where('comprobante_id', $comprobante->id)->first();
            if ($existing) {
                Log::warning('Comprobante ya existe en Hacienda', ['id' => $comprobante->id]);
                return $existing;
            }

            // Generar clave única (numero de comprobante electrónico)
            $clave = static::generateClave($comprobante, $tipo);

            // Crear registro en BD
            $haciendaComprobante = HaciendaComprobante::create([
                'comprobante_id' => $comprobante->id,
                'empresa_id' => $comprobante->empresa_id,
                'clave' => $clave,
                'tipo_comprobante' => $tipo,
                'estado' => static::STATUS_PENDING,
                'xml_content' => null, // Se generará en el siguiente paso
                'xml_firmado' => null,
                'respuesta_hacienda' => null,
            ]);

            Log::info('Comprobante electrónico generado', [
                'hacienda_id' => $haciendaComprobante->id,
                'clave' => $clave,
            ]);

            return $haciendaComprobante;
        } catch (Exception $e) {
            Log::error('Error generando comprobante electrónico', [
                'comprobante_id' => $comprobante->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generar XML del comprobante según DGT-R-000-2024
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return bool
     */
    public static function generateXml(HaciendaComprobante $haciendaComprobante): bool
    {
        try {
            $comprobante = $haciendaComprobante->comprobante;
            $empresa = $comprobante->empresa;

            // Construir XML base
            $xml = static::buildXml($haciendaComprobante, $comprobante, $empresa);

            if (! $xml) {
                Log::error('Fallo al construir XML', ['comprobante_id' => $comprobante->id]);
                return false;
            }

            // Guardar XML sin firmar
            $haciendaComprobante->update(['xml_content' => $xml]);

            Log::info('XML generado exitosamente', ['hacienda_id' => $haciendaComprobante->id]);

            return true;
        } catch (Exception $e) {
            Log::error('Error generando XML', [
                'hacienda_id' => $haciendaComprobante->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Firmar comprobante con certificado digital XAdES-EPES
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @param string $certificatePath Ruta del certificado PEM
     * @param string $certificatePassword Contraseña del certificado
     * @return bool
     */
    public static function signWithXADES(
        HaciendaComprobante $haciendaComprobante,
        string $certificatePath,
        string $certificatePassword
    ): bool
    {
        try {
            if (! file_exists($certificatePath)) {
                Log::error('Certificado no encontrado', ['path' => $certificatePath]);
                return false;
            }

            if (! $haciendaComprobante->xml_content) {
                Log::error('No hay contenido XML para firmar', ['hacienda_id' => $haciendaComprobante->id]);
                return false;
            }

            // Cargar certificado
            $certificate = openssl_x509_read(file_get_contents($certificatePath));
            if (! $certificate) {
                Log::error('Error cargando certificado');
                return false;
            }

            // Extraer clave privada
            $pkeyid = openssl_pkey_get_private(file_get_contents($certificatePath), $certificatePassword);
            if (! $pkeyid) {
                Log::error('Error cargando clave privada');
                return false;
            }

            // Generar firma XAdES-EPES
            // Nota: En producción, usar librería especializada como xades-php o similar
            $signedXml = static::buildXADESSignature(
                $haciendaComprobante->xml_content,
                $certificate,
                $pkeyid
            );

            if (! $signedXml) {
                Log::error('Error generando firma XAdES-EPES');
                return false;
            }

            // Guardar XML firmado
            $haciendaComprobante->update([
                'xml_firmado' => $signedXml,
                'estado' => static::STATUS_SIGNED,
            ]);

            Log::info('Comprobante firmado correctamente', ['hacienda_id' => $haciendaComprobante->id]);

            return true;
        } catch (Exception $e) {
            Log::error('Error firmando con XAdES-EPES', [
                'hacienda_id' => $haciendaComprobante->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enviar comprobante a la API de Hacienda
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return bool
     */
    public static function sendToHacienda(HaciendaComprobante $haciendaComprobante): bool
    {
        try {
            if (! $haciendaComprobante->xml_firmado) {
                Log::error('Comprobante no está firmado', ['hacienda_id' => $haciendaComprobante->id]);
                return false;
            }

            $environment = config('hacienda.environment', 'sandbox');
            $apiUrl = config("hacienda.api_urls.{$environment}.send");
            
            if (! $apiUrl) {
                Log::error('URL de API no configurada', ['environment' => $environment]);
                return false;
            }

            // Preparar payload
            $payload = [
                'clave' => $haciendaComprobante->clave,
                'fecha' => now()->format('Y-m-d'),
                'xmlContent' => base64_encode($haciendaComprobante->xml_firmado),
            ];

            // Enviar a Hacienda
            $response = Http::timeout(30)->post($apiUrl, $payload);

            if (! $response->successful()) {
                Log::error('Error en respuesta de Hacienda', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            // Procesar respuesta
            $respuestaData = $response->json();

            $haciendaComprobante->update([
                'estado' => static::STATUS_SENT,
                'respuesta_hacienda' => json_encode($respuestaData),
            ]);

            Log::info('Comprobante enviado a Hacienda', [
                'hacienda_id' => $haciendaComprobante->id,
                'respuesta' => $respuestaData,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error enviando a Hacienda', [
                'hacienda_id' => $haciendaComprobante->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validar estado en Hacienda
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return array|null
     */
    public static function getStatus(HaciendaComprobante $haciendaComprobante): ?array
    {
        try {
            $environment = config('hacienda.environment', 'sandbox');
            $apiUrl = config("hacienda.api_urls.{$environment}.get-status");

            if (! $apiUrl) {
                return null;
            }

            $response = Http::get($apiUrl, ['clave' => $haciendaComprobante->clave]);

            if (! $response->successful()) {
                return null;
            }

            $statusData = $response->json();

            // Actualizar estado en BD si cambió
            if (isset($statusData['status'])) {
                $newStatus = static::mapHaciendaStatus($statusData['status']);
                
                if ($newStatus !== $haciendaComprobante->estado) {
                    $haciendaComprobante->update(['estado' => $newStatus]);
                    
                    Log::info('Estado actualizado desde Hacienda', [
                        'hacienda_id' => $haciendaComprobante->id,
                        'nuevo_estado' => $newStatus,
                    ]);
                }
            }

            return $statusData;
        } catch (Exception $e) {
            Log::error('Error consultando estado en Hacienda', [
                'hacienda_id' => $haciendaComprobante->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validar comprobante antes de enviar
     *
     * @param Comprobante $comprobante
     * @return bool
     */
    protected static function validateComprobante(Comprobante $comprobante): bool
    {
        // Verificar campos obligatorios
        if (! $comprobante->empresa_id) {
            Log::warning('Empresa no especificada');
            return false;
        }

        if (! $comprobante->numero_comprobante) {
            Log::warning('Número de comprobante no especificado');
            return false;
        }

        if (! $comprobante->fecha_comprobante) {
            Log::warning('Fecha de comprobante no especificada');
            return false;
        }

        return true;
    }

    /**
     * Generar clave de comprobante electrónico
     *
     * Formato: AAMDDLLLLLLLLLLNNNNNNNEEEE
     * AAM: Última cifra del año + mes (ej: 506 = mayo 2025)
     * DDL: Día +  línea (3 dígitos)
     * LLLLLLLL: Código de sucursal (8 dígitos)
     * NNNNNNN: Correlativo (7 dígitos)
     * EEEE: Tipo de comprobante (01, 03, 04, 05, 07)
     *
     * @param Comprobante $comprobante
     * @param string $tipo
     * @return string
     */
    protected static function generateClave(Comprobante $comprobante, string $tipo): string
    {
        $fecha = $comprobante->fecha_comprobante ?? now();
        $year = $fecha->format('y');
        $month = $fecha->format('m');
        $day = $fecha->format('d');

        // AAM: última cifra del año + mes
        $aam = substr($year, -1) . $month;

        // DDL: día + línea (la línea es la sucursal o 01)
        $ddl = str_pad($day, 3, '0', STR_PAD_LEFT);

        // LLLLLLLL: Código de sucursal (usar empresa_id o configurado)
        $sucursal = str_pad($comprobante->empresa_id, 8, '0', STR_PAD_LEFT);

        // NNNNNNN: Se genera aleatoriamente o con correlativo
        $correlativo = str_pad(
            HaciendaComprobante::where('tipo_comprobante', $tipo)->count() + 1,
            7,
            '0',
            STR_PAD_LEFT
        );

        // Construir clave
        $claveBase = $aam . $ddl . $sucursal . $correlativo . $tipo;

        // Calcular dígito de control (módulo 9)
        $digitoControl = static::calculateVerificationDigit($claveBase);

        return $claveBase . $digitoControl;
    }

    /**
     * Calcular dígito de control (mod-9)
     *
     * @param string $clave
     * @return int
     */
    protected static function calculateVerificationDigit(string $clave): int
    {
        $suma = 0;
        $pesos = [2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7, 2, 3];

        for ($i = 0; $i < strlen($clave); $i++) {
            $suma += intval($clave[$i]) * $pesos[$i];
        }

        $digito = 10 - ($suma % 10);
        return $digito === 10 ? 0 : $digito;
    }

    /**
     * Construir XML del comprobante
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @param Comprobante $comprobante
     * @param mixed $empresa
     * @return string|null
     */
    protected static function buildXml($haciendaComprobante, $comprobante, $empresa): ?string
    {
        try {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<!-- Factura Electrónica generada por ' . config('hacienda.proveedor_sistemas') . ' -->' . "\n";

            // Mapear tipo de comprobante a elemento raíz
            $rootElement = static::getRootElement($haciendaComprobante->tipo_comprobante);

            $xml .= "<{$rootElement} xmlns=\"https://www.hacienda.go.cr/docs/esquemas/2016/v4.4\">\n";

            // Ambiente
            $ambiente = config('hacienda.environment') === 'sandbox' ? '100' : '200';
            $xml .= "  <Ambiente>{$ambiente}</Ambiente>\n";

            // Versión del esquema
            $xml .= "  <VersionEsquema>" . config('hacienda.version_esquema') . "</VersionEsquema>\n";

            // Tipo de comprobante
            $xml .= "  <TipoComprobante>{$haciendaComprobante->tipo_comprobante}</TipoComprobante>\n";

            // Información de la empresa (emisor)
            $xml .= "  <Emisor>\n";
            $xml .= "    <Nombre>" . htmlspecialchars($empresa->nombre) . "</Nombre>\n";
            $xml .= "    <Identificacion>\n";
            $xml .= "      <Tipo>01</Tipo>\n";
            $xml .= "      <Numero>" . $empresa->identification_number . "</Numero>\n";
            $xml .= "    </Identificacion>\n";
            $xml .= "  </Emisor>\n";

            // Información del receptor
            $xml .= "  <Receptor>\n";
            $xml .= "    <Nombre>" . htmlspecialchars($comprobante->cliente->nombre ?? 'Consumidor Final') . "</Nombre>\n";
            if ($comprobante->cliente) {
                $xml .= "    <Identificacion>\n";
                $xml .= "      <Tipo>01</Tipo>\n";
                $xml .= "      <Numero>" . $comprobante->cliente->identification_number . "</Numero>\n";
                $xml .= "    </Identificacion>\n";
            }
            $xml .= "  </Receptor>\n";

            // Detalles del comprobante (montos)
            $xml .= "  <Resumen>\n";
            $xml .= "    <TotalImpuestos>" . number_format($comprobante->total_impuestos, 2, '.', '') . "</TotalImpuestos>\n";
            $xml .= "    <TotalComprobante>" . number_format($comprobante->total, 2, '.', '') . "</TotalComprobante>\n";
            $xml .= "  </Resumen>\n";

            // Cierre del elemento raíz
            $xml .= "</{$rootElement}>\n";

            return $xml;
        } catch (Exception $e) {
            Log::error('Error construyendo XML', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener elemento raíz según tipo de comprobante
     *
     * @param string $tipo
     * @return string
     */
    protected static function getRootElement(string $tipo): string
    {
        return match($tipo) {
            self::TYPE_FACTURA => 'FacturaElectronica',
            self::TYPE_NOTA_CREDITO => 'NotaCredito',
            self::TYPE_NOTA_DEBITO => 'NotaDebito',
            self::TYPE_TIQUETE => 'TiqueteElectronico',
            self::TYPE_COMPROBANTE_EGRESO => 'ComprobanteEgreso',
            default => 'FacturaElectronica',
        };
    }

    /**
     * Construir firma XAdES-EPES
     *
     * Nota: Esta es una implementación simplificada.
     * En producción, usar librería especializada.
     *
     * @param string $xmlContent
     * @param resource $certificate
     * @param resource $privateKey
     * @return string|null
     */
    protected static function buildXADESSignature($xmlContent, $certificate, $privateKey): ?string
    {
        try {
            // En un proyecto real, usar librería como:
            // - phpseclib
            // - XML Digital Signatures (xmlsec-php)
            // - xades-php

            // Para esta demostración, simplemente envolveremos el XML
            // En producción, implementar la firma digital XAdES-EPES completa

            return $xmlContent; // Placeholder
        } catch (Exception $e) {
            Log::error('Error en firma XAdES-EPES', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Mapear estado de Hacienda al estado interno
     *
     * @param string $haciendaStatus
     * @return string
     */
    protected static function mapHaciendaStatus(string $haciendaStatus): string
    {
        return match($haciendaStatus) {
            'aceptado' => static::STATUS_ACCEPTED,
            'rechazado' => static::STATUS_REJECTED,
            'error' => static::STATUS_ERROR,
            'pendiente' => static::STATUS_SENT,
            default => static::STATUS_ERROR,
        };
    }

    /**
     * Obtener estadísticas de comprobantes
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total' => HaciendaComprobante::count(),
            'pending' => HaciendaComprobante::where('estado', static::STATUS_PENDING)->count(),
            'signed' => HaciendaComprobante::where('estado', static::STATUS_SIGNED)->count(),
            'sent' => HaciendaComprobante::where('estado', static::STATUS_SENT)->count(),
            'accepted' => HaciendaComprobante::where('estado', static::STATUS_ACCEPTED)->count(),
            'rejected' => HaciendaComprobante::where('estado', static::STATUS_REJECTED)->count(),
            'error' => HaciendaComprobante::where('estado', static::STATUS_ERROR)->count(),
        ];
    }
}
