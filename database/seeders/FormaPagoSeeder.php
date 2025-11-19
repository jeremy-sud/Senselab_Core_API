<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormaPagoSeeder extends Seeder
{
    public function run(): void
    {
        $formasPago = [
            ['codigo_dgt' => '01', 'nombre' => 'Efectivo', 'descripcion' => 'Pago en efectivo'],
            ['codigo_dgt' => '02', 'nombre' => 'Tarjeta', 'descripcion' => 'Pago con tarjeta de crédito o débito'],
            ['codigo_dgt' => '03', 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia bancaria'],
            ['codigo_dgt' => '04', 'nombre' => 'Cheque', 'descripcion' => 'Pago con cheque'],
            ['codigo_dgt' => '05', 'nombre' => 'Crédito', 'descripcion' => 'Pago a crédito'],
            ['codigo_dgt' => '99', 'nombre' => 'Otros', 'descripcion' => 'Otras formas de pago'],
        ];

        foreach ($formasPago as $forma) {
            DB::table('formas_pago')->insert([
                'codigo_dgt' => $forma['codigo_dgt'],
                'nombre' => $forma['nombre'],
                'descripcion' => $forma['descripcion'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
