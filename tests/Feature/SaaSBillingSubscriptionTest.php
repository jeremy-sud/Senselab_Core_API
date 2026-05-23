<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Subscription;
use App\Models\TenantUsage;
use App\Models\Venta;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(\App\Http\Controllers\API\TenantSubscriptionController::class)]
#[CoversClass(\App\Http\Middleware\EnforceTenantPlanLimits::class)]
#[Group('billing')]
#[Group('saas')]
class SaaSBillingSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private Usuario $usuario;
    private string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();

        // Crear empresa de prueba
        $this->empresa = $this->createEmpresa([
            'nombre' => 'Test Company S.A.',
            'num_identificacion_dgt' => '3-101-999999',
            'email' => 'billing-test@scisenselab.com',
        ]);

        // Crear usuario administrador
        $this->usuario = $this->createUsuario([
            'empresa_id' => $this->empresa->id,
            'email' => 'titular@scisenselab.com',
            'nombre' => 'Jeremy',
            'apellidos' => 'Arias',
        ], ['Administrador']);

        $this->tenantId = 'sl_tenant_' . str_pad((string)$this->empresa->id, 6, '0', STR_PAD_LEFT);
    }

    #[Test]
    public function test_can_get_tenant_profile_with_default_starter_plan()
    {
        $response = $this->authenticatedJson('GET', '/api/v5/user/profile', [], $this->usuario);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'user' => [
                'id',
                'name',
                'email',
                'company_name',
                'twofa_enabled',
                'linked_platforms',
                'subscription' => [
                    'plan',
                    'status',
                    'price',
                    'billing_period',
                    'usage' => [
                        'active_users' => ['used', 'max'],
                        'invoices_month' => ['used', 'max'],
                        'ai_queries_month' => ['used', 'max']
                    ]
                ]
            ]
        ]);

        $response->assertJsonPath('user.subscription.plan', 'starter');
        $response->assertJsonPath('user.subscription.usage.active_users.max', 1);
        $response->assertJsonPath('user.subscription.usage.invoices_month.max', 50);
        $response->assertJsonPath('user.subscription.usage.ai_queries_month.max', 10);
    }

    #[Test]
    public function test_can_upgrade_suscripcion_plan()
    {
        // Realizar upgrade a plan Pro
        $responseUpgrade = $this->authenticatedJson('POST', '/api/v5/billing/subscription/upgrade', [
            'plan' => 'pro'
        ], $this->usuario);

        $responseUpgrade->assertStatus(200);
        $responseUpgrade->assertJsonPath('success', true);

        // Recuperar perfil y verificar nuevos límites incrementados
        $responseProfile = $this->authenticatedJson('GET', '/api/v5/user/profile', [], $this->usuario);
        
        $responseProfile->assertStatus(200);
        $responseProfile->assertJsonPath('user.subscription.plan', 'pro');
        $responseProfile->assertJsonPath('user.subscription.usage.active_users.max', 3);
        $responseProfile->assertJsonPath('user.subscription.usage.invoices_month.max', 250);
        $responseProfile->assertJsonPath('user.subscription.usage.ai_queries_month.max', 100);
    }

    #[Test]
    public function test_enforces_active_users_limit_on_starter_plan()
    {
        // En el plan Starter el límite de usuarios activos es 1.
        // Como ya tenemos $this->usuario activo (1 usuario), intentar registrar otro usuario debería fallar.
        
        $response = $this->authenticatedJson('POST', '/api/usuarios', [
            'nombre' => 'Segundo',
            'apellidos' => 'Usuario',
            'email' => 'segundo@scisenselab.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'empresa_id' => $this->empresa->id,
            'cargo_id' => null,
            'activo' => true,
        ], $this->usuario);

        $response->assertStatus(402); // Límite excedido
        $response->assertJsonPath('error_code', 'PLAN_LIMIT_USERS_EXCEEDED');
    }

    #[Test]
    public function test_enforces_monthly_invoices_limit_on_starter_plan()
    {
        // Forzar límite de facturación mensual a 0 para esta prueba
        Subscription::updateOrCreate(
            ['tenant_id' => $this->tenantId],
            [
                'empresa_id' => $this->empresa->id,
                'usuario_id' => $this->usuario->id,
                'plan' => 'starter',
                'status' => 'active',
                'max_users' => 1,
                'max_invoices_month' => 0, // 0 facturas máximo
                'max_ai_queries_month' => 10,
                'current_period_end' => now()->addYear()
            ]
        );

        $response = $this->authenticatedJson('POST', '/api/ventas', [
            'nombre_cliente' => 'Cliente Test',
            'num_identificacion_cliente' => '1-1111-1111',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.0,
            'detalles' => [
                [
                    'producto_id' => $this->getProductMock()->id,
                    'cantidad' => 1,
                    'precio_unitario' => 1000.0,
                    'porcentaje_impuesto' => 13.0,
                ]
            ]
        ], $this->usuario);

        $response->assertStatus(402);
        $response->assertJsonPath('error_code', 'PLAN_LIMIT_INVOICES_EXCEEDED');
    }

    #[Test]
    public function test_enforces_ai_queries_limit_on_starter_plan()
    {
        // Forzar límite de consultas de IA a 0 para esta prueba
        Subscription::updateOrCreate(
            ['tenant_id' => $this->tenantId],
            [
                'empresa_id' => $this->empresa->id,
                'usuario_id' => $this->usuario->id,
                'plan' => 'starter',
                'status' => 'active',
                'max_users' => 1,
                'max_invoices_month' => 50,
                'max_ai_queries_month' => 0, // 0 consultas de IA máximo
                'current_period_end' => now()->addYear()
            ]
        );

        $response = $this->authenticatedJson('POST', '/api/ai/chat', [
            'message' => 'Hola, asistente Senselab'
        ], $this->usuario);

        $response->assertStatus(402);
        $response->assertJsonPath('error_code', 'PLAN_LIMIT_AI_EXCEEDED');
    }

    private function getProductMock(): Producto
    {
        return $this->createProducto([
            'nombre' => 'Producto Dummy',
            'precio_venta' => 1000.0,
        ], $this->empresa);
    }
}
