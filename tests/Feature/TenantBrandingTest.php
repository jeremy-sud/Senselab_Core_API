<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test: Un usuario puede obtener la configuración de branding.
     */
    public function test_usuario_puede_obtener_branding(): void
    {
        $usuario = $this->createUsuario();
        $empresa = $usuario->empresa;

        // Nombre de empresa por defecto (debe retornar null para company_name)
        $empresa->update([
            'nombre' => 'Nueva Empresa',
            'num_identificacion_dgt' => '',
            'actividad_economica_principal' => '',
            'direccion' => '',
        ]);

        $response = $this->authenticatedJson('GET', '/api/v5/tenant/branding', [], $usuario);

        $response->assertStatus(200)
            ->assertJson([
                'company_name' => null,
                'logo_url' => null,
                'primary_color' => '#6366f1',
            ]);

        // Configurar empresa correctamente
        $empresa->update([
            'nombre' => 'Mi Empresa Real S.A.',
            'num_identificacion_dgt' => '3-101-123456',
            'actividad_economica_principal' => 'Servicios',
            'direccion' => 'San José',
        ]);
        
        \Illuminate\Support\Facades\Auth::forgetUser();
        $usuario->unsetRelation('empresa');

        $response2 = $this->authenticatedJson('GET', '/api/v5/tenant/branding', [], $usuario);

        $response2->assertStatus(200)
            ->assertJson([
                'company_name' => 'Mi Empresa Real S.A.',
                'identificacion' => '3-101-123456',
                'actividad_economica' => 'Servicios',
                'direccion' => 'San José',
            ]);
    }

    /**
     * Test: Un usuario puede actualizar su branding y configurar su empresa.
     */
    public function test_usuario_puede_actualizar_branding(): void
    {
        $usuario = $this->createUsuario();
        
        $response = $this->authenticatedJson('POST', '/api/v5/tenant/branding', [
            'company_name' => 'Senselab Partner S.A.',
            'identificacion' => '3-101-998877',
            'actividad_economica' => 'Tecnología',
            'direccion' => 'San Pedro, Montes de Oca',
            'primary_color' => '#ff5500',
            'logo_url' => 'https://image.url/logo.png',
        ], $usuario);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'company_name' => 'Senselab Partner S.A.',
                'identificacion' => '3-101-998877',
                'actividad_economica' => 'Tecnología',
                'direccion' => 'San Pedro, Montes de Oca',
                'primary_color' => '#ff5500',
                'logo_url' => 'https://image.url/logo.png',
            ]);

        // Verificar cambios en el modelo de la empresa
        $empresa = $usuario->empresa->fresh();
        $this->assertEquals('Senselab Partner S.A.', $empresa->nombre);
        $this->assertEquals('3-101-998877', $empresa->num_identificacion_dgt);
        $this->assertEquals('Tecnología', $empresa->actividad_economica_principal);
        $this->assertEquals('San Pedro, Montes de Oca', $empresa->direccion);
    }
}
