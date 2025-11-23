<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposComprobantesFESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposComprobantes = [
            [
                'codigo_dgt' => '01',
                'nombre' => 'Factura Electrónica',
                'descripcion' => 'Factura electrónica estándar',
                'requiere_referencia' => false,
                'permite_exportacion' => true,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '02',
                'nombre' => 'Nota de Débito Electrónica',
                'descripcion' => 'Nota de débito electrónica',
                'requiere_referencia' => true,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '03',
                'nombre' => 'Nota de Crédito Electrónica',
                'descripcion' => 'Nota de crédito electrónica',
                'requiere_referencia' => true,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '04',
                'nombre' => 'Tiquete Electrónico',
                'descripcion' => 'Tiquete electrónico para ventas al consumidor final',
                'requiere_referencia' => false,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '05',
                'nombre' => 'Nota de Débito Electrónica de Tiquete',
                'descripcion' => 'Nota de débito para tiquete electrónico',
                'requiere_referencia' => true,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '06',
                'nombre' => 'Nota de Crédito Electrónica de Tiquete',
                'descripcion' => 'Nota de crédito para tiquete electrónico',
                'requiere_referencia' => true,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '07',
                'nombre' => 'Comprobante Electrónico de Compra',
                'descripcion' => 'Comprobante de compra a personas físicas sin factura',
                'requiere_referencia' => false,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '08',
                'nombre' => 'Factura Electrónica de Exportación',
                'descripcion' => 'Factura electrónica para exportaciones',
                'requiere_referencia' => false,
                'permite_exportacion' => true,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_dgt' => '09',
                'nombre' => 'Factura Electrónica de Compra',
                'descripcion' => 'Factura electrónica de compra',
                'requiere_referencia' => false,
                'permite_exportacion' => false,
                'activo' => true,
                'eliminado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipos_comprobantes_fe')->insert($tiposComprobantes);

        $this->command->info('✓ 9 tipos de comprobantes FE creados exitosamente.');
    }
}
