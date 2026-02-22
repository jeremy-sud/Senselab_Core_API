<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Empresa;

/**
 * Job para procesar importaciones CSV/Excel de forma asíncrona
 * Sprint 8.4 - Queue Jobs
 */
class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutos
    /** @var array<int, int> */
    public array $backoff = [120, 300, 600];

    /**
     * Create a new job instance.
     */
    /**
     * @param string $filePath Ruta al archivo CSV/Excel
     * @param string $importType Tipo de importación (productos|clientes|proveedores)
     */
    public function __construct(
        public string $filePath,
        public string $importType,
        public int $empresaId,
        public ?int $userId = null
    ) {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('ProcessImportJob: Iniciando importación', [
                'file_path' => $this->filePath,
                'import_type' => $this->importType,
                'empresa_id' => $this->empresaId,
                'user_id' => $this->userId,
            ]);

            $empresa = Empresa::findOrFail($this->empresaId);
            $fileContent = Storage::disk('local')->get($this->filePath);
            
            // Procesar según tipo de importación
            $result = match($this->importType) {
                'productos' => $this->importProductos($fileContent, $empresa),
                'clientes' => $this->importClientes($fileContent, $empresa),
                'proveedores' => $this->importProveedores($fileContent, $empresa),
                default => throw new \InvalidArgumentException("Tipo de importación no soportado: {$this->importType}")
            };

            Log::info('ProcessImportJob: Importación completada', [
                'imported' => $result['imported'],
                'errors' => $result['errors'],
                'total' => $result['total'],
            ]);

            // Limpiar archivo temporal
            Storage::disk('local')->delete($this->filePath);

            if ($this->userId) {
                $user = \App\Models\Usuario::find($this->userId);
                if ($user && $user->email) {
                    dispatch(new SendEmailJob(
                        to: $user->email,
                        subject: "Importación de {$this->importType} completada",
                        view: 'emails.imports.completed',
                        data: [
                            'user_name' => $user->nombre,
                            'import_type' => $this->importType,
                            'total' => $result['total'],
                            'imported' => $result['imported'],
                            'errors' => $result['errors']
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            Log::error('ProcessImportJob: Error procesando importación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @return array{imported:int,errors:array<int,string>,total:int}
     */
    protected function importProductos(string $csvContent, Empresa $empresa): array
    {
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));
        
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line);
                $row = array_combine($headers, $data);

                try {
                    Producto::create([
                        'empresa_id' => $empresa->id,
                        'codigo' => $row['codigo'] ?? null,
                        'nombre' => $row['nombre'],
                        'descripcion' => $row['descripcion'] ?? null,
                        'precio_compra' => $row['precio_compra'] ?? 0,
                        'precio_venta' => $row['precio_venta'],
                        'activo' => true,
                        'eliminado' => false,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Línea " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($lines),
        ];
    }

    /**
     * @return array{imported:int,errors:array<int,string>,total:int}
     */
    protected function importClientes(string $csvContent, Empresa $empresa): array
    {
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));
        
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line);
                if (count($headers) !== count($data)) {
                    $errors[] = "Línea " . ($index + 2) . ": Número de columnas incorrecto";
                    continue;
                }
                $row = array_combine($headers, $data);

                try {
                    Cliente::create([
                        'empresa_id' => $empresa->id,
                        'tipo_identificacion' => $row['tipo_identificacion'] ?? '01',
                        'identificacion' => $row['identificacion'],
                        'nombre' => $row['nombre'],
                        'correo' => $row['correo'] ?? null,
                        'telefono' => $row['telefono'] ?? null,
                        'activo' => true,
                        'eliminado' => false,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Línea " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($lines),
        ];
    }

    /**
     * @return array{imported:int,errors:array<int,string>,total:int}
     */
    protected function importProveedores(string $csvContent, Empresa $empresa): array
    {
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));
        
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line);
                if (count($headers) !== count($data)) {
                    $errors[] = "Línea " . ($index + 2) . ": Número de columnas incorrecto";
                    continue;
                }
                $row = array_combine($headers, $data);

                try {
                    Proveedor::create([
                        'empresa_id' => $empresa->id,
                        'tipo_identificacion' => $row['tipo_identificacion'] ?? '01',
                        'identificacion' => $row['identificacion'],
                        'nombre' => $row['nombre'],
                        'correo' => $row['correo'] ?? null,
                        'telefono' => $row['telefono'] ?? null,
                        'activo' => true,
                        'eliminado' => false,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Línea " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($lines),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessImportJob: Job failed permanently', [
            'file_path' => $this->filePath,
            'import_type' => $this->importType,
            'error' => $exception->getMessage(),
        ]);

        // Limpiar archivo temporal
        Storage::disk('local')->delete($this->filePath);

        if ($this->userId) {
            $user = \App\Models\Usuario::find($this->userId);
            if ($user && $user->email) {
                dispatch(new SendEmailJob(
                    to: $user->email,
                    subject: "Error en importación de {$this->importType}",
                    view: 'emails.imports.failed',
                    data: [
                        'user_name' => $user->nombre,
                        'import_type' => $this->importType,
                        'error_message' => 'Ocurrió un error al procesar el archivo. Por favor, verifique el formato e intente nuevamente.'
                    ]
                ));
            }
        }
    }
}
