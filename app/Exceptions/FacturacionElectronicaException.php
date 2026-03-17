<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de facturación electrónica.
 *
 * FASE 15: Excepciones tipadas para comprobantes electrónicos.
 */
class FacturacionElectronicaException extends DomainException
{
    public static function comprobanteYaAnulado(string $clave): self
    {
        return new self("El comprobante con clave '{$clave}' ya fue anulado", 409);
    }

    public static function comprobanteYaAceptado(string $clave): self
    {
        return new self("El comprobante con clave '{$clave}' ya fue aceptado por Hacienda", 409);
    }

    public static function consecutivoAgotado(string $tipo): self
    {
        return new self("Se agotaron los consecutivos para el tipo '{$tipo}'", 422);
    }

    public static function sinCertificadoActivo(): self
    {
        return new self('No hay certificado digital activo para la empresa', 422);
    }
}
