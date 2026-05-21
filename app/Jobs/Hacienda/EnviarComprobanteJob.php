<?php

namespace App\Jobs\Hacienda;

use App\Models\ComprobanteElectronicoFe;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\ClaveNumericaGenerator;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use App\Services\Hacienda\Xml\FirmaDigitalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

/**
 * Job para enviar comprobante electrónico a Hacienda
 * 
 * Realiza:
 * 1. Genera clave numérica si no existe
 * 2. Construye XML v4.3
 * 3. Firma digitalmente con certificado .p12
 * 4. Envía a API de Hacienda
 * 5. Procesa respuesta
 * 6. Programa job de consulta de estado
 */
class EnviarComprobanteJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout en segundos
     */
    public int $timeout = 120;

    /**
     * Backoff en segundos entre reintentos
     */
    public int $backoff = 60;

    /**
     * ID del comprobante
     */
    protected int $comprobanteId;

    /**
     * ID del certificado digital
     */
    protected int $certificadoId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $comprobanteId, int $certificadoId)
    {
        $this->comprobanteId = $comprobanteId;
        $this->certificadoId = $certificadoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando envío de comprobante a Hacienda', [
            'comprobante_id' => $this->comprobanteId,
            'certificado_id' => $this->certificadoId,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Cargar comprobante con relaciones (incluye tablas normalizadas v4.4)
            $comprobante = ComprobanteElectronicoFe::with([
                'empresa',
                'lineasDetalle.impuestos',
                'lineasDetalle.descuentos',
                'mediosPago',
                'informacionReferencia',
                'otrosCargos',
            ])->findOrFail($this->comprobanteId);

            // Validar que esté en estado pendiente o con error
            if (!in_array($comprobante->estado, ['pendiente', 'error'])) {
                Log::warning('Comprobante no está en estado válido para envío', [
                    'comprobante_id' => $this->comprobanteId,
                    'estado_actual' => $comprobante->estado,
                ]);
                return;
            }

            // Actualizar estado
            $comprobante->update([
                'estado' => 'enviando',
                'intentos_envio' => $comprobante->intentos_envio + 1,
                'ultimo_intento' => Carbon::now(),
            ]);

            // 1. Generar clave numérica si no existe
            if (!$comprobante->clave) {
                $this->generarClave($comprobante);
            }

            // 2. Generar XML si no existe
            if (!$comprobante->xml_original) {
                $this->generarXml($comprobante);
            }

            // 3. Firmar XML si no está firmado
            if (!$comprobante->xml_firmado) {
                $this->firmarXml($comprobante);
            }

            // 4. Enviar a Hacienda
            $this->enviarAHacienda($comprobante);

            // 5. Programar job de consulta de estado (en 30 segundos)
            ConsultarEstadoComprobanteJob::dispatch($this->comprobanteId)
                ->delay(now()->addSeconds(30));

        } catch (Exception $e) {
            Log::error('Error al enviar comprobante a Hacienda', [
                'comprobante_id' => $this->comprobanteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Actualizar comprobante con error
            $comprobante = ComprobanteElectronicoFe::find($this->comprobanteId);
            if ($comprobante) {
                $comprobante->update([
                    'estado' => 'error',
                    'ultimo_error' => $e->getMessage(),
                ]);
            }

            // Re-lanzar excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Generar clave numérica
     */
    protected function generarClave(ComprobanteElectronicoFe $comprobante): void
    {
        $generator = new ClaveNumericaGenerator();
        
        $clave = $generator->generar(
            $comprobante->fecha_emision,
            $comprobante->empresa->numero_identificacion,
            $comprobante->consecutivo,
            $comprobante->situacion
        );

        $comprobante->update(['clave' => $clave]);

        Log::info('Clave numérica generada', [
            'comprobante_id' => $this->comprobanteId,
            'clave' => $clave,
        ]);
    }

    /**
     * Generar XML del comprobante
     */
    protected function generarXml(ComprobanteElectronicoFe $comprobante): void
    {
        $builder = new XmlComprobanteBuilder();
        $xml = $builder->build($comprobante);

        $comprobante->update(['xml_original' => $xml]);

        // Guardar XML en disco si está habilitado
        if (config('hacienda.logging.save_xml', true)) {
            $this->guardarXmlEnDisco($comprobante, 'original', $xml);
        }

        Log::info('XML generado', [
            'comprobante_id' => $this->comprobanteId,
            'xml_length' => strlen($xml),
        ]);
    }

    /**
     * Firmar XML digitalmente
     */
    protected function firmarXml(ComprobanteElectronicoFe $comprobante): void
    {
        $firmador = new FirmaDigitalService();
        $xmlFirmado = $firmador->firmar($comprobante->xml_original, $this->certificadoId);

        $comprobante->update(['xml_firmado' => $xmlFirmado]);

        // Guardar XML firmado en disco
        if (config('hacienda.logging.save_xml', true)) {
            $this->guardarXmlEnDisco($comprobante, 'firmado', $xmlFirmado);
        }

        Log::info('XML firmado digitalmente', [
            'comprobante_id' => $this->comprobanteId,
            'xml_length' => strlen($xmlFirmado),
            'certificado_id' => $this->certificadoId,
        ]);
    }

    /**
     * Enviar comprobante a Hacienda
     */
    protected function enviarAHacienda(ComprobanteElectronicoFe $comprobante): void
    {
        $apiClient = new HaciendaApiClient($comprobante->empresa_id);
        $firmador = new FirmaDigitalService();

        // Convertir XML a Base64
        $xmlBase64 = $firmador->convertirABase64($comprobante->xml_firmado);

        // Preparar datos del emisor
        $emisor = [
            'tipoIdentificacion' => $comprobante->empresa->tipo_identificacion ?? '02',
            'numeroIdentificacion' => $comprobante->empresa->numero_identificacion,
        ];

        // Preparar datos del receptor (si existe)
        $receptor = null;
        if ($comprobante->receptor_numero_identificacion) {
            $receptor = [
                'tipoIdentificacion' => $comprobante->receptor_tipo_identificacion ?? '01',
                'numeroIdentificacion' => $comprobante->receptor_numero_identificacion,
            ];
        }

        // Enviar a Hacienda
        $response = $apiClient->enviarComprobante(
            $comprobante->clave,
            $xmlBase64,
            $comprobante->fecha_emision->toIso8601String(),
            $emisor,
            $receptor
        );

        // Procesar respuesta
        if ($response['success']) {
            $comprobante->update([
                'estado' => 'recibido',
                'fecha_envio' => Carbon::now(),
                'fecha_recibido' => Carbon::now(),
                'metadata' => array_merge($comprobante->metadata ?? [], [
                    'hacienda_response' => $response,
                ]),
            ]);

            Log::info('Comprobante enviado exitosamente a Hacienda', [
                'comprobante_id' => $this->comprobanteId,
                'clave' => $comprobante->clave,
                'status_code' => $response['status_code'],
            ]);
        } else {
            throw new Exception(
                "Error en respuesta de Hacienda: " . ($response['error'] ?? 'Error desconocido')
            );
        }
    }

    /**
     * Guardar XML en disco
     */
    protected function guardarXmlEnDisco(ComprobanteElectronicoFe $comprobante, string $tipo, string $xml): void
    {
        $path = config('hacienda.logging.xml_path');
        $fecha = $comprobante->fecha_emision->format('Y-m-d');
        $filename = "{$comprobante->clave}_{$tipo}.xml";
        $fullPath = "{$path}/{$fecha}/{$filename}";

        Storage::put($fullPath, $xml);

        Log::debug('XML guardado en disco', [
            'comprobante_id' => $this->comprobanteId,
            'tipo' => $tipo,
            'path' => $fullPath,
        ]);
    }

    /**
     * Manejar fallo del job
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job de envío de comprobante falló después de todos los intentos', [
            'comprobante_id' => $this->comprobanteId,
            'error' => $exception->getMessage(),
        ]);

        $comprobante = ComprobanteElectronicoFe::find($this->comprobanteId);
        if ($comprobante) {
            $comprobante->update([
                'estado' => 'error',
                'ultimo_error' => 'Fallo después de ' . $this->tries . ' intentos: ' . $exception->getMessage(),
            ]);
        }
    }
}
