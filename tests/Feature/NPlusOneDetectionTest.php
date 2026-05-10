<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Venta;

class NPlusOneDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seedRoles();
        $this->seedPermisos();
        
        // Desactivamos la restricción de Lazy Loading que tira excepciones en Testing
        // para que nosotros mismos podamos contar los queries y hacer la aserción
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
    }

    /**
     * Test para verificar que el endpoint de listar ventas no sufre de N+1
     */
    public function test_ventas_index_does_not_suffer_from_n_plus_one_queries()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        
        // Creamos 2 ventas
        Venta::factory()->count(2)->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado_venta' => 'Completada',
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->authenticatedJson('GET', '/api/ventas?per_page=2', [], $usuario);
        $response->assertStatus(200);

        $queriesFor2 = count(DB::getQueryLog());

        // Creamos 10 ventas más
        Venta::factory()->count(10)->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado_venta' => 'Completada',
        ]);

        DB::flushQueryLog();

        $response = $this->authenticatedJson('GET', '/api/ventas?per_page=12', [], $usuario);
        $response->assertStatus(200);

        $queriesFor12 = count(DB::getQueryLog());

        // Si hubiera N+1, las queries aumentarían proporcionalmente al número de registros.
        // Esperamos que el número de queries sea constante (o varíe muy poco)
        $this->assertLessThanOrEqual(
            $queriesFor2 + 2, 
            $queriesFor12, 
            "Posible N+1 detectado en /api/ventas. Queries (2 items): {$queriesFor2}, Queries (12 items): {$queriesFor12}"
        );
    }
}
