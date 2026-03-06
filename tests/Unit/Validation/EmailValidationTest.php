<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EmailValidationTest extends TestCase
{
    #[Test]
    public function email_valido_pasa_validacion()
    {
        $email = 'test@example.com';
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_sin_arroba_falla()
    {
        $email = 'testexample.com';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_sin_dominio_falla()
    {
        $email = 'test@';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_espacios_falla()
    {
        $email = 'test @example.com';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_caracteres_especiales_validos()
    {
        $email = 'test.user+tag@example.co.uk';
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_guion_bajo_es_valido()
    {
        $email = 'test_user@example.com';
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_numeros_es_valido()
    {
        $email = 'user123@example456.com';
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_vacio_falla()
    {
        $email = '';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_doble_arroba_falla()
    {
        $email = 'test@@example.com';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    #[Test]
    public function email_con_punto_final_falla()
    {
        $email = 'test@example.com.';
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }
}
