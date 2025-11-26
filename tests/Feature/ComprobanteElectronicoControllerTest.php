<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\FeCertificadoDigital;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\Hacienda\EnviarComprobanteJob;

/**
 * Tests de integración para ComprobanteElectronicoController
 * 
 * Valida:
 * - Endpoints CRUD
 * - Creación de comprobantes con líneas
 * - Validación de requests
 * - Autorización por empresa
 * - Jobs asíncronos
 */
class ComprobanteElectronicoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $user;
    protected Empresa $empresa;
    protected FeCertificadoDigital $certificado;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear empresa y usuario
        $this->empresa = Empresa::factory()->create();
        $this->user = Usuario::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);
        
        // Crear permisos necesarios
        $this->seedPermisos();
        
        // Crear rol con permisos
        $rol = \App\Models\Rol::create([
            'nombre' => 'Admin Test',
            'descripcion' => 'Rol para tests',
            'activo' => true,
        ]);
        
        // Asignar permisos al rol
        $permisos = \App\Models\Permiso::whereIn('slug', [
            'ver-facturacion_electronica',
            'crear-facturacion_electronica',
            'editar-facturacion_electronica',
            'eliminar-facturacion_electronica',
        ])->pluck('id');
        
        $rol->permisos()->attach($permisos);
        
        // Asignar rol al usuario
        $this->user->roles()->attach($rol->id);

        // Crear certificado digital de prueba
        $this->certificado = FeCertificadoDigital::factory()->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
            'fecha_vencimiento' => now()->addYear(),
        ]);
    }

    /** @test */
    public function puede_listar_comprobantes()
    {
        ComprobanteElectronicoFe::factory()->count(3)->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/comprobantes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'tipo_documento',
                        'clave',
                        'consecutivo',
                        'estado',
                        'fecha_emision',
                        'total_comprobante',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    /** @test */
    public function puede_filtrar_comprobantes_por_estado()
    {
        ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'aceptado',
        ]);

        ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/comprobantes?estado=aceptado');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    /** @test */
    public function puede_crear_comprobante_con_lineas()
    {
        Queue::fake();

        $data = [
            'tipo_documento' => '01',
            'consecutivo' => '00000000000000000001',
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'receptor_nombre' => 'Cliente Test',
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543',
            'certificado_id' => $this->certificado->id,
            'lineas' => [
                [
                    'numero_linea' => 1,
                    'codigo' => '8523102100000',
                    'cantidad' => 2,
                    'detalle' => 'Producto Test',
                    'precio_unitario' => 10000,
                    'monto_total' => 20000,
                    'subtotal' => 20000,
                    'monto_total_linea' => 22600,
                    'impuestos' => [
                        [
                            'codigo' => '01',
                            'codigo_tarifa' => '08',
                            'tarifa' => 13.00,
                            'monto' => 2600,
                        ]
                    ],
                ]
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/comprobantes', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'tipo_documento',
                    'consecutivo',
                    'estado',
                    'lineas_detalle',
                ]
            ]);

        // Verificar que se creó en BD
        $this->assertDatabaseHas('comprobantes_electronicos_fe', [
            'tipo_documento' => '01',
            'consecutivo' => '00000000000000000001',
            'estado' => 'pendiente',
        ]);

        // Verificar que se creó la línea
        $this->assertDatabaseHas('fe_lineas_detalle', [
            'numero_linea' => 1,
            'detalle' => 'Producto Test',
        ]);

        // Verificar que se disparó el job
        Queue::assertPushed(EnviarComprobanteJob::class);
    }

    /** @test */
    public function valida_campos_requeridos()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/comprobantes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'tipo_documento',
                'consecutivo',
                'condicion_venta',
                'medio_pago',
                'lineas',
                'certificado_id',
            ]);
    }

    /** @test */
    public function valida_tipo_documento()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/comprobantes', [
                'tipo_documento' => '99', // Inválido
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_documento']);
    }

    /** @test */
    public function valida_minimo_una_linea()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/comprobantes', [
                'tipo_documento' => '01',
                'consecutivo' => '1',
                'condicion_venta' => '01',
                'medio_pago' => '01',
                'certificado_id' => $this->certificado->id,
                'lineas' => [], // Vacío
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lineas']);
    }

    /** @test */
    public function puede_obtener_comprobante_especifico()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/comprobantes/{$comprobante->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $comprobante->id,
                'tipo_documento' => $comprobante->tipo_documento,
            ]);
    }

    /** @test */
    public function no_puede_ver_comprobante_de_otra_empresa()
    {
        $otraEmpresa = Empresa::factory()->create();
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $otraEmpresa->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/comprobantes/{$comprobante->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function puede_descargar_xml_firmado()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'xml_firmado' => '<?xml version="1.0"?><Factura>test</Factura>',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/comprobantes/{$comprobante->id}/xml?tipo=firmado");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertHeader('Content-Disposition', "attachment; filename=\"{$comprobante->clave}_firmado.xml\"");
    }

    /** @test */
    public function puede_reenviar_comprobante_en_error()
    {
        Queue::fake();

        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'error',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/comprobantes/{$comprobante->id}/reenviar", [
                'certificado_id' => $this->certificado->id,
            ]);

        $response->assertOk();

        $comprobante->refresh();
        $this->assertEquals('pendiente', $comprobante->estado);

        Queue::assertPushed(EnviarComprobanteJob::class);
    }

    /** @test */
    public function no_puede_reenviar_comprobante_aceptado()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'aceptado',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/comprobantes/{$comprobante->id}/reenviar", [
                'certificado_id' => $this->certificado->id,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function puede_anular_comprobante()
    {
        Queue::fake();

        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'estado' => 'aceptado',
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/comprobantes/{$comprobante->id}/anular", [
                'razon_anulacion' => 'Error en facturación',
                'certificado_id' => $this->certificado->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'tipo_documento'],
            ]);

        // Verificar que se creó nota crédito
        $notaCredito = ComprobanteElectronicoFe::where('tipo_documento', '03')
            ->latest()
            ->first();
            
        $this->assertNotNull($notaCredito);
        $this->assertEquals('03', $notaCredito->tipo_documento);
        
        // Verificar referencia en metadata
        $this->assertNotNull($notaCredito->metadata);
        $this->assertArrayHasKey('documento_referencia', $notaCredito->metadata);
        $this->assertEquals('01', $notaCredito->metadata['documento_referencia']['tipo_documento']);
        $this->assertEquals('Error en facturación', $notaCredito->metadata['documento_referencia']['razon']);

        Queue::assertPushed(EnviarComprobanteJob::class);
    }

    /** @test */
    public function puede_obtener_estadisticas()
    {
        ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'aceptado',
            'total_comprobante' => 50000,
        ]);

        ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'pendiente',
            'total_comprobante' => 30000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/comprobantes/estadisticas/resumen');

        $response->assertOk()
            ->assertJsonStructure([
                'total_comprobantes',
                'por_estado',
                'por_tipo',
                'total_ventas',
            ]);

        $this->assertEquals(2, $response->json('total_comprobantes'));
        $this->assertEquals(50000, $response->json('total_ventas'));
    }

    /** @test */
    public function requiere_autenticacion()
    {
        $response = $this->getJson('/api/comprobantes');
        $response->assertUnauthorized();
    }
}
