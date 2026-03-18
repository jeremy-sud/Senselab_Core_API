<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\FeCertificadoDigital;
use App\Services\FeCertificadoDigitalService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeCertificadoDigitalServiceTest extends TestCase
{
    protected FeCertificadoDigitalService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeCertificadoDigitalService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearCertificado(array $override = []): FeCertificadoDigital
    {
        return FeCertificadoDigital::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Certificado Test ' . uniqid(),
            'tipo' => 'p12',
            'ruta_archivo' => 'certificates/test_' . uniqid() . '.p12',
            'fecha_vencimiento' => now()->addYear(),
            'activo' => true,
            'valido' => true,
            'ambiente' => 'produccion',
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listarTodos_retorna_certificados_de_empresa(): void
    {
        $this->crearCertificado();
        $this->crearCertificado();

        $otraEmpresa = $this->createEmpresa(['email' => uniqid() . '@empresa.com']);
        FeCertificadoDigital::create([
            'empresa_id' => $otraEmpresa->id,
            'nombre' => 'Otro',
            'tipo' => 'p12',
            'ruta_archivo' => 'certificates/otro.p12',
            'fecha_vencimiento' => now()->addYear(),
            'activo' => true,
            'ambiente' => 'produccion',
        ]);

        $resultado = $this->service->listarTodos(['empresa_id' => $this->empresa->id]);

        $this->assertCount(2, $resultado);
    }

    #[Test]
    public function listarTodos_filtra_por_activo(): void
    {
        $this->crearCertificado(['activo' => true]);
        $this->crearCertificado(['activo' => false]);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
        ]);

        foreach ($resultado as $cert) {
            $this->assertTrue((bool) $cert->activo);
        }
    }

    #[Test]
    public function listarTodos_filtra_por_ambiente(): void
    {
        $this->crearCertificado(['ambiente' => 'produccion']);
        $this->crearCertificado(['ambiente' => 'sandbox']);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'ambiente' => 'produccion',
        ]);

        foreach ($resultado as $cert) {
            $this->assertEquals('produccion', $cert->ambiente);
        }
    }

    #[Test]
    public function listarTodos_filtra_solo_vigentes(): void
    {
        $this->crearCertificado(['fecha_vencimiento' => now()->addYear()]);
        $this->crearCertificado(['fecha_vencimiento' => now()->subDay()]);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'solo_vigentes' => true,
        ]);

        $this->assertCount(1, $resultado);
    }

    #[Test]
    public function listarTodos_filtra_por_dias_vencimiento(): void
    {
        $this->crearCertificado(['fecha_vencimiento' => now()->addDays(10)]);
        $this->crearCertificado(['fecha_vencimiento' => now()->addYear()]);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'dias_vencimiento' => 30,
        ]);

        $this->assertCount(1, $resultado);
    }

    // ─── crear() ───────────────────────────────────────────────

    #[Test]
    public function crear_certificado(): void
    {
        $certificado = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Nuevo Certificado',
            'tipo' => 'p12',
            'ruta_archivo' => 'certificates/nuevo.p12',
            'fecha_vencimiento' => now()->addYear(),
            'activo' => false,
            'ambiente' => 'produccion',
        ]);

        $this->assertDatabaseHas('fe_certificados_digitales', [
            'id' => $certificado->id,
            'nombre' => 'Nuevo Certificado',
        ]);
    }

    #[Test]
    public function crear_desactiva_otros_del_mismo_ambiente_si_activo(): void
    {
        $existente = $this->crearCertificado(['activo' => true, 'ambiente' => 'produccion']);

        $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Nuevo Activo',
            'tipo' => 'p12',
            'ruta_archivo' => 'certificates/nuevo.p12',
            'fecha_vencimiento' => now()->addYear(),
            'activo' => true,
            'ambiente' => 'produccion',
        ]);

        $this->assertFalse((bool) $existente->fresh()->activo);
    }

    // ─── actualizar() ──────────────────────────────────────────

    #[Test]
    public function actualizar_certificado(): void
    {
        $certificado = $this->crearCertificado(['nombre' => 'Original']);

        $this->service->actualizar($certificado, ['nombre' => 'Actualizado']);

        $this->assertEquals('Actualizado', $certificado->fresh()->nombre);
    }

    #[Test]
    public function actualizar_desactiva_otros_al_activar(): void
    {
        $cert1 = $this->crearCertificado(['activo' => true, 'ambiente' => 'produccion']);
        $cert2 = $this->crearCertificado(['activo' => false, 'ambiente' => 'produccion']);

        $this->service->actualizar($cert2, ['activo' => true]);

        $this->assertFalse((bool) $cert1->fresh()->activo);
        $this->assertTrue((bool) $cert2->fresh()->activo);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_borra_certificado_soft_delete(): void
    {
        Storage::fake('private');
        $certificado = $this->crearCertificado(['ruta_archivo' => 'certificates/archivo.p12']);

        $this->service->eliminar($certificado);

        $this->assertNull(FeCertificadoDigital::find($certificado->id));
        $deleted = FeCertificadoDigital::withTrashed()->find($certificado->id);
        $this->assertNotNull($deleted);
        $this->assertTrue($deleted->trashed());
    }

    // ─── activar() ──────────────────────────────────────────────

    #[Test]
    public function activar_certificado_exitoso(): void
    {
        $cert1 = $this->crearCertificado(['activo' => true, 'ambiente' => 'produccion']);
        $cert2 = $this->crearCertificado(['activo' => false, 'ambiente' => 'produccion']);

        $this->service->activar($cert2, $this->empresa->id);

        $this->assertFalse((bool) $cert1->fresh()->activo);
        $this->assertTrue((bool) $cert2->fresh()->activo);
    }

    #[Test]
    public function activar_certificado_vencido_lanza_excepcion(): void
    {
        $cert = $this->crearCertificado([
            'activo' => false,
            'fecha_vencimiento' => now()->subDay(),
        ]);

        $this->expectException(BusinessException::class);
        $this->service->activar($cert, $this->empresa->id);
    }

    // ─── desactivar() ──────────────────────────────────────────

    #[Test]
    public function desactivar_pone_activo_false(): void
    {
        $cert = $this->crearCertificado(['activo' => true]);

        $this->service->desactivar($cert);

        $this->assertFalse((bool) $cert->fresh()->activo);
    }

    // ─── obtenerActivo() ────────────────────────────────────────

    #[Test]
    public function obtenerActivo_retorna_certificado(): void
    {
        $this->crearCertificado(['activo' => true, 'ambiente' => 'produccion', 'fecha_vencimiento' => now()->addYear()]);

        $resultado = $this->service->obtenerActivo($this->empresa->id, 'produccion');

        $this->assertNotNull($resultado);
        $this->assertTrue((bool) $resultado->activo);
    }

    #[Test]
    public function obtenerActivo_retorna_null_si_no_hay(): void
    {
        $resultado = $this->service->obtenerActivo($this->empresa->id, 'produccion');

        $this->assertNull($resultado);
    }

    // ─── proximosVencer() ───────────────────────────────────────

    #[Test]
    public function proximosVencer_retorna_certificados_proximos(): void
    {
        $this->crearCertificado(['fecha_vencimiento' => now()->addDays(10)]);
        $this->crearCertificado(['fecha_vencimiento' => now()->addYear()]);

        $resultado = $this->service->proximosVencer($this->empresa->id, 30);

        $this->assertCount(1, $resultado);
    }
}
