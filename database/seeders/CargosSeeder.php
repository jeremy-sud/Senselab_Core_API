<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cargos = [
            ['nombre' => 'Fundador', 'descripcion' => 'Fundador de la empresa, máxima autoridad', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Co-Fundador', 'descripcion' => 'Co-fundador de la empresa, alta dirección', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'CEO', 'descripcion' => 'Chief Executive Officer - Director Ejecutivo', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'CTO', 'descripcion' => 'Chief Technology Officer - Director de Tecnología', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Gerente General', 'descripcion' => 'Gerente General de la empresa', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Contador', 'descripcion' => 'Contador de la empresa', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Administrador de Sistema', 'descripcion' => 'Administrador del sistema ERP', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Desarrollador Senior', 'descripcion' => 'Desarrollador de software senior', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Desarrollador', 'descripcion' => 'Desarrollador de software', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Vendedor', 'descripcion' => 'Vendedor', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Comprador', 'descripcion' => 'Encargado de compras', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Bodeguero', 'descripcion' => 'Encargado de bodega', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Asistente Administrativo', 'descripcion' => 'Asistente administrativo', 'activo' => true, 'eliminado' => false],
        ];

        foreach ($cargos as $cargo) {
            DB::table('cargos')->updateOrInsert(
                ['nombre' => $cargo['nombre']],
                $cargo
            );
        }

        $this->command->info('✓ Cargos cargados exitosamente.');
    }
}
