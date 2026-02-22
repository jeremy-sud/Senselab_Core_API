<?php

namespace App\Jobs\Hacienda;

use App\Models\ComprobanteElectronicoFe;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Exception;

/**
 * Job para procesar respuesta final de Hacienda
 * 
 * Realiza:
 * 1. Parsea respuesta XML de Hacienda
 * 2. Guarda respuesta en BD
 * 3. Guarda XML de respuesta en disco
 * 4. Envía notificaciones si está configurado
 * 5. Ejecuta hooks personalizados
 */
class ProcesarRespuestaHaciendaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout en segundos
     */
    public int $timeout = 60;

    /**
     * ID del comprobante
     */
    protected int $comprobanteId;

    /**
     * Datos de la respuesta
     * @var array<string, mixed>
     */
    protected array $respuestaData;

    /**
     * Create a new job instance.
     *
     * @param int $comprobanteId
     * @param array<string, mixed> $respuestaData
     */
    public function __construct(int $comprobanteId, array $respuestaData)
    {
        $this->comprobanteId = $comprobanteId;
        $this->respuestaData = $respuestaData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Procesando respuesta de Hacienda', [
            'comprobante_id' => $this->comprobanteId,
            'estado' => $this->respuestaData['estado'] ?? 'desconocido',
        ]);

        try {
            $comprobante = ComprobanteElectronicoFe::with('empresa')->findOrFail($this->comprobanteId);

            // Extraer información de la respuesta
            $this->procesarRespuesta($comprobante);

            // Guardar XML de respuesta en disco
            $this->guardarXmlRespuesta($comprobante);

            // Enviar notificaciones si aplica
            $this->enviarNotificaciones($comprobante);

            // Ejecutar hooks personalizados
            $this->ejecutarHooks($comprobante);

            Log::info('Respuesta de Hacienda procesada exitosamente', [
                'comprobante_id' => $this->comprobanteId,
                'estado_final' => $comprobante->estado,
            ]);

        } catch (Exception $e) {
            Log::error('Error al procesar respuesta de Hacienda', [
                'comprobante_id' => $this->comprobanteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Procesar datos de la respuesta
     */
    protected function procesarRespuesta(ComprobanteElectronicoFe $comprobante): void
    {
        $estado = strtolower($this->respuestaData['estado'] ?? '');
        $mensaje = $this->respuestaData['mensaje'] ?? '';
        $codigo = $this->respuestaData['codigo'] ?? '';
        $xmlRespuesta = $this->respuestaData['xml_respuesta'] ?? null;

        // Extraer detalles adicionales si existen
        $detalles = [];
        
        if (isset($this->respuestaData['detalles'])) {
            $detalles = $this->respuestaData['detalles'];
        }

        // Si hay XML de respuesta, parsearlo
        if ($xmlRespuesta) {
            try {
                $detallesXml = $this->parsearXmlRespuesta($xmlRespuesta);
                $detalles = array_merge($detalles, $detallesXml);
            } catch (Exception $e) {
                Log::warning('Error al parsear XML de respuesta', [
                    'comprobante_id' => $this->comprobanteId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Actualizar metadata con detalles completos
        $metadata = $comprobante->metadata ?? [];
        $metadata['respuesta_hacienda'] = [
            'estado' => $estado,
            'mensaje' => $mensaje,
            'codigo' => $codigo,
            'detalles' => $detalles,
            'fecha_procesamiento' => Carbon::now()->toIso8601String(),
            'datos_completos' => $this->respuestaData,
        ];

        $comprobante->update(['metadata' => $metadata]);
    }

    /**
     * Parsear XML de respuesta de Hacienda     *
     * @param string $xmlRespuesta
     * @return array<string, mixed>     */
    protected function parsearXmlRespuesta(string $xmlRespuesta): array
    {
        $detalles = [];

        try {
            $xml = simplexml_load_string($xmlRespuesta);
            
            if ($xml === false) {
                return $detalles;
            }

            // Extraer campos relevantes
            if (isset($xml->Mensaje)) {
                $detalles['mensaje_detallado'] = (string) $xml->Mensaje;
            }

            if (isset($xml->DetalleMensaje)) {
                $detalles['detalle_mensaje'] = (string) $xml->DetalleMensaje;
            }

            if (isset($xml->Clave)) {
                $detalles['clave_confirmada'] = (string) $xml->Clave;
            }

            if (isset($xml->Fecha)) {
                $detalles['fecha_respuesta_hacienda'] = (string) $xml->Fecha;
            }

        } catch (Exception $e) {
            Log::warning('Error al parsear XML de respuesta', [
                'error' => $e->getMessage(),
            ]);
        }

        return $detalles;
    }

    /**
     * Guardar XML de respuesta en disco
     */
    protected function guardarXmlRespuesta(ComprobanteElectronicoFe $comprobante): void
    {
        if (!config('hacienda.logging.save_xml', true)) {
            return;
        }

        $xmlRespuesta = $this->respuestaData['xml_respuesta'] ?? $comprobante->respuesta_hacienda_xml;

        if (!$xmlRespuesta) {
            return;
        }

        $path = config('hacienda.logging.xml_path');
        $fecha = $comprobante->fecha_emision->format('Y-m-d');
        $filename = "{$comprobante->clave}_respuesta.xml";
        $fullPath = "{$path}/{$fecha}/{$filename}";

        Storage::put($fullPath, $xmlRespuesta);

        Log::debug('XML de respuesta guardado en disco', [
            'comprobante_id' => $this->comprobanteId,
            'path' => $fullPath,
        ]);
    }

    /**
     * Enviar notificaciones
     */
    protected function enviarNotificaciones(ComprobanteElectronicoFe $comprobante): void
    {
        // Solo enviar notificaciones en rechazos o errores
        if (!in_array($comprobante->estado, ['rechazado', 'error'])) {
            return;
        }

        // Aquí puedes implementar notificaciones por email, SMS, etc.
        Log::info('Notificación de comprobante rechazado/error', [
            'comprobante_id' => $this->comprobanteId,
            'clave' => $comprobante->clave,
            'estado' => $comprobante->estado,
            'empresa_id' => $comprobante->empresa_id,
        ]);

        // Ejemplo:
        // $comprobante->empresa->notify(new ComprobanteRechazadoNotification($comprobante));
    }

    /**
     * Ejecutar hooks personalizados
     */
    protected function ejecutarHooks(ComprobanteElectronicoFe $comprobante): void
    {
        // Hooks para integraciones personalizadas
        // Por ejemplo: actualizar sistema contable, enviar a cliente, etc.
        
        if ($comprobante->estado === 'aceptado') {
            Log::info('Comprobante aceptado - ejecutar hooks de éxito', [
                'comprobante_id' => $this->comprobanteId,
            ]);

            // event(new ComprobanteAceptadoEvent($comprobante));
        }

        if ($comprobante->estado === 'rechazado') {
            Log::info('Comprobante rechazado - ejecutar hooks de rechazo', [
                'comprobante_id' => $this->comprobanteId,
                'mensaje' => $comprobante->mensaje_hacienda,
            ]);

            // event(new ComprobanteRechazadoEvent($comprobante));
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job de procesamiento de respuesta falló', [
            'comprobante_id' => $this->comprobanteId,
            'error' => $exception->getMessage(),
        ]);
    }
}
