<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida números de teléfono costarricenses.
 *
 * Acepta formatos: 22223333, +50622223333, +506 2222-3333
 * Internamente valida contra /^[2-8]\d{7}$/ (8 dígitos, inicia 2-8).
 *
 * FASE 15 — DT-5: Regex formatos CR.
 */
class CrTelefono implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('El :attribute debe ser una cadena de texto.');
            return;
        }

        // Eliminar espacios, guiones y paréntesis
        $cleaned = preg_replace('/[\s\-\(\)]+/', '', $value);

        // Remover prefijo +506 si existe
        if (str_starts_with($cleaned, '+506')) {
            $cleaned = substr($cleaned, 4);
        }

        if (!preg_match('/^[2-8]\d{7}$/', $cleaned)) {
            $fail('El :attribute debe ser un número de teléfono costarricense válido (8 dígitos, inicia con 2-8).');
        }
    }
}
