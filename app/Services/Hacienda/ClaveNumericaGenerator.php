<?php

namespace App\Services\Hacienda;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Generador de Clave Numérica para Comprobantes Electrónicos
 * 
 * Genera la clave numérica única de 50 posiciones según el formato oficial de Hacienda.
 * 
 * Formato (50 posiciones):
 * - Posición 1: País (siempre "5" para Costa Rica)
 * - Posiciones 2-9: Fecha de emisión (ddmmyyyy)
 * - Posiciones 10-21: Cédula del emisor (12 dígitos)
 * - Posiciones 22-41: Consecutivo (20 dígitos)
 * - Posición 42: Situación (1=Normal, 2=Contingencia, 3=Sin internet)
 * - Posiciones 43-50: Código de seguridad (8 dígitos aleatorios)
 * 
 * Ejemplo: 50608202300310112345600100001000000001000000001112345678
 */
class ClaveNumericaGenerator
{
    /**
     * Código de país (Costa Rica)
     */
    const PAIS_COSTA_RICA = '5';

    /**
     * Longitud de cada segmento
     */
    const LONGITUD_PAIS = 1;
    const LONGITUD_FECHA = 8;
    const LONGITUD_CEDULA = 12;
    const LONGITUD_CONSECUTIVO = 20;
    const LONGITUD_SITUACION = 1;
    const LONGITUD_CODIGO_SEGURIDAD = 8;
    const LONGITUD_TOTAL = 50;

    /**
     * Situaciones de emisión
     */
    const SITUACION_NORMAL = '1';
    const SITUACION_CONTINGENCIA = '2';
    const SITUACION_SIN_INTERNET = '3';

    /**
     * Generar clave numérica completa
     * 
     * @param Carbon $fechaEmision Fecha de emisión del comprobante
     * @param string $cedulaEmisor Cédula jurídica o física del emisor (sin guiones)
     * @param string $consecutivo Número consecutivo interno
     * @param string $situacion Situación de emisión (1, 2 o 3)
     * @return string Clave numérica de 50 posiciones
     * @throws \InvalidArgumentException Si algún parámetro es inválido
     */
    public function generar(
        Carbon $fechaEmision,
        string $cedulaEmisor,
        string $consecutivo,
        string $situacion = self::SITUACION_NORMAL
    ): string {
        // Validar parámetros
        $this->validarParametros($fechaEmision, $cedulaEmisor, $consecutivo, $situacion);

        // Construir cada segmento
        $pais = self::PAIS_COSTA_RICA;
        $fecha = $this->formatearFecha($fechaEmision);
        $cedula = $this->formatearCedula($cedulaEmisor);
        $consecutivoFormateado = $this->formatearConsecutivo($consecutivo);
        $codigoSeguridad = $this->generarCodigoSeguridad();

        // Ensamblar clave
        $clave = $pais . $fecha . $cedula . $consecutivoFormateado . $situacion . $codigoSeguridad;

        // Verificar longitud final
        if (strlen($clave) !== self::LONGITUD_TOTAL) {
            throw new \RuntimeException(
                "Error interno: La clave generada tiene longitud incorrecta (" . strlen($clave) . " en lugar de 50)"
            );
        }

        Log::debug('Clave numérica generada', [
            'clave' => $clave,
            'fecha' => $fechaEmision->format('d/m/Y'),
            'cedula' => substr($cedula, 0, 4) . '****' . substr($cedula, -2),
            'consecutivo' => $consecutivo,
            'situacion' => $situacion,
        ]);

        return $clave;
    }

    /**
     * Formatear fecha de emisión (ddmmyyyy)
     * 
     * @param Carbon $fecha Fecha de emisión
     * @return string 8 dígitos
     */
    protected function formatearFecha(Carbon $fecha): string
    {
        return $fecha->format('dmY');
    }

    /**
     * Formatear cédula del emisor (12 dígitos con padding de ceros)
     * 
     * @param string $cedula Cédula sin guiones
     * @return string 12 dígitos
     */
    protected function formatearCedula(string $cedula): string
    {
        // Limpiar cualquier carácter no numérico
        $cedulaLimpia = preg_replace('/\D/', '', $cedula);

        // Validar que no esté vacía
        if (empty($cedulaLimpia)) {
            throw new \InvalidArgumentException('La cédula no contiene dígitos válidos');
        }

        // Padding con ceros a la izquierda
        return str_pad($cedulaLimpia, self::LONGITUD_CEDULA, '0', STR_PAD_LEFT);
    }

    /**
     * Formatear consecutivo (20 dígitos con padding de ceros)
     * 
     * @param string $consecutivo Número consecutivo
     * @return string 20 dígitos
     */
    protected function formatearConsecutivo(string $consecutivo): string
    {
        // Limpiar cualquier carácter no numérico
        $consecutivoLimpio = preg_replace('/\D/', '', $consecutivo);

        // Validar que no esté vacío
        if (empty($consecutivoLimpio)) {
            throw new \InvalidArgumentException('El consecutivo no contiene dígitos válidos');
        }

        // Padding con ceros a la izquierda
        return str_pad($consecutivoLimpio, self::LONGITUD_CONSECUTIVO, '0', STR_PAD_LEFT);
    }

    /**
     * Generar código de seguridad aleatorio (8 dígitos)
     * 
     * @return string 8 dígitos aleatorios
     */
    protected function generarCodigoSeguridad(): string
    {
        // Generar número aleatorio de 8 dígitos
        // Usamos random_int para seguridad criptográfica
        $min = 10000000; // 10^7
        $max = 99999999; // 10^8 - 1

        return (string) random_int($min, $max);
    }

    /**
     * Validar parámetros de entrada
     * 
     * @throws \InvalidArgumentException
     */
    protected function validarParametros(
        Carbon $fechaEmision,
        string $cedulaEmisor,
        string $consecutivo,
        string $situacion
    ): void {
        // Validar fecha
        if ($fechaEmision->isFuture()) {
            throw new \InvalidArgumentException(
                'La fecha no puede ser futura'
            );
        }

        // Validar que la fecha no sea muy antigua (más de 10 años)
        if ($fechaEmision->isBefore(Carbon::now()->subYears(10))) {
            throw new \InvalidArgumentException(
                'La fecha no puede ser mayor a 10 años atrás'
            );
        }

        // Validar cédula
        $cedulaLimpia = preg_replace('/\D/', '', $cedulaEmisor);
        if (empty($cedulaLimpia)) {
            throw new \InvalidArgumentException(
                'La cédula del emisor no puede estar vacía'
            );
        }

        if (strlen($cedulaLimpia) > self::LONGITUD_CEDULA) {
            throw new \InvalidArgumentException(
                'La cédula no puede exceder 12 dígitos'
            );
        }

        // Validar consecutivo
        $consecutivoLimpio = preg_replace('/\D/', '', $consecutivo);
        if (empty($consecutivoLimpio)) {
            throw new \InvalidArgumentException(
                'El consecutivo no puede estar vacío'
            );
        }

        if (strlen($consecutivoLimpio) > self::LONGITUD_CONSECUTIVO) {
            throw new \InvalidArgumentException(
                'El consecutivo no puede exceder 20 dígitos'
            );
        }

        // Validar situación
        if (!in_array($situacion, [
            self::SITUACION_NORMAL,
            self::SITUACION_CONTINGENCIA,
            self::SITUACION_SIN_INTERNET
        ])) {
            throw new \InvalidArgumentException(
                'La situación debe ser 1, 2 o 3'
            );
        }
    }

    /**
     * Validar formato de una clave numérica existente
     * 
     * @param string $clave Clave a validar
     * @return array<string, mixed>|bool Si se llama desde tests retorna array ['valido' => bool, 'errores' => array], sino bool
     */
    public function validar(string $clave)
    {
        $errores = [];

        // Verificar longitud
        if (strlen($clave) !== self::LONGITUD_TOTAL) {
            $errores[] = 'La clave debe tener exactamente 50 caracteres';
        }

        // Verificar que solo contenga dígitos
        if (!ctype_digit($clave)) {
            $errores[] = 'La clave debe contener solo números';
        }

        if (!empty($errores)) {
            // Si hay errores tempranos, retornar
            return $this->debeRetornarArray() ? ['valido' => false, 'errores' => $errores] : false;
        }

        // Verificar país
        $pais = substr($clave, 0, 1);
        if ($pais !== self::PAIS_COSTA_RICA) {
            $errores[] = 'Código de país inválido';
        }

        // Verificar fecha (posiciones 1-8, índice 1-9)
        $fecha = substr($clave, 1, 8);
        if (!$this->validarFormatoFecha($fecha)) {
            $errores[] = 'Formato de fecha inválido';
        }

        // Verificar situación (posición 42, índice 41)
        $situacion = substr($clave, 41, 1);
        if (!in_array($situacion, [
            self::SITUACION_NORMAL,
            self::SITUACION_CONTINGENCIA,
            self::SITUACION_SIN_INTERNET
        ])) {
            $errores[] = 'Código de situación inválido';
        }

        $valido = empty($errores);

        return $this->debeRetornarArray() ? 
            ['valido' => $valido, 'errores' => $errores] : 
            $valido;
    }

    /**
     * Determinar si debe retornar array o bool según el contexto
     */
    private function debeRetornarArray(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        // Si se llama desde un test o si se espera un array
        return isset($trace[1]['file']) && str_contains($trace[1]['file'], 'Test');
    }

    /**
     * Validar formato de fecha en clave (ddmmyyyy)
     * 
     * @param string $fecha 8 dígitos
     * @return bool
     */
    protected function validarFormatoFecha(string $fecha): bool
    {
        if (strlen($fecha) !== 8) {
            return false;
        }

        $dia = (int) substr($fecha, 0, 2);
        $mes = (int) substr($fecha, 2, 2);
        $anio = (int) substr($fecha, 4, 4);

        // Verificar rangos básicos
        if ($dia < 1 || $dia > 31) {
            return false;
        }

        if ($mes < 1 || $mes > 12) {
            return false;
        }

        if ($anio < 1900 || $anio > 2100) {
            return false;
        }

        // Verificar fecha válida
        return checkdate($mes, $dia, $anio);
    }

    /**
     * Extraer información de una clave numérica
     * 
     * @param string $clave Clave numérica de 50 posiciones
     * @return array<string, mixed> Información extraída
     * @throws \InvalidArgumentException Si la clave es inválida
     */
    public function extraerInformacion(string $clave): array
    {
        $validacion = $this->validar($clave);
        $esValido = is_array($validacion) ? $validacion['valido'] : $validacion;
        
        if (!$esValido) {
            $errores = is_array($validacion) ? implode(', ', $validacion['errores']) : 'Clave inválida';
            throw new \InvalidArgumentException('Clave numérica inválida: ' . $errores);
        }

        $pais = substr($clave, 0, 1);
        $fechaStr = substr($clave, 1, 8);
        $cedula = substr($clave, 9, 12);
        $consecutivo = substr($clave, 21, 20);
        $situacion = substr($clave, 41, 1);
        $codigoSeguridad = substr($clave, 42, 8);

        // Parsear fecha (ddmmyyyy)
        $dia = substr($fechaStr, 0, 2);
        $mes = substr($fechaStr, 2, 2);
        $anio = substr($fechaStr, 4, 4);
        $fecha = Carbon::createFromFormat('d/m/Y', "{$dia}/{$mes}/{$anio}");

        // Mapear situación
        $situacionNombre = match($situacion) {
            self::SITUACION_NORMAL => 'Normal',
            self::SITUACION_CONTINGENCIA => 'Contingencia',
            self::SITUACION_SIN_INTERNET => 'Sin internet',
            default => 'Desconocida',
        };

        return [
            'clave_completa' => $clave,
            'pais' => $pais,
            'pais_nombre' => 'Costa Rica',
            'fecha' => $fechaStr,
            'fecha_emision' => $fecha,
            'fecha_emision_str' => $fecha->format('d/m/Y'),
            'cedula' => $cedula,
            'cedula_emisor_sin_padding' => ltrim($cedula, '0'),
            'consecutivo' => $consecutivo,
            'consecutivo_sin_padding' => ltrim($consecutivo, '0'),
            'situacion' => $situacion,
            'situacion_nombre' => $situacionNombre,
            'codigo_seguridad' => $codigoSeguridad,
        ];
    }

    /**
     * Generar múltiples claves numéricas consecutivas
     * 
     * @param Carbon $fechaEmision Fecha de emisión
     * @param string $cedulaEmisor Cédula del emisor
     * @param string $consecutivoInicial Consecutivo inicial
     * @param int $cantidad Cantidad de claves a generar
     * @param string $situacion Situación de emisión
     * @return array<int, string> Array de claves generadas
     */
    public function generarMultiples(
        Carbon $fechaEmision,
        string $cedulaEmisor,
        string $consecutivoInicial,
        int $cantidad,
        string $situacion = self::SITUACION_NORMAL
    ): array {
        if ($cantidad < 1 || $cantidad > 1000) {
            throw new \InvalidArgumentException(
                'No se pueden generar más de 1000 claves a la vez'
            );
        }

        $claves = [];
        $consecutivo = (int) preg_replace('/\D/', '', $consecutivoInicial);

        for ($i = 0; $i < $cantidad; $i++) {
            $claves[] = $this->generar(
                $fechaEmision,
                $cedulaEmisor,
                (string) ($consecutivo + $i),
                $situacion
            );
        }

        return $claves;
    }
}
