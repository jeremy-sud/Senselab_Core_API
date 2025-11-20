<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = [
            'regimen_tributario_id' => 1, // Régimen Tradicional
            'nombre' => 'Sistemas Ursol S.A.',
            'nombre_comercial' => 'Ursol',
            'tipo_identificacion' => '02', // 02 = Jurídica
            'num_identificacion_dgt' => '3-101-123456',
            'email' => 'sistemas@ursol.com',
            'telefono' => '+506 8868-7765',
            'direccion' => 'San José, Costa Rica',
            'provincia' => '1', // San José
            'canton' => '01', // Central
            'distrito' => '01', // Carmen
            'activo' => true,
            'eliminado' => false,
        ];

        $empresaId = DB::table('empresas')->insertGetId($empresa);

        // Crear sucursal principal
        DB::table('sucursales')->insert([
            'empresa_id' => $empresaId,
            'nombre' => 'Oficina Central',
            'direccion' => 'San José, Costa Rica',
            'telefono' => '+506 8868-7765',
            'email' => 'central@ursol.com',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->command->info('✓ Empresa demo creada exitosamente (ID: ' . $empresaId . ')');
    }
}
