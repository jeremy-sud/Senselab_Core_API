<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tasas de impuesto vigentes según la Ley de Fortalecimiento
 * de las Finanzas Públicas (Ley 9635) de Costa Rica.
 *
 * Depende de TipoImpuestoSeeder (tipo_impuesto_id = IVA código '01').
 */
class TasaImpuestoSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el ID del tipo IVA
        $tipoIva = DB::table('tipos_impuesto')
            ->where('codigo_hacienda', '01')
            ->first();

        if (!$tipoIva) {
            $this->command->warn('TipoImpuestoSeeder debe ejecutarse antes. Saltando TasaImpuestoSeeder.');
            return;
        }

        $tasas = [
            [
                'tipo_impuesto_id' => $tipoIva->id,
                'tasa_porcentaje' => 13.00,
                'fecha_inicio_vigencia' => '2019-07-01',
                'fecha_fin_vigencia' => null,
                'descripcion' => 'IVA General (tarifa plena)',
            ],
            [
                'tipo_impuesto_id' => $tipoIva->id,
                'tasa_porcentaje' => 4.00,
                'fecha_inicio_vigencia' => '2019-07-01',
                'fecha_fin_vigencia' => null,
                'descripcion' => 'IVA Reducido — servicios de salud privados, boletos aéreos',
            ],
            [
                'tipo_impuesto_id' => $tipoIva->id,
                'tasa_porcentaje' => 2.00,
                'fecha_inicio_vigencia' => '2019-07-01',
                'fecha_fin_vigencia' => null,
                'descripcion' => 'IVA Reducido — medicamentos, primas de seguros, educación privada',
            ],
            [
                'tipo_impuesto_id' => $tipoIva->id,
                'tasa_porcentaje' => 1.00,
                'fecha_inicio_vigencia' => '2019-07-01',
                'fecha_fin_vigencia' => null,
                'descripcion' => 'IVA Reducido — canasta básica tributaria',
            ],
            [
                'tipo_impuesto_id' => $tipoIva->id,
                'tasa_porcentaje' => 0.00,
                'fecha_inicio_vigencia' => '2019-07-01',
                'fecha_fin_vigencia' => null,
                'descripcion' => 'Exento de IVA — exportaciones, bienes exentos por ley',
            ],
        ];

        foreach ($tasas as $tasa) {
            DB::table('tasas_impuesto')->updateOrInsert(
                [
                    'tipo_impuesto_id' => $tasa['tipo_impuesto_id'],
                    'tasa_porcentaje' => $tasa['tasa_porcentaje'],
                ],
                [
                    'fecha_inicio_vigencia' => $tasa['fecha_inicio_vigencia'],
                    'fecha_fin_vigencia' => $tasa['fecha_fin_vigencia'],
                    'descripcion' => $tasa['descripcion'],
                    'activo' => true,
                    'eliminado' => false,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]
            );
        }

        $this->command->info('Tasas de impuesto IVA Costa Rica cargadas exitosamente.');
    }
}
