<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposClientes = [
            [
                'codigo' => 'MAYOR',
                'nombre' => 'Mayorista',
                'descripcion' => 'Cliente mayorista con compras en grandes volúmenes',
                'descuento_default' => 15.00,
                'dias_credito_default' => 30,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'MINOR',
                'nombre' => 'Minorista',
                'descripcion' => 'Cliente minorista con compras al detalle',
                'descuento_default' => 5.00,
                'dias_credito_default' => 15,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'DISTRI',
                'nombre' => 'Distribuidor',
                'descripcion' => 'Cliente distribuidor autorizado',
                'descuento_default' => 20.00,
                'dias_credito_default' => 45,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'GOBIER',
                'nombre' => 'Gobierno',
                'descripcion' => 'Entidades gubernamentales y municipalidades',
                'descuento_default' => 0.00,
                'dias_credito_default' => 60,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'EXPORT',
                'nombre' => 'Exportación',
                'descripcion' => 'Clientes de exportación',
                'descuento_default' => 0.00,
                'dias_credito_default' => 30,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'CONSFI',
                'nombre' => 'Consumidor Final',
                'descripcion' => 'Consumidor final, ventas al contado',
                'descuento_default' => 0.00,
                'dias_credito_default' => 0,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tiposClientes as $tipo) {
            DB::table('tipos_clientes')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                collect($tipo)->except('codigo')->toArray()
            );
        }

        $this->command->info('✓ 6 tipos de clientes cargados exitosamente.');
    }
}
