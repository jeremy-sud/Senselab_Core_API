<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormasPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formasPago = [
            ['codigo_dgt' => '01', 'nombre' => 'Efectivo', 'descripcion' => 'Pago en efectivo', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => '02', 'nombre' => 'Tarjeta', 'descripcion' => 'Pago con tarjeta de crédito o débito', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => '03', 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia bancaria', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => '04', 'nombre' => 'Cheque', 'descripcion' => 'Pago con cheque', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => '05', 'nombre' => 'Crédito', 'descripcion' => 'Pago a crédito', 'activo' => true, 'eliminado' => false],
            ['codigo_dgt' => '99', 'nombre' => 'Otros', 'descripcion' => 'Otras formas de pago', 'activo' => true, 'eliminado' => false],
        ];

        foreach ($formasPago as $forma) {
            DB::table('formas_pago')->updateOrInsert(
                ['codigo_dgt' => $forma['codigo_dgt']],
                $forma
            );
        }

        $this->command->info('✓ Formas de pago cargadas exitosamente.');
    }
}
