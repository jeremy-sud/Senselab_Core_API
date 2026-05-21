<?php

namespace App\Jobs\Hacienda;

use App\Models\ComprobanteElectronicoFe;
use App\Services\Hacienda\HaciendaApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Job para consultar estado de comprobante en Hacienda
 * 
 * Realiza polling del estado del comprobante hasta obtener respuesta final.
 * Estados finales: aceptado, rechazado, error
 * Estados intermedios: recibido, procesando (reintentar después)
 */
class ConsultarEstadoComprobanteJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos
     */
    public int $tries = 10;

    /**
     * Timeout en segundos
     */
    public int $timeout = 60;

    /**
     * Backoff en segundos entre reintentos
     */
    public int $backoff = 30;

    /**
     * ID del comprobante
     */
    protected int $comprobanteId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $comprobanteId)
    {
        $this->comprobanteId = $comprobanteId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Consultando estado de comprobante en Hacienda', [
            'comprobante_id' => $this->comprobanteId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $comprobante = ComprobanteElectronicoFe::findOrFail($this->comprobanteId);

            // Solo consultar si está en estados intermedios
            if (!in_array($comprobante->estado, ['recibido', 'procesando'])) {
                Log::info('Comprobante no requiere consulta de estado', [
                    'comprobante_id' => $this->comprobanteId,
                    'estado_actual' => $comprobante->estado,
                ]);
                return;
            }

            // Consultar estado en Hacienda
            $apiClient = new HaciendaApiClient($comprobante->empresa_id);
            $response = $apiClient->consultarEstado($comprobante->clave);

            if (!$response['success']) {
                Log::warning('Error al consultar estado en Hacienda', [
                    'comprobante_id' => $this->comprobanteId,
                    'clave' => $comprobante->clave,
                    'error' => $response['error'] ?? 'Error desconocido',
                ]);

                // Re-intentar después
                $this->release(60);
                return;
            }

            $data = $response['data'];
            $estadoHacienda = strtolower($data['estado'] ?? 'desconocido');

            Log::info('Estado consultado en Hacienda', [
                'comprobante_id' => $this->comprobanteId,
                'clave' => $comprobante->clave,
                'estado_hacienda' => $estadoHacienda,
            ]);

            // Mapear estado de Hacienda
            $nuevoEstado = match($estadoHacienda) {
                'recibido' => 'recibido',
                'procesando' => 'procesando',
                'aceptado' => 'aceptado',
                'rechazado' => 'rechazado',
                'error' => 'error',
                default => 'procesando',
            };

            // Actualizar comprobante
            $actualizaciones = [
                'estado' => $nuevoEstado,
            ];

            if ($estadoHacienda === 'procesando' && $comprobante->estado !== 'procesando') {
                $actualizaciones['fecha_procesado'] = Carbon::now();
            }

            if (in_array($estadoHacienda, ['aceptado', 'rechazado', 'error'])) {
                $actualizaciones['fecha_respuesta'] = Carbon::now();
                $actualizaciones['respuesta_hacienda_xml'] = $data['xml_respuesta'] ?? null;
                $actualizaciones['mensaje_hacienda'] = $data['mensaje'] ?? null;
                $actualizaciones['codigo_respuesta_hacienda'] = $data['codigo'] ?? null;

                // Disparar job de procesamiento de respuesta
                ProcesarRespuestaHaciendaJob::dispatch($this->comprobanteId, $data);
            }

            $comprobante->update($actualizaciones);

            // Si aún está procesando, re-consultar después
            if ($nuevoEstado === 'procesando') {
                Log::info('Comprobante aún en procesamiento, programando nueva consulta', [
                    'comprobante_id' => $this->comprobanteId,
                ]);

                // Re-consultar en 1 minuto
                self::dispatch($this->comprobanteId)
                    ->delay(now()->addSeconds(60));
            }

        } catch (Exception $e) {
            Log::error('Error al consultar estado de comprobante', [
                'comprobante_id' => $this->comprobanteId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job de consulta de estado falló', [
            'comprobante_id' => $this->comprobanteId,
            'error' => $exception->getMessage(),
        ]);
    }
}
