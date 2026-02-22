<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\ComprobanteRecibidoElectronico;
use App\Models\MensajeHacienda;
use App\Models\Empresa;

/**
 * Job para sincronizar con API de Hacienda (Costa Rica) de forma asíncrona
 * Sprint 8.4 - Queue Jobs
 */
class SyncHaciendaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;
    /** @var array<int, int> */
    public array $backoff = [60, 120, 300, 600, 1200];

    /**
     * Create a new job instance.
     */
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(
        public int $empresaId,
        public string $action,
        public array $data = []
    ) {
        $this->onQueue('hacienda');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SyncHaciendaJob: Iniciando sincronización Hacienda', [
                'empresa_id' => $this->empresaId,
                'action' => $this->action,
            ]);

            $empresa = Empresa::findOrFail($this->empresaId);
            
            // Ejecutar acción según tipo
            $result = match($this->action) {
                'enviar_factura' => $this->enviarFactura($empresa, $this->data),
                'consultar_estado' => $this->consultarEstado($empresa, $this->data),
                'recibir_comprobante' => $this->recibirComprobante($empresa, $this->data),
                'validar_ced_juridica' => $this->validarCedulaJuridica($this->data),
                default => throw new \InvalidArgumentException("Acción no soportada: {$this->action}")
            };

            Log::info('SyncHaciendaJob: Sincronización completada', [
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('SyncHaciendaJob: Error en sincronización Hacienda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array{success:bool,clave?:string}
     */
    protected function enviarFactura(Empresa $empresa, array $data): array
    {
        // URL de pruebas de Hacienda
        $url = config('hacienda.api_url', 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getHaciendaToken($empresa),
                'Content-Type' => 'application/json',
            ])->post($url, [
                'clave' => $data['clave'],
                'fecha' => $data['fecha'],
                'emisor' => [
                    'tipoIdentificacion' => '02',
                    'numeroIdentificacion' => $empresa->cedula_juridica,
                ],
                'receptor' => $data['receptor'],
                'comprobanteXml' => $data['xml'],
            ]);

            if ($response->successful()) {
                MensajeHacienda::create([
                    'empresa_id' => $empresa->id,
                    'clave' => $data['clave'],
                    'mensaje' => 'Factura enviada exitosamente',
                    'estado' => 'aceptado',
                    'respuesta_hacienda' => $response->json(),
                ]);

                return ['success' => true, 'clave' => $data['clave']];
            }

            throw new \Exception('Error HTTP: ' . $response->status());

        } catch (\Exception $e) {
            MensajeHacienda::create([
                'empresa_id' => $empresa->id,
                'clave' => $data['clave'] ?? null,
                'mensaje' => 'Error enviando factura: ' . $e->getMessage(),
                'estado' => 'rechazado',
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function consultarEstado(Empresa $empresa, array $data): array
    {
        $url = config('hacienda.api_url_consulta', 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getHaciendaToken($empresa),
        ])->get($url . '/' . $data['clave']);

        return $response->json();
    }

    /**
     * @param array<string,mixed> $data
     * @return array{success:bool}
     */
    protected function recibirComprobante(Empresa $empresa, array $data): array
    {
        ComprobanteRecibidoElectronico::create([
            'empresa_id' => $empresa->id,
            'clave' => $data['clave'],
            'tipo_comprobante' => $data['tipo_comprobante'],
            'numero_comprobante' => $data['numero_comprobante'],
            'fecha_emision' => $data['fecha_emision'],
            'emisor_identificacion' => $data['emisor_identificacion'],
            'emisor_nombre' => $data['emisor_nombre'],
            'monto_total' => $data['monto_total'],
            'xml' => $data['xml'],
        ]);

        return ['success' => true];
    }

    /**
     * @param array<string,mixed> $data
     * @return array{valid:int|bool,cedula:string}
     */
    protected function validarCedulaJuridica(array $data): array
    {
        // Validación básica formato cédula jurídica CR: 3-NNN-NNNNNN
        $cedula = $data['cedula'] ?? '';
        $valid = preg_match('/^[0-9]-[0-9]{3}-[0-9]{6}$/', $cedula);

        return ['valid' => $valid, 'cedula' => $cedula];
    }

    protected function getHaciendaToken(Empresa $empresa): string
    {
        $ambiente = config('hacienda.ambiente', 'sandbox');
        $tokenManager = new \App\Services\Hacienda\OAuthTokenManager($ambiente);
        return $tokenManager->getValidToken();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncHaciendaJob: Job failed permanently', [
            'empresa_id' => $this->empresaId,
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);

        // Registrar fallo en mensajes Hacienda
        MensajeHacienda::create([
            'empresa_id' => $this->empresaId,
            'mensaje' => 'Error permanente: ' . $exception->getMessage(),
            'estado' => 'error',
        ]);
    }
}
