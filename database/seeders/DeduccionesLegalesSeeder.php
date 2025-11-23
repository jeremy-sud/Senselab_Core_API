<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeduccionesLegalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deducciones = [
            [
                'codigo' => 'CCSS_OBR',
                'nombre' => 'CCSS Cuota Obrera',
                'descripcion' => 'Contribución obrera a la Caja Costarricense del Seguro Social',
                'tipo' => 'ccss_obrero',
                'porcentaje_base' => 10.50,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => true,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'CCSS_PAT',
                'nombre' => 'CCSS Cuota Patronal',
                'descripcion' => 'Contribución patronal a la Caja Costarricense del Seguro Social',
                'tipo' => 'ccss_patronal',
                'porcentaje_base' => 26.50,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => true,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'INS_LAB',
                'nombre' => 'INS Póliza de Riesgos del Trabajo',
                'descripcion' => 'Seguro de riesgos del trabajo del INS',
                'tipo' => 'ins_laboral',
                'porcentaje_base' => 1.00,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => true,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'LPT',
                'nombre' => 'Ley de Protección al Trabajador',
                'descripcion' => 'Fondo de Capitalización Laboral (FCL) y Pensión Complementaria',
                'tipo' => 'ins_lpt',
                'porcentaje_base' => 3.00,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => true,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'IMP_REN',
                'nombre' => 'Impuesto sobre la Renta',
                'descripcion' => 'Retención del impuesto sobre la renta según tramos',
                'tipo' => 'impuesto_renta',
                'porcentaje_base' => null,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => false,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'ASSO_SOL',
                'nombre' => 'Asociación Solidarista',
                'descripcion' => 'Aporte a la Asociación Solidarista (voluntario)',
                'tipo' => 'asociacion_solidarista',
                'porcentaje_base' => 5.00,
                'monto_fijo' => null,
                'aplica_sobre' => 'salario_bruto',
                'es_obligatoria' => false,
                'activa' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('deducciones_legales')->insert($deducciones);

        $this->command->info('✓ 6 deducciones legales de Costa Rica creadas exitosamente.');
    }
}
