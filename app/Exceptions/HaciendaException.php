<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de facturación electrónica con Hacienda.
 *
 * Cubre: API Hacienda, OAuth, firma digital, XML, rate limiting.
 *
 * FASE 15: Excepciones tipadas para módulo Hacienda.
 */
class HaciendaException extends DomainException
{
    public static function apiCommunicationError(string $detail, int $attempts, ?\Throwable $previous = null): self
    {
        return new self(
            "Error en comunicación con API Hacienda después de {$attempts} intentos: {$detail}",
            502,
            0,
            $previous
        );
    }

    public static function networkError(string $detail, ?\Throwable $previous = null): self
    {
        return new self(
            "Error de red con API Hacienda: {$detail}",
            502,
            0,
            $previous
        );
    }

    public static function maxRetriesExceeded(int $attempts): self
    {
        return new self(
            "No se pudo completar la petición a API Hacienda después de {$attempts} intentos",
            502
        );
    }

    public static function rateLimitExceeded(int $waitedSeconds): self
    {
        return new self(
            "No se pudo realizar el request debido a rate limiting después de esperar {$waitedSeconds} segundos",
            429
        );
    }

    public static function oauthTokenError(string $detail, int $statusCode = 502): self
    {
        return new self("Error al obtener token OAuth: {$detail}", $statusCode);
    }

    public static function oauthMissingAccessToken(): self
    {
        return new self('Respuesta OAuth no contiene access_token', 502);
    }

    public static function oauthRefreshError(string $detail): self
    {
        return new self("Error al refrescar token OAuth: {$detail}", 502);
    }

    public static function oauthConfigError(string $detail): self
    {
        return new self("Error de configuración OAuth: {$detail}", 500);
    }

    public static function invalidAmbiente(string $ambiente): self
    {
        return new self("Ambiente inválido: {$ambiente}", 422);
    }

    public static function certificadoNoEncontrado(int $certificadoId): self
    {
        return new self("Certificado digital no encontrado (ID: {$certificadoId})", 404);
    }

    public static function certificadoInactivo(): self
    {
        return new self('El certificado digital está inactivo', 422);
    }

    public static function certificadoVencido(string $fechaVencimiento): self
    {
        return new self("El certificado digital está vencido (venció el {$fechaVencimiento})", 422);
    }

    public static function certificadoArchivoNoEncontrado(string $ruta): self
    {
        return new self("Archivo de certificado no encontrado: {$ruta}", 404);
    }

    public static function certificadoLecturaError(): self
    {
        return new self('No se pudo leer el archivo del certificado', 500);
    }

    public static function certificadoPasswordError(string $detail = ''): self
    {
        $message = $detail !== '' ? $detail : 'Error al procesar la contraseña del certificado';
        return new self($message, 422);
    }

    public static function certificadoParseError(string $detail = ''): self
    {
        $message = $detail !== '' ? $detail : 'Error al parsear el certificado';
        return new self($message, 500);
    }

    public static function firmaError(string $detail): self
    {
        return new self("Error al firmar: {$detail}", 500);
    }

    public static function xmlParseError(): self
    {
        return new self('No se pudo cargar el documento XML', 422);
    }

    public static function xmlSinFirma(): self
    {
        return new self('El XML no contiene firma digital', 422);
    }

    public static function xmlFirmaInvalida(): self
    {
        return new self('El nodo de firma no es un elemento válido', 422);
    }

    public static function xmlClavePublicaNoEncontrada(): self
    {
        return new self('No se pudo localizar la clave pública en el XML firmado', 500);
    }

    public static function xmlGeneracionError(): self
    {
        return new self('Error al generar XML firmado', 500);
    }

    public static function claveNumericaInvalida(string $errores): self
    {
        return new self("Clave numérica inválida: {$errores}", 422);
    }

    public static function parametroInvalido(string $detail): self
    {
        return new self($detail, 422);
    }

    public static function pkcs12Error(): self
    {
        return new self('No se pudo obtener el certificado del almacén PKCS12', 500);
    }
}
