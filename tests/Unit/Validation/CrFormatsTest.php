<?php

namespace Tests\Unit\Validation;

use App\Rules\CrIdentificacion;
use App\Rules\CrTelefono;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests para validaciones de formatos costarricenses — FASE 15 DT-5
 */
class CrFormatsTest extends TestCase
{
    // ─── CrTelefono ──────────────────────────────────────────────

    #[Test]
    #[DataProvider('telefonosValidosProvider')]
    public function telefono_acepta_formatos_validos(string $valor): void
    {
        $failed = false;
        $rule = new CrTelefono();
        $rule->validate('telefono', $valor, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "'{$valor}' debería ser un teléfono CR válido");
    }

    public static function telefonosValidosProvider(): array
    {
        return [
            '8 dígitos celular' => ['88887777'],
            '8 dígitos fijo' => ['22223333'],
            '8 dígitos inicia con 7' => ['70001234'],
            'con +506' => ['+50622223333'],
            'con +506 y espacios' => ['+506 2222 3333'],
            'con guiones' => ['2222-3333'],
            'con +506 y guión' => ['+506 2222-3333'],
        ];
    }

    #[Test]
    #[DataProvider('telefonosInvalidosProvider')]
    public function telefono_rechaza_formatos_invalidos(string $valor): void
    {
        $failed = false;
        $rule = new CrTelefono();
        $rule->validate('telefono', $valor, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, "'{$valor}' debería ser rechazado");
    }

    public static function telefonosInvalidosProvider(): array
    {
        return [
            '7 dígitos' => ['2222333'],
            '9 dígitos' => ['222233334'],
            'inicia con 0' => ['02223333'],
            'inicia con 1' => ['12223333'],
            'inicia con 9' => ['92223333'],
            'solo letras' => ['abcdefgh'],
            'vacío' => [''],
            'código país incorrecto' => ['+50522223333'],
        ];
    }

    // ─── CrIdentificacion ────────────────────────────────────────

    #[Test]
    #[DataProvider('cedulaFisicaValidaProvider')]
    public function cedula_fisica_acepta_formatos_validos(string $valor): void
    {
        $failed = false;
        $rule = new CrIdentificacion('01');
        $rule->validate('numero_identificacion', $valor, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "'{$valor}' debería ser cédula física válida");
    }

    public static function cedulaFisicaValidaProvider(): array
    {
        return [
            '9 dígitos' => ['101230456'],
            'con guiones' => ['1-0123-0456'],
            'otra cédula' => ['304560789'],
        ];
    }

    #[Test]
    #[DataProvider('cedulaFisicaInvalidaProvider')]
    public function cedula_fisica_rechaza_formatos_invalidos(string $valor): void
    {
        $failed = false;
        $rule = new CrIdentificacion('01');
        $rule->validate('numero_identificacion', $valor, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, "'{$valor}' debería ser rechazado como cédula física");
    }

    public static function cedulaFisicaInvalidaProvider(): array
    {
        return [
            '8 dígitos' => ['12345678'],
            '10 dígitos' => ['1234567890'],
            'con letras' => ['12345678A'],
        ];
    }

    #[Test]
    public function cedula_juridica_acepta_10_digitos_con_3(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('02');
        $rule->validate('id', '3101123456', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[Test]
    public function cedula_juridica_rechaza_sin_prefijo_3(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('02');
        $rule->validate('id', '1101123456', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    #[Test]
    public function dimex_acepta_11_y_12_digitos(): void
    {
        foreach (['12345678901', '123456789012'] as $valor) {
            $failed = false;
            $rule = new CrIdentificacion('03');
            $rule->validate('id', $valor, function () use (&$failed) {
                $failed = true;
            });
            $this->assertFalse($failed, "DIMEX '{$valor}' debería ser válido");
        }
    }

    #[Test]
    public function nite_acepta_10_digitos(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('04');
        $rule->validate('id', '1234567890', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[Test]
    public function tipo_texto_mapea_a_codigo_dgt(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('fisica');
        $rule->validate('id', '101230456', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "'fisica' debería mapearse a tipo 01");
    }

    #[Test]
    public function tipo_desconocido_no_falla(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('tipo_inventado');
        $rule->validate('id', 'WHATEVER-123', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "Tipo desconocido debería pasar sin validar formato");
    }

    // ─── Brecha #37: Tipos 05 (Extranjero) y 06 (No Contribuyente) ──────

    #[Test]
    public function extranjero_no_domiciliado_acepta_alfanumerico(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('05');
        $rule->validate('id', 'PASS12345678', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[Test]
    public function extranjero_no_domiciliado_rechaza_caracteres_especiales(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('05');
        $rule->validate('id', 'PASS@12345!', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Tipo 05 no debe aceptar caracteres especiales como @, !');
    }

    #[Test]
    public function extranjero_no_domiciliado_rechaza_mas_de_20_caracteres(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('05');
        $rule->validate('id', 'ABCDEFGHIJKLMNOPQRSTU', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Tipo 05 no debe aceptar más de 20 caracteres');
    }

    #[Test]
    public function no_contribuyente_acepta_alfanumerico(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('06');
        $rule->validate('id', 'NC2026001234', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[Test]
    public function tipo_texto_extranjero_mapea_a_05(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('extranjero');
        $rule->validate('id', 'EXT123456789', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "'extranjero' debería mapearse a tipo 05");
    }

    #[Test]
    public function tipo_texto_no_contribuyente_mapea_a_06(): void
    {
        $failed = false;
        $rule = new CrIdentificacion('no_contribuyente');
        $rule->validate('id', 'NC001234', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "'no_contribuyente' debería mapearse a tipo 06");
    }
}
