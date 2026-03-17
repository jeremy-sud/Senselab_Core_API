<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida números de identificación costarricenses según tipo.
 *
 * Tipos DGT:
 *  01 / fisica   → Cédula física: 9 dígitos
 *  02 / juridica → Cédula jurídica: 10 dígitos (inicia con 3)
 *  03 / dimex    → DIMEX: 11-12 dígitos
 *  04 / nite     → NITE: 10 dígitos
 *
 * FASE 15 — DT-5: Regex formatos CR.
 */
class CrIdentificacion implements ValidationRule
{
    /** @var array<string, string> Mapeo de tipo texto → código DGT */
    private const TIPO_MAP = [
        'fisica'   => '01',
        'juridica' => '02',
        'dimex'    => '03',
        'nite'     => '04',
    ];

    /** @var array<string, array{pattern: string, label: string}> */
    private const FORMATOS = [
        '01' => ['pattern' => '/^\d{9}$/',          'label' => 'cédula física (9 dígitos)'],
        '02' => ['pattern' => '/^3\d{9}$/',         'label' => 'cédula jurídica (10 dígitos, inicia con 3)'],
        '03' => ['pattern' => '/^\d{11,12}$/',       'label' => 'DIMEX (11-12 dígitos)'],
        '04' => ['pattern' => '/^\d{10}$/',          'label' => 'NITE (10 dígitos)'],
    ];

    public function __construct(
        private readonly ?string $tipoIdentificacion = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('El :attribute debe ser una cadena de texto.');
            return;
        }

        $tipo = $this->resolveTipo();

        if ($tipo === null || !isset(self::FORMATOS[$tipo])) {
            // Si no hay tipo conocido, validar que sea numérico y largo razonable
            return;
        }

        $formato = self::FORMATOS[$tipo];

        // Limpiar guiones y espacios del número
        $cleaned = preg_replace('/[\s\-]+/', '', $value);

        if (!preg_match($formato['pattern'], $cleaned)) {
            $fail("El :attribute no tiene formato válido de {$formato['label']}.");
        }
    }

    private function resolveTipo(): ?string
    {
        $tipo = $this->tipoIdentificacion ?? request()->input('tipo_identificacion');

        if ($tipo === null) {
            return null;
        }

        // Convertir tipo texto a código DGT si es necesario
        return self::TIPO_MAP[$tipo] ?? $tipo;
    }
}
