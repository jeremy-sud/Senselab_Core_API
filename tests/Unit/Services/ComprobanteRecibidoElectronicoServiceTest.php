<?php

namespace Tests\Unit\Services;

use App\Models\ComprobanteRecibidoElectronico;
use App\Services\ComprobanteRecibidoElectronicoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComprobanteRecibidoElectronicoServiceTest extends TestCase
{
    protected ComprobanteRecibidoElectronicoService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComprobanteRecibidoElectronicoService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearComprobante(array $override = []): ComprobanteRecibidoElectronico
    {
        $comprobante = new ComprobanteRecibidoElectronico(array_merge([
            'clave_numerica' => uniqid() . rand(1000, 9999),
            'fecha_emision' => now(),
            'tipo_documento' => '01',
            'numero_cedula_emisor' => '123456789',
            'nombre_emisor' => 'Proveedor Test',
            'monto_total' => 50000.00,
            'monto_impuesto' => 6500.00,
            'moneda' => 'CRC',
            'estado_validacion' => 'Recibido',
            'activo' => true,
            'eliminado' => false,
        ], $override));
        $comprobante->empresa_id = $this->empresa->id;
        $comprobante->saveQuietly();

        return $comprobante->fresh();
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearComprobante();
        $this->crearComprobante();

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_empresa_id(): void
    {
        $this->crearComprobante();

        $otraEmpresa = $this->createEmpresa(['email' => uniqid() . '@empresa.com']);
        $otro = new ComprobanteRecibidoElectronico([
            'clave_numerica' => uniqid() . rand(1000, 9999),
            'fecha_emision' => now(),
            'tipo_documento' => '01',
            'numero_cedula_emisor' => '987654321',
            'nombre_emisor' => 'Otro Proveedor',
            'monto_total' => 10000.00,
            'moneda' => 'CRC',
            'activo' => true,
            'eliminado' => false,
        ]);
        $otro->empresa_id = $otraEmpresa->id;
        $otro->saveQuietly();

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals($this->empresa->id, $item->empresa_id);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_asigna_defaults(): void
    {
        $comprobante = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'clave_numerica' => 'CLAVE_' . uniqid(),
            'fecha_emision' => now()->toDateString(),
            'tipo_documento' => '01',
            'numero_cedula_emisor' => '111222333',
            'nombre_emisor' => 'Emisor Nuevo',
            'monto_total' => 25000.00,
        ]);

        $fresh = $comprobante->fresh();
        $this->assertEquals('CRC', $fresh->moneda);
    }

    // ─── obtenerPorEmpresa() ────────────────────────────────────

    #[Test]
    public function obtenerPorEmpresa_encuentra_comprobante(): void
    {
        $comprobante = $this->crearComprobante();

        $resultado = $this->service->obtenerPorEmpresa($this->empresa->id, $comprobante->id);

        $this->assertEquals($comprobante->id, $resultado->id);
    }

    #[Test]
    public function obtenerPorEmpresa_lanza_excepcion_si_no_existe(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtenerPorEmpresa($this->empresa->id, 99999);
    }

    #[Test]
    public function obtenerPorEmpresa_lanza_excepcion_si_otra_empresa(): void
    {
        $comprobante = $this->crearComprobante();
        $otraEmpresa = $this->createEmpresa(['email' => uniqid() . '@empresa.com']);

        $this->expectException(ModelNotFoundException::class);

        $this->service->obtenerPorEmpresa($otraEmpresa->id, $comprobante->id);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_comprobante(): void
    {
        $comprobante = $this->crearComprobante();

        $result = $this->service->eliminar($comprobante);

        $this->assertTrue($result);
        $this->assertNull(ComprobanteRecibidoElectronico::withoutGlobalScopes()->find($comprobante->id));
    }

    // ─── pendientes() ───────────────────────────────────────────

    #[Test]
    public function pendientes_retorna_coleccion(): void
    {
        $this->crearComprobante();

        $resultado = $this->service->pendientes($this->empresa->id);

        $this->assertIsIterable($resultado);
    }

}
