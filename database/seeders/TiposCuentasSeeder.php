<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposCuentasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposCuentas = [
            ['nombre' => 'Activo Corriente', 'descripcion' => 'Bienes y derechos líquidos en menos de un año', 'naturaleza' => 'Deudora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Activo No Corriente', 'descripcion' => 'Bienes y derechos a largo plazo', 'naturaleza' => 'Deudora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Pasivo Corriente', 'descripcion' => 'Obligaciones a corto plazo', 'naturaleza' => 'Acreedora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Pasivo No Corriente', 'descripcion' => 'Obligaciones a largo plazo', 'naturaleza' => 'Acreedora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Patrimonio', 'descripcion' => 'Capital y resultados acumulados', 'naturaleza' => 'Acreedora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Ingresos', 'descripcion' => 'Ingresos operacionales y no operacionales', 'naturaleza' => 'Acreedora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Costos', 'descripcion' => 'Costos de ventas y producción', 'naturaleza' => 'Deudora', 'activo' => true, 'eliminado' => false],
            ['nombre' => 'Gastos', 'descripcion' => 'Gastos operacionales y administrativos', 'naturaleza' => 'Deudora', 'activo' => true, 'eliminado' => false],
        ];

        foreach ($tiposCuentas as $tipo) {
            DB::table('tipos_cuentas')->updateOrInsert(
                ['nombre' => $tipo['nombre']],
                $tipo
            );
        }

        $this->command->info('✓ Tipos de cuentas cargados exitosamente.');
    }
}
