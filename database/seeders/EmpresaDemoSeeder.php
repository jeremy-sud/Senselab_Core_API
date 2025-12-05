<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Sistemas Ursol S.A. - Empresa de desarrollo de software especializada
     * en soluciones empresariales, facturación electrónica y sistemas ERP.
     * Fundada por Eduardo Ureña Solano.
     */
    public function run(): void
    {
        $empresa = [
            'regimen_tributario_id' => 1, // Régimen Tradicional
            'nombre' => 'Sistemas Ursol S.A.',
            'nombre_comercial' => 'Ursol',
            'razon_social' => 'Sistemas Ursol Sociedad Anónima',
            'tipo_identificacion' => '02', // 02 = Jurídica
            'num_identificacion_dgt' => '3-101-876543', // Cédula jurídica ficticia
            'actividad_economica_principal' => '620100', // Actividades de programación informática
            'proveedor_sistemas' => 'Ursol',
            'email' => 'info@ursol.com',
            'telefono' => '+506 8868-7765',
            'direccion' => 'Tibás, San José, Costa Rica. Del Más x Menos 300m Norte, 50m Este.',
            'provincia' => '1', // San José
            'canton' => '07', // Tibás
            'distrito' => '01', // San Juan
            'subdominio' => 'ursol',
            'moneda_defecto' => 'CRC',
            'activo' => true,
            'eliminado' => false,
        ];

        $empresaId = DB::table('empresas')->insertGetId($empresa);

        // Crear sucursal principal (Oficina Central)
        DB::table('sucursales')->insert([
            'empresa_id' => $empresaId,
            'nombre' => 'Oficina Central - Casa Matriz',
            'direccion' => 'Tibás, San José, Costa Rica. Del Más x Menos 300m Norte, 50m Este.',
            'telefono' => '+506 8868-7765',
            'email' => 'central@ursol.com',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->command->info('✓ Empresa Sistemas Ursol S.A. creada exitosamente (ID: ' . $empresaId . ')');
        $this->command->info('   Cédula Jurídica: 3-101-876543');
        $this->command->info('   Email: info@ursol.com');
    }
}
