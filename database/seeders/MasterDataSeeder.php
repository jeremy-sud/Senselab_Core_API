<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder de datos maestros: datos de referencia/catálogo necesarios
 * para que el sistema funcione en producción.
 *
 * Estos datos NO dependen de una empresa y son obligatorios.
 * Ejecutar en producción: php artisan db:seed --class=MasterDataSeeder
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Ejecutando seeders de datos maestros del sistema...');

        $this->call([
            // Catálogos base del sistema
            RegimenesTributariosSeeder::class,         // 2 regímenes tributarios
            FormasPagoSeeder::class,                   // 6 formas de pago DGT
            TiposCuentasSeeder::class,                 // 8 tipos de cuentas contables
            UnidadesMedidaSeeder::class,               // 11 unidades de medida DGT
            CargosSeeder::class,                       // 13 cargos/puestos laborales

            // Seguridad: permisos y roles
            PermisosSeeder::class,                     // 68 permisos CRUD (17 módulos × 4)
            RolesSeeder::class,                        // 8 roles del sistema

            // Catálogos específicos de Costa Rica (DGT/Hacienda)
            TiposComprobantesFESeeder::class,          // 9 tipos de comprobantes FE
            TipoImpuestoSeeder::class,                 // 6 tipos de impuesto Hacienda
            TasaImpuestoSeeder::class,                 // Tasas de IVA vigentes (13%, 4%, 2%, 1%, 0%)
            DeduccionesLegalesSeeder::class,           // 6 deducciones CCSS/INS
            CodigosActividadEconomicaSeeder::class,    // 35 códigos más comunes
            ZonasGeograficasCRSeeder::class,           // 7 provincias
            TiposClientesSeeder::class,                // 6 tipos de clientes
        ]);

        $this->command->info('Datos maestros cargados exitosamente.');
    }
}
