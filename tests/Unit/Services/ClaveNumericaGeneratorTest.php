<?php

namespace Tests\Unit\Services;

use App\Services\Hacienda\ClaveNumericaGenerator;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Tests para ClaveNumericaGenerator
 * 
 * Valida:
 * - Generación correcta de clave numérica 50 posiciones
 * - Formato de cada segmento
 * - Validación de claves existentes
 * - Extracción de información
 * - Generación múltiple
 */
class ClaveNumericaGeneratorTest extends TestCase
{
    protected ClaveNumericaGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ClaveNumericaGenerator();
    }

    /** @test */
    public function puede_generar_clave_numerica_valida()
    {
        $fecha = Carbon::parse('2025-11-26');
        $cedula = '310112345678';
        $consecutivo = '1';
        $situacion = '1';

        $clave = $this->generator->generar($fecha, $cedula, $consecutivo, $situacion);

        // Verificar longitud
        $this->assertEquals(50, strlen($clave));
        
        // Verificar que es numérico
        $this->assertMatchesRegularExpression('/^\d{50}$/', $clave);
        
        // Verificar país (Costa Rica = 5)
        $this->assertEquals('5', substr($clave, 0, 1));
        
        // Verificar fecha (26112025 = ddmmyyyy)
        $this->assertEquals('26112025', substr($clave, 1, 8));
        
        // Verificar cédula (12 dígitos)
        $this->assertEquals('310112345678', substr($clave, 9, 12));
        
        // Verificar consecutivo (20 dígitos con padding)
        $this->assertEquals('00000000000000000001', substr($clave, 21, 20));
        
        // Verificar situación
        $this->assertEquals('1', substr($clave, 41, 1));
        
        // Verificar código seguridad (8 dígitos)
        $codigoSeguridad = substr($clave, 42, 8);
        $this->assertEquals(8, strlen($codigoSeguridad));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $codigoSeguridad);
    }

    /** @test */
    public function valida_clave_correctamente()
    {
        $claveValida = '52611202531011234567800000000000000000001154489877';
        
        $resultado = $this->generator->validar($claveValida);
        
        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    /** @test */
    public function detecta_clave_invalida_por_longitud()
    {
        $claveInvalida = '123456789'; // Muy corta
        
        $resultado = $this->generator->validar($claveInvalida);
        
        $this->assertFalse($resultado['valido']);
        $this->assertContains('La clave debe tener exactamente 50 caracteres', $resultado['errores']);
    }

    /** @test */
    public function detecta_clave_invalida_por_formato()
    {
        $claveInvalida = 'ABCDEFGHIJ' . str_repeat('0', 40); // Con letras
        
        $resultado = $this->generator->validar($claveInvalida);
        
        $this->assertFalse($resultado['valido']);
        $this->assertContains('La clave debe contener solo números', $resultado['errores']);
    }

    /** @test */
    public function detecta_pais_invalido()
    {
        // Clave con país 9 (inválido, debería ser 5)
        $claveInvalida = '9' . str_repeat('0', 49);
        
        $resultado = $this->generator->validar($claveInvalida);
        
        $this->assertFalse($resultado['valido']);
        $this->assertContains('Código de país inválido', $resultado['errores']);
    }

    /** @test */
    public function extrae_informacion_correctamente()
    {
        $clave = '52611202531011234567800000000000000000001154489877';
        
        $info = $this->generator->extraerInformacion($clave);
        
        $this->assertEquals('5', $info['pais']);
        $this->assertEquals('26112025', $info['fecha']);
        $this->assertEquals('310112345678', $info['cedula']);
        $this->assertEquals('00000000000000000001', $info['consecutivo']);
        $this->assertEquals('1', $info['situacion']);
        $this->assertEquals('54489877', $info['codigo_seguridad']);
        $this->assertInstanceOf(Carbon::class, $info['fecha_emision']);
    }

    /** @test */
    public function puede_generar_multiples_claves_consecutivas()
    {
        $fecha = Carbon::parse('2025-11-26');
        $cedula = '310112345678';
        $consecutivoInicio = 1;
        $cantidad = 5;
        $situacion = '1';

        $claves = $this->generator->generarMultiples(
            $fecha,
            $cedula,
            $consecutivoInicio,
            $cantidad,
            $situacion
        );

        $this->assertCount(5, $claves);
        
        // Verificar que todas son únicas
        $this->assertEquals(5, count(array_unique($claves)));
        
        // Verificar que los consecutivos son incrementales
        for ($i = 0; $i < $cantidad; $i++) {
            $consecutivoEsperado = str_pad($consecutivoInicio + $i, 20, '0', STR_PAD_LEFT);
            $consecutivoReal = substr($claves[$i], 21, 20);
            $this->assertEquals($consecutivoEsperado, $consecutivoReal);
        }
    }

    /** @test */
    public function rechaza_fecha_futura()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La fecha no puede ser futura');

        $fechaFutura = Carbon::now()->addDay();
        $this->generator->generar($fechaFutura, '310112345678', '1', '1');
    }

    /** @test */
    public function rechaza_fecha_muy_antigua()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La fecha no puede ser mayor a 10 años atrás');

        $fechaAntigua = Carbon::now()->subYears(11);
        $this->generator->generar($fechaAntigua, '310112345678', '1', '1');
    }

    /** @test */
    public function rechaza_cedula_muy_larga()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La cédula no puede exceder 12 dígitos');

        $cedulaLarga = '1234567890123'; // 13 dígitos
        $this->generator->generar(Carbon::now(), $cedulaLarga, '1', '1');
    }

    /** @test */
    public function rechaza_consecutivo_muy_largo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El consecutivo no puede exceder 20 dígitos');

        $consecutivoLargo = '123456789012345678901'; // 21 dígitos
        $this->generator->generar(Carbon::now(), '310112345678', $consecutivoLargo, '1');
    }

    /** @test */
    public function rechaza_situacion_invalida()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La situación debe ser 1, 2 o 3');

        $this->generator->generar(Carbon::now(), '310112345678', '1', '5');
    }

    /** @test */
    public function formatea_cedula_con_padding()
    {
        $fecha = Carbon::parse('2025-11-26');
        $cedulaCorta = '123'; // Solo 3 dígitos
        
        $clave = $this->generator->generar($fecha, $cedulaCorta, '1', '1');
        
        // Debería tener padding a la izquierda
        $cedulaFormateada = substr($clave, 9, 12);
        $this->assertEquals('000000000123', $cedulaFormateada);
    }

    /** @test */
    public function codigo_seguridad_es_aleatorio()
    {
        $fecha = Carbon::now();
        $cedula = '310112345678';
        
        $clave1 = $this->generator->generar($fecha, $cedula, '1', '1');
        $clave2 = $this->generator->generar($fecha, $cedula, '1', '1');
        
        // Los códigos de seguridad deberían ser diferentes
        $codigo1 = substr($clave1, 42, 8);
        $codigo2 = substr($clave2, 42, 8);
        
        $this->assertNotEquals($codigo1, $codigo2);
    }

    /** @test */
    public function situacion_normal_es_1()
    {
        $clave = $this->generator->generar(Carbon::now(), '310112345678', '1', '1');
        $situacion = substr($clave, 41, 1);
        $this->assertEquals('1', $situacion);
    }

    /** @test */
    public function situacion_contingencia_es_2()
    {
        $clave = $this->generator->generar(Carbon::now(), '310112345678', '1', '2');
        $situacion = substr($clave, 41, 1);
        $this->assertEquals('2', $situacion);
    }

    /** @test */
    public function situacion_sin_internet_es_3()
    {
        $clave = $this->generator->generar(Carbon::now(), '310112345678', '1', '3');
        $situacion = substr($clave, 41, 1);
        $this->assertEquals('3', $situacion);
    }

    /** @test */
    public function no_genera_mas_de_1000_claves_multiples()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se pueden generar más de 1000 claves a la vez');

        $this->generator->generarMultiples(
            Carbon::now(),
            '310112345678',
            1,
            1001, // Excede límite
            '1'
        );
    }
}
