<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadesMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            ['codigo_dgt' => 'Unid', 'nombre' => 'Unidad', 'descripcion' => 'Unidad individual', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'Kg', 'nombre' => 'Kilogramo', 'descripcion' => 'Kilogramo', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'g', 'nombre' => 'Gramo', 'descripcion' => 'Gramo', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'L', 'nombre' => 'Litro', 'descripcion' => 'Litro', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'mL', 'nombre' => 'Mililitro', 'descripcion' => 'Mililitro', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'm', 'nombre' => 'Metro', 'descripcion' => 'Metro lineal', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'm2', 'nombre' => 'Metro cuadrado', 'descripcion' => 'Metro cuadrado', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'm3', 'nombre' => 'Metro cúbico', 'descripcion' => 'Metro cúbico', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'Caja', 'nombre' => 'Caja', 'descripcion' => 'Caja', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'Paq', 'nombre' => 'Paquete', 'descripcion' => 'Paquete', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => 'Serv', 'nombre' => 'Servicio', 'descripcion' => 'Servicio', 'activo' => true, 'eliminado' => false],
        ];

        foreach ($unidades as $unidad) {
            DB::table('unidades_medida')->updateOrInsert(
                ['codigo_dgt' => $unidad['codigo_dgt']],
                $unidad
            );
        }

        $this->command->info('✓ Unidades de medida cargadas exitosamente.');
    }
}
